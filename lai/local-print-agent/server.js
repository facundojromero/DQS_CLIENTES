const http = require('http');
const os = require('os');
const { execFile } = require('child_process');

const PORT = process.env.PORT || 3000;
const HOST = process.env.HOST || '127.0.0.1';
const COMMAND_TIMEOUT_MS = Number(process.env.PRINT_TIMEOUT_MS || 7000);

function log(level, message, meta) {
  const entry = {
    timestamp: new Date().toISOString(),
    level,
    message,
    ...(meta || {})
  };
  console.log(JSON.stringify(entry));
}

function readJsonBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';

    req.on('data', (chunk) => {
      body += chunk;
      if (body.length > 1024 * 1024) {
        reject(new Error('Payload demasiado grande (max 1MB).'));
        req.destroy();
      }
    });

    req.on('end', () => {
      try {
        const payload = JSON.parse(body || '{}');
        resolve(payload);
      } catch (error) {
        reject(new Error('JSON inválido.'));
      }
    });

    req.on('error', reject);
  });
}

function validatePayload(payload) {
  if (!payload || typeof payload !== 'object') {
    throw new Error('Body requerido.');
  }
  if (payload.type !== 'ticket') {
    throw new Error('type debe ser "ticket".');
  }
  if (typeof payload.content !== 'string' || payload.content.trim() === '') {
    throw new Error('content es obligatorio.');
  }

  const copies = Number(payload.copies || 1);
  if (!Number.isInteger(copies) || copies < 1 || copies > 10) {
    throw new Error('copies debe ser un entero entre 1 y 10.');
  }

  return {
    type: payload.type,
    content: payload.content,
    copies
  };
}

function printLinux(content, copies) {
  return new Promise((resolve, reject) => {
    const args = ['-n', String(copies)];
    const child = execFile('lp', args, { timeout: COMMAND_TIMEOUT_MS }, (error, stdout, stderr) => {
      if (error) {
        reject(new Error(`lp error: ${stderr || error.message}`));
        return;
      }
      resolve((stdout || '').trim());
    });

    child.stdin.write(content, 'utf8');
    child.stdin.end();
  });
}

function printWindows(content, copies) {
  return new Promise((resolve, reject) => {
    const escapedContent = content
      .replace(/`/g, '``')
      .replace(/\$/g, '`$')
      .replace(/"/g, '`"');

    const script = [
      `$ticket = \"${escapedContent}\"`,
      '$tmp = [System.IO.Path]::GetTempFileName() + ".txt"',
      '[System.IO.File]::WriteAllText($tmp, $ticket, [System.Text.Encoding]::UTF8)',
      `1..${copies} | ForEach-Object { Get-Content $tmp | Out-Printer }`,
      'Remove-Item $tmp -ErrorAction SilentlyContinue'
    ].join('; ');

    execFile(
      'powershell.exe',
      ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', script],
      { timeout: COMMAND_TIMEOUT_MS },
      (error, stdout, stderr) => {
        if (error) {
          reject(new Error(`powershell error: ${stderr || error.message}`));
          return;
        }
        resolve((stdout || '').trim());
      }
    );
  });
}

async function printTicket(content, copies) {
  if (process.platform === 'win32') {
    return printWindows(content, copies);
  }

  if (process.platform === 'linux' || process.platform === 'darwin') {
    return printLinux(content, copies);
  }

  throw new Error(`Sistema operativo no soportado: ${process.platform}`);
}

function sendJson(res, statusCode, payload) {
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type'
  });
  res.end(JSON.stringify(payload));
}

const server = http.createServer(async (req, res) => {
  if (req.method === 'OPTIONS') {
    sendJson(res, 204, {});
    return;
  }

  if (req.method === 'GET' && req.url === '/health') {
    sendJson(res, 200, { status: 'ok', host: os.hostname() });
    return;
  }

  if (req.method === 'POST' && req.url === '/print') {
    try {
      const payload = validatePayload(await readJsonBody(req));
      const result = await printTicket(payload.content, payload.copies);

      log('info', 'Trabajo de impresión completado', {
        type: payload.type,
        copies: payload.copies,
        chars: payload.content.length
      });

      sendJson(res, 200, {
        ok: true,
        message: 'Impresión enviada.',
        spool_response: result
      });
      return;
    } catch (error) {
      log('error', 'Error de impresión', {
        error: error.message
      });

      sendJson(res, 500, {
        ok: false,
        error: error.message
      });
      return;
    }
  }

  sendJson(res, 404, { ok: false, error: 'Ruta no encontrada.' });
});

server.listen(PORT, HOST, () => {
  log('info', 'Agente de impresión iniciado', {
    host: HOST,
    port: PORT,
    platform: process.platform
  });
});
