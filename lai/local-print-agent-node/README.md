# LAI Local Print Agent (Windows)

Agente local para desacoplar la impresión del navegador en `/lai/`, pensado para desplegarse en múltiples PCs con un instalador `.exe`.

## Objetivo de despliegue

Cada PC solo debe:

1. Ejecutar el instalador `LAI-Print-Agent-Setup.exe`.
2. Elegir impresora y ancho de ticket durante el asistente.
3. Finalizar, y dejar el agente listo (opcional: auto inicio con Windows).

Sin instalación manual de dependencias una por una.

## Arquitectura propuesta (Windows-first)

- **Runtime del agente:** Node.js embebido dentro del instalador (portable runtime copiado en `runtime/`).
- **Ejecutable principal del agente:** `run-agent.cmd` (entrypoint estable para servicio/tarea).
- **Instalador:** Inno Setup (`installer/LAIPrintAgent.iss`).
- **Autoejecución:** Scheduled Task por usuario o por equipo (`scripts/install_agent.ps1`).
- **Configuración local:** `config.json` con defaults por PC.

## Componentes incluidos

- `agent.js`: API local `GET /health` y `POST /print`.
- `scripts/print_ticket.ps1`: impresión térmica usando APIs de impresión de Windows.
- `scripts/install_agent.ps1`: registra tarea programada y configura `config.json`.
- `scripts/uninstall_agent.ps1`: elimina tarea programada.
- `installer/LAIPrintAgent.iss`: instalador `.exe` con asistentes y parámetros.

## Configuración por PC

El instalador permite definir:

- URL base del sistema (por defecto `http://192.168.0.113/lai/`).
- `apiKey` local del agente.
- Impresora por nombre (`printerName`, vacío = default de Windows).
- Ancho del ticket (`ticketWidthMm`, recomendado 58 u 80).
- Auto inicio al loguear en Windows.

## Flujo recomendado de build y empaquetado

1. Preparar runtime Node portátil en `runtime/node.exe`.
2. Generar artefacto de instalación con Inno Setup:
   - Abrir `installer/LAIPrintAgent.iss` en Inno Setup.
   - Compilar y obtener `LAI-Print-Agent-Setup.exe`.

> Nota: si se prefiere, se puede reemplazar el runtime portable por un ejecutable único generado con `pkg`/`nexe`. El flujo de instalador no cambia.

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

## Integración con `/lai/`

- El frontend/backend en `http://192.168.0.113/lai/` debe enviar el ticket a `http://127.0.0.1:5399/print`.
- Usar la API key configurada en cada PC.
- Mantener fallback visual si el agente no responde.

## Logs

- Archivo: `logs/agent.log`
- Guarda inicio, impresiones OK y errores.

## Nota de seguridad

- Escucha solamente en `127.0.0.1`.
- Rechaza impresiones sin API key válida.
