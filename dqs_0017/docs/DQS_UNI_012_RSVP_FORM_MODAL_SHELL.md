# DQS UNI-012 - RSVP formulario como modal visual sin persistencia

## Propósito

UNI-012 convierte la shell pública de RSVP formulario en una experiencia modal visual para validar la UX del modo `rsvp_modo=form` sin guardar datos, sin crear endpoints y sin consultar tablas `pre_*`.

## Diferencia entre UNI-011 y UNI-012

- **UNI-011** mostraba una shell pública aislada con campos visuales deshabilitados.
- **UNI-012** mantiene el aislamiento, pero muestra una llamada a la acción que abre una modal Bootstrap con campos completables visualmente.

El formulario sigue siendo solo una maqueta funcional de frontend: permite escribir en los campos, pero bloquea cualquier envío real.

## Componente modificado

El cambio principal está en `includes/rsvp_form_public.php`:

- Renderiza el título `Confirmar Asistencia`.
- Muestra un botón `Confirmar asistencia`.
- Abre la modal Bootstrap `rsvpFormPublicModal`.
- Incluye un formulario visual completables sin persistencia.
- Intercepta el submit con JavaScript local y `preventDefault()`.

## Cambios en `index.php`

No fue necesario modificar `index.php` para UNI-012. La compuerta pública de UNI-010/UNI-011 ya incluye `includes/rsvp_form_public.php` cuando el modo efectivo es `form` y conserva el flujo existente cuando el modo efectivo es `codigo`.

## Comportamiento con `rsvp_modo=codigo`

Con `rsvp_modo=codigo` no cambia el comportamiento visible:

- Se mantiene el buscador por código.
- Se conserva `/index.php#rsvp`.
- Se conserva `/index.php?busqueda=CODIGO#rsvp`.
- Se mantienen los botones `Confirmar` y `Modificar Asistencia`.
- Se mantiene la carga de `confirmacion_modal.php` en `confirmacionModal`.
- Se mantienen las consultas actuales sobre `invitados`, `invitados_listado_mesa` e `invitados_tel`.
- No cambia el procesamiento vigente.

## Comportamiento con `rsvp_modo=form`

Con `rsvp_modo=form` la sección RSVP:

- Muestra título/texto `Confirmar Asistencia`.
- Muestra el botón `Confirmar asistencia`.
- Abre la modal Bootstrap `rsvpFormPublicModal`.
- No abre `confirmacionModal`.
- No carga `confirmacion_modal.php`.
- No postea a `procesar_confirmacion.php`.
- No consulta `pre_invitados`, `pre_invitados_listado_mesa` ni `pre_invitados_tel`.
- No modifica datos.

## Campos de la modal

La modal visual incluye:

- Nombre.
- Apellido.
- Teléfono.
- Confirmo asistencia: Sí / No.
- Cantidad de mayores.
- Cantidad de menores.
- Restricción alimentaria.
- Comentario.

## Bloqueo del envío real

El formulario tiene `action=""`, pero el envío se bloquea con JavaScript local mediante `event.preventDefault()` en el evento `submit`. Al intentar completar/enviar, se muestra el mensaje controlado:

> El formulario todavía no está habilitado para guardar confirmaciones.

No hay `fetch`, no hay AJAX, no hay endpoint nuevo y no se invoca ningún script de confirmación.

## Por qué todavía no guarda datos

UNI-012 es una etapa visual de validación UX. La persistencia queda pendiente para un issue futuro porque todavía no se define el contrato final de escritura, validaciones, fuente de datos `pre_*`, compatibilidad con WhatsApp ni reglas administrativas asociadas.

## Qué NO se implementa todavía

UNI-012 no implementa:

- Persistencia del formulario.
- Confirmaciones reales.
- Creación o alteración de tablas.
- Migraciones.
- Endpoints nuevos.
- AJAX/fetch.
- Consultas a tablas `pre_*`.
- Cambios en `confirmacion_modal.php`.
- Cambios en `procesar_confirmacion.php`.
- Cambios en `confirmar_asistencia.php`.
- Cambios en admin activo.
- Cambios en tienda, regalos, WhatsApp o Node.
- Cambios en `admin_tmp`.

## Cómo probar con `tools/dqs_provider_config.php`

Ver configuración actual:

```bash
php tools/dqs_provider_config.php --show
```

Activar temporalmente la shell modal de formulario:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=form --apply
```

Validaciones esperadas con `form`:

- Home carga.
- La sección RSVP muestra el botón de confirmación.
- El botón abre `rsvpFormPublicModal`.
- Los campos son completables visualmente.
- El submit muestra el mensaje controlado y no envía datos.
- No se consultan tablas `pre_*`.
- No se abre `confirmacionModal`.
- No se postea a `procesar_confirmacion.php`.

## Cómo restaurar `rsvp_modo=codigo`

Después de probar, restaurar el modo por código:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=codigo --apply
```

Verificar nuevamente:

```bash
php tools/dqs_provider_config.php --show
```

La configuración esperada del entorno activo es:

- `plan_servicio: oro`
- `rsvp_modo: codigo`
- `fuente_envios_whatsapp: invitados`
- `whatsapp_enabled: 1`
- `regalos_enabled: 1`

## Confirmación de alcance

UNI-012 solo modifica la shell pública del formulario y su documentación. No toca `confirmacion_modal.php`, `procesar_confirmacion.php`, `confirmar_asistencia.php`, `admin7WZiwEM3XY/`, tienda, regalos, WhatsApp, Node ni `admin_tmp`.
