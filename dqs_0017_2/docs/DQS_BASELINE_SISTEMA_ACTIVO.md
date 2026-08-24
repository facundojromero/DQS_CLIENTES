# DQS baseline del sistema activo

Fecha de auditoría: 2026-06-24.

## Alcance y metodología

Auditoría estática del código activo del repositorio. No se ejecutó PHP, Node, SQL real, instaladores ni scripts WhatsApp. No se copiaron archivos desde `docs/referencia_planes/` ni se modificaron archivos productivos.

Archivos activos revisados principalmente:

- Raíz pública: `index.php`, `confirmacion_modal.php`, `confirmar_asistencia.php`, `procesar_confirmacion.php`, `conexion.php`, `header.php`, `footer.php`.
- Tienda/regalos: `tienda/index.php`, `tienda/mostrar_productos.php`, `tienda/carrito.php`, `tienda/ver_carrito.php`, `tienda/finalizar_compra.php`, `tienda/procesar_compra.php`, `tienda/compra_exitosa.php`, `tienda/regalo_libre_helper.php`, endpoints de carrito y correo.
- Admin activo: `admin7WZiwEM3XY/index.php`, `menu.php`, `datos.php`, `lista_regalos.php`, `productos_vendidos.php`, `invitados.php`, `invitados_basico.php`, `dashboard_casamiento.php`, `gestionar_envios.php`, `invitados_invitaciones.php` y auxiliares directos.
- WhatsApp activo/candidato: `admin7WZiwEM3XY/whatsapp/envio_invitaciones.php`, `admin7WZiwEM3XY/whatsapp/reenvio_invitaciones_erroneas.php`, `dqs envios invitaciones v202512/`, `dqs envios invitaciones v202602/`, `dqs reenvio invitaciones/`.
- Auditorías de referencia consultadas solo como comparación conceptual: `docs/DQS_AUDITORIA_REFERENCIA_PLANES.md`, `docs/DQS_PLAN_UNIFICACION_SEGURA.md`, `docs/DQS_FLUJOS_RSVP_WHATSAPP.md`, `docs/DQS_COMPARATIVA_BASE_DATOS_PLANES.md`, `docs/DQS_COMPARATIVA_PLANES_ARCHIVOS.md`.

## Resumen ejecutivo

El sistema activo actual parece ser una base tipo **básico con extensiones de formulario RSVP por persona, regalos/carrito y administración completa**. El front público principal (`index.php`) carga contenido configurable desde base de datos, muestra secciones informativas del evento, permite buscar una invitación por código y abre un modal de confirmación. La confirmación activa del front moderno usa `confirmacion_modal.php` + `procesar_confirmacion.php`, no el flujo legacy de página completa `confirmar_asistencia.php`.

El flujo de regalos activo está integrado de dos maneras:

1. En el home, la sección `#regalar` puede mostrar datos bancarios para transferencia según configuración.
2. El botón “Regalar” apunta dinámicamente a la tienda (`tienda/`) si la lista de regalos está habilitada, o al bloque de transferencia si solo transferencia está habilitada.

La tienda usa productos, imágenes, carrito por sesión, checkout, gift card/regalo libre y registro de regalos comprados. La configuración visual de regalos se administra en `admin7WZiwEM3XY/lista_regalos.php` mediante helpers de `tienda/regalo_libre_helper.php` y persiste en `site_settings`.

WhatsApp tiene más de un flujo coexistente. El admin web incluye gestión manual de envíos (`gestionar_envios.php`) y un envío por API PHP (`admin7WZiwEM3XY/whatsapp/envio_invitaciones.php`). Además existen carpetas Node con flujos WhatsApp versionados. Los flujos activos detectados trabajan con `invitados`, `invitados_listado_mesa`, `invitados_tel`, colas/estados (`invitados_a_enviar`, `invitados_enviados`, `registro_mensajes_enviados`, `invitaciones_estado`). No se detectó `pre_invitados` como fuente principal activa en los archivos revisados.

## Qué comportamiento tiene hoy el sistema activo

### Front público

`index.php`:

- Incluye conexión global y helper de regalos.
- Consulta `info_casamiento` para portada, fecha y contador.
- Consulta `info_eventos`, `info_nosotros`, `info_historia`, imágenes de galería, `info_otra` y `info_mostrar` para construir secciones visibles.
- Calcula configuración visual de regalos y destino del botón “Regalar”.
- Lee datos bancarios desde `cliente` (`cbu_titular`, `cbu`, `alias`, `cbu_dolar`, `alias_dolar`) para mostrar transferencia.
- Renderiza búsqueda RSVP por código (`busqueda`) y botón que abre modal con `confirmacion_modal.php?codigo=...`.

Secciones visibles típicas detectadas:

- Header/portada.
- Nosotros/about.
- Historia.
- Galería/fotos.
- Eventos.
- Más info / wedding.
- Regalos / transferencia.
- RSVP.
- Contact/footer según configuración.

La visibilidad de varias secciones se apoya en `info_mostrar`, administrada desde el menú admin.

### RSVP público actual

Flujo activo recomendado/detectado en `index.php`:

1. El invitado busca por `busqueda` o llega con URL tipo `?busqueda=CODIGO#rsvp`.
2. `index.php` consulta `invitados` por `codigo` y cruza con `invitados_listado_mesa` para mostrar grupo/personas.
3. El botón “Confirmar Asistencia” abre un modal por AJAX: `confirmacion_modal.php?codigo=CODIGO`.
4. `confirmacion_modal.php` consulta `invitados` y `invitados_listado_mesa`, muestra selector Sí/No, checklist por persona y restricciones alimentarias por persona.
5. El formulario POSTea a `procesar_confirmacion.php`.
6. `procesar_confirmacion.php` valida método POST, valida código, resetea/asigna asistencia por persona en `invitados_listado_mesa`, calcula mayores/menores por `es_menor`, actualiza resumen en `invitados` y devuelve JSON.
7. El modal muestra el resultado sin navegar a una página nueva.

`confirmar_asistencia.php` sigue existiendo y funciona como flujo legacy por página completa: lee `id` desde request, actualiza `invitados` directamente con mayores/menores/comentario/alimento por código y renderiza formulario HTML. No parece ser el flujo principal llamado por `index.php`.

### Regalos actual

Flujo activo:

- `index.php` y `tienda/index.php` usan `obtenerConfiguracionVisualRegalos()` y `obtenerDestinoLinkRegalar()`.
- Defaults actuales desde helper:
  - `mostrar_lista_regalos`: `1`.
  - `mostrar_transferencia_regalos`: `0`.
  - `titulo_transferencia_regalos`: `¿Nos querés hacer un regalo?`.
  - `titulo_cuenta_pesos_regalos`: `Cuenta en pesos`.
  - `titulo_cuenta_dolares_regalos`: `Cuenta en dólares`.
  - `show_giftcard`: `1`.
- Si la lista está habilitada, el link “Regalar” va a `tienda/` desde el home o `../tienda/` desde vistas internas.
- Si la lista está deshabilitada y transferencia habilitada, el link apunta a `#regalar`/`../#regalar`.
- Si ambas están deshabilitadas, el helper indica no mostrar el link.

Tienda/carrito:

- `tienda/mostrar_productos.php` lista `productos` activos, excluye el título compatible con Gift Card y consulta imágenes en `imagenes`.
- Gift Card/regalo libre se modela como producto especial con título `Gift Card`, precio `0`, monto ingresado por usuario y columnas `monto_libre` en `carrito`/`regalos_detalles`.
- `tienda/script.js` agrega productos al carrito vía `tienda/carrito.php`, abre modal de carrito con `tienda/ver_carrito.php`, modifica cantidades y permite vaciar/eliminar.
- `tienda/finalizar_compra.php` muestra resumen y formulario de checkout. La forma de pago visible es transferencia bancaria.
- `tienda/procesar_compra.php` inserta en `regalos`, inserta detalle en `regalos_detalles`, borra el carrito de la sesión, envía correos si corresponde y redirige/continúa a compra exitosa.
- `tienda/compra_exitosa.php` muestra datos de la compra, datos bancarios y estado pendiente de confirmación por transferencia.

Admin de regalos:

- `admin7WZiwEM3XY/lista_regalos.php` administra productos (`productos`, `imagenes`) y configuración visual (`site_settings`).
- `admin7WZiwEM3XY/productos_vendidos.php` lista regalos pendientes y confirmados, confirma pagos insertando en `regalos_confirmacion`, permite cancelar/deshacer y calcula totales.

### Admin actual

Entrada principal: `admin7WZiwEM3XY/index.php` con sesión `user_id` obligatoria. El menú visible se define en `admin7WZiwEM3XY/menu.php`.

Menú detectado:

- Inicio: dashboard.
- Regalos:
  - Confirmar.
  - Recibidos.
  - Lista de regalos.
- Invitados:
  - Lista de invitados.
  - Nuevo invitado.
  - Enviar Invitaciones.
  - Envío Automático.
  - Exportar Invitados.
- Web:
  - Colores.
  - Logo.
  - Portada.
  - Imagen Portada.
  - Cronómetro.
  - Nosotros.
  - Historia.
  - Fotos.
  - Eventos.
  - Más info.
  - Contactar.
- Datos.
- Cerrar sesión.

Datos del cliente:

- `admin7WZiwEM3XY/datos.php` lee/actualiza/inserta `cliente` por `user_id`, incluyendo datos personales, teléfonos, ubicación, cuentas en pesos/dólares y cotización dólar.

Invitados/confirmaciones:

- `admin7WZiwEM3XY/invitados.php` y `invitados_basico.php` gestionan invitados, estados `activo`, confirmación manual, filtros, edición y links.
- `dashboard_casamiento.php` usa `invitados_listado_mesa` como fuente por persona para KPIs, y `invitados` para agrupaciones/categorías.

Envíos:

- `gestionar_envios.php` mueve teléfonos entre “activos”, “a enviar” y “enviados”, usando `invitados_tel`, `invitados_a_enviar`, `invitados_enviados`, y también modifica `invitados.confirmacion`/`activo` en acciones puntuales.

### WhatsApp actual

Flujos detectados:

1. **PHP API desde admin**: `admin7WZiwEM3XY/whatsapp/envio_invitaciones.php` consulta `invitados`, `invitados_listado_mesa`, `invitados_tel`, `cliente`, `invitados_prioridad` e `invitaciones_estado`; construye parámetros de invitación y registra estado en `invitaciones_estado`.
2. **Gestión manual de cola**: `admin7WZiwEM3XY/gestionar_envios.php` prepara/remueve registros en `invitados_a_enviar`, registra enviados en `invitados_enviados` y consulta estados.
3. **Node WhatsApp versionado**: `dqs envios invitaciones v202512/` y `dqs envios invitaciones v202602/` consultan `invitados` + `invitados_listado_mesa` + `invitados_tel` + `invitados_a_enviar`, envían por WhatsApp, insertan en `invitados_enviados` y `registro_mensajes_enviados`, y eliminan de `invitados_a_enviar`.
4. **Node reenvío**: `dqs reenvio invitaciones/` lee un listado CSV y arma reenvíos hacia `?busqueda={{codigo}}#rsvp`; parece un flujo auxiliar de reenvío, no la cola principal.

No se detectó que el flujo activo use `pre_invitados` como tabla primaria. El modelo activo se centra en `invitados` y `invitados_listado_mesa`.

## Qué plan parece representar hoy

Comparado con las auditorías de referencia, el estado activo parece una mezcla de:

- Base pública tipo `basico`.
- RSVP modernizado por formulario/persona (`basico_form`/unificación parcial): checklist por persona, restricciones alimentarias individuales y resumen en `invitados`.
- Regalos/carrito de plan con tienda: productos, carrito, checkout, transferencia, regalos vendidos y gift card.
- Administración ampliada para contenido web, invitados, envíos y regalos.
- WhatsApp con coexistencia de flujo PHP/API y flujos Node versionados.

## Tablas usadas por el código activo

### Front/contenido

- `info_casamiento`: portada, fecha, títulos.
- `info_eventos`: eventos activos.
- `info_nosotros`: sección nosotros.
- `info_historia`: historia.
- `info_otra`: más info.
- `info_mostrar`: visibilidad de secciones.
- `cliente`: datos bancarios, teléfonos, cotización dólar.
- `admin_config`: carpeta admin actual para algunos correos/vistas.

### RSVP/invitados

- `invitados`: código, nombre/apellido, cantidades, ingreso, confirmación, activo, alimento/resumen.
- `invitados_listado_mesa`: personas del grupo, `nombre_invitado`, `es_menor`, `asiste`, `confirm_date`, `alimento`, `alimento_comentario`.
- `intivados_acompanante`: categoría de acompañante (nombre con typo histórico).
- `invitados_prioridad`: prioridad/categoría.
- `invitados_tel`: teléfonos para envío.

### Regalos/tienda

- `productos`: catálogo y producto especial Gift Card.
- `imagenes`: imágenes de productos.
- `carrito`: carrito por `session_id`, cantidad y `monto_libre`.
- `regalos`: compra/regalo registrado.
- `regalos_detalles`: detalle normal y gift card con `monto_libre`.
- `regalos_confirmacion`: pagos/regalos confirmados.
- `site_settings`: configuración visual de regalos y gift card.

### WhatsApp/envíos

- `invitados_a_enviar`: cola preparada.
- `invitados_enviados`: enviados.
- `registro_mensajes_enviados`: auditoría/historial de mensajes enviados.
- `invitaciones_estado`: estado API PHP.
- `cliente`: datos del emisor/evento.

### Admin/seguridad/visitas

- `user`: login y usuarios.
- `visitas`, `visitas_con_sesion`, `sesiones`: métricas del dashboard.
- `admin_config`: carpeta admin.

## Configuraciones actuales detectadas

- `conexion.php` contiene host, base, usuario y password embebidos. No se copian valores en esta auditoría por seguridad.
- `site_settings` se crea automáticamente si no existe desde `regalo_libre_helper.php`.
- `regalo_libre_helper.php` también contiene lógica que puede ejecutar `ALTER TABLE` para agregar `monto_libre` en `regalos_detalles` y `carrito` si se invoca `asegurarEstructuraRegaloLibre()`.
- El admin depende de `$_SESSION['user_id']`.
- El RSVP público depende de `codigo`/`busqueda`, no de login.
- Links WhatsApp generados apuntan al home con `?busqueda=CODIGO#rsvp`.

## Riesgos antes de unificar

1. **Credenciales en código**: `conexion.php` contiene secretos embebidos. No exponer ni copiar valores.
2. **Helpers con DDL implícito**: `regalo_libre_helper.php` tiene funciones capaces de crear tablas y alterar columnas. Cualquier ejecución puede tocar base de datos.
3. **Múltiples flujos RSVP coexistentes**: flujo moderno modal/JSON y `confirmar_asistencia.php` legacy actualizan datos de manera diferente.
4. **Múltiples fuentes de conteo**: admin/dashboard usa `invitados_listado_mesa` para personas, pero algunos archivos siguen escribiendo resumen en `invitados`.
5. **WhatsApp duplicado**: PHP API, gestión manual y Node versionado pueden superponerse si se ejecutan sin criterio.
6. **Nombre histórico `intivados_acompanante`**: typo en tabla usado por código activo; corregirlo sin migración rompería consultas.
7. **Admin folder hardcodeado**: rutas referencian `admin7WZiwEM3XY` y algunos correos usan `admin_config`.
8. **SQL dinámico legacy**: varios archivos construyen SQL con interpolación directa; cualquier refactor debe tratar seguridad sin cambiar comportamiento inadvertidamente.
9. **Regalos dependen de configuración combinada**: cambiar defaults de `site_settings` altera si se muestra tienda, transferencia o link “Regalar”.
10. **No hay evidencia de tests automatizados activos** para estos flujos; la regresión debe apoyarse primero en checklist manual.

## Qué no se debe tocar todavía

- No unificar ni eliminar `confirmar_asistencia.php` hasta tener pruebas sobre URLs existentes.
- No cambiar tablas ni ejecutar SQL/DDL real.
- No ejecutar scripts WhatsApp ni Node.
- No renombrar tablas históricas (`intivados_acompanante`, etc.).
- No modificar defaults de regalos o `site_settings` sin una PR específica.
- No mover carpetas admin ni rutas de tienda.
- No limpiar credenciales en esta misma etapa sin plan de configuración/secretos.
- No copiar código desde `docs/referencia_planes/` al sistema activo.

## Recomendación para el primer cambio seguro

El primer cambio seguro debería ser **documental/observabilidad manual**, no funcional: agregar un mapa de rutas activas y checklist de regresión al proceso de PR. Si luego se decide tocar código, la primera PR funcional debería ser pequeña y reversible: por ejemplo, agregar comentarios o logs no sensibles detrás de bandera local, o separar configuración de secretos solo si se acompaña de variables de entorno y verificación manual, sin cambiar nombres de tablas ni flujos RSVP/regalos/WhatsApp.
