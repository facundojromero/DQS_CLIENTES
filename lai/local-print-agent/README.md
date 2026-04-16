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

`lai/index.php` ahora intenta imprimir por `fetch('http://127.0.0.1:3000/print')`.

Si falla el agente local (apagado/error/time-out), usa fallback al mecanismo previo:

- `window.open('factura_tkt.php...')`
- `window.print()` desde `factura_tkt.php`

> **Modo actual en `lai/index.php`:** `strictLocalPrint: true`.  
> Esto evita popup/fallback de navegador y muestra aviso si el agente local no está disponible.

## Diagnóstico cuando sigue apareciendo la ventana del navegador

Si aparece la ventana de impresión, significa que se activó el fallback.

Validar en este orden:

1. **Agente encendido**  
   `curl http://127.0.0.1:3000/health`
2. **Permisos CORS/PNA**  
   Esta versión ya responde `Access-Control-Allow-Private-Network: true` para navegadores modernos.
3. **Motor de impresión del SO**  
   - Linux/macOS necesita `lp` (CUPS).
   - Windows usa `powershell` + `Out-Printer`.
4. **Impresora predeterminada del equipo**  
   Debe existir una predeterminada accesible por el usuario que corre el agente.

Revisar logs del agente: cada error se registra en JSON con detalle.

## Importante para sitio online

Si la web está publicada en internet, **igual necesitás un programa local en cada PC de caja**.

Arquitectura correcta:

1. Usuario abre la web online.
2. El navegador de esa PC llama a `127.0.0.1:3000` (agente local).
3. El agente imprime en la impresora configurada en esa misma PC.

Sin agente local no existe forma segura de imprimir directo desde una web pública sin diálogo del navegador.

## Prueba manual rápida

```bash
curl -X POST http://127.0.0.1:3000/print \
  -H 'Content-Type: application/json' \
  -d '{"type":"ticket","content":"Ticket de prueba","copies":1}'
```
