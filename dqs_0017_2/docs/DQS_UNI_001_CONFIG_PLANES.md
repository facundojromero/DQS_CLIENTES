# DQS UNI-001 - Configuración central de planes y flujos

## Estado

UNI-001 agrega una capa central de lectura de configuración para futuros pasos de unificación de planes. Esta PR no cambia el comportamiento visible del sistema activo.

## Admin activo y carpetas fuera de alcance

- El admin activo instalado es `admin7WZiwEM3XY/`.
- `admin_tmp` pertenece a referencias/instalador y no se toca en esta etapa.
- No se ejecutan migraciones, SQL real, instaladores, Node ni procesos WhatsApp.

## Helper creado

El helper central está en:

- `includes/plan_config.php`

Funciones disponibles:

- `dqs_get_plan_config(?mysqli $conn = null): array`
- `dqs_get_plan_config_value(string $key, ?mysqli $conn = null): ?string`
- `dqs_is_valid_plan_config_value(string $key, string $value): bool`

La lectura usa `site_settings` si existe y si contiene claves soportadas. Si una clave no existe, si la tabla no está disponible o si el valor guardado no pertenece al dominio permitido, el helper devuelve el default en memoria. El helper no crea tablas, no inserta defaults y no altera datos.

## Claves, valores y defaults

| Clave | Valores aceptados | Default obligatorio | Motivo del default |
| --- | --- | --- | --- |
| `plan_servicio` | `basico`, `oro` | `oro` | El sistema activo actual incluye regalos/tienda y corresponde al flujo oro instalado. |
| `rsvp_modo` | `codigo`, `form` | `codigo` | El front público activo resuelve RSVP por código/búsqueda. |
| `fuente_envios_whatsapp` | `ninguno`, `invitados`, `pre_invitados` | `invitados` | El WhatsApp actual se basa en `invitados`, no en `pre_invitados`. |
| `whatsapp_enabled` | `0`, `1` | `1` | Preserva la disponibilidad actual de herramientas WhatsApp. |
| `regalos_enabled` | `0`, `1` | `1` | Preserva regalos, tienda, transferencia, gift card y carrito actuales. |

## Cómo leer la configuración desde código

Ejemplo usando una conexión existente:

```php
require_once __DIR__ . '/includes/plan_config.php';

$config = dqs_get_plan_config($conn);
$planServicio = $config['plan_servicio'];
$rsvpModo = dqs_get_plan_config_value('rsvp_modo', $conn);
```

Si se omite `$conn`, el helper intenta usar `$GLOBALS['conn']`. Si no hay conexión activa, devuelve los defaults en memoria.

## Preservación del comportamiento actual

Los defaults elegidos representan el sistema activo documentado:

- Front público `index.php` sin cambios.
- RSVP por código/búsqueda sin cambios.
- Modal `confirmacion_modal.php` sin cambios.
- Guardado en `procesar_confirmacion.php` sin cambios.
- Invitados en `invitados` e `invitados_listado_mesa` sin cambios.
- Regalos, tienda, transferencia, gift card y carrito habilitados como hasta ahora.
- WhatsApp con fuente `invitados`, no `pre_invitados`.
- Admin activo en `admin7WZiwEM3XY/` sin cambios.

## Fuera de alcance de UNI-001

Esta PR solo prepara la configuración para futuros PRs. No aplica todavía las claves al comportamiento visible. En particular:

- No oculta menú admin.
- No cambia el front.
- No cambia RSVP.
- No cambia tienda.
- No cambia WhatsApp.
- No cambia `gestionar_envios.php`.
- No cambia `procesar_confirmacion.php`.
- No cambia `confirmacion_modal.php`.
- No toca `admin_tmp`.
- No usa `pre_invitados`.
- No fusiona tablas.
- No migra datos.
