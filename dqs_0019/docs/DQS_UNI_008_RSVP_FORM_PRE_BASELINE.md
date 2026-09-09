# DQS UNI-008 — Baseline RSVP formulario / pre_invitados

## Resumen ejecutivo

UNI-008 caracteriza el flujo histórico **RSVP formulario** y la fuente `pre_invitados` usando referencias existentes, sin aplicarlo al sitio activo. La instalación actual sigue configurada y operando como Oro Código: `plan_servicio=oro`, `rsvp_modo=codigo`, `fuente_envios_whatsapp=invitados`, `whatsapp_enabled=1`, `regalos_enabled=1`.

Esta documentación es preparatoria: no modifica front, admin activo, tienda, regalos, WhatsApp, Node ni base de datos. La ausencia de tablas `pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel` en una base activa de RSVP por código es válida y no debe tratarse como error.

## Qué es RSVP formulario

RSVP formulario es la variante donde el visitante abre un modal/formulario genérico para cargar sus datos y confirmar asistencia. En las referencias `basico_form` y `oro_form`, el front carga `confirmacion_modal.php` sin buscar primero un código en el HTML visible, y el POST a `procesar_confirmacion.php` puede registrar la confirmación desde datos ingresados por el usuario.

En el material de unificación, el modo se representa como `rsvp_modo=form`, pero UNI-008 **solo lo documenta**; no se usa todavía para rutear el front activo.

## Diferencia con RSVP por código

| Punto | RSVP por código actual | RSVP formulario |
|---|---|---|
| Entrada principal | `/index.php?busqueda=CODIGO#rsvp` | Botón/modal de formulario; WhatsApp form también conserva `?busqueda=CODIGO#rsvp` como link esperado. |
| Fuente activa actual | `invitados` | Referencias form pueden trabajar con `invitados` y, para prelista/WhatsApp form, `pre_invitados`. |
| Confirmación | Código identifica un grupo existente y actualiza cabecera/detalle. | Formulario puede cargar datos y/o confirmar contra una prelista según adaptación futura. |
| Estado en producción | Activo | No activo en esta PR. |

## Rol de `pre_invitados`

`pre_invitados` funciona como una **prelista separada** para flujos formulario/pre-envío. Sus tablas equivalentes son:

- `pre_invitados`: cabecera del preinvitado y código.
- `pre_invitados_listado_mesa`: integrantes asociados al preinvitado.
- `pre_invitados_tel`: teléfonos de envío, sin que deban imprimirse completos en herramientas de diagnóstico.

La referencia `oro_form` incluye estas tablas. La referencia `dqs envios invitaciones_form` usa `pre_invitados*` para armar mensajes, pero mantiene colas/registro compartidos como `invitados_a_enviar`, `invitados_enviados` y `registro_mensajes_enviados`.

## Por qué esta PR no activa nada

No se activa `rsvp_modo=form` porque el sistema activo usa RSVP por código y WhatsApp sobre `invitados`. Cambiar el ruteo sin un adaptador read/write explícito podría mezclar IDs, escribir en tablas incorrectas o romper links actuales. UNI-008 deja solo documentación y una herramienta CLI read-only opcional.

## Flujo Básico Form

1. El usuario llega a la home del plan básico form.
2. El botón RSVP abre el modal `confirmacion_modal.php`.
3. El modal muestra campos de nombre, apellido, asistencia, cantidades y restricciones.
4. El formulario envía por AJAX a `procesar_confirmacion.php`.
5. `procesar_confirmacion.php` procesa la confirmación formulario según la referencia básica.
6. No hay tienda/regalos Oro como eje funcional del flujo básico, aunque la referencia contiene módulos reutilizados.

## Flujo Oro Form

1. El usuario llega a la home del plan Oro form.
2. El RSVP abre el modal `confirmacion_modal.php`.
3. El formulario Oro agrega campos/comentarios adicionales respecto del básico.
4. `procesar_confirmacion.php` guarda la confirmación formulario.
5. La referencia Oro form incluye tienda/regalos y además define tablas `pre_` en su SQL.
6. La existencia de `pre_` en referencia no significa que deban existir en todas las bases activas.

## Flujo WhatsApp Form/pre_

1. La variante Node/JS `dqs envios invitaciones_form` consulta `pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel`.
2. Para componer nombres del grupo usa integrantes de `pre_invitados_listado_mesa` unidos con `pre_invitados` por `id_invitados`.
3. Para teléfonos usa `pre_invitados_tel.id` como `id_invitados_tel` y `pre_invitados_tel.id_invitados`.
4. El mensaje conserva el link `?busqueda={{codigo}}#rsvp`, reemplazando `{{codigo}}` por el código del preinvitado.
5. Esta PR no ejecuta WhatsApp ni Node; solo documenta el contrato observado.

## Diferencias con Oro Código

- Oro Código activo busca en `invitados.codigo`; Oro Form/pre_ puede buscar o enviar desde `pre_invitados.codigo`.
- Oro Código actual persiste resumen en `invitados` y detalle en `invitados_listado_mesa`.
- Oro Form de referencia usa formulario genérico; para WhatsApp form/pre_ la fuente de envío es `pre_invitados*`.
- Oro Código actual tiene control de regalos/tienda y WhatsApp sobre `invitados`; UNI-008 no toca esas superficies.

## Archivos de referencia involucrados

- `docs/referencia_planes/basico_form/index.php`
- `docs/referencia_planes/basico_form/confirmacion_modal.php`
- `docs/referencia_planes/basico_form/procesar_confirmacion.php`
- `docs/referencia_planes/basico_form/confirmar_asistencia.php`
- `docs/referencia_planes/oro_form/index.php`
- `docs/referencia_planes/oro_form/confirmacion_modal.php`
- `docs/referencia_planes/oro_form/procesar_confirmacion.php`
- `docs/referencia_planes/oro_form/confirmar_asistencia.php`
- `docs/referencia_planes/oro_form/BASE DE DATOS.sql`
- `docs/referencia_planes/dqs envios invitaciones_form/whatsapp.js`
- `docs/referencia_planes/dqs envios invitaciones_form/template_singular.txt`
- `docs/referencia_planes/dqs envios invitaciones_form/template_plural.txt`
- `docs/referencia_planes/dqs envios invitaciones_form/web-logic.js`

## Tablas detectadas

| Tabla | Rol |
|---|---|
| `invitados` | Cabecera del RSVP por código actual y base normal. |
| `invitados_listado_mesa` | Integrantes/personas del grupo normal. |
| `invitados_tel` | Teléfonos normales. |
| `pre_invitados` | Cabecera de prelista form/pre_. |
| `pre_invitados_listado_mesa` | Integrantes de prelista. |
| `pre_invitados_tel` | Teléfonos de prelista. |
| `invitados_a_enviar` | Cola compartida observada por variantes WhatsApp. |
| `invitados_enviados` | Registro de enviados. |
| `registro_mensajes_enviados` | Auditoría de mensajes enviados. |

## Comparativa `invitados` vs `pre_invitados`

| Aspecto | `invitados` | `pre_invitados` |
|---|---|---|
| Identificador | `id` | `id` |
| Código | `codigo` | `codigo` |
| Estado activo | `activo` | `activo` |
| Cantidades | `cantidad_mayores`, `cantidad_menores` | `cantidad_mayores`, `cantidad_menores` |
| Confirmación | `confirmacion`, fecha, comentario, mayores/menores | Campos equivalentes en referencia Oro form. |
| Uso actual | Producción Oro Código. | Referencia form/pre_; puede no existir. |

## Comparativa `invitados_listado_mesa` vs `pre_invitados_listado_mesa`

| Aspecto | `invitados_listado_mesa` | `pre_invitados_listado_mesa` |
|---|---|---|
| FK observada | `id_invitados` | `id_invitados` en referencias revisadas. |
| Integrante | `nombre_invitado` | `nombre_invitado` |
| ID fila | `id` | `id` |
| Campos extra actuales | Puede incluir `es_menor`, `asiste`, `alimento`, `alimento_comentario`. | Referencia Oro form contiene estructura más simple: `mesa`, `nombre2`, `apellido2`. |

## Comparativa `invitados_tel` vs `pre_invitados_tel`

| Aspecto | `invitados_tel` | `pre_invitados_tel` |
|---|---|---|
| FK observada | `id_invitados` | `id_invitados` |
| Teléfono | `tel_enviar` | `tel_enviar` |
| ID teléfono | `id` | `id` usado como `id_invitados_tel` en WhatsApp form/pre_. |
| Riesgo | Cola/estado puede asumir IDs de invitados normales. | IDs pueden colisionar si no se agrega fuente explícita. |

## Campos relevantes

- Cabecera: `id`, `nombre`, `apellido`, `activo`, `acompanado`, `cantidad_mayores`, `cantidad_menores`, `id_prioridad`, `ingreso`, `fecha_registro`, `codigo`.
- Confirmación: `confirmacion`, `confirmacion_fecha`, `confirmacion_comentario`, `confirmacion_mayores`, `confirmacion_menores`, `alimento`.
- Integrantes: `id`, `id_invitados`, `nombre_invitado`, y según tabla `mesa`, `nombre2`, `apellido2`, `es_menor`, `asiste`, `alimento`, `alimento_comentario`.
- Teléfonos: `id`, `id_invitados`, `tel_enviar`.

## Uso esperado de link `?busqueda=CODIGO#rsvp`

Aunque el flujo form abre un modal genérico en las referencias, WhatsApp form/pre_ sigue generando links con el patrón:

```text
/index.php?busqueda=CODIGO#rsvp
```

La recomendación futura es mantener ese contrato de URL para compatibilidad de mensajes, pero rutear internamente según `rsvp_modo` y fuente (`invitados` o `pre_invitados`) sin cambiar el comportamiento cuando `rsvp_modo=codigo`.

## Riesgos de mezclar IDs

- `invitados.id` y `pre_invitados.id` pueden tener los mismos valores.
- `invitados_tel.id` y `pre_invitados_tel.id` pueden colisionar al registrarse en colas compartidas.
- `invitados_a_enviar`, `invitados_enviados`, `registro_mensajes_enviados` e `invitaciones_estado` necesitan discriminador de fuente antes de unificar escritura.
- Un modal o procesador que reciba solo `id` no puede inferir de forma segura la tabla de origen.

## Riesgos de base sin tablas `pre_`

- No debe ser fatal error en bases activas de Oro Código.
- Herramientas de diagnóstico deben informar ausencia como advertencia.
- Cualquier futura activación de `rsvp_modo=form` debe validar existencia de tablas o proveer fallback explícito.

## Riesgos para RSVP código actual

- Cambiar `index.php`, `confirmacion_modal.php`, `procesar_confirmacion.php` o `confirmar_asistencia.php` sin matriz de compatibilidad puede romper búsquedas por código.
- Aplicar `pre_invitados` al front activo podría impedir confirmar invitados reales.
- Cambiar `rsvp_modo` en configuración alteraría comportamiento visible y queda fuera de UNI-008.

## Riesgos para WhatsApp actual

- El WhatsApp activo usa `invitados*`; apuntarlo a `pre_` accidentalmente cambiaría destinatarios.
- Ejecutar scripts Node o WhatsApp durante esta caracterización podría enviar mensajes reales.
- La fuente `fuente_envios_whatsapp` debe seguir en `invitados` hasta un PR específico de migración/adaptador.

## Recomendación futura para aplicar `rsvp_modo=form`

1. Agregar un selector interno de flujo que preserve `codigo` como default.
2. Implementar adaptadores read/write separados para `invitados*` y `pre_invitados*`.
3. Agregar discriminador de fuente en colas/registros compartidos antes de enviar desde `pre_`.
4. Validar existencia de tablas `pre_` antes de activar `form` en una instalación.
5. Mantener links `?busqueda=CODIGO#rsvp` y probar ambos modos.
6. Ejecutar checklist UNI-008 y checklist de regresión actual antes de habilitar en producción.
