# UNI-007 — Checklist manual RSVP por código

> Checklist para validar que el baseline por código sigue igual. No ejecutar confirmaciones sobre invitados reales durante PRs documentales; usar código de prueba controlado.

## Preparación

- [ ] Confirmar que el entorno apunta al admin activo `admin7WZiwEM3XY/` y no a `admin_tmp`.
- [ ] Confirmar que existe un `CODIGO_DE_PRUEBA` seguro para pruebas no destructivas.
- [ ] Confirmar configuración esperada: `plan_servicio=oro`, `rsvp_modo=codigo`, `fuente_envios_whatsapp=invitados`, `whatsapp_enabled=1`, `regalos_enabled=1`.
- [ ] No ejecutar migraciones, alter tables, scripts WhatsApp, Node ni instaladores.

## Home y búsqueda

- [ ] Home carga correctamente en `/index.php`.
- [ ] Entrada directa `/index.php#rsvp` posiciona en la sección “Confirmar Asistencia”.
- [ ] Buscar código válido muestra la invitación correspondiente.
- [ ] Buscar código inexistente muestra “No se encontró el código. Por favor, verifica en la invitación.”
- [ ] Link `/index.php?busqueda=CODIGO#rsvp` rellena la búsqueda y muestra el resultado.

## Modal moderno

- [ ] Abrir modal desde “Confirmar” o “Modificar Asistencia”.
- [ ] Modal muestra saludo, código de invitación y select `Confirmo asistencia`.
- [ ] Grupo de una persona muestra la persona y restricción alimentaria individual.
- [ ] Grupo de varias personas muestra checklist por integrante.
- [ ] Los menores se identifican con badge “Menor”.
- [ ] Seleccionar alimento distinto de `No` muestra textarea de aclaración.
- [ ] Al volver alimento a `No`, la aclaración se oculta/limpia visualmente según comportamiento actual.

## Confirmación “Sí” con código de prueba

- [ ] Confirmar “Sí” exige al menos una persona seleccionada.
- [ ] Confirmar “Sí” en grupo de una persona guarda un asistente.
- [ ] Confirmar “Sí” en grupo de varias personas guarda solo integrantes seleccionados.
- [ ] El JSON de éxito muestra mensaje “¡Tu asistencia ha sido confirmada con éxito! 🎉”.
- [ ] El mensaje visible incluye mayores y menores devueltos por JSON.
- [ ] Validar conteo de mayores/menores contra `es_menor` de integrantes seleccionados.
- [ ] Validar restricciones alimentarias por integrante y resumen.
- [ ] Cerrar modal recarga a `index.php?busqueda=CODIGO&_nocache=...#rsvp`.
- [ ] Reabrir modal y ver estado preseleccionado/checkeado.

## Confirmación “No” con código de prueba

- [ ] Confirmar “No” no exige integrantes seleccionados.
- [ ] Confirmar “No” devuelve mensaje “Lástima que no vas a poder asistir 😢 (Igual, podés cambiar de opinión más adelante)”.
- [ ] Reabrir modal muestra `No` preseleccionado.
- [ ] Integrantes quedan sin asistencia y sin restricciones alimentarias activas.
- [ ] Mayores/menores guardados quedan en 0/0.

## Regresiones fuera de alcance

- [ ] Validar que tienda no cambia.
- [ ] Validar que admin activo no cambia.
- [ ] Validar que WhatsApp no cambia ni se ejecuta.
- [ ] Validar que regalos no cambia.
- [ ] Validar que no se tocó `admin_tmp`.
- [ ] Validar que no se ejecutó Node.
- [ ] Validar que no se modificaron datos reales fuera del código de prueba.

## Probe read-only opcional

- [ ] `php tools/dqs_rsvp_codigo_probe.php --help` muestra uso.
- [ ] `php tools/dqs_rsvp_codigo_probe.php --codigo=CODIGO_DE_PRUEBA` informa existencia, ID, confirmación, integrantes, asistentes, mayores/menores calculados, mayores/menores guardados, restricciones y advertencias.
- [ ] Confirmar que el probe no muestra teléfonos, credenciales, tokens ni secretos.
