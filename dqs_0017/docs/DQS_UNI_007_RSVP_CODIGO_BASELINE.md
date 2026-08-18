# UNI-007 — Baseline RSVP actual por código

## Resumen ejecutivo

UNI-007 deja caracterizado el RSVP actual por código **sin cambiar comportamiento visible**. El front activo entra por `index.php`, busca un código en `invitados.codigo`, abre un modal remoto con `confirmacion_modal.php`, envía el formulario por AJAX a `procesar_confirmacion.php` y persiste el estado en `invitados` y `invitados_listado_mesa`.

La configuración guardada actualmente (`plan_servicio=oro`, `rsvp_modo=codigo`, `fuente_envios_whatsapp=invitados`, `whatsapp_enabled=1`, `regalos_enabled=1`) coincide con este flujo, pero esta PR **no aplica todavía `rsvp_modo` al front**. El objetivo es dejar una línea base para futuros PRs.

## Archivos relevados

- `index.php`: entrada pública, búsqueda por código, render de resultado, apertura de modal y AJAX.
- `confirmacion_modal.php`: consulta read de invitado e integrantes, HTML del modal y JS interno de selección/restricciones.
- `procesar_confirmacion.php`: endpoint JSON que valida y actualiza confirmación.
- `confirmar_asistencia.php`: flujo legacy independiente por `id`/código.

## Flujo principal moderno con modal/JSON

1. El usuario llega a `/index.php#rsvp` o a `/index.php?busqueda=CODIGO#rsvp`.
2. La sección RSVP muestra un formulario GET con el campo `busqueda`.
3. Si `busqueda` no está vacía, `index.php` consulta `invitados` e `invitados_listado_mesa` filtrando `a.codigo='$busqueda'` y `activo=1`.
4. Si el código existe:
   - Si `invitados.confirmacion = 'Si'`, se muestra tarjeta de asistencia confirmada, invitados, mayores y menores guardados, más botón “Modificar Asistencia”.
   - Para otros estados, se muestra tabla con invitación y link “Confirmar”.
5. El link con clase `.confirmar-modal-btn` carga `confirmacion_modal.php?codigo=CODIGO` dentro de `#confirmacionModal .modal-content` y luego abre Bootstrap modal.
6. El formulario del modal postea por AJAX a `procesar_confirmacion.php` y espera JSON.
7. En éxito, el modal muestra mensaje, oculta formulario e intro. Al cerrar modal, `index.php` redirige a `index.php?busqueda=CODIGO&_nocache=TIMESTAMP#rsvp` para refrescar estado.

## Entradas soportadas

### `/index.php#rsvp`

Carga la home y posiciona al usuario en la sección RSVP. Sin `busqueda`, no se ejecuta búsqueda de código.

### `/index.php?busqueda=CODIGO#rsvp`

Carga la home, rellena el input con el valor recibido y ejecuta la consulta del código. El código se normaliza con `strtolower()` antes de interpolarse en la SQL actual.

## Cómo se busca el código

- En el front activo, `index.php` toma `$_REQUEST['busqueda']`, lo pasa a minúsculas y consulta `invitados` (`a.codigo`) con `activo=1`.
- El query compone nombres visibles con agregaciones sobre `invitados_listado_mesa`.
- Si no hay resultados, muestra “No se encontró el código. Por favor, verifica en la invitación.”
- En el modal, `confirmacion_modal.php` toma `$_GET['codigo']`, usa prepared statement y busca `invitados.codigo = ? AND activo = 1`.

## Cómo se abre el modal

El resultado de búsqueda renderiza links con `data-codigo`. El JS de `index.php` intercepta click, guarda `lastSearchCode`, carga `confirmacion_modal.php?codigo=...` por jQuery `.load()` y llama a `$('#confirmacionModal').modal('show')`.

## Qué muestra el modal

El modal muestra:

- título “Confirmar Asistencia”;
- saludo con integrantes calculados desde `invitados_listado_mesa`;
- código de invitación;
- select `confirmar_asistencia` con valores `Si` y `No`, preseleccionado si ya existe confirmación;
- para un solo integrante, un input hidden `seleccionados[]` y bloque de restricción alimentaria para esa persona;
- para varios integrantes, checklist `seleccionados[]` por integrante, badge “Menor” si `es_menor=1`, select de alimento y textarea de aclaración por persona;
- botón de envío y botón cerrar.

## Qué envía el formulario

El formulario `#formConfirmacion` usa método POST hacia `procesar_confirmacion.php` y serializa:

- `codigo_invitado`;
- `confirmar_asistencia` (`Si` o `No`);
- `seleccionados[]` con IDs de `invitados_listado_mesa` seleccionados;
- `alimento_persona[ID]` por integrante habilitado;
- `comentario_persona[ID]` por integrante habilitado.

## Qué recibe `procesar_confirmacion.php`

El endpoint espera POST y valida:

- `codigo_invitado` no vacío;
- `confirmar_asistencia` con valor `Si` o `No`;
- conexión disponible;
- existencia de `invitados.codigo`;
- si confirma `Si`, al menos un integrante seleccionado.

Luego abre transacción, actualiza integrantes y resumen, confirma o revierte según resultado, y devuelve JSON.

## Qué JSON devuelve

Respuesta base:

```json
{"success": false, "message": ""}
```

En confirmación `No` exitosa:

```json
{"success": true, "message": "Lástima que no vas a poder asistir 😢 (Igual, podés cambiar de opinión más adelante)"}
```

En confirmación `Si` exitosa:

```json
{
  "success": true,
  "message": "¡Tu asistencia ha sido confirmada con éxito! 🎉",
  "data": {
    "codigo": "CODIGO",
    "mayores": 0,
    "menores": 0,
    "confirmacion": "Si"
  }
}
```

En errores devuelve `success=false` y `message` con el motivo actual.

## Tablas y campos

### Tablas leídas

- `invitados`: `id`, `codigo`, `activo`, `nombre`, `apellido`, `cantidad_mayores`, `cantidad_menores`, `confirmacion`, `confirmacion_mayores`, `confirmacion_menores`, `alimento`, `confirmacion_comentario`, `ingreso`, `acompanado`, `id_prioridad`.
- `invitados_listado_mesa`: `id`, `id_invitados`, `nombre_invitado`, `es_menor`, `asiste`, `alimento`, `alimento_comentario`.
- En queries legacy/actuales también aparecen joins a `intivados_acompanante` e `invitados_prioridad` para datos auxiliares.

### Tablas escritas

- `procesar_confirmacion.php` escribe `invitados_listado_mesa` y `invitados`.
- `confirmar_asistencia.php` legacy escribe solo `invitados`.
- `index.php` y `confirmacion_modal.php` no deben escribir RSVP.

### Campos escritos por el flujo moderno

En `invitados_listado_mesa`:

- `asiste`;
- `confirm_date`;
- `alimento`;
- `alimento_comentario`.

En `invitados`:

- `confirmacion`;
- `confirmacion_mayores`;
- `confirmacion_menores`;
- `alimento`;
- `confirmacion_comentario`;
- `confirmacion_fecha`.

## Cómo calcula mayores/menores

En el flujo moderno, los máximos del modal vienen de `invitados.cantidad_mayores` y `invitados.cantidad_menores`, pero el resultado guardado se calcula desde integrantes seleccionados:

- mayor: `asiste=1 AND es_menor=0`;
- menor: `asiste=1 AND es_menor=1`.

Esos totales se guardan en `invitados.confirmacion_mayores` y `invitados.confirmacion_menores`.

## Cómo maneja integrantes

- Grupo de una persona: el modal siempre envía el único ID en `seleccionados[]`; los controles de alimento se habilitan según estado visual.
- Grupo de varias personas: el usuario marca checkboxes; antes de aplicar una confirmación `Si`, el endpoint resetea todos los integrantes del invitado y luego marca únicamente los IDs recibidos.
- Al confirmar `No`, todos los integrantes quedan con `asiste=0`, alimento `No` y comentario `NULL`.

## Cómo maneja restricciones alimentarias

Cada integrante asistente puede tener `alimento` (`No`, `Vegetariano`, `Vegano`, `Celiaco`, `Otro`) y comentario. Si alimento es `No`, el comentario se guarda como `NULL`. Para el resumen en `invitados`, si hay restricciones de asistentes, se guarda:

- `alimento = 'Ver detalle por invitado'`;
- `confirmacion_comentario` con detalle concatenado por persona.

Si no hay restricciones, el resumen queda `alimento='No'` y comentario vacío.

## Confirmación “Sí”

- Requiere al menos un integrante en `seleccionados[]`.
- Resetea integrantes del grupo.
- Marca asistentes seleccionados y sus restricciones.
- Recalcula mayores/menores desde `invitados_listado_mesa`.
- Actualiza resumen en `invitados` con `confirmacion='Si'`.
- Devuelve JSON con `data.codigo`, `data.mayores`, `data.menores` y `data.confirmacion`.

## Confirmación “No”

- No requiere selección de integrantes.
- Pone todos los integrantes del grupo en `asiste=0` y limpia restricciones.
- Actualiza resumen en `invitados` con `confirmacion='No'`, mayores/menores 0 y alimento `No`.
- Devuelve JSON exitoso sin bloque `data`.

## Estado al reabrir modal

Al reabrir, `confirmacion_modal.php` lee nuevamente `invitados` e `invitados_listado_mesa`. Por eso muestra:

- select `Si`/`No` preseleccionado según `invitados.confirmacion`;
- checkboxes pre-marcados según `invitados_listado_mesa.asiste`;
- alimento y comentario por integrante según valores actuales;
- resumen de mayores/menores desde `invitados` en la tarjeta de `index.php` cuando la confirmación es `Si`.

## Flujo legacy `confirmar_asistencia.php`

El legacy recibe `id` por request, busca el código en `invitados.codigo` y renderiza un formulario completo. En POST:

- Si `entrada='No'`, actualiza `invitados` con confirmación `No`, mayores/menores 0, comentario y alimento enviados.
- Si `entrada!='No'`, exige `mayores`, toma `menores` opcional, y actualiza `invitados` con esos valores.
- No actualiza `invitados_listado_mesa.asiste` ni restricciones por integrante.
- Usa SQL interpolado y no devuelve JSON; muestra HTML/alertas.

Este flujo existe como compatibilidad histórica y debe tratarse como superficie separada antes de aplicar `rsvp_modo`.

## Riesgos del flujo actual

- `index.php` y `confirmar_asistencia.php` interpolan valores de request en SQL.
- Hay dos fuentes de verdad parciales: resumen en `invitados` e integrantes en `invitados_listado_mesa`.
- El legacy puede desincronizar resumen e integrantes porque no escribe `invitados_listado_mesa`.
- El cálculo moderno depende de `es_menor` por integrante; datos faltantes o inconsistentes impactan totales.
- Cambios en nombres de campos o IDs pueden romper serialización AJAX.
- Cambios en textos JSON pueden afectar JS actual o pruebas manuales.
- El cierre de modal fuerza recarga con `_nocache`; modificarlo puede dejar estado viejo visible.

## Riesgos de tocarlo en futuros PRs

- Aplicar `rsvp_modo` en `index.php` sin preservar `codigo` puede romper el plan oro actual.
- Reemplazar consultas por `GuestSource` directamente en front puede cambiar nombres, orden o estructura visible.
- Mezclar `pre_invitados` en producción sin adaptador puede escribir tablas incorrectas.
- Unificar legacy y moderno sin migración de datos puede perder estado por integrante.
- Cambiar validaciones de `Si`/`No` puede confirmar asistencia automáticamente o impedir modificaciones válidas.

## Recomendaciones para aplicar `rsvp_modo` en el futuro

1. Mantener `rsvp_modo=codigo` como default equivalente al comportamiento actual.
2. Encapsular selección de modo antes de tocar consultas visibles.
3. Agregar tests/manual checklist por URL `/index.php#rsvp` y `/index.php?busqueda=CODIGO#rsvp`.
4. No activar `pre_invitados` en producción hasta tener adaptador read/write explícito.
5. Preservar los nombres POST y formato JSON mientras no exista versión nueva del front.
6. Tratar `confirmar_asistencia.php` como flujo legacy separado, con criterio de deprecación documentado.
7. Antes de cualquier cambio de escritura, comparar resumen vs integrantes con una herramienta read-only.

## Herramienta CLI read-only

Se agregó `tools/dqs_rsvp_codigo_probe.php` para inspección manual read-only:

```bash
php tools/dqs_rsvp_codigo_probe.php --help
php tools/dqs_rsvp_codigo_probe.php --codigo=CODIGO
```

La herramienta bloquea navegador con `CLI only`, usa `GuestSource` solo para lectura de `invitados`, no muestra teléfonos ni secretos y advierte diferencias entre resumen e integrantes.
