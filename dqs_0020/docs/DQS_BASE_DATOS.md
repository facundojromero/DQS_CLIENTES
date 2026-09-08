# DQS - Documentación de base de datos

## Fuentes y limitaciones

No se encontró un dump completo ni migraciones iniciales con `CREATE TABLE` para todo el modelo. La fuente estructural explícita disponible son estas migraciones:

- `2026-02-21_invitados_menores_asistencia.sql`: agrega `es_menor`, `asiste`, `confirm_date` a `invitados_listado_mesa`.
- `2026-02-22_invitados_alimento_por_persona.sql`: agrega `alimento`, `alimento_comentario` a `invitados_listado_mesa`.

El resto fue reconstruido por análisis estático de consultas SQL. Por eso, salvo donde se indica lo contrario, los tipos, claves e índices son inferidos o desconocidos.

## Relaciones principales detectadas

- `cliente.user_id` → `user.id`.
- `invitados_listado_mesa.id_invitados` → `invitados.id`.
- `invitados_tel.id_invitados` → `invitados.id`.
- `invitados.acompanado` → `intivados_acompanante.id`.
- `invitados.id_prioridad` → `invitados_prioridad.id`.
- `invitados_a_enviar.id_invitados` / `invitados_enviados.id_invitados` / `invitaciones_estado.id_invitado` → `invitados.id`.
- `invitados_a_enviar.id_invitados_tel` / `invitados_enviados.id_invitados_tel` / `invitaciones_estado.id_invitados_tel` → `invitados_tel.id`.
- `carrito.producto_id` → `productos.id`.
- `imagenes.producto_id` → `productos.id`.
- `regalos_detalles.regalo_id` → `regalos.id`.
- `regalos_detalles.producto_id` → `productos.id`.
- `regalos_confirmacion.regalo_id` → `regalos.id`.

## Tablas

### `user`

- **Uso:** credenciales/admin y relación con cliente.
- **Columnas detectadas:** `id` (inferida), `email`, `password`.
- **Tipos:** desconocidos; `password` debería ser hash.
- **PK:** `id` inferida.
- **Índices:** desconocidos; recomendable unique en `email`.
- **Archivos:** `admin7WZiwEM3XY/login.php`, `registrar_usuario.php`, `cambiar_password.php`, `datos.php`, `productos_vendidos.php`, `php/form-process.php`, scripts de tienda.
- **Módulos:** seguridad/login, configuración cliente, emails, regalos.

### `cliente`

- **Uso:** datos del cliente/evento, contacto, banco y cotización dólar.
- **Columnas detectadas:** `user_id`, `nombre`, `apellido`, `telefono`, `telefono2`, `direccion`, `ciudad`, `provincia`, `cbu_titular`, `cbu`, `alias`, `cbu_dolar`, `alias_dolar`, `cotizacion_dolar`.
- **Tipos:** inferidos: textos para datos personales/bancarios; numérico/decimal para `cotizacion_dolar`.
- **PK:** no detectada; posiblemente `user_id` o `id` no usado.
- **Índices:** recomendable índice/FK en `user_id`.
- **Archivos:** `admin7WZiwEM3XY/datos.php`, `datos_modificar.php`, `index.php`, WhatsApp PHP, `php/form-process.php`, `tienda/*`.
- **Módulos:** configuración cliente, datos bancarios, tienda, emails, WhatsApp.
- **Observación:** múltiples consultas usan `user_id=1`, limitando multi-cliente.

### `admin_config`

- **Uso:** nombre de carpeta admin generada/activa.
- **Columnas detectadas:** `nombre_carpeta`, `fecha_creacion`.
- **Tipos:** texto y fecha/timestamp inferidos.
- **PK/índices:** desconocidos; se consulta `MAX(fecha_creacion)`.
- **Archivos:** `crear_carpeta_admin.php`, `registrar_usuario.php`, `productos_vendidos.php`, `tienda/procesar_compra.php`, `tienda/compra_exitosa.php`, `tienda/enviar_correo_vendedor.php`.
- **Módulos:** configuración cliente, emails, tienda.

### `info_casamiento`

- **Uso:** portada, frase, fecha/hora y datos generales del evento.
- **Columnas detectadas:** `portada_titulo`, `portada_frase`, `portada_fecha`, `portada_fecha_hora`.
- **Tipos:** textos/fecha/datetime inferidos.
- **PK/índices:** desconocidos; parece tabla singleton.
- **Archivos:** `admin7WZiwEM3XY/info_casamiento.php`, `info_cronometro.php`, `modificar_portada.php`, `index.php`, `tienda/index.php`, `tienda/finalizar_compra.php`, `tienda/procesar_compra.php`.
- **Módulos:** portada, front público, tienda.

### `info_eventos`

- **Uso:** agenda/lugares/secciones de eventos.
- **Columnas detectadas:** `id` inferida, `titulo`, `descripcion`, `direccion`, `fecha`, `url`, `icono`, `imagen`, `tipo_visual`, `activo`.
- **Tipos:** textos; `fecha` fecha/datetime; `activo` boolean/tinyint inferido.
- **PK:** `id` inferida.
- **Índices:** recomendable índice en `activo`.
- **Archivos:** `admin7WZiwEM3XY/info_eventos.php`, `modificar_portada.php`, `index.php`, `tienda/index.php`, `tienda/finalizar_compra.php`.
- **Módulos:** eventos, front público, tienda.

### `info_historia`

- **Uso:** línea/sección historia.
- **Columnas detectadas:** `id` inferida, `titulo`, `texto`, `fecha`, `activo`.
- **Tipos:** textos; `fecha`; `activo` boolean/tinyint inferido.
- **PK:** `id` inferida.
- **Archivos:** `admin7WZiwEM3XY/info_historia.php`, `index.php`, `tienda/index.php`.
- **Módulos:** historia/front.

### `info_nosotros`

- **Uso:** textos de integrantes/pareja.
- **Columnas detectadas:** `id` inferida, `nombre`, `texto`, `activo`.
- **Tipos:** textos; `activo` boolean/tinyint inferido.
- **PK:** `id` inferida.
- **Archivos:** `admin7WZiwEM3XY/info_nosotros.php`, `index.php`, `tienda/index.php`.
- **Módulos:** nosotros/front.

### `info_otra`

- **Uso:** información adicional del casamiento.
- **Columnas detectadas:** `id` inferida, `titulo`, `descripcion`, `direccion`, `url`, `icono`, `activo`.
- **Tipos:** textos; `activo` boolean/tinyint inferido.
- **PK:** `id` inferida.
- **Archivos:** `admin7WZiwEM3XY/info_otra.php`, `modificar_portada.php`, `index.php`, `tienda/index.php`.
- **Módulos:** información adicional/front.

### `info_mostrar`

- **Uso:** control de visibilidad de módulos/secciones en menú/header.
- **Columnas detectadas:** `activo`; probablemente identificador/nombre de sección no capturado.
- **Tipos:** `activo` boolean/tinyint inferido.
- **PK/índices:** desconocidos.
- **Archivos:** `admin7WZiwEM3XY/menu.php`, `header.php`.
- **Módulos:** configuración de visualización.
- **Observación:** requiere inspección con schema real para conocer claves.

### `invitados`

- **Uso:** entidad cabecera de invitado/grupo familiar.
- **Columnas detectadas:** `id`, `codigo`, `nombre`, `apellido`, `acompanado`, `cantidad_mayores`, `cantidad_menores`, `id_prioridad`, `confirmacion`, `confirmacion_fecha`, `confirmacion_mayores`, `confirmacion_menores`, `confirmacion_comentario`, `alimento`, `fecha_registro`, `ingreso`, `activo`.
- **Tipos:** `id` int; conteos int; `activo` tinyint; fechas timestamp/date; textos para el resto inferidos.
- **PK:** `id` inferida.
- **Índices:** recomendable unique en `codigo`, índices en `activo`, `id_prioridad`, `acompanado`.
- **Archivos:** RSVP, todos los módulos admin de invitados, WhatsApp, exportación, dashboard.
- **Módulos:** invitados, confirmación, WhatsApp, dashboard.

### `invitados_listado_mesa`

- **Uso:** integrantes/personas dentro de cada invitado/grupo y su confirmación individual.
- **Columnas detectadas:** `id` inferida, `id_invitados`, `nombre_invitado`, `nombre2`, `apellido2`, `es_menor`, `asiste`, `confirm_date`, `alimento`, `alimento_comentario`.
- **Tipos disponibles:** por migraciones: `es_menor TINYINT(1) NOT NULL DEFAULT 0`, `asiste TINYINT(1) NULL DEFAULT NULL`, `confirm_date TIMESTAMP NULL DEFAULT NULL`, `alimento VARCHAR(30) NOT NULL DEFAULT 'No'`, `alimento_comentario VARCHAR(255) NULL DEFAULT NULL`.
- **PK:** `id` inferida.
- **Índices:** recomendable índice en `id_invitados` y `asiste`.
- **Relaciones:** `id_invitados` → `invitados.id`.
- **Archivos:** `procesar_confirmacion.php`, `confirmar_asistencia.php`, módulos admin de invitados, WhatsApp, exportación, dashboard.
- **Módulos:** confirmación de asistencia, invitados, alimentación, reportes.

### `invitados_tel`

- **Uso:** teléfonos asociados a invitados para WhatsApp.
- **Columnas detectadas:** `id`, `id_invitados`, `tel_enviar`.
- **Tipos:** enteros para ids; texto para teléfono.
- **PK:** `id` inferida.
- **Índices:** recomendable índice en `id_invitados` y quizá unique compuesto (`id_invitados`, `tel_enviar`).
- **Archivos:** admin invitados, `gestionar_envios.php`, WhatsApp PHP, exportación.
- **Módulos:** invitados, WhatsApp.

### `intivados_acompanante`

- **Uso:** catálogo de tipo/categoría de acompañante.
- **Columnas detectadas:** `id`, `categoria_acompanante`.
- **Tipos:** `id` int; texto.
- **PK:** `id` inferida.
- **Archivos:** admin invitados, RSVP, WhatsApp, exportación, `index.php`.
- **Módulos:** invitados, confirmación.
- **Observación:** el nombre parece un typo; no renombrar sin migración planificada.

### `invitados_prioridad`

- **Uso:** catálogo de prioridad/categoría de invitado.
- **Columnas detectadas:** `id`, `categoria_prioridad`.
- **Tipos:** `id` int; texto.
- **PK:** `id` inferida.
- **Archivos:** admin invitados, RSVP, WhatsApp, exportación.
- **Módulos:** invitados, segmentación.

### `invitados_a_enviar`

- **Uso:** cola de invitaciones pendientes de envío.
- **Columnas detectadas:** `id_invitados`, `id_invitados_tel`, `tel_enviar`, `fecha_agregado`.
- **Tipos:** ids int; teléfono texto; fecha timestamp/datetime.
- **PK:** no detectada; recomendable compuesta (`id_invitados`, `id_invitados_tel`).
- **Archivos:** `admin7WZiwEM3XY/gestionar_envios.php`, herramienta Node v202512.
- **Módulos:** WhatsApp/envíos.

### `invitados_enviados`

- **Uso:** registro de invitaciones enviadas.
- **Columnas detectadas:** `id_invitados`, `id_invitados_tel`, `tel_enviar`, `fecha_envio`.
- **Tipos:** ids int; teléfono texto; fecha timestamp/datetime.
- **PK:** no detectada; recomendable compuesta.
- **Archivos:** `gestionar_envios.php`, `invitados.php`, herramienta Node v202512.
- **Módulos:** WhatsApp/envíos/reportes.

### `invitaciones_estado`

- **Uso:** estados/respuestas API de invitaciones WhatsApp.
- **Columnas detectadas:** `id_invitado`, `id_invitados_tel`, `fecha_envio`, `estado_api`, `detalle_api`.
- **Tipos:** ids int; fecha; textos para estado/detalle.
- **PK:** no detectada.
- **Índices:** recomendable en (`id_invitado`, `id_invitados_tel`, `estado_api`).
- **Archivos:** `dashboard_casamiento.php`, `invitados_invitaciones.php`, WhatsApp PHP.
- **Módulos:** WhatsApp, dashboard.

### `registro_mensajes_enviados`

- **Uso:** bitácora de mensajes enviados por herramienta Node.
- **Columnas detectadas:** `id_invitados`, `id_invitados_tel`, `tel_enviar`, `fecha_envio`.
- **Tipos:** ids int; texto; fecha.
- **PK:** no detectada.
- **Archivos:** `dashboard_casamiento.php`, `dqs envios invitaciones v202512/whatsapp.js`.
- **Módulos:** WhatsApp, dashboard.
- **Observación:** posible solapamiento con `invitados_enviados`.

### `productos`

- **Uso:** catálogo de regalos/productos.
- **Columnas detectadas:** `id`, `titulo`, `descripcion`, `precio`, `activo`.
- **Tipos:** `id` int; textos; `precio` decimal; `activo` tinyint inferidos.
- **PK:** `id` inferida.
- **Índices:** recomendable en `activo`.
- **Archivos:** `admin7WZiwEM3XY/lista_regalos.php`, `productos_vendidos.php`, `tienda/mostrar_productos.php`, `paginacion.php`, `finalizar_compra.php`, `procesar_compra.php`, `ver_carrito.php`, `regalo_libre_helper.php`.
- **Módulos:** lista de regalos, tienda, reportes.

### `imagenes`

- **Uso:** imágenes asociadas a productos.
- **Columnas detectadas:** `id` inferida, `producto_id`, `url`.
- **Tipos:** ids int; url texto.
- **PK:** `id` inferida.
- **Relaciones:** `producto_id` → `productos.id`.
- **Archivos:** `admin7WZiwEM3XY/lista_regalos.php`, `tienda/mostrar_productos.php`, `tienda/ver_carrito.php`.
- **Módulos:** lista de regalos/tienda.

### `carrito`

- **Uso:** carrito temporal por sesión PHP.
- **Columnas detectadas:** `id`, `session_id`, `producto_id`, `cantidad`, `monto_libre`.
- **Tipos:** `session_id` texto; ids/cantidad int; `monto_libre` decimal nullable.
- **PK:** `id` inferida.
- **Índices:** recomendable en `session_id`, `producto_id` y único compuesto (`session_id`, `producto_id`).
- **Archivos:** `tienda/carrito.php`, `ver_carrito.php`, `finalizar_compra.php`, `procesar_compra.php`, `modificar_cantidad.php`, `eliminar_producto.php`, `vaciar_carrito.php`, `regalo_libre_helper.php`.
- **Módulos:** tienda/carrito.

### `regalos`

- **Uso:** cabecera de regalo/compra realizado por invitado.
- **Columnas detectadas:** `id`, `nombre`, `apellido`, `email`, `telefono`, `forma_pago`, `monto_total`, `productos`, `compartido`, `mensaje`, `activo`, `pago_con`.
- **Tipos:** `id` int; `monto_total` decimal; `activo` tinyint; textos para resto.
- **PK:** `id` inferida.
- **Índices:** recomendable en `activo`, `email`, fecha si existe.
- **Archivos:** `tienda/procesar_compra.php`, `compra_exitosa.php`, `admin7WZiwEM3XY/productos_vendidos.php`, `cancelar_regalo.php`, `deshacer_cancelacion.php`, `totales.php`.
- **Módulos:** regalos, pagos manuales, reportes.

### `regalos_detalles`

- **Uso:** detalle normalizado de productos por regalo.
- **Columnas detectadas:** `regalo_id`, `producto_id`, `cantidad`, `monto_libre`, `subtotal`.
- **Tipos:** ids/cantidad int; montos decimal.
- **PK:** no detectada; puede tener `id` no usado.
- **Relaciones:** `regalo_id` → `regalos.id`; `producto_id` → `productos.id`.
- **Archivos:** `tienda/procesar_compra.php`, `admin7WZiwEM3XY/productos_vendidos.php`, `tienda/regalo_libre_helper.php`.
- **Módulos:** regalos/reportes.

### `regalos_confirmacion`

- **Uso:** marca de pago confirmado para regalos.
- **Columnas detectadas:** `regalo_id`; posiblemente fecha/id no capturados.
- **Tipos:** `regalo_id` int.
- **PK:** no detectada; recomendable unique en `regalo_id`.
- **Relaciones:** `regalo_id` → `regalos.id`.
- **Archivos:** `admin7WZiwEM3XY/confirmar_pago.php`, `deshacer_confirmacion.php`, `productos_vendidos.php`, `totales.php`.
- **Módulos:** pagos manuales/reportes.

### `visitas`

- **Uso:** registro de visitas a páginas.
- **Columnas detectadas:** `ip_usuario`, `pagina_visitada`; probablemente fecha/id no capturados.
- **Tipos:** textos; fecha inferida si existe.
- **PK:** desconocida.
- **Archivos:** `contador.php`, `admin7WZiwEM3XY/dashboard_casamiento.php`.
- **Módulos:** analytics/dashboard.

### `visitas_con_sesion`

- **Uso:** métricas de visitas con sesión para dashboard.
- **Columnas detectadas:** no suficientes por inserts; usada en consultas del dashboard.
- **Tipos/PK/índices:** desconocidos.
- **Archivos:** `admin7WZiwEM3XY/dashboard_casamiento.php`.
- **Módulos:** analytics/dashboard.
- **Observación:** requiere schema real.

### `sesiones`

- **Uso:** métrica de sesiones en dashboard.
- **Columnas detectadas:** no suficientes por inserts; usada en consultas del dashboard.
- **Tipos/PK/índices:** desconocidos.
- **Archivos:** `admin7WZiwEM3XY/dashboard_casamiento.php`.
- **Módulos:** analytics/dashboard.

### `site_settings`

- **Uso:** configuración de regalo libre u otros ajustes de tienda.
- **Columnas detectadas:** `setting_key`, `setting_value`; se infiere `created_at/updated_at` desde helper.
- **Tipos:** texto para key/value; timestamps inferidos.
- **PK/índices:** recomendable unique en `setting_key`.
- **Archivos:** `tienda/regalo_libre_helper.php`.
- **Módulos:** tienda/lista de regalos.
- **Observación:** el helper contiene SQL de creación/alteración, a diferencia del patrón general.

## Tablas huérfanas o poco usadas

- `sesiones` y `visitas_con_sesion`: solo vistas en dashboard; no se detectó escritura clara en el análisis principal.
- `registro_mensajes_enviados`: solapa con `invitados_enviados` y depende de herramienta Node.
- `info_mostrar`: usada por menú/header, pero estructura incompleta desde código.
- `site_settings`: usada solo por regalo libre; su mantenimiento vive en helper, no en migraciones.

## Campos usados por varias funcionalidades

- `cliente.cotizacion_dolar`: carrito, listado de productos y checkout.
- `cliente.cbu*`/`alias*`: checkout, compra exitosa, emails.
- `info_casamiento.portada_*`: front, tienda, compra exitosa.
- `invitados.codigo`: RSVP e invitaciones.
- `invitados.confirmacion_*` y `invitados_listado_mesa.asiste/*`: dashboard, exportación y RSVP.
- `productos.precio` y `regalos_detalles.subtotal`: tienda y reportes.

## Posibles inconsistencias de datos

- Confirmación duplicada cabecera/detalle: `invitados.confirmacion_*` puede desincronizarse de `invitados_listado_mesa` si se actualiza por caminos distintos.
- Multi-cliente incompleto: algunas tablas no tienen `cliente_id`/`user_id`; varias consultas usan singleton.
- `regalos.productos` guarda resumen textual mientras `regalos_detalles` guarda detalle normalizado.
- `invitados_enviados`, `registro_mensajes_enviados` e `invitaciones_estado` representan estados parecidos.
- Falta schema versionado completo, por lo que los ambientes pueden divergir.
