# LAI Local Print Agent (Windows)

Agente local para desacoplar la impresión del navegador en `/lai/`.

## Requisitos

- Windows 10/11
- Node.js 18+
- PowerShell 5+
- Impresora térmica instalada en Windows

## Instalación rápida (manual)

1. Copiar esta carpeta a la PC (por ejemplo `C:\lai-print-agent`).
2. Crear `config.json` desde el ejemplo:
   - `copy config.example.json config.json`
3. Editar `config.json`:
   - `server.apiKey`
   - `printDefaults.printerName` (opcional, vacío = impresora por defecto de Windows)
   - tamaño y estilo (`ticketWidthMm`, márgenes, fuente, copias)
4. Iniciar:
   - `npm start`

## Instalación automática en Windows (tipo instalador)

Dentro de `windows/` tenés opciones para que la instalación sea ejecutable:

- `install-agent.cmd`: doble click (ideal con click derecho `Ejecutar como administrador`).
- `install-agent.ps1`: instalador por PowerShell con parámetros.
- `build-installer-exe.ps1`: genera un `.exe` instalador usando `ps2exe`.

### Opción A: instalar con CMD (sin compilar EXE)

1. Editar variables en `windows/install-agent.cmd`:
   - `API_KEY`
   - `PRINTER_NAME`
   - `TICKET_WIDTH` (58/80)
   - `AGENT_PORT`
2. Ejecutar como administrador.

Esto:
- instala Node.js LTS con `winget` si falta,
- copia archivos a `C:\LAI\PrintAgent`,
- genera `config.json`,
- crea servicio Windows `LAIPrintAgent`,
- inicia y valida `GET /health`.

### Opción B: generar un EXE instalador

En PowerShell (Windows):

```powershell
cd .\windows
.\build-installer-exe.ps1
```

Luego ejecutar `LAI-Print-Agent-Installer.exe` como administrador.

> Importante: distribuir el EXE junto a la carpeta del agente (debe tener `agent.js`, `scripts\`, etc.).

## API local

### GET /health
Respuesta de estado.

### POST /print
Headers:
- `Content-Type: application/json`
- `x-lai-api-key: <apiKey local>`

Body:

```json
{
  "ticketText": "JINETEADA LAI\n...",
  "printConfig": {
    "ticketWidthMm": 58,
    "copies": 1,
    "fontName": "Consolas",
    "fontSize": 9,
    "marginLeftMm": 2,
    "marginRightMm": 2,
    "marginTopMm": 2,
    "marginBottomMm": 6,
    "printerName": ""
  }
}
```

## Logs

- Archivo: `logs/agent.log`
- Guarda inicio, impresiones OK y errores.

## Nota de seguridad

- Escucha solamente en `127.0.0.1`.
- Rechaza impresiones sin API key válida.

## Desinstalación

```powershell
cd .\windows
.\uninstall-agent.ps1
```
