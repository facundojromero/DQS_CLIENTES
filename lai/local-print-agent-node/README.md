# LAI Local Print Agent (Windows)

Agente local para desacoplar la impresión del navegador en `/lai/`.

## Requisitos

- Windows 10/11
- Node.js 18+
- PowerShell 5+
- Impresora térmica instalada en Windows

## Instalación rápida

1. Copiar esta carpeta a la PC (por ejemplo `C:\lai-print-agent`).
2. Crear `config.json` desde el ejemplo:
   - `copy config.example.json config.json`
3. Editar `config.json`:
   - `server.apiKey`
   - `printDefaults.printerName` (opcional, vacío = impresora por defecto de Windows)
   - tamaño y estilo (`ticketWidthMm`, márgenes, fuente, copias)
4. Iniciar:
   - `npm start`

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
