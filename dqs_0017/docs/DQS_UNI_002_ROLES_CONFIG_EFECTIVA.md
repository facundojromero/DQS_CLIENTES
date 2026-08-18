# DQS UNI-002 - Roles y configuración efectiva

## Objetivo

UNI-002 agrega una capa helper de **configuración efectiva** sobre la configuración central creada en UNI-001. Esta capa calcula reglas comerciales en memoria según el plan y los roles reales del producto, sin cambiar pantallas ni flujos existentes.

Esta PR deja las funciones disponibles para próximos cambios, pero **no aplica todavía** estas decisiones en front, RSVP, tienda, WhatsApp ni menú admin.

## Roles del sistema

### 1. Proveedor del servicio / dueño de DQS

Es quien vende, instala o configura el servicio para cada cliente. Define la configuración comercial base:

- Si el cliente tiene plan `basico` u `oro`.
- Si WhatsApp está habilitado mediante `whatsapp_enabled`.
- Si regalos está habilitado mediante `regalos_enabled`.
- Qué fuente de envíos WhatsApp se configura mediante `fuente_envios_whatsapp`.

Esta configuración pertenece al proveedor del servicio y no debe quedar editable por el cliente/admin de los novios.

### 2. Cliente / admin de los novios

Es quien usa el admin instalado, por ejemplo `admin7WZiwEM3XY/`, para administrar su web, invitados, regalos y datos operativos.

El cliente/admin:

- Puede administrar contenido y datos de su evento según las pantallas existentes.
- Podrá elegir en el futuro el modo RSVP solo si su plan efectivo lo permite.
- No debe poder cambiar el plan comercial.
- No debe poder habilitar por sí mismo WhatsApp si el proveedor lo deshabilitó.
- No debe poder habilitar por sí mismo regalos si el proveedor lo deshabilitó.

### 3. Invitado

Es quien usa la web pública. No configura plan, WhatsApp, regalos ni modo RSVP comercial.

## Qué NO debe poder configurar el cliente/admin

El cliente/admin no debe poder modificar directamente estas decisiones comerciales:

- `plan_servicio`
- `whatsapp_enabled`
- `regalos_enabled`

En PRs futuros, si el plan es Oro, el cliente/admin podrá elegir entre `rsvp_modo = codigo` y `rsvp_modo = form`, pero esa elección debe estar limitada por el plan efectivo.

## Reglas por plan

### Plan Básico

Configuración comercial esperada:

- `plan_servicio = basico`

Reglas efectivas:

- RSVP efectivo siempre es `form`.
- No permite RSVP por código, aunque el valor guardado diga `codigo`.
- El cliente/admin no puede elegir modo RSVP.
- Regalos pueden existir si `regalos_enabled = 1`.

### Plan Oro

Configuración comercial esperada:

- `plan_servicio = oro`

Reglas efectivas:

- RSVP efectivo respeta el `rsvp_modo` configurado.
- El cliente/admin podrá elegir en el futuro entre `codigo` y `form`.
- No deben estar ambos modos RSVP activos al mismo tiempo.
- Si el modo es `codigo`, la fuente normal será `invitados`.
- Si el modo es `form` y se usa WhatsApp/prelista, la fuente posible será `pre_invitados`.

## Reglas WhatsApp

- Si `whatsapp_enabled = 0`, la fuente efectiva de WhatsApp es `ninguno`.
- Si `whatsapp_enabled = 1`, la fuente efectiva puede conservar `fuente_envios_whatsapp`.

## Funciones creadas

Las funciones nuevas quedan disponibles en `includes/plan_config.php`:

- `dqs_get_effective_plan_config(?mysqli $conn = null): array`
- `dqs_get_effective_rsvp_modo(?mysqli $conn = null): string`
- `dqs_is_plan_basico(?mysqli $conn = null): bool`
- `dqs_is_plan_oro(?mysqli $conn = null): bool`
- `dqs_can_cliente_choose_rsvp_mode(?mysqli $conn = null): bool`
- `dqs_is_whatsapp_enabled(?mysqli $conn = null): bool`
- `dqs_is_regalos_enabled(?mysqli $conn = null): bool`

> Nota: el helper mantiene el estilo PHP existente del proyecto y documenta los tipos esperados en PHPDoc.

## Ejemplos de configuración efectiva

### Básico con RSVP guardado como código

Configuración guardada:

```php
[
    'plan_servicio' => 'basico',
    'rsvp_modo' => 'codigo',
    'fuente_envios_whatsapp' => 'invitados',
    'whatsapp_enabled' => '1',
    'regalos_enabled' => '1',
]
```

Configuración efectiva:

```php
[
    'plan_servicio' => 'basico',
    'rsvp_modo' => 'form',
    'fuente_envios_whatsapp' => 'invitados',
    'whatsapp_enabled' => '1',
    'regalos_enabled' => '1',
]
```

Resultado:

- `dqs_get_effective_rsvp_modo()` devuelve `form`.
- `dqs_can_cliente_choose_rsvp_mode()` devuelve `false`.

### Oro con RSVP por código

Configuración guardada:

```php
[
    'plan_servicio' => 'oro',
    'rsvp_modo' => 'codigo',
    'fuente_envios_whatsapp' => 'invitados',
    'whatsapp_enabled' => '1',
    'regalos_enabled' => '1',
]
```

Configuración efectiva:

```php
[
    'plan_servicio' => 'oro',
    'rsvp_modo' => 'codigo',
    'fuente_envios_whatsapp' => 'invitados',
    'whatsapp_enabled' => '1',
    'regalos_enabled' => '1',
]
```

Resultado:

- `dqs_get_effective_rsvp_modo()` devuelve `codigo`.
- `dqs_can_cliente_choose_rsvp_mode()` devuelve `true`.

### WhatsApp deshabilitado

Configuración guardada:

```php
[
    'plan_servicio' => 'oro',
    'rsvp_modo' => 'form',
    'fuente_envios_whatsapp' => 'pre_invitados',
    'whatsapp_enabled' => '0',
    'regalos_enabled' => '1',
]
```

Configuración efectiva:

```php
[
    'plan_servicio' => 'oro',
    'rsvp_modo' => 'form',
    'fuente_envios_whatsapp' => 'ninguno',
    'whatsapp_enabled' => '0',
    'regalos_enabled' => '1',
]
```

Resultado:

- `dqs_is_whatsapp_enabled()` devuelve `false`.
- La fuente efectiva de envíos WhatsApp queda en `ninguno`.

## Confirmación de alcance

UNI-002 solo agrega helpers y documentación. Esta PR no cambia comportamiento visible:

- No crea pantalla visible.
- No cambia front.
- No cambia RSVP.
- No cambia tienda.
- No cambia WhatsApp.
- No cambia menú admin.
- No toca `admin_tmp`.
- No migra datos.
- No fusiona tablas.
