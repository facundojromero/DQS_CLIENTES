# DQS UNI-013 - RSVP formulario con acompañantes dinámicos (shell visual)

## Propósito

UNI-013 adapta la modal pública visual del modo `rsvp_modo=form` para acercarla al formulario real/anterior, incorporando datos del invitado principal, cantidad de acompañantes y campos dinámicos por acompañante.

El alcance sigue siendo únicamente visual y de front-end local: no guarda datos, no crea endpoints, no ejecuta AJAX/fetch y no consulta tablas `pre_*`.

## Diferencia entre UNI-012 y UNI-013

- **UNI-012** dejó preparada una modal Bootstrap pública para el modo formulario, con campos básicos y submit interceptado.
- **UNI-013** mantiene esa modal aislada y agrega la experiencia de acompañantes: selector de cantidad, render dinámico de bloques y campos por cada acompañante.

## Componente modificado

El cambio principal está en:

- `includes/rsvp_form_public.php`

No fue necesario modificar `index.php` para esta iteración. La compuerta pública existente sigue decidiendo si se muestra el flujo por código o el shell visual del formulario.

## Comportamiento con `rsvp_modo=codigo`

Con `rsvp_modo=codigo` se conserva el flujo activo actual:

- Se mantiene el buscador por código.
- Se mantienen `/index.php#rsvp` y `/index.php?busqueda=CODIGO#rsvp`.
- Se mantiene el botón actual de confirmar/modificar asistencia.
- Se mantiene la apertura de `confirmacion_modal.php`.
- Se mantienen las consultas actuales sobre `invitados`, `invitados_listado_mesa` e `invitados_tel`.
- No se consulta ninguna tabla `pre_*`.

## Comportamiento con `rsvp_modo=form`

Con `rsvp_modo=form`, la sección RSVP muestra una llamada a la acción con el botón **Confirmar asistencia**. Ese botón abre la modal Bootstrap con ID propio:

- `rsvpFormPublicModal`

La modal no reutiliza ni interfiere con:

- `confirmacionModal`

## Campos de la modal

### Invitado principal

La modal permite completar visualmente:

- Nombre.
- Apellido.
- Teléfono.
- Confirmo asistencia: Sí / No.
- Restricción alimentaria.
- Comentario/aclaración.

Las opciones de restricción alimentaria son:

- No.
- Vegetariano.
- Vegano.
- Celíaco.
- Otro.

### Cantidad de acompañantes

El campo **Cantidad de acompañantes** controla cuántos bloques se muestran en la modal:

- `0`: no se muestran bloques de acompañantes.
- `1`: se muestra **Acompañante 1**.
- `2`: se muestran **Acompañante 1** y **Acompañante 2**.
- Si el usuario aumenta o disminuye la cantidad, los bloques se recalculan sin recargar la página.

Para evitar entradas accidentales demasiado grandes en esta shell visual, el control limita localmente la cantidad entre `0` y `20`.

## Campos por acompañante

Por cada acompañante generado se muestran:

- Nombre.
- Apellido.
- Restricción alimentaria.
- Comentario/aclaración.

Cada bloque se identifica visualmente como **Acompañante N**.

## Confirmación en “No”

Si el invitado principal selecciona **No**, la UI deshabilita la cantidad de acompañantes y limpia los bloques visibles. Esto es solo comportamiento visual local; no guarda ni envía datos.

## Bloqueo del envío real

El formulario de la modal usa JavaScript local para interceptar el evento `submit` con `preventDefault()`.

Al intentar completar el formulario se muestra el mensaje controlado:

> El formulario todavía no está habilitado para guardar confirmaciones.

Este bloqueo garantiza que la shell visual:

- No postea a `procesar_confirmacion.php`.
- No abre `confirmacion_modal.php`.
- No hace `fetch`.
- No hace AJAX.
- No crea endpoints nuevos.
- No guarda en base.
- No confirma asistencias.

## Por qué todavía no guarda datos

UNI-013 solo prepara la experiencia visual de captura de datos. La persistencia requiere definir en un issue posterior el contrato de guardado, validaciones, fuente de datos y compatibilidad con instalaciones donde las tablas `pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel` pueden no existir.

La ausencia de tablas `pre_*` en la base activa es válida y no debe tratarse como error en UNI-013.

## Qué NO se implementa todavía

UNI-013 no implementa:

- Persistencia del formulario.
- Endpoint nuevo.
- AJAX/fetch.
- Consulta a `pre_invitados`.
- Consulta a `pre_invitados_listado_mesa`.
- Consulta a `pre_invitados_tel`.
- Creación o alteración de tablas.
- Migraciones.
- Inserción de datos.
- Confirmación real de asistencia.
- Cambios en WhatsApp, regalos, tienda, Node o admin activo.

## Cómo probar con `tools/dqs_provider_config.php`

### Ver configuración actual

```bash
php tools/dqs_provider_config.php --show
```

La configuración esperada para la base activa es:

```text
plan_servicio: oro
rsvp_modo: codigo
fuente_envios_whatsapp: invitados
whatsapp_enabled: 1
regalos_enabled: 1
```

### Probar temporalmente el modo formulario

```bash
php tools/dqs_provider_config.php --set rsvp_modo=form --apply
```

Validar manualmente:

- Home carga.
- La sección RSVP no rompe.
- Se muestra el botón **Confirmar asistencia**.
- El botón abre `rsvpFormPublicModal`.
- Se pueden completar los datos del invitado principal.
- Con `0` acompañantes no se muestran bloques.
- Con `1` acompañante se muestra un bloque.
- Con `3` acompañantes se muestran tres bloques.
- Al reducir la cantidad, los bloques se actualizan sin recargar.
- Cada acompañante tiene nombre, apellido, restricción alimentaria y comentario/aclaración.
- Al intentar enviar, no hay POST real y se muestra el mensaje controlado.
- No se consulta ni requiere `pre_invitados`.
- No se abre `confirmacion_modal.php`.
- No se postea a `procesar_confirmacion.php`.

## Cómo restaurar `rsvp_modo=codigo`

Después de la prueba temporal, restaurar el modo por código:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=codigo --apply
```

Volver a validar:

```bash
php tools/dqs_provider_config.php --show
```

Debe quedar nuevamente:

```text
rsvp_modo: codigo
```

## Confirmación de alcance

UNI-013 confirma que no toca:

- `confirmacion_modal.php`.
- `procesar_confirmacion.php`.
- `confirmar_asistencia.php`.
- `admin7WZiwEM3XY/`.
- `admin_tmp/`.
- Tienda.
- Regalos.
- WhatsApp activo.
- Node.
- Datos reales de invitados.
