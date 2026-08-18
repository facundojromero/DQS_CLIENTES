# UNI-017 — RSVP formulario: plan interno de persistencia futura

## Propósito

UNI-017 prepara una capa interna para auditar y planificar la persistencia futura del RSVP por formulario, sin activar guardado real. La configuración esperada sigue siendo el flujo vigente por código:

- `plan_servicio = oro`
- `rsvp_modo = codigo`
- `fuente_envios_whatsapp = invitados`
- `whatsapp_enabled = 1`
- `regalos_enabled = 1`

El flujo activo continúa usando `invitados`, `invitados_listado_mesa` e `invitados_tel`. La ausencia de tablas `pre_*` en la base activa es válida y se informa solo como advertencia controlada.

## Diferencia entre UNI-016 y UNI-017

- **UNI-016** conectó la modal pública del RSVP formulario al endpoint `rsvp_form_validate.php` en modo dry-run. Valida payloads y muestra respuestas, pero conserva `dry_run=true` y `persisted=false`.
- **UNI-017** no modifica la modal, el endpoint ni el front. Agrega una capa interna para diagnosticar schema `pre_*` en modo read-only y construir un plan no ejecutable de operaciones futuras a partir del payload normalizado por UNI-014.

## Helper creado

Se creó `includes/rsvp_form_persistence_plan.php`.

Funciones principales:

- `dqs_rsvp_form_persistence_target_tables()` devuelve las tablas futuras objetivo.
- `dqs_rsvp_form_persistence_required_columns()` declara columnas mínimas esperadas por tabla.
- `dqs_rsvp_form_persistence_schema_diagnostics(mysqli $conn): array` inspecciona tablas y columnas en modo read-only.
- `dqs_rsvp_form_persistence_build_plan(array $normalizedPayload): array` construye un plan no ejecutable con `executable=false` y `write_enabled=false`.
- `dqs_rsvp_form_persistence_is_ready(array $diagnostics): bool` devuelve `true` solo cuando están todas las tablas y columnas mínimas.

## CLI creado

Se creó `tools/dqs_rsvp_form_persistence_probe.php`.

Es una herramienta solo CLI. Si se intenta ejecutar por web, responde `403` y corta con `CLI only`.

Opciones:

```bash
php tools/dqs_rsvp_form_persistence_probe.php --help
php tools/dqs_rsvp_form_persistence_probe.php --schema
php tools/dqs_rsvp_form_persistence_probe.php --sample=valid
php tools/dqs_rsvp_form_persistence_probe.php --sample=no
php tools/dqs_rsvp_form_persistence_probe.php --sample=companions
```

## Tablas futuras auditadas

UNI-017 audita estas tablas futuras:

- `pre_invitados`
- `pre_invitados_listado_mesa`
- `pre_invitados_tel`

## Columnas mínimas esperadas

### `pre_invitados`

- `id`
- `nombre`
- `apellido`
- `confirmacion`
- `restriccion_alimentaria`
- `comentario`

### `pre_invitados_listado_mesa`

- `id`
- `id_pre_invitado`
- `nombre`
- `apellido`
- `restriccion_alimentaria`
- `comentario`

### `pre_invitados_tel`

- `id`
- `id_pre_invitado`
- `telefono`

Estas columnas son mínimas para planificar una persistencia futura. UNI-017 no asume que todas existan hoy.

## Qué pasa si faltan tablas `pre_*`

Si faltan tablas `pre_*`, el helper devuelve:

- `missing_tables` con las tablas ausentes.
- `missing_columns` con las columnas que no pueden verificarse por tabla ausente.
- `warnings` no fatales.
- `ready=false`.

Esto no debe romper el flujo activo porque `rsvp_modo` permanece en `codigo` y la fuente activa sigue siendo `invitados`.

## Cómo se genera el plan de operaciones futuras

Los samples locales del CLI se normalizan con el contrato UNI-014 mediante `dqs_rsvp_form_validate_payload()`. Luego el payload normalizado se pasa a `dqs_rsvp_form_persistence_build_plan()`.

El resultado incluye:

- `executable=false`
- `write_enabled=false`
- `contains_sql=false`
- `operations[]` con items de tipo `future_insert`
- `data_preview` con datos normalizados, sin SQL ejecutable

El plan puede incluir operaciones futuras para:

- Crear el invitado principal en `pre_invitados`.
- Guardar teléfono en `pre_invitados_tel`, si hay teléfono.
- Crear acompañantes en `pre_invitados_listado_mesa`.

## Confirmación explícita de seguridad

UNI-017:

- No escribe datos.
- No crea tablas.
- No altera tablas.
- No ejecuta migraciones.
- No confirma asistencias reales.
- No conecta el endpoint dry-run a guardado real.
- No modifica `index.php`.
- No modifica `includes/rsvp_form_public.php`.
- No modifica `rsvp_form_validate.php`.
- No modifica `includes/rsvp_form_dry_run.php`.
- No modifica admin, tienda, regalos, WhatsApp, Node ni `admin_tmp`.

## Cómo probar CLI

Lint:

```bash
php -l includes/rsvp_form_persistence_plan.php
php -l tools/dqs_rsvp_form_persistence_probe.php
```

Diagnóstico read-only de schema:

```bash
php tools/dqs_rsvp_form_persistence_probe.php --schema
```

Samples locales sin guardado:

```bash
php tools/dqs_rsvp_form_persistence_probe.php --sample=valid
php tools/dqs_rsvp_form_persistence_probe.php --sample=no
php tools/dqs_rsvp_form_persistence_probe.php --sample=companions
```

Verificación de strings de escritura en los archivos nuevos:

```bash
grep -nE "(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE|CREATE TABLE|mysqli_query\(.+INSERT|mysqli_query\(.+UPDATE|mysqli_query\(.+DELETE)" includes/rsvp_form_persistence_plan.php tools/dqs_rsvp_form_persistence_probe.php
```

El resultado esperado para esa verificación es no encontrar coincidencias.

## Cómo confirmar que `rsvp_modo` queda en `codigo`

Ejecutar:

```bash
php tools/dqs_provider_config.php --show
```

Verificar que siga mostrando:

- `plan_servicio: oro`
- `rsvp_modo: codigo`
- `fuente_envios_whatsapp: invitados`
- `whatsapp_enabled: 1`
- `regalos_enabled: 1`

## Pendiente para UNI-018

UNI-018 debería definir, todavía con compuertas explícitas, cómo pasar de plan no ejecutable a persistencia real. Queda pendiente:

- Confirmar schema definitivo de `pre_*`.
- Definir transacciones, ids relacionados y auditoría.
- Definir compuertas para activar escritura real.
- Mantener compatibilidad con bases sin tablas `pre_*`.
- Decidir cómo se conectaría el endpoint sin afectar el flujo activo por código.
