# DQS UNI-005 - `whatsapp_enabled` aplicado al admin activo

## Propósito

UNI-005 aplica la clave central `whatsapp_enabled` al admin activo `admin7WZiwEM3XY/` para controlar la visibilidad y el acceso a herramientas de envíos WhatsApp.

El objetivo es que el comportamiento siga igual cuando `whatsapp_enabled = 1`, y que cuando `whatsapp_enabled = 0` el cliente/admin no pueda ver ni ejecutar herramientas de envío WhatsApp.

## Pantallas y endpoints controlados

UNI-005 controla exclusivamente el sistema admin activo:

- `admin7WZiwEM3XY/menu.php`
- `admin7WZiwEM3XY/gestionar_envios.php`
- `admin7WZiwEM3XY/invitados_invitaciones.php`
- `admin7WZiwEM3XY/whatsapp/envio_invitaciones.php`
- `admin7WZiwEM3XY/whatsapp/reenvio_invitaciones_erroneas.php`

El helper reutilizable agregado es:

- `includes/admin_feature_guard.php`

## Comportamiento con `whatsapp_enabled = 1`

Con `whatsapp_enabled = 1` el admin conserva el comportamiento previo:

- El menú muestra las opciones de envíos WhatsApp.
- `gestionar_envios.php` abre y funciona igual.
- `invitados_invitaciones.php` abre y permite iniciar los flujos existentes.
- Los endpoints PHP de WhatsApp mantienen su lógica actual.
- No se cambian consultas existentes ni lógica de envío.

## Comportamiento con `whatsapp_enabled = 0`

Con `whatsapp_enabled = 0`:

- El menú oculta las opciones relacionadas con envíos WhatsApp:
  - `Enviar Invitaciones`.
  - `Envio Automatico`.
- El acceso directo a pantallas/endpoints de envío queda bloqueado.
- Se muestra el mensaje amigable:

> La funcionalidad de envíos WhatsApp no está habilitada para este evento.

- No se ejecutan envíos WhatsApp.
- No se modifican invitados por entrar a las pantallas bloqueadas.
- No se modifica `invitados_a_enviar`.
- No se modifica `invitados_enviados`.
- No se modifica `registro_mensajes_enviados`.
- No se modifica `invitaciones_estado`.

## Cómo probar con la herramienta CLI del proveedor

### Probar WhatsApp habilitado

```bash
php tools/dqs_provider_config.php --set whatsapp_enabled=1 fuente_envios_whatsapp=invitados --apply
```

Validar:

- El menú admin muestra opciones de envío.
- `gestionar_envios.php` abre.
- `invitados_invitaciones.php` abre.
- No cambió home.
- No cambió RSVP.
- No cambió tienda.

### Probar WhatsApp deshabilitado en DEV

```bash
php tools/dqs_provider_config.php --set whatsapp_enabled=0 fuente_envios_whatsapp=ninguno --apply
```

Validar:

- El menú admin oculta `Enviar Invitaciones` y `Envio Automatico`.
- El acceso directo a `gestionar_envios.php` muestra el mensaje de funcionalidad no habilitada.
- El acceso directo a `invitados_invitaciones.php` muestra el mensaje de funcionalidad no habilitada.
- Los endpoints `whatsapp/envio_invitaciones.php` y `whatsapp/reenvio_invitaciones_erroneas.php` quedan bloqueados antes de ejecutar envíos.
- No se ejecuta envío.
- No se modifican invitados ni colas solo por entrar a una pantalla bloqueada.

## Cómo restaurar la configuración actual

La configuración actual indicada para el cliente es:

- `plan_servicio = oro`
- `rsvp_modo = codigo`
- `fuente_envios_whatsapp = invitados`
- `whatsapp_enabled = 1`
- `regalos_enabled = 1`

Para restaurar el estado operativo de WhatsApp:

```bash
php tools/dqs_provider_config.php --set whatsapp_enabled=1 fuente_envios_whatsapp=invitados --apply
```

## Confirmaciones de alcance

- UNI-005 no toca `admin_tmp`.
- UNI-005 no cambia RSVP.
- UNI-005 no cambia tienda.
- UNI-005 no cambia regalos.
- UNI-005 no cambia el front público.
- UNI-005 no ejecuta WhatsApp.
- UNI-005 no ejecuta Node.
- UNI-005 no crea, altera, fusiona ni borra tablas.
- UNI-005 no borra colas ni históricos.
