# DQS UNI-021.1 — Corrección de destino RSVP formulario

## Criterio funcional corregido

UNI-021.1 corrige el criterio dejado preparado en UNI-020: las tablas `pre_*` **no son el destino final** de una confirmación RSVP enviada por formulario.

El modelo correcto queda documentado así:

- **Plan Oro + WhatsApp + confirmación por formulario**:
  - `pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel` funcionan como prelista, staging o fuente para envíos de WhatsApp.
  - WhatsApp puede consultar `pre_*` para enviar invitaciones o links.
  - Cuando el invitado completa el formulario, la confirmación final debe guardarse en las tablas reales `invitados`, `invitados_listado_mesa` e `invitados_tel`.
- **Plan Oro sin formulario**:
  - No debe usar `pre_*` para confirmar por formulario.
  - Mantiene el flujo actual por código contra `invitados*`.
- **Plan Básico**:
  - No usa WhatsApp.
  - La confirmación por formulario debe guardar directo en `invitados`, `invitados_listado_mesa` e `invitados_tel`.

## Cambio aplicado en esta rama

La persistencia real hacia `pre_*` quedó explícitamente deshabilitada como destino final. Aunque `rsvp_form_persist_enabled` se active accidentalmente con `rsvp_modo=form`, el endpoint debe responder en modo controlado con:

- `dry_run=true`
- `persisted=false`
- `reason=persistence_target_not_finalized`

Si la configuración segura permanece en `rsvp_modo=codigo` o `rsvp_form_persist_enabled=0`, el endpoint conserva el bloqueo por configuración y devuelve `persistence_disabled_by_mode` o `persistence_feature_disabled`.

## Validación server-side obligatoria

El contrato server-side bloquea payloads incompletos antes de cualquier decisión de persistencia:

- `nombre` requerido.
- `apellido` requerido.
- `confirmacion` debe ser `Si` o `No`.
- Si `cantidad_acompanantes > 0` y `confirmacion=Si`, cada acompañante debe incluir `nombre` y `apellido`.

Esto evita repetir el caso detectado en UNI-021 donde un payload vacío podía llegar a generar un registro vacío en `pre_invitados`.

## Estado del helper experimental

`includes/rsvp_form_persistence.php` se conserva solo como helper experimental/preparado para previews y diagnósticos. No debe usarse como escritura productiva porque `pre_*` es staging/fuente para WhatsApp, no destino definitivo.

La persistencia final deberá implementarse en un próximo paso hacia:

- `invitados`
- `invitados_listado_mesa`
- `invitados_tel`

## Probe actualizado

`tools/dqs_rsvp_form_persistence_probe.php` informa explícitamente:

- `target_current=pre_*`
- `target_status=staging_only` o `deprecated_as_final_target`
- `would_persist=false`
- `reason=persistence_disabled_by_mode`, `persistence_feature_disabled`, `payload_invalid` o `persistence_target_not_finalized`

El probe sigue siendo read-only y muestra conteos de `pre_*` y `invitados*` para verificar que no aumenten durante las pruebas.

## Configuración segura requerida

La rama debe quedar con:

```text
rsvp_modo: codigo
rsvp_form_persist_enabled: 0
```

No se activó persistencia real, no se cambiaron flujos de WhatsApp y no se escribieron datos como parte de UNI-021.1.

## Continuidad en UNI-022

UNI-022 implementa el helper final hacia `invitados*` y reemplaza en el endpoint la decisión de escritura preparada contra `pre_*`. La regla de UNI-021.1 se mantiene: `pre_*` puede servir como staging/fuente para WhatsApp, pero nunca como destino final de una confirmación enviada por formulario.
