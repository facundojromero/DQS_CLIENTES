# DQS - Mapa funcional por módulos

## Admin

- **Descripción funcional:** backoffice para gestionar datos del cliente, contenidos del sitio, invitados, invitaciones, regalos vendidos, dashboard y accesos.
- **Archivos involucrados:** `admin7WZiwEM3XY/index.php`, `dashboard_casamiento.php`, `menu.php`, `datos.php`, `datos_modificar.php`, `info_*.php`, `modificar_portada.php`, `invitados*.php`, `nuevo_invitado.php`, `editar_invitado.php`, `lista_regalos.php`, `productos_vendidos.php`.
- **Rutas/parámetros:** carpeta admin fija; varios endpoints reciben `id`, `codigo`, acciones POST y formularios multipart para imágenes.
- **Tablas utilizadas:** `user`, `cliente`, `admin_config`, `info_*`, `invitados*`, `productos`, `imagenes`, `regalos*`, `visitas*`.
- **Campos importantes:** `user.email/password`, `cliente.*`, `info_mostrar.activo`, `invitados.codigo/confirmacion/activo`, `regalos.activo`, `regalos_confirmacion.regalo_id`.
- **Flujo principal:** login → dashboard/menu → edición de módulos → persistencia directa en MySQL → front consume los cambios.
- **Observaciones:** no hay routing centralizado; cada archivo combina lógica, SQL y HTML. Se recomienda estandarizar sesiones/autorización antes de ampliar funcionalidades.

## Front público

- **Descripción funcional:** web del casamiento/evento con portada, secciones informativas, eventos, historia, fotos y accesos a RSVP/regalos.
- **Archivos involucrados:** `index.php`, `header.php`, `footer.php`, `confirmacion_modal.php`, `invitacion.php`, CSS/JS en `css/` y `js/`.
- **Rutas/parámetros:** `/index.php`; enlaces internos por anclas; confirmación por `codigo`.
- **Tablas utilizadas:** `info_casamiento`, `info_eventos`, `info_historia`, `info_nosotros`, `info_otra`, `info_mostrar`, `cliente`, `invitados*` en componentes de confirmación.
- **Campos importantes:** `portada_titulo`, `portada_frase`, `portada_fecha`, `portada_fecha_hora`, `activo`, `titulo`, `descripcion`, `url`, `imagen`.
- **Flujo principal:** carga conexión → consulta configuración/secciones activas → renderiza HTML con assets.
- **Observaciones:** se detecta dependencia fuerte de datos globales sin scoping explícito por cliente.

## Invitados

- **Descripción funcional:** administración de grupos/personas invitadas, acompañantes, menores, teléfonos, prioridad y estado activo.
- **Archivos involucrados:** `admin7WZiwEM3XY/invitados.php`, `invitados_basico.php`, `invitados_invitaciones.php`, `nuevo_invitado.php`, `editar_invitado.php`, `cargar_formulario_edicion.php`, `exportar_invitados.php`.
- **Rutas/parámetros:** acciones POST de alta/edición; parámetros `id`; exportación CSV/Excel desde admin.
- **Tablas utilizadas:** `invitados`, `invitados_listado_mesa`, `invitados_tel`, `intivados_acompanante`, `invitados_prioridad`, `invitados_enviados`, `invitaciones_estado`.
- **Campos importantes:** `codigo`, `nombre`, `apellido`, `acompanado`, `cantidad_mayores`, `cantidad_menores`, `id_prioridad`, `activo`, `tel_enviar`, `es_menor`.
- **Flujo principal:** crear invitado → insertar cabecera en `invitados` → insertar personas en `invitados_listado_mesa` → insertar teléfonos en `invitados_tel` → administrar envíos/confirmaciones.
- **Observaciones:** nombre de tabla `intivados_acompanante` parece tener typo histórico. La confirmación existe tanto a nivel cabecera como detalle.

## Confirmación de asistencia

- **Descripción funcional:** RSVP por código, con confirmación individual de asistentes, conteo de mayores/menores y restricciones alimentarias.
- **Archivos involucrados:** `confirmar_asistencia.php`, `procesar_confirmacion.php`, `confirmacion_modal.php`, endpoints/admin para deshacer confirmación.
- **Rutas/parámetros:** `confirmar_asistencia.php?codigo=...`; POST a `procesar_confirmacion.php`.
- **Tablas utilizadas:** `invitados`, `invitados_listado_mesa`, `intivados_acompanante`, `invitados_prioridad`.
- **Campos importantes:** `invitados.codigo`, `confirmacion`, `confirmacion_fecha`, `confirmacion_mayores`, `confirmacion_menores`, `confirmacion_comentario`, `alimento`; en detalle `asiste`, `confirm_date`, `alimento`, `alimento_comentario`, `es_menor`.
- **Flujo principal:** validar código → mostrar integrantes → usuario selecciona asistentes/alimento → actualizar detalle → recalcular totales y actualizar cabecera.
- **Observaciones:** las migraciones disponibles corresponden a esta evolución. Hay que cuidar consistencia entre detalle y cabecera.

## Regalos

- **Descripción funcional:** registro de regalos comprados/contribuidos por invitados y administración posterior.
- **Archivos involucrados:** `tienda/procesar_compra.php`, `tienda/compra_exitosa.php`, `admin7WZiwEM3XY/productos_vendidos.php`, `confirmar_pago.php`, `cancelar_regalo.php`, `deshacer_*`.
- **Rutas/parámetros:** checkout POST; `compra_exitosa.php?id=...`; acciones admin con `regalo_id`.
- **Tablas utilizadas:** `regalos`, `regalos_detalles`, `regalos_confirmacion`, `productos`, `imagenes`, `cliente`.
- **Campos importantes:** `regalos.nombre/apellido/email/telefono`, `forma_pago`, `monto_total`, `productos`, `compartido`, `mensaje`, `activo`, `pago_con`; `regalos_detalles.subtotal`.
- **Flujo principal:** carrito → checkout → insertar regalo → insertar detalles → vaciar carrito → mostrar datos de pago/confirmación.
- **Observaciones:** confirmación/cancelación es manual; no hay evidencia de gateway de pago automático.

## Datos bancarios

- **Descripción funcional:** muestra datos para transferencia en pesos/dólares y cotización usada por regalos.
- **Archivos involucrados:** `admin7WZiwEM3XY/datos.php`, `datos_modificar.php`, `tienda/finalizar_compra.php`, `tienda/procesar_compra.php`, `tienda/index.php`, `tienda/carrito.php`, `tienda/mostrar_productos.php`.
- **Rutas/parámetros:** formularios admin y pantallas de checkout.
- **Tablas utilizadas:** `cliente`.
- **Campos importantes:** `cbu_titular`, `cbu`, `alias`, `cbu_dolar`, `alias_dolar`, `cotizacion_dolar`, `telefono`, `telefono2`.
- **Flujo principal:** admin actualiza cliente → tienda lee cotización/datos → checkout presenta instrucciones.
- **Observaciones:** varias consultas usan `user_id=1`, lo que limita multi-cliente.

## Lista de regalos

- **Descripción funcional:** catálogo de productos/regalos con imágenes, precios y regalo libre.
- **Archivos involucrados:** `admin7WZiwEM3XY/lista_regalos.php`, `tienda/index.php`, `tienda/mostrar_productos.php`, `tienda/paginacion.php`, `tienda/regalo_libre_helper.php`.
- **Rutas/parámetros:** filtros/paginación por AJAX; acciones admin de productos.
- **Tablas utilizadas:** `productos`, `imagenes`, `site_settings`, `carrito`.
- **Campos importantes:** `productos.titulo`, `descripcion`, `precio`, `activo`; `imagenes.url`; `site_settings.setting_key/setting_value`.
- **Flujo principal:** admin mantiene productos → front lista activos → usuario agrega al carrito.
- **Observaciones:** `regalo_libre_helper.php` contiene creación/aseguramiento de tabla/configuración, distinto del resto de migraciones.

## Pagos o comprobantes

- **Descripción funcional:** pago se modela como forma de pago y confirmación manual; no se detectaron comprobantes adjuntos.
- **Archivos involucrados:** `tienda/procesar_compra.php`, `tienda/compra_exitosa.php`, `admin7WZiwEM3XY/confirmar_pago.php`, `deshacer_confirmacion.php`, `productos_vendidos.php`.
- **Rutas/parámetros:** `regalo_id`; POST de checkout.
- **Tablas utilizadas:** `regalos`, `regalos_confirmacion`.
- **Campos importantes:** `forma_pago`, `pago_con`, `monto_total`, `regalo_id`.
- **Flujo principal:** regalo queda registrado → admin confirma pago insertando en `regalos_confirmacion` → reportes/totales lo consideran confirmado.
- **Observaciones:** crear issue específico si se necesita comprobante, conciliación o gateway.

## Fotos

- **Descripción funcional:** galería e imágenes de secciones/eventos/logo.
- **Archivos involucrados:** `admin7WZiwEM3XY/info_fotos.php`, `info_imagenes.php`, `info_logo.php`, `info_eventos.php`, `index.php`, carpetas `images/gallery`, `images/events`, `images/logo`.
- **Rutas/parámetros:** formularios multipart admin; front lee rutas de imagen.
- **Tablas utilizadas:** `info_eventos` para imágenes de eventos; posiblemente archivos directos para galería/logo.
- **Campos importantes:** `imagen`, `tipo_visual`, `activo`.
- **Flujo principal:** subir/guardar imagen → guardar ruta o archivo → front renderiza.
- **Observaciones:** conviene inventariar política de nombres/limpieza en un issue futuro.

## Portada

- **Descripción funcional:** título, frase, fecha y hora principal del casamiento/evento.
- **Archivos involucrados:** `admin7WZiwEM3XY/info_casamiento.php`, `info_cronometro.php`, `modificar_portada.php`, `index.php`, `tienda/index.php`, `tienda/finalizar_compra.php`.
- **Rutas/parámetros:** edición admin; render público.
- **Tablas utilizadas:** `info_casamiento`.
- **Campos importantes:** `portada_titulo`, `portada_frase`, `portada_fecha`, `portada_fecha_hora`.
- **Flujo principal:** admin edita → front/tienda consultan → renderizan portada/fecha.
- **Observaciones:** tabla aparentemente singleton.

## Eventos

- **Descripción funcional:** agenda/lugares del evento con dirección, fecha, icono/imagen y URL.
- **Archivos involucrados:** `admin7WZiwEM3XY/info_eventos.php`, `modificar_portada.php`, `index.php`, `tienda/index.php`, `tienda/finalizar_compra.php`.
- **Rutas/parámetros:** CRUD admin; se filtra por `activo=1` en front.
- **Tablas utilizadas:** `info_eventos`.
- **Campos importantes:** `titulo`, `descripcion`, `direccion`, `fecha`, `url`, `icono`, `imagen`, `tipo_visual`, `activo`.
- **Flujo principal:** admin carga eventos → front lista activos.
- **Observaciones:** eventos se reutilizan en páginas de tienda/checkout.

## Historia / Nosotros

- **Descripción funcional:** secciones narrativas sobre la pareja/evento.
- **Archivos involucrados:** `admin7WZiwEM3XY/info_historia.php`, `info_nosotros.php`, `index.php`, `tienda/index.php`.
- **Rutas/parámetros:** edición admin.
- **Tablas utilizadas:** `info_historia`, `info_nosotros`.
- **Campos importantes:** `titulo`, `texto`, `fecha`, `nombre`, `activo`.
- **Flujo principal:** admin edita registros → front muestra activos.
- **Observaciones:** separar textos por cliente si se evoluciona a multi-evento.

## Información del casamiento

- **Descripción funcional:** información complementaria, lugares, links y datos generales.
- **Archivos involucrados:** `admin7WZiwEM3XY/info_otra.php`, `info_casamiento.php`, `index.php`, `tienda/index.php`.
- **Rutas/parámetros:** edición admin; links externos.
- **Tablas utilizadas:** `info_otra`, `info_casamiento`.
- **Campos importantes:** `titulo`, `descripcion`, `direccion`, `url`, `icono`, `activo`.
- **Flujo principal:** admin carga items → front muestra activos.
- **Observaciones:** `info_otra` parece flexible para contenido adicional.

## Configuración del cliente

- **Descripción funcional:** datos del cliente/admin, banco, contacto, carpeta admin y usuario.
- **Archivos involucrados:** `admin7WZiwEM3XY/datos.php`, `datos_modificar.php`, `registrar_usuario.php`, `crear_carpeta_admin.php`, `conexion.php`.
- **Rutas/parámetros:** formularios admin.
- **Tablas utilizadas:** `cliente`, `user`, `admin_config`.
- **Campos importantes:** `cliente.user_id`, `nombre`, `apellido`, `telefono`, `telefono2`, `direccion`, `ciudad`, `provincia`, `cbu*`, `alias*`, `cotizacion_dolar`; `admin_config.nombre_carpeta`.
- **Flujo principal:** usuario/cliente se registra o edita → datos usados por tienda, emails y WhatsApp.
- **Observaciones:** la aplicación actual no aplica consistentemente `user_id` en todas las consultas.

## Emails

- **Descripción funcional:** envío de correos de contacto, compra/regalo y notificaciones al vendedor.
- **Archivos involucrados:** `php/form-process.php`, `admin7WZiwEM3XY/enviar_correo.php`, `tienda/enviar_correo.php`, `tienda/enviar_correo_vendedor.php`, `tienda/test_mail.php`.
- **Rutas/parámetros:** POST desde formularios y checkout.
- **Tablas utilizadas:** `cliente`, `user`, `admin_config`, `regalos` según script.
- **Campos importantes:** emails de usuario/cliente, datos de regalo y productos.
- **Flujo principal:** acción de usuario/admin → script arma HTML → envío por mail/PHPMailer o endpoint.
- **Observaciones:** revisar configuración SMTP/secrets fuera del repo.

## WhatsApp

- **Descripción funcional:** envío y reenvío de invitaciones por WhatsApp/API y herramientas locales Node.
- **Archivos involucrados:** `admin7WZiwEM3XY/gestionar_envios.php`, `admin7WZiwEM3XY/whatsapp/envio_invitaciones.php`, `reenvio_invitaciones_erroneas.php`, `dqs reenvio invitaciones/*`, `dqs envios invitaciones v202512/*`.
- **Rutas/parámetros:** acciones admin; scripts Node con CSV/sesiones; config externa.
- **Tablas utilizadas:** `invitados`, `invitados_tel`, `invitados_a_enviar`, `invitados_enviados`, `invitaciones_estado`, `registro_mensajes_enviados`, `cliente`.
- **Campos importantes:** `tel_enviar`, `fecha_envio`, `estado_api`, `detalle_api`, teléfonos del cliente.
- **Flujo principal:** seleccionar/agregar invitados a cola → enviar → registrar estado → reintentar fallidos.
- **Observaciones:** hay dos enfoques de envío; conviene consolidar y documentar operación.

## Seguridad / login / accesos

- **Descripción funcional:** autenticación admin mediante usuario/password y sesiones PHP; protección adicional por `.htpasswd`.
- **Archivos involucrados:** `admin7WZiwEM3XY/login.php`, `logout.php`, `cambiar_password.php`, `registrar_usuario.php`, `.htaccess`, `.htpasswd`, `conexion.php`.
- **Rutas/parámetros:** POST login/cambio password.
- **Tablas utilizadas:** `user`, `cliente`.
- **Campos importantes:** `email`, `password`.
- **Flujo principal:** login valida usuario → setea sesión → páginas admin deberían verificar sesión.
- **Observaciones:** auditar hash de passwords, protección de endpoints y eliminación de secretos hardcodeados.
