> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Flujos RSVP y WhatsApp

## Flujo RSVP por código

1. Invitado llega al front con código o lo ingresa en RSVP.
2. `index.php` y/o `confirmacion_modal.php` muestran búsqueda/confirmación.
3. `confirmar_asistencia.php` valida datos de invitado.
4. `procesar_confirmacion.php` persiste respuesta.
5. Admin consulta estado desde `admin_tmp/invitados.php`, `admin_tmp/invitados_basico.php`, dashboard y exportaciones.

Tablas leídas: `invitados`, `invitados_listado_mesa`, `invitados_tel`, catálogos de prioridad/acompañante.  
Tablas escritas: `invitados` y posiblemente integrantes/listado según edición.  
Lo que ve el invitado: confirmación asociada a código.  
Lo que ve el admin: invitado con estado confirmado/no/pendiente y teléfonos/envíos.

## Flujo RSVP por formulario básico

1. Invitado abre front de formulario (`basico_form/index.php`).
2. El enlace puede traer `?busqueda={{codigo}}#rsvp` desde WhatsApp.
3. Modal/formulario reutiliza archivos comunes pero `procesar_confirmacion.php` difiere.
4. Admin usa estructuras normales salvo que se conecte con preinvitación externa.

Tablas leídas/escritas: principalmente `invitados*` en la referencia PHP básica form.  
Riesgo: el WhatsApp form externo usa `pre_`, por lo que el puente entre preinvitación y confirmación debe definirse explícitamente.

## Flujo RSVP por formulario oro

1. Front `oro_form/index.php` comparte hash con `basico_form/index.php`, pero el plan oro agrega tienda/regalos y SQL `pre_`.
2. WhatsApp form apunta a URL con `?busqueda={{codigo}}#rsvp`.
3. El usuario confirma por formulario; el sistema debe decidir si la búsqueda va contra `pre_invitados` o `invitados`.
4. Admin `oro_form` presenta diferencias en `gestionar_envios.php` e `invitados.php`.

Tablas relevantes: `pre_invitados`, `pre_invitados_listado_mesa`, `pre_invitados_tel`, además de `invitados*` para confirmación final si existe promoción/manual.  
Recomendación: configurar `rsvp_modo=form` y `fuente_envios_whatsapp=pre_invitados`, sin fusionar tablas.

## Flujo WhatsApp con invitados normales

Herramienta: `docs/referencia_planes/dqs envios invitaciones_codigo/`.

Pasos:

1. Panel local carga plantillas plural/singular.
2. `app.js` expone endpoints locales y configuración.
3. `whatsapp.js` o lógica del servidor consulta `invitados_a_enviar` y une con `invitados`, `invitados_tel`, `invitados_listado_mesa`, `cliente`.
4. Genera mensaje con variables como `{{invitados}}` y `{{codigo}}`.
5. Envía por WhatsApp y registra en `invitados_enviados` y `registro_mensajes_enviados`.

Tablas lee: `invitados_a_enviar`, `invitados`, `invitados_tel`, `invitados_listado_mesa`, `cliente`.  
Tablas escribe: `invitados_enviados`, `registro_mensajes_enviados`; también puede limpiar `invitados_a_enviar`.

## Flujo WhatsApp con `pre_invitados`

Herramienta: `docs/referencia_planes/dqs envios invitaciones_form/`.

Pasos equivalentes al flujo código, pero la fuente cambia:

- Lee `pre_invitados`, `pre_invitados_tel`, `pre_invitados_listado_mesa`.
- Mantiene salida en `invitados_enviados` y `registro_mensajes_enviados`.
- Usa la misma forma de URL con `?busqueda={{codigo}}#rsvp`.

## WhatsApp PHP embebido en admin

En apps PHP existe `admin_tmp/whatsapp/envio_invitaciones.php` y `reenvio_invitaciones_erroneas.php`. Este flujo registra `invitaciones_estado`, usa API WhatsApp por cURL y depende de configuración local. Debe mantenerse separado del flujo Node hasta documentar equivalencias.

## Selector recomendado futuro

| Configuración | Front | Fuente RSVP | WhatsApp |
|---|---|---|---|
| `basico + codigo + ninguno` | Front básico código | `invitados` | deshabilitado |
| `basico + form + pre_invitados` | Front form básico | `pre_invitados` para envío; confirmar según regla | Node form/pre_ |
| `oro + codigo + invitados` | Front oro código + regalos | `invitados` | Node código o PHP admin |
| `oro + form + pre_invitados` | Front oro form + regalos | `pre_invitados`/puente | Node form/pre_ |

## Qué ve cada actor

- Invitado código: código de invitación y confirmación directa.
- Invitado form: formulario prellenado/buscado por código de URL.
- Admin normal: lista `invitados`, estados RSVP y teléfonos.
- Admin pre_: cola/carga previa para envíos y posterior seguimiento.
- Admin oro: además ve regalos, productos vendidos, carrito y configuración de regalo libre.
