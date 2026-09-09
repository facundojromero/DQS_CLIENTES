# DQS UNI-004 - GuestSource

## Propósito

`GuestSource` es una capa interna de lectura para abstraer el acceso a invitados normales y preinvitados sin cambiar todavía las pantallas visibles ni los flujos activos.

El helper vive en `includes/guest_source.php` y cumple estas reglas:

- No ejecuta consultas al ser incluido.
- No abre conexiones automáticamente.
- No inserta, actualiza ni elimina datos.
- Usa consultas preparadas para todos los valores dinámicos.
- Devuelve respuestas controladas si la fuente es inválida, si una tabla no existe o si no hay resultados.
- No expone credenciales ni imprime secretos.
- No requiere que `pre_invitados` exista en todas las bases.

## Fuentes soportadas

- `invitados`
- `pre_invitados`

## Comparativa de tablas

| Fuente | Tabla principal | Integrantes/personas | Teléfonos | FK integrantes usada | FK teléfonos usada |
| --- | --- | --- | --- | --- | --- |
| `invitados` | `invitados` | `invitados_listado_mesa` | `invitados_tel` | `id_invitados` | `id_invitados` |
| `pre_invitados` | `pre_invitados` | `pre_invitados_listado_mesa` | `pre_invitados_tel` | `id_invitados` | `id_invitados` |

### Nota de compatibilidad sobre `pre_invitados`

El objetivo inicial mencionaba `id_pre_invitados` para las tablas `pre_`. Al revisar las referencias existentes, el SQL de `oro_form` y el WhatsApp de formulario usan `id_invitados` en `pre_invitados_listado_mesa` y `pre_invitados_tel`. Por compatibilidad con el material existente, `GuestSource` usa `id_invitados` para la fuente `pre_invitados`.

Columnas relevantes confirmadas en referencias:

- `pre_invitados`: `id`, `nombre`, `apellido`, `activo`, `acompanado`, `cantidad_mayores`, `id_prioridad`, `ingreso`, `cantidad_menores`, `fecha_registro`, `confirmacion`, `confirmacion_fecha`, `confirmacion_comentario`, `confirmacion_mayores`, `confirmacion_menores`, `alimento`, `codigo`.
- `pre_invitados_listado_mesa`: `id_invitados`, `nombre_invitado`, `mesa`, `id`, `nombre2`, `apellido2`.
- `pre_invitados_tel`: `id`, `id_invitados`, `tel_enviar`.

## Funciones creadas

### `dqs_guest_source_allowed_sources(): array`

Devuelve las fuentes soportadas:

```php
['invitados', 'pre_invitados']
```

### `dqs_guest_source_is_valid(string $source): bool`

Devuelve `true` si `$source` es `invitados` o `pre_invitados`; en caso contrario devuelve `false`.

### `dqs_guest_source_get_table_map(string $source): array`

Devuelve el mapa interno de tablas y FK para una fuente válida. Si la fuente es inválida devuelve `[]`.

Ejemplo para `invitados`:

```php
[
    'source' => 'invitados',
    'main_table' => 'invitados',
    'members_table' => 'invitados_listado_mesa',
    'phones_table' => 'invitados_tel',
    'members_fk' => 'id_invitados',
    'phones_fk' => 'id_invitados',
]
```

### `dqs_guest_source_table_exists(mysqli $conn, string $tableName): bool`

Consulta `information_schema.TABLES` con prepared statement y devuelve si la tabla existe en la base actual.

Si el nombre está vacío, la conexión no permite preparar la consulta o la consulta falla, devuelve `false`.

### `dqs_guest_source_find_by_codigo(mysqli $conn, string $codigo, string $source = 'invitados'): ?array`

Busca en la tabla principal de la fuente por `codigo`.

- Con `invitados`, lee `invitados`.
- Con `pre_invitados`, lee `pre_invitados`.
- Si la fuente es inválida, la tabla no existe o el código no existe, devuelve `null`.

### `dqs_guest_source_get_members(mysqli $conn, int $guestId, string $source = 'invitados'): array`

Devuelve integrantes/personas del invitado/preinvitado ordenados por `id ASC`.

- Con `invitados`, lee `invitados_listado_mesa` por `id_invitados`.
- Con `pre_invitados`, lee `pre_invitados_listado_mesa` por `id_invitados`.
- Si la fuente es inválida, la tabla no existe o no hay filas, devuelve `[]`.

### `dqs_guest_source_get_phones(mysqli $conn, int $guestId, string $source = 'invitados'): array`

Devuelve teléfonos del invitado/preinvitado ordenados por `id ASC`.

- Con `invitados`, lee `invitados_tel` por `id_invitados`.
- Con `pre_invitados`, lee `pre_invitados_tel` por `id_invitados`.
- Si la fuente es inválida, la tabla no existe o no hay filas, devuelve `[]`.

### `dqs_guest_source_get_rsvp_status(mysqli $conn, int $guestId, string $source = 'invitados'): array`

Devuelve el estado RSVP agregado desde la tabla principal:

- `id`
- `confirmacion`
- `confirmacion_fecha`
- `confirmacion_comentario`
- `confirmacion_mayores`
- `confirmacion_menores`

Si la fuente es inválida, la tabla no existe, la consulta no puede prepararse o no existe el registro, devuelve `[]`.

### `dqs_guest_source_from_plan_config(?mysqli $conn = null): string`

Lee `fuente_envios_whatsapp` desde `dqs_get_effective_plan_config()` si `includes/plan_config.php` está disponible. Si la configuración no está disponible o devuelve una fuente no soportada, usa `invitados` como fallback.

Esta función solo devuelve un string. No aplica cambios a front, admin ni WhatsApp.

## Ejemplos de uso

```php
require_once __DIR__ . '/includes/guest_source.php';

$map = dqs_guest_source_get_table_map('invitados');
```

```php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/guest_source.php';

$guest = dqs_guest_source_find_by_codigo($conn, '123456', 'invitados');
if ($guest) {
    $members = dqs_guest_source_get_members($conn, (int)$guest['id'], 'invitados');
    $phones = dqs_guest_source_get_phones($conn, (int)$guest['id'], 'invitados');
    $rsvp = dqs_guest_source_get_rsvp_status($conn, (int)$guest['id'], 'invitados');
}
```

```php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/guest_source.php';

$preGuest = dqs_guest_source_find_by_codigo($conn, '675211', 'pre_invitados');
```

## Manejo de tablas inexistentes

Antes de consultar una tabla de fuente, el helper llama a `dqs_guest_source_table_exists()`.

Si `pre_invitados`, `pre_invitados_listado_mesa` o `pre_invitados_tel` no existen en una base, el helper no provoca fatal error y devuelve:

- `null` en búsquedas por código.
- `[]` en listados, teléfonos y estado RSVP.
- `false` en validación de existencia de tabla.

## Alcance de esta PR

UNI-004 deja preparada la abstracción interna. Esta PR confirma que:

- No cambia comportamiento visible.
- No cambia `index.php`.
- No cambia `confirmacion_modal.php`.
- No cambia `procesar_confirmacion.php`.
- No cambia `confirmar_asistencia.php`.
- No cambia `gestionar_envios.php`.
- No cambia WhatsApp.
- No cambia Node.
- No cambia tienda.
- No cambia menú admin.
- No toca `admin_tmp`.
- No reemplaza consultas actuales.
- No migra datos.
- No crea, altera ni fusiona tablas.
