> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Comparativa de archivos por plan

## Tabla comparativa de archivos principales

| Archivo | Básico | Básico Form | Oro Código | Oro Form | Observación | Base recomendada |
|---|---:|---:|---:|---:|---|---|
| `index.php` | distinto | igual a oro_form | distinto | igual a basico_form | Front varía por RSVP y secciones oro. | `oro_form` para form; `oro_codigo` para código/oro. |
| `confirmar_asistencia.php` | igual | igual | igual | igual | Archivo idéntico en los 4 planes analizados. | Cualquiera, con tests de integración. |
| `confirmacion_modal.php` | básico | básico | muy distinto | form leve | Diferencia crítica en modal/RSVP. | No canonizar sin matriz por `rsvp_modo`. |
| `procesar_confirmacion.php` | distinto | distinto | distinto | distinto | Núcleo RSVP; no mezclar. | Selector por flujo. |
| `admin_tmp/gestionar_envios.php` | igual | igual | igual | distinto | Oro form parece adaptar envíos/pre_. | Separar por `fuente_envios_whatsapp`. |
| `admin_tmp/crear_carpeta_admin.php` | igual | igual | igual | igual | Instalador admin común. | Reescribir seguro, no ejecutar directo. |
| `admin_tmp/invitados.php` | igual | igual | igual | distinto | Admin de invitados normal vs form/pre_. | Mantener dos fuentes lógicas. |
| `admin_tmp/invitados_basico.php` | igual | igual | igual | igual | Admin básico común. | Candidato a módulo compartido. |
| `tienda/index.php` | igual a basico_form | igual a basico | igual a oro_form | igual a oro_codigo | Tienda oro contiene cambios frente a básico. | `oro_codigo/oro_form` para regalos. |

## Archivos iguales detectados

- `confirmar_asistencia.php` es idéntico en los cuatro planes PHP.
- `admin_tmp/crear_carpeta_admin.php` es idéntico en los cuatro planes PHP.
- `admin_tmp/invitados_basico.php` es idéntico en los cuatro planes PHP.
- `admin_tmp/gestionar_envios.php` es idéntico en `basico`, `basico_form` y `oro_codigo`; `oro_form` difiere.
- `tienda/index.php` forma dos grupos: básico/básico_form y oro_codigo/oro_form.
- `index.php` forma grupo `basico_form`/`oro_form`, mientras `basico` y `oro_codigo` son propios.

## Archivos parecidos

- `confirmacion_modal.php`: comparte propósito, pero el de `oro_codigo` es bastante más grande y el de `oro_form` tiene ajuste de formulario.
- `procesar_confirmacion.php`: mismo rol, pero diferencias por plan, código vs formulario y persistencia.
- `admin_tmp/invitados.php`: tres planes comparten versión normal; `oro_form` agrega lógica distinta.
- Scripts WhatsApp externos: `codigo` y `form` son casi gemelos, pero cambian tablas fuente (`invitados*` vs `pre_invitados*`).

## Archivos diferentes o únicos

- SQL: `basico/base_datos/*`, `basico_form/admin_tmp/base_datos.sql`, `basico_form/_BD/nuevo_cliente_v2.sql`, `oro_codigo/__base_datos/*`, `oro_form/BASE DE DATOS.sql`.
- `oro_form` aporta tablas `pre_` que no aparecen como schema principal en los otros planes.
- `oro_codigo` y `oro_form` aportan `site_settings` y campos asociados a regalo libre.
- Carpetas `dqs envios invitaciones_*` aportan `app.js`, `web-logic.js`, `whatsapp.js`, plantillas y `.bat`.

## Archivos obsoletos o sospechosos

- `*.bkp`: respaldos históricos.
- `prueba.php`, `test_mail.php`: pruebas manuales.
- `whatsapp.zip`: binario empaquetado; no auditable sin extracción controlada y no debe usarse directo.
- `config.txt` y conexiones: riesgo de secretos; documentar existencia, no copiar valores.
- Instaladores SQL con nombres superpuestos (`editado`, `nuevo_cliente`, `nuevo_cliente_v2`) pueden representar etapas, no versiones lineales.

## Recomendación canónica por funcionalidad

| Funcionalidad | Recomendación |
|---|---|
| Front código | Tomar `oro_codigo` como referencia más completa y degradar por flags para básico. |
| Front form | Tomar `oro_form`/`basico_form` como referencia, aislando diferencias de regalos. |
| RSVP código | Mantener flujo `invitados` + `codigo`, basado en `oro_codigo`/`basico`. |
| RSVP form | Mantener flujo form, con atención a `pre_invitados` si proviene de WhatsApp/pre_. |
| Admin invitados | Separar módulo normal y módulo pre_ inicialmente. |
| Regalos/carrito | Base oro; habilitar solo con `regalos_enabled=1`. |
| WhatsApp externo | Dos adaptadores: `invitados` y `pre_invitados`; no fusionar tablas. |
| Instalador admin | Reescribir idempotente; no usar `crear_carpeta_admin.php` sin rediseño. |
