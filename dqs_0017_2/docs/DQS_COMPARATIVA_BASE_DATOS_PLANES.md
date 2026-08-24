> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Comparativa de base de datos por planes

## Tablas detectadas por plan

| Plan/carpeta | Tablas principales detectadas |
|---|---|
| `basico` | `invitados`, `invitados_listado_mesa`, `invitados_tel`, `cliente`, `intivados_acompanante`, `invitados_prioridad`, `info_casamiento`, `info_eventos`, `carrito`, `regalos`, `user`, `info_otra`, `regalos_confirmacion`, `admin_config`, `productos`, `invitaciones_estado`, `imagenes`, `visitas`, `invitados_a_enviar`, `invitados_enviados`, `registro_mensajes_enviados`. |
| `basico_form` | Similar a básico, con SQL menos disperso. No se detectó schema `pre_` como eje principal. |
| `oro_codigo` | Similar a básico, más `site_settings` y alteraciones de carrito/regalo libre. |
| `oro_form` | Similar a oro, más `pre_invitados`, `pre_invitados_listado_mesa`, `pre_invitados_tel`. |
| `dqs envios invitaciones_codigo` | Lee `invitados_a_enviar`, `invitados`, `invitados_listado_mesa`, `invitados_tel`, `cliente`; escribe `invitados_enviados`, `registro_mensajes_enviados`. |
| `dqs envios invitaciones_form` | Lee `invitados_a_enviar`, `pre_invitados`, `pre_invitados_listado_mesa`, `pre_invitados_tel`, `cliente`; escribe `invitados_enviados`, `registro_mensajes_enviados`. |

## Tablas compartidas

- Identidad/admin: `user`, `cliente`, `admin_config`.
- Contenido web: `info_casamiento`, `info_eventos`, `info_nosotros`, `info_historia`, `info_otra`, `info_mostrar`, `visitas`.
- Invitados normales: `invitados`, `invitados_listado_mesa`, `invitados_tel`, `intivados_acompanante`, `invitados_prioridad`.
- Regalos/tienda: `productos`, `imagenes`, `carrito`, `regalos`, `regalos_detalles`, `regalos_confirmacion`, `site_settings`.
- WhatsApp/estado: `invitados_a_enviar`, `invitados_enviados`, `invitaciones_estado`, `registro_mensajes_enviados`.

## Tablas con prefijo `pre_`

| Tabla `pre_` | Equivalente normal | Uso inferido |
|---|---|---|
| `pre_invitados` | `invitados` | Invitado/preinvitado fuente para formulario/WhatsApp form. |
| `pre_invitados_listado_mesa` | `invitados_listado_mesa` | Integrantes/nombres asociados al preinvitado. |
| `pre_invitados_tel` | `invitados_tel` | Teléfonos para envío a preinvitados. |

## Campos detectados por familias

- `invitados`/`pre_invitados`: nombre, apellido, acompañamiento, cantidades mayores/menores, prioridad, ingreso, fecha, código, confirmación, activo y restricciones como alimento en algunos flujos.
- `*_listado_mesa`: relación a invitado/preinvitado, nombre de invitado, nombre/apellido normalizados.
- `*_tel`: relación a invitado/preinvitado y teléfono de envío.
- `invitaciones_estado`: invitado, teléfono, fecha de envío, estado API y detalle API.
- `regalos`: comprador, contacto, monto, pago, estado/activo; `regalos_detalles` amplía productos/cantidades/monto libre.
- `site_settings`: clave/valor para configuración de regalo libre.

## Relaciones detectadas

- `invitados.id` -> `invitados_listado_mesa.id_invitados`.
- `invitados.id` -> `invitados_tel.id_invitados`.
- `pre_invitados.id` -> `pre_invitados_listado_mesa.id_pre_invitados` o equivalente observado por consultas.
- `pre_invitados.id` -> `pre_invitados_tel.id_pre_invitados` o equivalente observado por consultas.
- `productos.id` -> `imagenes.producto_id`.
- `regalos.id` -> `regalos_confirmacion.regalo_id` y `regalos_detalles.regalo_id`.
- `cliente.user_id` y `user.id` se usan para identidad/configuración del cliente.

## Diferencias entre schemas

- Oro agrega o usa más intensamente tienda/regalo libre (`site_settings`, `regalos_detalles`, alteraciones de `carrito`).
- `oro_form` agrega tablas `pre_`; no se deben considerar simples aliases de `invitados`.
- Los SQL de referencia no parecen una única migración ordenada; hay scripts base, editados y alteraciones.

## Riesgos de choque de IDs

- `invitaciones_estado` puede guardar IDs de invitado normal; si se alimenta con `pre_invitados` sin columna discriminadora, los IDs pueden colisionar.
- `invitados_enviados` y `registro_mensajes_enviados` reciben resultados de ambos flujos; requieren fuente (`invitados` vs `pre_invitados`) explícita si se unifican.
- No usar el mismo `id_invitado` para dos tablas distintas sin `fuente_envios_whatsapp` o `tipo_origen`.

## Riesgos de migración

1. Cambiar PK/FK sin migraciones transaccionales romperá históricos de envíos y confirmaciones.
2. Fusionar `pre_invitados` con `invitados` como primer paso perdería distinción operativa.
3. Corregir typos de tabla (`intivados_acompanante`) requiere compatibilidad o vistas.
4. Scripts SQL con INSERT inicial pueden pisar datos si se ejecutan en bases existentes.

## Tablas críticas por flujo

| Flujo | Tablas críticas |
|---|---|
| RSVP código | `invitados`, `invitados_listado_mesa`, `invitados_tel`, `invitados_prioridad`, `intivados_acompanante`. |
| RSVP form | `invitados` y/o `pre_invitados` según entrada; listados y teléfonos equivalentes. |
| WhatsApp invitados | `invitados_a_enviar`, `invitados`, `invitados_tel`, `invitados_listado_mesa`, `invitados_enviados`, `registro_mensajes_enviados`. |
| WhatsApp pre_ | `invitados_a_enviar`, `pre_invitados`, `pre_invitados_tel`, `pre_invitados_listado_mesa`, `invitados_enviados`, `registro_mensajes_enviados`. |
| Regalos | `productos`, `imagenes`, `carrito`, `regalos`, `regalos_detalles`, `regalos_confirmacion`, `site_settings`, `cliente`. |
