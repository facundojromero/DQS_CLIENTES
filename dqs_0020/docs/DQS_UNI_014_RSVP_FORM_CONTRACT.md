# DQS UNI-014 - Contrato interno para RSVP formulario

## Propósito

UNI-014 crea un contrato interno y puro para el futuro RSVP por formulario. Define qué payload se espera recibir, cómo se normaliza, qué validaciones se aplican y qué estructura queda lista para una persistencia futura.

Esta etapa no implementa guardado, endpoints públicos, consultas a base de datos ni integración con WhatsApp o Node.

## Diferencia entre UNI-013 y UNI-014

- **UNI-013** preparó la modal pública visual con acompañantes dinámicos, manteniendo el submit bloqueado y sin persistencia.
- **UNI-014** agrega una capa interna de contrato y validación pura para esos datos, sin conectar todavía la modal a guardado real.

## Archivos creados

- `includes/rsvp_form_contract.php`: helper puro, seguro de incluir y sin efectos secundarios.
- `tools/dqs_rsvp_form_contract_check.php`: CLI de prueba con samples locales y sin datos reales de invitados.

## Funciones disponibles

### `dqs_rsvp_form_allowed_restrictions()`

Devuelve las restricciones alimentarias aceptadas:

- `No`
- `Vegetariano`
- `Vegano`
- `Celíaco`
- `Otro`

### `dqs_rsvp_form_contract_schema()`

Devuelve una descripción interna del contrato esperado, incluyendo campos del invitado principal, campos de acompañantes, límites y garantías de no persistencia.

### `dqs_rsvp_form_normalize_payload(array $payload)`

Recibe un array estilo `POST` y devuelve una estructura normalizada:

```php
[
  'principal' => [
    'nombre' => string,
    'apellido' => string,
    'telefono' => string,
    'confirmacion' => 'Si'|'No',
    'restriccion_alimentaria' => string,
    'comentario' => string,
  ],
  'cantidad_acompanantes' => int,
  'acompanantes' => [
    [
      'nombre' => string,
      'apellido' => string,
      'restriccion_alimentaria' => string,
      'comentario' => string,
    ]
  ],
  'totales' => [
    'total_personas' => int,
    'total_acompanantes' => int,
  ]
]
```

Normaliza con estas reglas:

- Aplica `trim` a strings.
- Convierte `cantidad_acompanantes` a entero.
- Limita `cantidad_acompanantes` entre `0` y `20`.
- Normaliza `confirmacion` a `Si` o `No` cuando reconoce variantes como `si`, `sí`, `yes`, `1`, `no`, `0`.
- Si `confirmacion = No`, la cantidad efectiva de acompañantes queda en `0`, `acompanantes` queda vacío y `total_personas` queda en `0`.
- Las restricciones alimentarias inválidas se conservan normalizadas para que la validación pueda reportar el error; no se reemplazan silenciosamente por `No`.

### `dqs_rsvp_form_validate_payload(array $payload)`

Devuelve:

```php
[
  'valid' => bool,
  'errors' => [
    ['field' => string, 'message' => string]
  ],
  'warnings' => [
    ['field' => string, 'message' => string]
  ],
  'normalized' => array
]
```

Validaciones aplicadas:

- `confirmacion` debe ser `Si` o `No`.
- Si `confirmacion = Si`, `nombre` y `apellido` principal son requeridos.
- `telefono` vacío genera warning, no error, porque el contrato aún no define obligatoriedad final de contacto.
- `restriccion_alimentaria` debe estar en las opciones permitidas.
- `cantidad_acompanantes` debe ser entero entre `0` y `20`.
- Si `confirmacion = No`, los acompañantes recibidos se ignoran y se reporta warning si venían bloques enviados.
- Si `confirmacion = Si` y `cantidad_acompanantes > 0`, debe existir un bloque por cada acompañante esperado.
- Cada acompañante esperado debe tener `nombre`, `apellido` y restricción válida.
- `comentario` es opcional, con máximo de `500` caracteres tanto para principal como para acompañantes.

### `dqs_rsvp_form_persistence_plan()`

Devuelve un plan documental no ejecutable para una etapa posterior:

- Principal hacia `pre_invitados` o tabla equivalente.
- Acompañantes hacia `pre_invitados_listado_mesa` o tabla equivalente.
- Teléfono hacia `pre_invitados_tel` o tabla equivalente.

El mapeo final, endpoint, transacciones, autorización y compatibilidad con bases sin tablas `pre_*` quedan explícitamente pendientes para otro issue.

## Payload esperado

Campos del invitado principal:

- `nombre`
- `apellido`
- `telefono`
- `confirmacion`
- `restriccion_alimentaria`
- `comentario`
- `cantidad_acompanantes`
- `acompanantes`

Campos por acompañante:

- `nombre`
- `apellido`
- `restriccion_alimentaria`
- `comentario`

## Acompañantes dinámicos

El contrato acepta `acompanantes` como array. Para compatibilidad con la shell visual actual, soporta índices `1..N`; también tolera arrays `0..N-1` en payloads locales. La normalización solo conserva los acompañantes efectivos definidos por `cantidad_acompanantes` y por `confirmacion`.

## CLI de prueba

La herramienta es solo CLI y bloquea ejecución web. No requiere `conexion.php`, no abre DB, no consulta tablas, no escribe datos y no usa datos reales de invitados.

Comandos:

```bash
php tools/dqs_rsvp_form_contract_check.php --help
php tools/dqs_rsvp_form_contract_check.php --schema
php tools/dqs_rsvp_form_contract_check.php --sample=valid
php tools/dqs_rsvp_form_contract_check.php --sample=invalid
php tools/dqs_rsvp_form_contract_check.php --sample=no
php tools/dqs_rsvp_form_contract_check.php --sample=companions
```

Cada sample imprime `valid`, `errors`, `warnings` y `normalized` en JSON.

## Cómo probar que no toca DB

Ejecutar el grep de seguridad sobre el helper y la herramienta:

```bash
grep -nE "(conexion.php|mysqli_|new PDO|SELECT|INSERT|UPDATE|DELETE|->query|fetch\(|procesar_confirmacion|confirmacion_modal|XMLHttpRequest|\.ajax|fetch\()" includes/rsvp_form_contract.php tools/dqs_rsvp_form_contract_check.php
```

El resultado esperado es sin coincidencias. Si el comando termina con código `1`, significa que no encontró patrones.

## Cómo restaurar `rsvp_modo=codigo` si se prueba `form`

UNI-014 no cambia configuración guardada. Si en una prueba manual se cambia el modo, restaurar con la herramienta de proveedor existente usando el flujo documentado de UNI-003, por ejemplo revisando primero:

```bash
php tools/dqs_provider_config.php --show
```

Luego volver a aplicar `rsvp_modo=codigo` con el procedimiento de configuración del proveedor definido para el entorno. No es necesario hacerlo para UNI-014 si la configuración permanece como `codigo`.

## Qué no se implementa todavía

UNI-014 no implementa:

- Guardado en base de datos.
- Endpoint público nuevo.
- Envío real desde la modal.
- `fetch`, AJAX o POST real.
- Consultas a `pre_invitados`, `pre_invitados_listado_mesa` o `pre_invitados_tel`.
- Creación, alteración o migración de tablas.
- Confirmaciones reales de asistencia.
- Integración con WhatsApp o Node.

## Confirmación de alcance

UNI-014 no toca `confirmacion_modal.php`, `procesar_confirmacion.php`, `confirmar_asistencia.php`, `admin7WZiwEM3XY/`, tienda, regalos, WhatsApp, Node ni `admin_tmp`.
