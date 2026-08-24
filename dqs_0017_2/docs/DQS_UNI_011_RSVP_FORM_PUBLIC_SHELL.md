# DQS UNI-011 - Estructura pública aislada para RSVP formulario

## Propósito

UNI-011 prepara una estructura pública aislada para el modo `rsvp_modo=form` sin activar persistencia ni modificar el flujo vigente por código. El objetivo es que la home pueda renderizar una interfaz controlada de RSVP formulario cuando el proveedor seleccione temporalmente ese modo, aun si la base activa no tiene tablas `pre_`.

La configuración productiva esperada sigue siendo:

```text
plan_servicio: oro
rsvp_modo: codigo
fuente_envios_whatsapp: invitados
whatsapp_enabled: 1
regalos_enabled: 1
```

## Componente creado

Se creó `includes/rsvp_form_public.php` como componente seguro de incluir desde el front público.

El componente:

- No abre conexión por sí solo.
- No escribe en base de datos.
- No consulta `pre_invitados`, `pre_invitados_listado_mesa` ni `pre_invitados_tel`.
- No depende de que existan tablas `pre_`.
- No postea a `procesar_confirmacion.php`.
- Renderiza una estructura visual de formulario bloqueada.

## Cambio en `index.php`

`index.php` ya resolvía el modo efectivo mediante `includes/rsvp_mode.php`. UNI-011 reemplaza el mensaje simple del branch `rsvp_modo=form` por la inclusión de `includes/rsvp_form_public.php`.

El branch `rsvp_modo=codigo` conserva intactos el buscador, las queries actuales sobre `invitados` e `invitados_listado_mesa`, los textos visibles, los links con `?busqueda=CODIGO#rsvp` y la apertura del modal existente.

## Comportamiento con `rsvp_modo=codigo`

Con `rsvp_modo=codigo`:

- Se mantiene el buscador por código en `/index.php#rsvp`.
- Se mantiene `/index.php?busqueda=CODIGO#rsvp`.
- Se mantienen los botones “Confirmar” y “Modificar Asistencia”.
- Se mantiene la apertura de `confirmacion_modal.php`.
- Se mantienen las queries actuales del flujo por código.
- No cambia `procesar_confirmacion.php` ni el procesamiento real de asistencias.

## Comportamiento con `rsvp_modo=form`

Con `rsvp_modo=form`:

- Se muestra la estructura pública del formulario.
- Se informa claramente: “La confirmación de asistencia por formulario todavía no está habilitada para este evento.”
- El botón aparece deshabilitado con el texto “Formulario en preparación”.
- El formulario no envía datos ni tiene endpoint real de persistencia.
- No se consulta ni requiere ninguna tabla `pre_`.
- No se abre el modal actual de `confirmacion_modal.php` desde la sección RSVP.
- No se confirma asistencia ni se modifican datos.

## Campos visuales del formulario

La estructura visual incluye:

- Nombre.
- Apellido.
- Teléfono.
- Confirmo asistencia: Sí / No.
- Cantidad de mayores.
- Cantidad de menores.
- Restricción alimentaria.
- Comentario.

Todos los campos están deshabilitados porque UNI-011 es solo una shell pública sin guardado.

## Por qué todavía no guarda datos

UNI-011 separa la presentación pública del futuro flujo de persistencia para evitar mezclar fuentes y tablas. La base activa usa RSVP por código con `invitados`, `invitados_listado_mesa` e `invitados_tel`; además, puede no tener `pre_invitados`, `pre_invitados_listado_mesa` ni `pre_invitados_tel`. Por eso esta etapa no crea endpoints, no altera tablas, no inserta registros y no confirma asistencias.

## Qué no se implementa todavía

UNI-011 no implementa:

- Persistencia del formulario.
- Endpoint público para guardar confirmaciones.
- Consultas a tablas `pre_`.
- Creación o alteración de tablas.
- Migraciones.
- Inserción de datos.
- Cambios en invitados reales.
- Cambios en WhatsApp, regalos, tienda, Node o admin.

## Cómo probar con `tools/dqs_provider_config.php`

Validar la configuración actual:

```bash
php tools/dqs_provider_config.php --show
```

Activar temporalmente el modo formulario para prueba manual:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=form --apply
```

Validar manualmente:

- La home carga.
- La sección `#rsvp` muestra la shell pública del formulario.
- El mensaje indica que todavía no guarda datos.
- El botón está deshabilitado.
- No se muestran errores SQL por tablas `pre_` ausentes.
- No se abre `confirmacion_modal.php` desde la sección RSVP.
- No se confirma asistencia.

## Cómo restaurar `rsvp_modo=codigo`

Después de la prueba temporal, restaurar:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=codigo --apply
```

Volver a validar:

```bash
php tools/dqs_provider_config.php --show
```

El valor debe quedar nuevamente en `rsvp_modo: codigo`.

## Archivos y áreas fuera de alcance

UNI-011 confirma que no toca:

- `confirmacion_modal.php`.
- `procesar_confirmacion.php`.
- `confirmar_asistencia.php`.
- `admin7WZiwEM3XY/`.
- `admin_tmp/`.
- Tienda.
- Regalos.
- WhatsApp activo.
- Node.
