# DQS - Auditoría general del estado actual

## Alcance y fuentes revisadas

Esta auditoría documenta el estado actual del proyecto sin modificar comportamiento, pantallas, base de datos ni datos reales. Las fuentes principales fueron:

- Archivos PHP del front público y del panel administrador.
- Módulo de tienda/regalos en `tienda/`.
- Scripts de WhatsApp en `admin7WZiwEM3XY/whatsapp/`, `dqs reenvio invitaciones/` y `dqs envios invitaciones v202512/`.
- Migraciones SQL disponibles en `database/migrations/`.
- Búsqueda estática de consultas `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE TABLE` y `ALTER TABLE`.

No se encontró un dump completo ni un set de migraciones que reconstruya toda la base. La estructura de la mayoría de las tablas fue inferida desde el código; las únicas alteraciones explícitas disponibles están en las migraciones de invitados menores/asistencia/alimento.

## Qué es el sistema

DQS es una plataforma PHP/MySQL para publicar una web personalizada de casamiento/evento y administrarla desde un backoffice. Permite mostrar portada, cronómetro, historia, información de la pareja, eventos, galería/fotos, datos bancarios/regalos, tienda/lista de regalos, confirmación de asistencia, invitados e invitaciones por WhatsApp.

La aplicación parece orientada a un cliente/evento activo por instalación, con varios datos leídos sin parámetro de cliente y con usos frecuentes de `user_id = 1` o del último registro de `admin_config`.

## Arquitectura observada

- **Frontend público raíz**: `index.php`, `header.php`, `footer.php`, `confirmar_asistencia.php`, `confirmacion_modal.php`, `procesar_confirmacion.php`, `invitacion.php`, `contador.php`.
- **Panel administrador**: carpeta `admin7WZiwEM3XY/`, con login, dashboard, CRUD parcial de contenidos, invitados, regalos vendidos e invitaciones.
- **Tienda/lista de regalos**: carpeta `tienda/`, con productos, carrito por sesión, checkout, registro de regalos y emails.
- **Base de datos**: conexión MySQL central en `conexion.php`; no hay capa de acceso a datos compartida ni ORM.
- **WhatsApp**: hay scripts PHP server-side y dos herramientas Node locales para envíos/reenvíos.
- **Assets**: estilos, imágenes de galería/eventos/logo y plantillas de invitación JPG/TTF.

## Módulos existentes

| Módulo | Estado | Descripción |
|---|---|---|
| Portada / home pública | Parcial-completo | Muestra datos principales del evento, secciones configurables, eventos, historia, galería e información adicional. |
| Confirmación de asistencia | Parcial-completo | Confirmación por código de invitado, con invitados de mesa/personas individuales, menores, asistencia y alimentación. |
| Invitados admin | Parcial-completo | Alta/edición/listados/exportación y gestión de teléfonos. |
| Invitaciones | Parcial | Genera invitaciones JPG y gestiona estados/envíos, con lógica duplicada entre PHP y Node. |
| Tienda/lista de regalos | Parcial-completo | Productos, imágenes, carrito, checkout, regalos y detalles. Incluye regalo libre por configuración. |
| Pagos/comprobantes | Parcial | Se registra forma de pago/pago con y confirmación/cancelación admin, pero no se ve upload de comprobantes ni integración de gateway. |
| Datos bancarios | Completo básico | Lee datos de `cliente` para transferencias en pesos/dólares. |
| Fotos/galería | Parcial | Admin sube/actualiza imágenes; front las muestra desde carpeta/tabla según sección. |
| Configuración cliente | Parcial | Datos personales/bancarios/cotización en `cliente`; usuario en `user`; carpeta admin en `admin_config`. |
| Emails | Parcial | Scripts de envío para contacto, compra y vendedor; hay test PHPMailer. |
| WhatsApp | Parcial | Scripts para envío de invitaciones, reenvío de fallidas y herramientas Node. Requiere configuración externa. |
| Seguridad/login | Básico/parcial | Login admin con sesión; credenciales DB están en archivo PHP; hay `.htpasswd`. Requiere endurecimiento. |

## Pantallas públicas detectadas

- `/index.php`: web principal del casamiento/evento.
- `/invitacion.php`: probable landing de invitación.
- `/confirmar_asistencia.php?codigo=...`: pantalla/formulario de RSVP.
- `/procesar_confirmacion.php`: endpoint POST de confirmación.
- `/tienda/index.php`: lista/regalos con secciones del evento.
- `/tienda/ver_carrito.php`: vista parcial o endpoint de carrito.
- `/tienda/finalizar_compra.php`: checkout.
- `/tienda/compra_exitosa.php?id=...`: confirmación posterior a compra/regalo.
- Endpoints AJAX de tienda: `carrito.php`, `modificar_cantidad.php`, `eliminar_producto.php`, `vaciar_carrito.php`, `mostrar_productos.php`, `paginacion.php`, `procesar_compra.php`.
- `/contador.php`: registro de visitas.
- `/php/form-process.php`: contacto/email.

## Pantallas del admin detectadas

- Login/logout/registro/cambio password: `login.php`, `logout.php`, `registrar_usuario.php`, `cambiar_password.php`.
- Dashboard: `index.php`, `dashboard_casamiento.php`, `totales.php`.
- Datos/configuración cliente: `datos.php`, `datos_modificar.php`.
- Contenido web: `info_casamiento.php`, `info_cronometro.php`, `info_eventos.php`, `info_fotos.php`, `info_historia.php`, `info_imagenes.php`, `info_logo.php`, `info_nosotros.php`, `info_otra.php`, `modificar_portada.php`, `paletas_colores.php`.
- Invitados: `invitados.php`, `invitados_basico.php`, `invitados_invitaciones.php`, `nuevo_invitado.php`, `editar_invitado.php`, `cargar_formulario_edicion.php`, `exportar_invitados.php`.
- Regalos: `lista_regalos.php`, `productos_vendidos.php`, `confirmar_pago.php`, `deshacer_confirmacion.php`, `cancelar_regalo.php`, `deshacer_cancelacion.php`.
- Invitaciones/WhatsApp: `gestionar_envios.php`, `invitaciones/index.php`, `invitaciones/generador_masivo.php`, `whatsapp/envio_invitaciones.php`, `whatsapp/reenvio_invitaciones_erroneas.php`.

## Funcionalidades dependientes de cada cliente/evento

- Textos de portada, frase, fecha y hora del evento (`info_casamiento`).
- Secciones de historia, nosotros, eventos, otra información y visibilidad (`info_historia`, `info_nosotros`, `info_eventos`, `info_otra`, `info_mostrar`).
- Galería/fotos/logo/imágenes en carpetas `images/` y tablas de contenido.
- Datos bancarios, alias, CBU, cotización dólar y datos de contacto (`cliente`).
- Invitados, teléfonos, prioridad, acompañantes, confirmaciones y alimentación (`invitados`, `invitados_listado_mesa`, `invitados_tel`).
- Productos/lista de regalos, carrito, regalos realizados y detalles (`productos`, `imagenes`, `carrito`, `regalos`, `regalos_detalles`).
- Estados/envíos de invitaciones (`invitados_a_enviar`, `invitados_enviados`, `invitaciones_estado`, `registro_mensajes_enviados`).

## Funcionalidades globales o de instalación

- Conexión a base de datos definida en `conexion.php`.
- Usuario admin en tabla `user`.
- Carpeta admin generada/registrada en `admin_config`.
- Assets y plantillas base del sitio.
- Herramientas Node de WhatsApp y dependencias locales.

## Archivos principales que intervienen

- `conexion.php`: conexión MySQL global. Contiene credenciales en claro; no deben exponerse en documentación ni logs.
- `index.php`: front público principal.
- `confirmar_asistencia.php` y `procesar_confirmacion.php`: flujo RSVP.
- `admin7WZiwEM3XY/menu.php`: navegación admin y lectura de visibilidad.
- `admin7WZiwEM3XY/dashboard_casamiento.php`: métricas de invitados/envíos/visitas/regalos.
- `admin7WZiwEM3XY/invitados*.php`, `nuevo_invitado.php`, `editar_invitado.php`: gestión de invitados.
- `admin7WZiwEM3XY/productos_vendidos.php`, `lista_regalos.php`: gestión de regalos/productos.
- `tienda/procesar_compra.php`, `tienda/carrito.php`, `tienda/finalizar_compra.php`: checkout.
- `admin7WZiwEM3XY/whatsapp/*.php` y herramientas `dqs * invitaciones*/`: envíos WhatsApp.

## Partes completas, parciales o pendientes

### Más completas

- Renderizado público básico del evento.
- Gestión de invitados y confirmación de asistencia con detalle por persona.
- Carrito/checkout de regalos con persistencia en tablas.
- Panel admin operativo para contenidos, invitados y regalos.

### Parciales

- Multi-cliente/multi-evento: hay indicios de `user`, `cliente`, `admin_config`, pero muchas consultas asumen un único cliente o `user_id = 1`.
- Pagos: se registran regalos y confirmación manual, pero no se detectó integración transaccional ni comprobantes.
- Emails: varios scripts, posible duplicación y configuración dispersa.
- WhatsApp: existe implementación, pero con configuración y lógica duplicada entre PHP/Node; requiere revisión operativa.
- Fotos: mezcla de carga desde admin y archivos estáticos.

### Pendientes o riesgos

- Falta schema SQL completo/versionado.
- Credenciales de DB y posiblemente configuración sensible en archivos del repo.
- Consultas con interpolación directa en algunos puntos, aunque otros usan prepared statements.
- Falta control consistente de autorización en todos los endpoints admin/AJAX.
- Riesgo de inconsistencia entre confirmación agregada en `invitados` y confirmación por persona en `invitados_listado_mesa`.
- Falta separación clara entre datos globales y datos por cliente.
