> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Auditoría de referencia de planes

## Resumen ejecutivo

La referencia contiene seis desarrollos separados: `basico`, `basico_form`, `oro_codigo`, `oro_form`, `dqs envios invitaciones_codigo` y `dqs envios invitaciones_form`. Los cuatro primeros son aplicaciones PHP completas con front, `admin_tmp`, tienda/regalos, SQL y flujos RSVP. Los dos últimos son herramientas Node/PHP/JS auxiliares para envíos WhatsApp masivos.

Hallazgos principales:

- La primera etapa de unificación debe ser **seleccionar el flujo por configuración**, no mezclar tablas ni migrar datos.
- `basico` y `oro_codigo` representan RSVP por código; `basico_form` y `oro_form` representan RSVP por formulario/búsqueda en URL.
- `oro_form` es el único plan completo que incluye explícitamente tablas `pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel` en su SQL de referencia.
- WhatsApp externo tiene dos variantes: código lee `invitados*`; form lee `pre_invitados*`.
- Hay duplicación muy alta entre planes; algunos archivos son byte-a-byte iguales, otros difieren solo en RSVP, tienda/regalo libre o manejo de `pre_`.
- `admin_tmp/crear_carpeta_admin.php` mueve archivos de `admin_tmp` a una carpeta `adminXXXXXXXXXX` aleatoria y registra esa carpeta en `admin_config`.
- Existen archivos `.bkp`, `prueba.php`, `test_mail.php`, zips y scripts con configuración local o sensible: deben tratarse como referencia, no como base directa.

## Carpetas revisadas

| Carpeta | Contenido observado | Plan/rol inferido | Estado recomendado |
|---|---|---|---|
| `docs/referencia_planes/basico/` | App PHP completa, admin, SQL, tienda y WhatsApp PHP embebido. | Básico con RSVP por código. | Útil para flujo código básico; revisar antigüedad frente a `oro_codigo`. |
| `docs/referencia_planes/basico_form/` | App PHP completa con front similar a `oro_form` pero SQL parcial. | Básico con RSVP por formulario. | Útil para UI form básica; no asumir schema completo. |
| `docs/referencia_planes/oro_codigo/` | App PHP completa, tienda/regalos más reciente, SQL con alteraciones y `site_settings`. | Oro con RSVP por código. | Mejor base documental para tienda/regalos y código. |
| `docs/referencia_planes/oro_form/` | App PHP completa, SQL único `BASE DE DATOS.sql`, tablas `pre_`. | Oro con RSVP por formulario/preinvitados. | Mejor base para flujo form + `pre_`. |
| `docs/referencia_planes/dqs envios invitaciones_codigo/` | Herramienta Node/JS con plantillas, `package.json`, `app.js`, `whatsapp.js`. | WhatsApp externo para invitados normales. | Analizar dependencias; no ejecutar. |
| `docs/referencia_planes/dqs envios invitaciones_form/` | Herramienta Node/JS equivalente. | WhatsApp externo para `pre_invitados`. | Canónico para envíos form/pre_. |

## Partes actuales vs viejas

### Parecen más actuales

- `oro_codigo`: contiene diferencias de tienda/regalo libre y `site_settings`, además de alteraciones posteriores en SQL.
- `oro_form`: contiene el modelo `pre_`, necesario para formularios y preinvitaciones.
- Herramientas WhatsApp externas: separan panel local y envío, aunque dependen de Node y tablas intermedias.

### Parecen viejas o sospechosas

- Archivos `.bkp`, `prueba.php`, `test_mail.php`, `whatsapp.zip`, `config.txt` y scripts con endpoints locales.
- SQL duplicado entre `base_datos`, `__base_datos`, `_BD` y nombres como `editado.sql`: indican historia de instalación, no una única verdad.
- `intivados_acompanante` parece mantener un typo histórico que no debe corregirse sin migración controlada.

## Partes que parecen instalador

- `admin_tmp/crear_carpeta_admin.php` genera carpeta admin aleatoria, crea directorio, mueve archivos sueltos desde `admin_tmp`, incluye `conexion.php` e inserta en `admin_config`.
- SQL `nuevo_cliente*.sql`, `base_datos.sql`, `BASE DE DATOS.sql`, `alter_table_20250929.sql` contienen schema/semillas/alteraciones de cliente inicial.

## Riesgos principales

1. Mezclar `invitados` y `pre_invitados` rompería RSVP y trazabilidad WhatsApp.
2. Reutilizar directamente `admin_tmp` puede mover archivos de forma irreversible si se ejecuta.
3. Unificar tienda/regalos sin feature flags puede exponer carrito/regalos en plan básico.
4. WhatsApp tiene dependencias duales: PHP embebido y Node externo.
5. Diferentes SQL pueden crear columnas o tablas incompatibles.
6. Posibles secretos o credenciales en archivos de conexión/configuración: no copiarlos a documentación ni PR.

## Recomendación ejecutiva

Crear primero una capa de configuración declarativa:

```ini
plan_servicio=basico|oro
rsvp_modo=codigo|form
fuente_envios_whatsapp=ninguno|invitados|pre_invitados
whatsapp_enabled=0|1
regalos_enabled=0|1
```

Luego adaptar front/admin/WhatsApp para seleccionar flujo sin migrar datos ni fusionar tablas en la primera etapa.
