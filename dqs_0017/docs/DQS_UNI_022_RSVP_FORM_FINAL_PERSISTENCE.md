# DQS UNI-022 — RSVP formulario: persistencia final hacia invitados*

UNI-022 prepara la persistencia final del RSVP por formulario hacia el modelo real de invitados, manteniéndola apagada por defecto.

## Destino final

La confirmación final de un formulario RSVP debe guardarse en:

- `invitados`: cabecera del grupo/invitado principal.
- `invitados_listado_mesa`: titular principal y acompañantes/personas asociadas cuando corresponde.
- `invitados_tel`: teléfono del principal si fue informado y el schema lo permite.

`pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel` quedan exclusivamente como staging/fuente/prelista para WhatsApp. No son destino final de confirmación.

## Reglas por plan

- **Plan Básico**: no usa WhatsApp; RSVP formulario debe persistir directo en `invitados*` cuando el flag esté habilitado.
- **Plan Oro + RSVP formulario**: la confirmación final guarda en `invitados*`. Si WhatsApp usa `pre_*`, esas tablas siguen siendo staging/fuente.
- **Plan Oro + RSVP por código**: conserva el flujo actual; UNI-022 no modifica la confirmación por código.

## Condiciones exactas para escribir

`rsvp_form_validate.php` solo intenta escribir si se cumplen todas las condiciones:

1. Request `POST` válida.
2. Payload válido y persistible.
3. `rsvp_modo=form` efectivo.
4. `rsvp_form_persist_enabled=1` efectivo.
5. Schema `invitados*` listo según `includes/rsvp_form_final_persistence.php`.
6. Target final confirmado como `invitados*`.

Con los defaults seguros (`rsvp_modo=codigo` y `rsvp_form_persist_enabled=0`) el endpoint continúa en dry-run y responde `persisted=false`.

## Mapeo detectado

El helper inspecciona `information_schema.COLUMNS` y detecta columnas disponibles, sin asumir a ciegas variantes inexistentes.

### `invitados`

Mínimo requerido: `id`, `nombre`, `apellido`.

Si existen, usa: `codigo`, `confirmacion`, `confirmacion_fecha`, `confirmacion_comentario`, `alimento`, `cantidad_mayores`, `cantidad_menores`, `confirmacion_mayores`, `confirmacion_menores`, `acompanado`/`acompaniado`, `activo`, `fecha_registro`, `ingreso`.

Para confirmación `Si`, el formulario guarda `cantidad_mayores` y `confirmacion_mayores` como `1 + cantidad de acompañantes`, y `cantidad_menores`/`confirmacion_menores` como `0`. Para confirmación `No`, los cuatro contadores quedan en `0`.

### `invitados_tel`

Usa `id_invitados`/equivalente para FK y `tel_enviar`/equivalente para teléfono. Si no detecta ambos, omite el teléfono con warning controlado.

### `invitados_listado_mesa`

Usa `id_invitados`/equivalente y `nombre_invitado`/equivalente. Si existen, completa `nombre2`, `apellido2`, `mesa`, `es_menor`, `asiste`, `confirm_date`, `alimento` y `alimento_comentario`.

UNI-028 ajusta la compatibilidad con admin/reportes actuales: cuando el schema mínimo lo permite, la persistencia final inserta siempre una fila del titular/principal en `invitados_listado_mesa`. El titular usa `asiste=1` si confirmó `Si`, `asiste=0` si confirmó `No`, `es_menor=0`, `mesa=0`, nombre/apellido del principal y la restricción alimentaria/comentario del principal. Los acompañantes se siguen insertando solo cuando la confirmación es `Si`, con `asiste=1` y `es_menor=0`.

El teléfono sigue siendo opcional: se guarda en `invitados_tel` únicamente si fue informado y el schema detectado tiene FK y columna de teléfono.

## Código único y dedupe

Si `invitados.codigo` existe, el formato generado depende del tipo real detectado en `information_schema.COLUMNS`:

- Para columnas numéricas (`int`, `bigint`, `decimal`, etc.), se genera un código numérico compatible de 6 dígitos y se verifica que no colisione. En el schema actual de DEV se observaron códigos numéricos, por lo que no se asume prefijo `FORM-`.
- Para columnas de texto cortas (`varchar`, `char`, `text`, etc.) cuya longitud no alcance para `FORM-YYYYMMDD-HHMMSS-RAND`, se genera un código numérico de 6 dígitos como string compatible. Por ejemplo, con `codigo varchar(10)` el formato esperado es `numeric_6_digits_string`, no `FORM-...`.
- Para columnas de texto con longitud suficiente para el formato completo, se genera `FORM-YYYYMMDD-HHMMSS-RAND` y se verifica que no colisione.

Antes de insertar se busca un duplicado reciente de formulario en ventana de 5 minutos por mismo nombre, apellido y confirmación. Para esa ventana se prefiere `confirmacion_fecha` si existe y es `datetime`/`timestamp`; `fecha_registro` solo se usa si también guarda hora. Si `fecha_registro` es `date`, no sirve para una ventana de 5 minutos y se omite. Si existe teléfono y el mapeo completo de `invitados_tel` está disponible, el dedupe también exige el mismo teléfono mediante join con `invitados_tel`. Si no hay teléfono, deduplica por nombre/apellido/confirmación/ventana. El dedupe no depende de `codigo LIKE 'FORM-%'`, por lo que funciona con códigos numéricos o textuales. Si detecta duplicado, no inserta de nuevo y devuelve `deduped=true` con el `principal_id` existente.

## Qué NO hace UNI-022

- No escribe en `pre_*`.
- No altera tablas.
- No borra, trunca ni migra datos.
- No cambia `rsvp_modo`.
- No activa `rsvp_form_persist_enabled`.
- No modifica admin, tienda, regalos, WhatsApp, Node ni `admin_tmp`.
- No toca `procesar_confirmacion.php`, `confirmacion_modal.php` ni `confirmar_asistencia.php`.

## Cómo probar sin escribir

Comandos read-only principales:

```bash
php tools/dqs_rsvp_form_final_persistence_probe.php --status
php tools/dqs_rsvp_form_final_persistence_probe.php --schema
php tools/dqs_rsvp_form_final_persistence_probe.php --sample=empty
php tools/dqs_rsvp_form_final_persistence_probe.php --sample=valid
php tools/dqs_rsvp_form_final_persistence_probe.php --sample=no
php tools/dqs_rsvp_form_final_persistence_probe.php --sample=companions
```

Con configuración segura, `would_persist=false` y `reason=persistence_disabled_by_mode`. El diagnóstico `--schema` también expone el tipo detectado de `invitados.codigo`, `dedupe_strategy`, `dedupe_uses_code_prefix=false` y `dedupe_window_minutes=5`.

## UNI-023

UNI-023 deberá hacer una prueba real controlada en un entorno autorizado: activar temporalmente `rsvp_modo=form` y `rsvp_form_persist_enabled=1`, enviar un payload trazable, verificar inserts en `invitados*`, verificar rollback/dedupe, y restaurar la configuración segura.
