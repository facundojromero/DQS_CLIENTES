# DQS UNI-020 — Persistencia RSVP formulario contract_v1

## Propósito

UNI-020 prepara la persistencia real del RSVP por formulario público en las tablas `pre_*` del perfil `contract_v1`, manteniendo el comportamiento dry-run por defecto.

## Diferencia con UNI-019

- UNI-019 preparó/migró el schema `contract_v1` de tablas `pre_*`.
- UNI-020 agrega el helper transaccional y conecta el endpoint para guardar solo cuando todas las condiciones de seguridad están activas.

## Nueva configuración

Clave: `rsvp_form_persist_enabled`.

Default en memoria: `0`.

Queda apagada por defecto para evitar escrituras reales accidentales durante la transición desde RSVP por código hacia RSVP por formulario.

## Condiciones exactas para guardar

El endpoint `rsvp_form_validate.php` solo guarda cuando se cumplen todas estas condiciones:

1. Request `POST` válida.
2. Payload válido según el contrato RSVP formulario.
3. Configuración efectiva `rsvp_modo=form`.
4. Configuración efectiva `rsvp_form_persist_enabled=1`.
5. Schema `contract_v1` listo (`ready=true`).

Si cualquiera falla, la respuesta conserva `dry_run=true` y `persisted=false`.

## Tablas y campos

### `pre_invitados`

Guarda:

- `nombre`
- `apellido`
- `confirmacion`
- `restriccion_alimentaria`
- `comentario`
- `cantidad_acompanantes`
- `total_personas`
- `origen`
- `activo`

### `pre_invitados_tel`

Guarda solo si hay teléfono:

- `id_pre_invitado`
- `telefono`

### `pre_invitados_listado_mesa`

Guarda acompañantes solo si `confirmacion=Si`:

- `id_pre_invitado`
- `nombre`
- `apellido`
- `restriccion_alimentaria`
- `comentario`
- `orden`

## Transacción y rollback

`includes/rsvp_form_persistence.php` usa `begin_transaction`, prepared statements, `commit` y `rollback`. Si falla cualquier insert del principal, teléfono o acompañantes, se revierte toda la operación.

## Dedupe anti doble-submit

Antes de insertar, el helper busca una confirmación igual de los últimos 5 minutos por:

- mismo `nombre`
- mismo `apellido`
- misma `confirmacion`
- mismo `telefono` cuando existe
- `fecha_registro >= NOW() - INTERVAL 5 MINUTE`

Si encuentra duplicado, no inserta nuevamente y devuelve `deduped=true` con el `principal_id` existente.

## Comportamientos por configuración

### `rsvp_modo=codigo`

No guarda nunca, aunque `rsvp_form_persist_enabled=1`. La razón controlada es `persistence_disabled_by_mode`.

### `rsvp_modo=form` con flag apagado

No guarda. La razón controlada es `persistence_feature_disabled`.

### Schema no ready

No guarda. La razón controlada es `persistence_schema_not_ready`.

## Qué NO hace UNI-020

- No cambia `rsvp_modo`.
- No activa `rsvp_form_persist_enabled` en `site_settings`.
- No escribe en tablas `invitados` actuales.
- No escribe en tablas legacy.
- No envía WhatsApp.
- No envía emails.
- No altera tablas.
- No hace `DROP`, `TRUNCATE` ni borrado de datos.
- No modifica admin, tienda, regalos, Node ni `admin_tmp`.

## Cómo probar sin escribir datos

```bash
php tools/dqs_rsvp_form_persistence_probe.php --status
php tools/dqs_rsvp_form_persistence_probe.php --sample=valid
php tools/dqs_rsvp_form_persistence_probe.php --sample=no
php tools/dqs_rsvp_form_persistence_probe.php --sample=companions
```

El probe es solo lectura y muestra:

- payload normalizado
- `insert_preview` sin SQL ejecutable
- configuración efectiva
- `persistence_enabled`
- `schema_ready`
- `would_persist`
- razón controlada
- conteos actuales de `pre_*`

## Cómo confirmar que las tablas siguen vacías

```bash
php -r 'require "conexion.php"; foreach (["pre_invitados","pre_invitados_listado_mesa","pre_invitados_tel"] as $t) { $r = $conn->query("SELECT COUNT(*) AS c FROM `$t`"); $row = $r ? $r->fetch_assoc() : ["c" => "ERROR"]; echo $t . ": " . $row["c"] . PHP_EOL; }'
```

Si estaban vacías antes de UNI-020, deben seguir en `0` tras ejecutar los probes.

## Pendiente para UNI-021

- Definir activación operativa controlada de `rsvp_modo=form`.
- Definir pruebas end-to-end con persistencia habilitada en entorno seguro.
- Definir UX final post-guardado y monitoreo operativo.
- Definir si habrá panel/admin para revisar/promover registros `pre_*`.
