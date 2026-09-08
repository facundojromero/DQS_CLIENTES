# DQS UNI-008 — Checklist futuros PRs RSVP formulario / pre_invitados

## Configuración y alcance

- [ ] Confirmar configuración actual en `site_settings`: `plan_servicio`, `rsvp_modo`, `fuente_envios_whatsapp`, `whatsapp_enabled`, `regalos_enabled`.
- [ ] Confirmar que el PR declara explícitamente si activa o no `rsvp_modo=form`.
- [ ] Confirmar que el admin activo sigue siendo `admin7WZiwEM3XY/`.
- [ ] Confirmar que `admin_tmp` no fue modificado.

## Tablas `pre_`

- [ ] Confirmar si existen `pre_invitados`, `pre_invitados_listado_mesa` y `pre_invitados_tel`.
- [ ] Confirmar que la ausencia de tablas `pre_` no rompe home, RSVP por código, tienda, admin, regalos ni WhatsApp activo.
- [ ] Confirmar campos detectados antes de asumir FK o columnas de confirmación.
- [ ] Confirmar que no se crean ni alteran tablas en PRs de caracterización.

## URL y búsqueda

- [ ] Confirmar que el link `?busqueda=CODIGO#rsvp` se conserva en mensajes y pruebas.
- [ ] Confirmar búsqueda por código form contra la fuente definida para el modo form.
- [ ] Confirmar que códigos de `pre_invitados` no se confunden con códigos de `invitados`.
- [ ] Confirmar que URLs existentes de Oro Código siguen resolviendo igual.

## RSVP actual por código

- [ ] Confirmar que no se rompe RSVP código.
- [ ] Confirmar que `index.php` conserva comportamiento cuando `rsvp_modo=codigo`.
- [ ] Confirmar que buscar código abre modal.
- [ ] Confirmar que confirmar/no confirmar no mezcla cabecera y detalle de otra fuente.

## IDs y fuentes

- [ ] Confirmar que no se mezclan IDs entre `invitados` y `pre_invitados`.
- [ ] Confirmar que teléfonos de `pre_invitados_tel` no se registran como si fueran `invitados_tel` sin discriminador.
- [ ] Confirmar que colas/registros compartidos tienen fuente explícita antes de usarse con `pre_`.

## WhatsApp

- [ ] Confirmar que WhatsApp con `pre_` no se ejecuta accidentalmente.
- [ ] Confirmar que `fuente_envios_whatsapp` no cambia salvo PR dedicado.
- [ ] Confirmar que no se ejecutan scripts Node ni envíos PHP durante pruebas de caracterización.
- [ ] Confirmar que no se imprimen teléfonos completos en logs o herramientas.

## Superficies no relacionadas

- [ ] Confirmar que admin activo no cambia.
- [ ] Confirmar que regalos/tienda no cambian.
- [ ] Confirmar que home carga.
- [ ] Confirmar que tienda carga.
- [ ] Confirmar que WhatsApp activo sigue igual.
- [ ] Confirmar que regalos sigue igual.

## Herramienta CLI read-only

- [ ] `php tools/dqs_rsvp_form_pre_probe.php --help` muestra uso.
- [ ] `php tools/dqs_rsvp_form_pre_probe.php --source=pre_invitados` informa existencia/ausencia de tablas sin fatal error.
- [ ] `php tools/dqs_rsvp_form_pre_probe.php --codigo=CODIGO --source=pre_invitados` busca solo si existen tablas `pre_`.
- [ ] Confirmar que la herramienta no crea, altera, inserta, confirma asistencia, ejecuta WhatsApp ni ejecuta Node.
