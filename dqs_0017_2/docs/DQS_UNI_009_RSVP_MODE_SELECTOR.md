# DQS UNI-009 - Selector interno de modo RSVP

## Propósito

UNI-009 agrega una capa interna para interpretar `rsvp_modo` sin aplicar todavía esa decisión al front activo. El objetivo es preparar futuros cambios de selección entre RSVP por código y RSVP por formulario, manteniendo el comportamiento visible actual.

## Helper interno

El helper creado es:

- `includes/rsvp_mode.php`

Funciones disponibles:

- `dqs_rsvp_allowed_modes()`
- `dqs_rsvp_is_valid_mode($mode)`
- `dqs_rsvp_get_effective_mode($conn = null)`
- `dqs_rsvp_is_codigo_mode($conn = null)`
- `dqs_rsvp_is_form_mode($conn = null)`
- `dqs_rsvp_expected_guest_source_for_mode($mode)`
- `dqs_rsvp_get_mode_diagnostics(mysqli $conn = null, $mode = null)`

El helper es seguro de incluir: no abre conexiones, no ejecuta consultas automáticamente al ser requerido, no escribe en base de datos y no imprime salida por sí solo.

## Qué no hace todavía

UNI-009 no cambia el flujo activo. En particular:

- No aplica `rsvp_modo` al front.
- No modifica `index.php`.
- No modifica modales o procesadores de confirmación.
- No modifica el admin activo `admin7WZiwEM3XY/`.
- No toca tienda, regalos, WhatsApp ni Node.
- No toca `admin_tmp`.
- No crea ni altera tablas.
- No inserta datos ni modifica invitados reales.
- No confirma asistencias.
- No activa `rsvp_modo=form`.
- No cambia `rsvp_modo` ni `fuente_envios_whatsapp` guardados.

## Por qué no se aplica aún al front

El sistema activo usa RSVP por código con las tablas `invitados`, `invitados_listado_mesa` e `invitados_tel`. UNI-009 solo prepara la interpretación interna para que futuros PRs puedan cambiar el flujo de forma controlada y con regresiones puntuales. Aplicarlo ahora al front podría alterar el comportamiento visible y depender de tablas `pre_` que pueden no existir en la instalación activa.

## Significado de `rsvp_modo=codigo`

`codigo` es el modo actual seguro. Representa el flujo vigente en el que el invitado confirma usando un código asociado a la fuente actual `invitados`.

Fuente esperada:

- `codigo` -> `invitados`

## Significado de `rsvp_modo=form`

`form` queda preparado como modo futuro para un RSVP basado en formulario/pre-registro. UNI-009 puede diagnosticarlo, pero no lo activa ni lo conecta al front activo.

Fuente esperada:

- `form` -> `pre_invitados`

## Relación con GuestSource

UNI-009 usa el contrato read-only de `GuestSource` para mapear fuentes y verificar tablas esperadas de forma segura. Para `codigo`, la fuente esperada es `invitados`. Para `form`, la fuente esperada es `pre_invitados`.

Si se analiza `form` y faltan tablas `pre_`, el diagnóstico debe reportar advertencias no fatales. La ausencia de `pre_invitados`, `pre_invitados_listado_mesa` o `pre_invitados_tel` en la base activa no se considera error para el comportamiento actual.

## Relación con UNI-007 y UNI-008

- UNI-007 documentó el baseline del RSVP actual por código.
- UNI-008 documentó el baseline futuro del RSVP formulario/pre_ y dejó claro que las tablas `pre_` pueden no existir en la base activa.
- UNI-009 une ambos conceptos en un selector interno read-only, sin activar cambios visibles.

## Riesgos de activar `form` sin tablas `pre_`

Activar `form` en una instalación sin tablas `pre_` podría romper consultas futuras si el front o el admin comenzaran a depender de `pre_invitados`. Por eso UNI-009 solo diagnostica: informa que `form` no es seguro para la instalación actual cuando faltan tablas `pre_`, pero no ejecuta migraciones ni cambia configuración.

## CLI read-only

Se creó la herramienta opcional:

```bash
php tools/dqs_rsvp_mode_check.php --help
php tools/dqs_rsvp_mode_check.php --show
php tools/dqs_rsvp_mode_check.php --mode=codigo
php tools/dqs_rsvp_mode_check.php --mode=form
```

La herramienta muestra:

- configuración guardada/base calculada;
- configuración efectiva;
- modo RSVP efectivo;
- fuente esperada;
- si el modo es válido;
- si el modo es seguro para la instalación actual;
- existencia o ausencia de tablas esperadas;
- advertencias sin fatal error.

La herramienta es solo CLI. Si se abre desde navegador, responde `403` con `CLI only`.

## Confirmaciones de alcance

UNI-009 no toca front activo, admin activo, tienda, regalos, WhatsApp, Node ni `admin_tmp`. Tampoco modifica configuración real ni datos de invitados.
