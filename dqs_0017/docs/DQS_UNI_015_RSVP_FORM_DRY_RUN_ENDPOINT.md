# DQS UNI-015 - Endpoint dry-run para RSVP formulario

## Propósito

UNI-015 agrega una capa de prueba para validar payloads del futuro RSVP por formulario sin guardar datos. Permite verificar cómo respondería el backend ante payloads válidos, inválidos, JSON malformado o métodos no permitidos, manteniendo aislado el front público y el flujo actual por código.

## Diferencia entre UNI-014 y UNI-015

- **UNI-014** define el contrato interno puro: normalización, validaciones, errores, advertencias y plan documental de persistencia futura en `includes/rsvp_form_contract.php`.
- **UNI-015** reutiliza ese contrato para construir respuestas dry-run y exponerlas por un endpoint público de validación y una herramienta CLI local.

Ninguna de las dos etapas guarda datos, abre DB, consulta tablas `pre_*`, modifica invitados reales ni envía WhatsApp.

## Helper creado

Se creó `includes/rsvp_form_dry_run.php`.

El helper:

- Requiere `includes/rsvp_form_contract.php`.
- Es seguro de incluir y no imprime salida al incluirse.
- No requiere `conexion.php`.
- No abre conexiones a DB.
- No consulta ni escribe tablas.
- No llama endpoints.
- No ejecuta WhatsApp ni Node.
- Expone funciones reutilizables:
  - `dqs_rsvp_form_dry_run_validate(array $payload): array`
  - `dqs_rsvp_form_dry_run_http_status(array $response): int`
  - `dqs_rsvp_form_dry_run_read_json_or_post(): array`

## Endpoint dry-run creado

Se creó `rsvp_form_validate.php`.

El endpoint:

- Acepta únicamente `POST`.
- Responde JSON con `Content-Type: application/json`.
- Setea `Cache-Control: no-store`.
- Usa `includes/rsvp_form_dry_run.php`.
- Soporta `application/json`, `application/x-www-form-urlencoded` y `multipart/form-data`.
- No incluye `conexion.php`.
- No abre DB.
- No consulta tablas reales ni tablas `pre_*`.
- No escribe datos.
- No crea sesiones.
- No envía WhatsApp.
- No llama Node.

## Respuestas del endpoint

### Payload válido

Devuelve HTTP `200` con `ok=true`, `dry_run=true`, `persisted=false`, `valid=true`, errores vacíos, advertencias si aplican y resumen de confirmación/personas/acompañantes.

### Payload inválido

Devuelve HTTP `422` con `ok=false`, `dry_run=true`, `persisted=false`, `valid=false`, listado de errores, advertencias si aplican y resumen normalizado.

### Método no permitido

Para `GET`, `PUT`, `DELETE` u otros métodos devuelve HTTP `405`:

```json
{
  "ok": false,
  "dry_run": true,
  "persisted": false,
  "message": "Método no permitido. Usar POST."
}
```

### JSON inválido

Para `application/json` malformado devuelve HTTP `400`:

```json
{
  "ok": false,
  "dry_run": true,
  "persisted": false,
  "message": "JSON inválido."
}
```

## CLI creado

Se creó `tools/dqs_rsvp_form_endpoint_probe.php`.

La herramienta es solo CLI, bloquea ejecución web, no abre DB, no escribe datos, no consulta tablas y usa el mismo helper dry-run que el endpoint.

Comandos:

```bash
php tools/dqs_rsvp_form_endpoint_probe.php --help
php tools/dqs_rsvp_form_endpoint_probe.php --sample=valid
php tools/dqs_rsvp_form_endpoint_probe.php --sample=invalid
php tools/dqs_rsvp_form_endpoint_probe.php --sample=no
php tools/dqs_rsvp_form_endpoint_probe.php --sample=companions
```

Cada sample imprime JSON con `simulated_http_status` y `response`, sin datos reales de invitados.

## Por qué todavía no guarda datos

UNI-015 es una etapa de validación dry-run. Su objetivo es estabilizar la forma de respuesta backend antes de definir persistencia, transacciones, tablas destino, compatibilidad con instalaciones sin `pre_*`, auditoría y relación futura con WhatsApp. Por eso `persisted` siempre es `false`.

## Por qué todavía no se conecta la modal pública

La conexión real del front queda para UNI-016. En UNI-015 no se modifica `includes/rsvp_form_public.php`, no se agrega `fetch`, no se agrega AJAX y no se cambia el `preventDefault` local de la modal visual. Esto evita afectar el comportamiento actual de `rsvp_modo=codigo` y mantiene el formulario público en modo visual sin persistencia.

## Pruebas por curl si el entorno lo permite

Payload válido:

```bash
curl -i -X POST \
  -H 'Content-Type: application/json' \
  --data '{"nombre":"Ana","apellido":"Demo","telefono":"111","confirmacion":"Si","restriccion_alimentaria":"No","cantidad_acompanantes":"0","acompanantes":[]}' \
  http://localhost/rsvp_form_validate.php
```

JSON inválido:

```bash
curl -i -X POST \
  -H 'Content-Type: application/json' \
  --data '{"nombre":' \
  http://localhost/rsvp_form_validate.php
```

Método no permitido:

```bash
curl -i http://localhost/rsvp_form_validate.php
```

## Cómo probar que no toca DB

Ejecutar una búsqueda de seguridad sobre los archivos de UNI-015:

```bash
grep -nE "(conexion.php|mysqli_|new PDO|SELECT|INSERT|UPDATE|DELETE|->query|fetch\(|procesar_confirmacion|confirmacion_modal|confirmacionModal|XMLHttpRequest|\.ajax)" includes/rsvp_form_dry_run.php rsvp_form_validate.php tools/dqs_rsvp_form_endpoint_probe.php
```

La salida esperada es vacía.

## Confirmación de alcance

UNI-015 no toca ni conecta:

- `index.php`.
- `includes/rsvp_form_public.php`.
- `confirmacion_modal.php`.
- `procesar_confirmacion.php`.
- `confirmar_asistencia.php`.
- `admin7WZiwEM3XY/`.
- `admin_tmp/`.
- Tienda.
- Regalos.
- WhatsApp activo.
- Node.
- Tablas `invitados`, `invitados_listado_mesa`, `invitados_tel`.
- Tablas `pre_invitados`, `pre_invitados_listado_mesa`, `pre_invitados_tel`.
