# DQS UNI-016 - Modal RSVP formulario conectada al dry-run

## Propósito

UNI-016 conecta la modal pública del RSVP por formulario con el endpoint `rsvp_form_validate.php` creado en UNI-015, manteniendo el flujo como **dry-run**: valida el payload y muestra la respuesta dentro de la modal, pero no guarda datos reales.

## Diferencia entre UNI-015 y UNI-016

- **UNI-015** creó el contrato de endpoint dry-run (`rsvp_form_validate.php`) y el helper `includes/rsvp_form_dry_run.php` para validar payloads sin abrir DB ni persistir.
- **UNI-016** usa ese endpoint desde la modal pública ya existente en `includes/rsvp_form_public.php`, mediante `fetch` y `FormData`.

## Archivo modificado

- `includes/rsvp_form_public.php`: mantiene el HTML de la modal pública y reemplaza el submit visual anterior por una llamada `POST` dry-run a `rsvp_form_validate.php`.

No se modifica `index.php`, `confirmacion_modal.php`, `procesar_confirmacion.php`, `confirmar_asistencia.php`, admin activo, tienda, regalos, WhatsApp, Node ni `admin_tmp`.

## Conexión de la modal al endpoint dry-run

El formulario conserva `event.preventDefault()` y arma `new FormData(form)` para preservar los nombres actuales, incluyendo acompañantes dinámicos con formato:

- `acompanantes[1][nombre]`
- `acompanantes[1][apellido]`
- `acompanantes[1][restriccion_alimentaria]`
- `acompanantes[1][comentario]`

El `fetch` usa:

- `method: 'POST'`
- `body: new FormData(form)`
- sin setear manualmente `Content-Type`, para que el navegador gestione el boundary multipart.

## Endpoint usado

La modal postea a:

```text
rsvp_form_validate.php
```

El endpoint sigue devolviendo respuestas con:

- `dry_run = true`
- `persisted = false`
- mensajes que indican que no se guardaron datos.

## Comportamiento con `rsvp_modo=codigo`

No cambia el flujo visible actual:

- Se mantiene el buscador por código.
- Se mantiene `/index.php#rsvp`.
- Se mantiene `/index.php?busqueda=CODIGO#rsvp`.
- Se mantiene el botón de confirmar/modificar asistencia del flujo por código.
- Se mantiene la apertura de `confirmacion_modal.php`.
- Se mantienen las queries actuales y el procesamiento existente.

UNI-016 no toca ese camino.

## Comportamiento con `rsvp_modo=form`

Cuando el selector público renderiza `includes/rsvp_form_public.php`:

- Se muestra la modal visual del RSVP formulario.
- Se mantienen invitado principal, confirmación Sí/No, restricción alimentaria, comentario y acompañantes dinámicos.
- El submit hace `POST` a `rsvp_form_validate.php`.
- La respuesta JSON se muestra dentro de la modal.
- No se postea a `procesar_confirmacion.php`.
- No se abre `confirmacion_modal.php`.
- No se recarga la página.
- No se guardan datos.
- No se consulta DB ni tablas `pre_*` o `invitados*`.

## Respuestas válidas HTTP 200

Si el endpoint responde válido, la modal muestra:

- `Payload válido. Dry-run: no se guardaron datos.`
- Confirmación.
- Total de personas.
- Total de acompañantes.
- `Persisted: false. Esta prueba no guardó datos reales.`
- Warnings si existen.

## Errores HTTP 422

Si el endpoint responde inválido, la modal muestra:

- `Payload inválido. Dry-run: no se guardaron datos.`
- `Persisted: false. Esta prueba no guardó datos reales.`
- Errores devueltos por el endpoint.
- Warnings si existen.

No limpia campos y no cierra la modal.

## Warnings

Los warnings se renderizan como lista dentro de la respuesta, tanto en payload válido como inválido, si el endpoint los devuelve.

## JSON inválido o error inesperado

Si la respuesta no puede parsearse como JSON o no tiene una forma esperada, la modal muestra:

```text
No se pudo validar el formulario en modo prueba. No se guardaron datos.
```

No se guarda nada y la modal permanece abierta.

## Error de red

Si falla la conexión con el endpoint, la modal muestra:

```text
No se pudo conectar con la validación dry-run. No se guardaron datos.
```

En DEV con Basic Auth, la navegación same-origin debería funcionar; un `curl` sin credenciales puede devolver 401 y eso no implica un error de UNI-016.

## Por qué todavía no guarda datos

UNI-016 solo conecta la UI pública al endpoint dry-run. El endpoint usa el contrato de validación y declara explícitamente `dry_run=true` y `persisted=false`. No se incluye `conexion.php`, no se abre conexión a DB, no se crean o alteran tablas, no se insertan datos y no se confirman asistencias reales.

## Cómo probar con CLI

Validar payloads simulados:

```bash
php tools/dqs_rsvp_form_endpoint_probe.php --sample=valid
php tools/dqs_rsvp_form_endpoint_probe.php --sample=invalid
php tools/dqs_rsvp_form_endpoint_probe.php --sample=no
php tools/dqs_rsvp_form_endpoint_probe.php --sample=companions
```

Validar configuración activa:

```bash
php tools/dqs_provider_config.php --show
```

La configuración esperada al finalizar es:

- `plan_servicio: oro`
- `rsvp_modo: codigo`
- `fuente_envios_whatsapp: invitados`
- `whatsapp_enabled: 1`
- `regalos_enabled: 1`

## Cómo probar en Network del navegador

1. Cambiar temporalmente a formulario:

   ```bash
   php tools/dqs_provider_config.php --set rsvp_modo=form --apply
   ```

2. Abrir la home y la modal de RSVP formulario.
3. Enviar un payload válido y verificar en Network un `POST` a `rsvp_form_validate.php`.
4. Confirmar que no hay `POST` a `procesar_confirmacion.php`.
5. Confirmar que no se abre `confirmacion_modal.php`.
6. Enviar un payload inválido y verificar HTTP 422 con errores dentro de la modal.
7. Probar confirmación `No` y verificar respuesta dry-run válida con `total_personas` en `0`.
8. Confirmar que no aparecen errores SQL ni mensajes de confirmación guardada.

## Restaurar `rsvp_modo=codigo`

Después de las pruebas temporales:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=codigo --apply
```

Volver a verificar:

```bash
php tools/dqs_provider_config.php --show
```

## Confirmación de alcance

UNI-016 no toca:

- `index.php`
- `confirmacion_modal.php`
- `procesar_confirmacion.php`
- `confirmar_asistencia.php`
- `admin7WZiwEM3XY/`
- tienda
- regalos
- WhatsApp activo
- Node
- `admin_tmp`

Tampoco crea tablas, altera tablas, ejecuta migraciones, inserta datos, consulta invitados reales ni consulta tablas `pre_*`.
