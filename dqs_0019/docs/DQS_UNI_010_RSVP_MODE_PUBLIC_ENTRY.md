# DQS UNI-010 - Selector `rsvp_modo` en la entrada pública del RSVP

## Propósito

UNI-010 aplica el selector interno `rsvp_modo` a la entrada pública del RSVP en `index.php`, manteniendo intacto el flujo vigente por código cuando la configuración efectiva es `codigo`.

El objetivo es dejar una compuerta segura para el futuro RSVP por formulario sin activar todavía ese flujo ni depender de tablas `pre_` en la base activa.

## Cambio aplicado en `index.php`

`index.php` ahora carga `includes/rsvp_mode.php` y obtiene el modo efectivo con:

```php
$dqsRsvpMode = dqs_rsvp_get_effective_mode($conn);
```

La sección pública `#rsvp` usa ese valor para decidir si muestra el flujo actual por código o un mensaje controlado para `form`.

## Comportamiento con `rsvp_modo=codigo`

Con `rsvp_modo=codigo`, el comportamiento visible se mantiene igual:

- La home sigue cargando normalmente.
- `/index.php#rsvp` sigue mostrando el buscador por código.
- `/index.php?busqueda=CODIGO#rsvp` sigue funcionando.
- Se mantienen las consultas actuales sobre `invitados` e `invitados_listado_mesa`.
- Se mantienen los textos actuales del buscador y los mensajes de resultado.
- Se mantiene el botón “Confirmar” / “Modificar Asistencia”.
- Se mantiene la apertura de `confirmacion_modal.php` desde el modal actual.
- No cambia el procesamiento de confirmación.

## Comportamiento con `rsvp_modo=form`

Con `rsvp_modo=form`, UNI-010 no implementa el formulario nuevo. En su lugar, la sección RSVP muestra el mensaje controlado:

> La confirmación de asistencia por formulario no está habilitada para este evento.

En este modo:

- La home debe seguir cargando.
- La sección RSVP no debe producir fatal errors.
- No se consultan tablas `pre_invitados`, `pre_invitados_listado_mesa` ni `pre_invitados_tel`.
- No se abre `confirmacion_modal.php` desde la sección RSVP.
- No se postea a `procesar_confirmacion.php`.
- No se modifican datos ni se confirman asistencias.

## Modo inválido

`includes/rsvp_mode.php` resuelve modos inválidos con fallback seguro a `codigo` mediante `dqs_rsvp_get_effective_mode($conn)`. Por eso, un valor inválido no debe romper la home y conserva el flujo actual por código.

## Qué NO se implementa todavía

UNI-010 no implementa todavía:

- RSVP por formulario.
- Lectura productiva desde `pre_invitados`.
- Consultas a `pre_invitados_listado_mesa` o `pre_invitados_tel`.
- Cambios en `confirmacion_modal.php`.
- Cambios en `procesar_confirmacion.php`.
- Cambios en `confirmar_asistencia.php`.
- Migraciones, creación o alteración de tablas.
- Inserción o modificación de invitados reales.

## Por qué `form` queda bloqueado/controlado

La base activa puede no tener tablas `pre_`, y eso es válido para el estado actual del producto. Por ese motivo, `form` queda como estado bloqueado/controlado en la entrada pública: permite validar que el selector no rompe la home sin activar un flujo incompleto ni generar errores SQL por tablas ausentes.

## Cómo probar con `tools/dqs_provider_config.php`

Ver configuración actual:

```bash
php tools/dqs_provider_config.php --show
```

Activar temporalmente el modo formulario de forma explícita:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=form --apply
```

Validar que la home carga, que `#rsvp` muestra el mensaje controlado y que no se consultan ni requieren tablas `pre_`.

## Cómo restaurar `rsvp_modo=codigo`

Después de la prueba temporal, restaurar el modo vigente por código:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=codigo --apply
```

Volver a validar:

```bash
php tools/dqs_provider_config.php --show
```

La configuración esperada para el estado actual es:

```text
plan_servicio: oro
rsvp_modo: codigo
fuente_envios_whatsapp: invitados
whatsapp_enabled: 1
regalos_enabled: 1
```

## Confirmación de alcance

UNI-010 solo toca la entrada pública del RSVP en `index.php` y esta documentación. No toca:

- `confirmacion_modal.php`.
- `procesar_confirmacion.php`.
- `confirmar_asistencia.php`.
- `admin7WZiwEM3XY/`.
- Tienda.
- Regalos.
- WhatsApp activo.
- Node.
- `admin_tmp`.
