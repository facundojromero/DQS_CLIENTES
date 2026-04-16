const http = require('http');
const fs = require('fs');
const path = require('path');
const os = require('os');
const crypto = require('crypto');
const { spawn } = require('child_process');

const BASE_DIR = __dirname;
const CONFIG_PATH = path.join(BASE_DIR, 'config.json');
const CONFIG_EXAMPLE_PATH = path.join(BASE_DIR, 'config.example.json');
const LOG_DIR = path.join(BASE_DIR, 'logs');
const LOG_FILE = path.join(LOG_DIR, 'agent.log');
const PRINT_SCRIPT_PATH = path.join(BASE_DIR, 'scripts', 'print_ticket.ps1');

function ensureLogDir() {
  if (!fs.existsSync(LOG_DIR)) {
    fs.mkdirSync(LOG_DIR, { recursive: true });
  }
}

function writeLog(level, message, extra = null) {
  ensureLogDir();
  const entry = {
    ts: new Date().toISOString(),
    level,
    message,
    extra,
  };
  fs.appendFileSync(LOG_FILE, `${JSON.stringify(entry)}${os.EOL}`);
}

function loadConfig() {
  if (!fs.existsSync(CONFIG_PATH)) {
    if (fs.existsSync(CONFIG_EXAMPLE_PATH)) {
      fs.copyFileSync(CONFIG_EXAMPLE_PATH, CONFIG_PATH);
      writeLog('WARN', 'No existía config.json. Se copió config.example.json automáticamente.');
    } else {
      throw new Error('No existe config.json ni config.example.json');
    }
  }

  const raw = fs.readFileSync(CONFIG_PATH, 'utf8');
  return JSON.parse(raw);
}

function readBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';
    req.on('data', (chunk) => {
      body += chunk;
      if (body.length > 1024 * 1024) {
        reject(new Error('Payload demasiado grande (máximo 1MB)'));
      }
    });
    req.on('end', () => resolve(body));
    req.on('error', reject);
  });
}

function jsonResponse(res, statusCode, payload) {
  res.writeHead(statusCode, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify(payload));
}

function clampNumber(value, fallback, min, max) {
  const parsed = Number(value);
  if (Number.isNaN(parsed)) return fallback;
  return Math.min(max, Math.max(min, parsed));
}

function normalizePrintConfig(agentConfig, jobConfig = {}) {
  const defaults = agentConfig.printDefaults || {};
  const ticketWidthMm = clampNumber(jobConfig.ticketWidthMm, defaults.ticketWidthMm || 58, 40, 120);
  const charsPerLine = ticketWidthMm <= 58
    ? clampNumber(jobConfig.charsPerLine, defaults.charsPerLine58 || 32, 20, 48)
    : clampNumber(jobConfig.charsPerLine, defaults.charsPerLine80 || 42, 32, 72);

  return {
    printerName: String(jobConfig.printerName || defaults.printerName || ''),
    copies: clampNumber(jobConfig.copies, defaults.copies || 1, 1, 5),
    fontName: String(jobConfig.fontName || defaults.fontName || 'Consolas'),
    fontSize: clampNumber(jobConfig.fontSize, defaults.fontSize || 9, 6, 14),
    marginLeftMm: clampNumber(jobConfig.marginLeftMm, defaults.marginLeftMm || 2, 0, 10),
    marginRightMm: clampNumber(jobConfig.marginRightMm, defaults.marginRightMm || 2, 0, 10),
    marginTopMm: clampNumber(jobConfig.marginTopMm, defaults.marginTopMm || 2, 0, 20),
    marginBottomMm: clampNumber(jobConfig.marginBottomMm, defaults.marginBottomMm || 6, 0, 30),
    ticketWidthMm,
    charsPerLine,
  };
}

function wrapLine(line, maxChars) {
  if (line.length <= maxChars) return [line];
  const pieces = [];
  let current = line;

  while (current.length > maxChars) {
    let splitAt = current.lastIndexOf(' ', maxChars);
    if (splitAt <= 0) splitAt = maxChars;
    pieces.push(current.slice(0, splitAt).trimEnd());
    current = current.slice(splitAt).trimStart();
  }

  if (current.length > 0) {
    pieces.push(current);
  }

  return pieces;
}

function normalizeText(rawText, charsPerLine) {
  const safeText = String(rawText || '').replace(/\r\n/g, '\n');
  const lines = safeText.split('\n');
  const wrapped = [];

  for (const line of lines) {
    if (line === '') {
      wrapped.push('');
      continue;
    }

    wrapped.push(...wrapLine(line, charsPerLine));
  }

  return wrapped.join('\n');
}

function runPrintJob(payload) {
  return new Promise((resolve, reject) => {
    const tempFile = path.join(os.tmpdir(), `lai-print-job-${crypto.randomUUID()}.json`);
    fs.writeFileSync(tempFile, JSON.stringify(payload), 'utf8');

    const child = spawn('powershell.exe', [
      '-NoProfile',
      '-ExecutionPolicy',
      'Bypass',
      '-File',
      PRINT_SCRIPT_PATH,
      '-PayloadPath',
      tempFile,
    ]);

    let stdout = '';
    let stderr = '';

    child.stdout.on('data', (chunk) => { stdout += chunk.toString(); });
    child.stderr.on('data', (chunk) => { stderr += chunk.toString(); });

    child.on('close', (code) => {
      try {
        fs.unlinkSync(tempFile);
      } catch (err) {
        writeLog('WARN', 'No se pudo borrar archivo temporal', { file: tempFile, err: err.message });
      }

      if (code === 0) {
        resolve({ stdout: stdout.trim() });
      } else {
        reject(new Error(stderr.trim() || stdout.trim() || `Error de impresión (exit ${code})`));
      }
    });

    child.on('error', (err) => reject(err));
  });
}

function startServer() {
  const config = loadConfig();
  const host = config.server?.host || '127.0.0.1';
  const port = Number(config.server?.port || 3000);
  const apiKey = String(config.server?.apiKey || '');

  const server = http.createServer(async (req, res) => {
    const reqHost = req.headers.host || `${host}:${port}`;
    const url = new URL(req.url, `http://${reqHost}`);

    if (req.method === 'GET' && url.pathname === '/health') {
      return jsonResponse(res, 200, { status: 'ok', host, port });
    }

    if (req.method === 'POST' && url.pathname === '/print') {
      if (apiKey) {
        const sentKey = String(req.headers['x-lai-api-key'] || '');
        if (sentKey !== apiKey) {
          writeLog('WARN', 'Intento de impresión con API key inválida');
          return jsonResponse(res, 401, { ok: false, error: 'API key inválida' });
        }
      }

      try {
        const rawBody = await readBody(req);
        const body = JSON.parse(rawBody);

        if (!body || typeof body !== 'object') {
          return jsonResponse(res, 400, { ok: false, error: 'Body inválido' });
        }

        const rawText = String(body.ticketText || '');
        if (!rawText.trim()) {
          return jsonResponse(res, 400, { ok: false, error: 'ticketText es obligatorio' });
        }

        const finalConfig = normalizePrintConfig(config, body.printConfig || {});
        writeLog('INFO', 'Solicitud de impresión recibida', {
          printerName: finalConfig.printerName || 'default',
          copies: finalConfig.copies,
          ticketWidthMm: finalConfig.ticketWidthMm,
        });
        const normalizedText = normalizeText(rawText, finalConfig.charsPerLine);

        const printPayload = {
          ticketText: normalizedText,
          printConfig: finalConfig,
        };

        await runPrintJob(printPayload);
        writeLog('INFO', 'Ticket impreso', {
          printerName: finalConfig.printerName || 'default',
          ticketWidthMm: finalConfig.ticketWidthMm,
          copies: finalConfig.copies,
        });

        return jsonResponse(res, 200, {
          ok: true,
          message: 'Impresión enviada',
          appliedConfig: finalConfig,
        });
      } catch (err) {
        writeLog('ERROR', 'Fallo de impresión', { error: err.message });
        return jsonResponse(res, 500, { ok: false, error: err.message });
      }
    }

    return jsonResponse(res, 404, { ok: false, error: 'Ruta no encontrada' });
  });

  server.listen(port, host, () => {
    writeLog('INFO', 'Agente iniciado', { host, port, configPath: CONFIG_PATH, printScript: PRINT_SCRIPT_PATH });
    // eslint-disable-next-line no-console
    console.log(`LAI Local Print Agent escuchando en http://${host}:${port}`);
  });
}

startServer();
