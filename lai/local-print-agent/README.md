# LAI Local Print Agent (PoC)

Agente local para imprimir tickets sin `window.print` ni popups del navegador.

## Requisitos

- Node.js 18+
- Linux/macOS: comando `lp` disponible (CUPS)
- Windows: PowerShell habilitado

## Instalación

```bash
cd /clientes/lai/local-print-agent
npm install
```

> `npm install` no descarga dependencias externas en esta PoC, pero deja el flujo estándar listo.

## Ejecución

```bash
npm start
```

Por defecto levanta en `http://127.0.0.1:3000`.

## Endpoints

### `GET /health`
Chequeo rápido del estado del agente.

### `POST /print`
Recibe y envía trabajo de impresión.

Payload esperado:

```json
{
  "type": "ticket",
  "content": "LAI\nProducto: X\nPrecio: $1000",
  "copies": 1
}
```

Respuesta OK:

```json
{
  "ok": true,
  "message": "Impresión enviada.",
  "spool_response": "..."
}
```

## Integración con LAI

`lai/index.php` ahora intenta imprimir por `fetch('http://localhost:3000/print')`.

Si falla el agente local (apagado/error/time-out), usa fallback al mecanismo previo:

- `window.open('factura_tkt.php...')`
- `window.print()` desde `factura_tkt.php`

## Prueba manual rápida

```bash
curl -X POST http://127.0.0.1:3000/print \
  -H 'Content-Type: application/json' \
  -d '{"type":"ticket","content":"Ticket de prueba","copies":1}'
```
