# DQS UNI-034 — Checklist final de activación RSVP formulario por cliente

UNI-034 documenta el procedimiento seguro para activar el RSVP por formulario web en un cliente real. Es una guía operativa final: no requiere cambios de base de datos, no borra datos y no cambia comportamiento funcional del sistema.

## Alcance y reglas de seguridad

Esta guía asume que el RSVP por formulario ya fue implementado y validado en las etapas previas:

- Configuración admin de modo RSVP.
- Configuración admin de opciones del formulario.
- Formulario público unificado.
- Validación backend de adultos, menores, límites y alimentos.
- Persistencia final en `invitados`, `invitados_listado_mesa` e `invitados_tel`.
- Teléfono opcional.
- `pre_*` no se usa como destino final de confirmación.

Reglas obligatorias para la activación:

- No modificar estructura de DB.
- No borrar datos.
- No tocar WhatsApp.
- No tocar regalos.
- No tocar tienda.
- No activar persistencia real desde un PR o despliegue automático.
- Mantener configuración segura por defecto hasta que el cliente autorice la activación.

## 1. Estado seguro recomendado

Antes de cualquier prueba o activación, el sistema debe quedar en modo seguro:

```text
rsvp_modo=codigo
rsvp_form_persist_enabled=0
```

Significado:

- `rsvp_modo=codigo`: mantiene activo el flujo clásico por código y evita que el formulario sea el flujo principal de confirmación.
- `rsvp_form_persist_enabled=0`: evita escritura final real desde el formulario, aunque existan endpoints y validaciones disponibles.

Este es el estado recomendado para desarrollo, staging, revisión de PR, despliegue inicial y cualquier cliente que todavía no haya autorizado la activación del formulario.

## 2. Cómo activar formulario desde admin

URL de administración:

```text
admin7WZiwEM3XY/index.php?new=rsvp_config
```

Pasos:

1. Ingresar al admin del cliente.
2. Abrir la pantalla de configuración RSVP con la URL anterior.
3. Elegir **Confirmación por formulario web** como modo RSVP.
4. Revisar todas las opciones del formulario antes de guardar.
5. Confirmar explícitamente el checkbox de **guardado real** o persistencia real del formulario.
6. Guardar la configuración.
7. Ejecutar la checklist posterior a la activación incluida en este documento.

Importante: activar solo el modo formulario no debe considerarse suficiente para operar en producción si el guardado real no fue confirmado. La activación operativa requiere modo formulario y persistencia real habilitada de forma consciente.

## 3. Cómo configurar opciones del formulario

Desde la misma pantalla de configuración RSVP se deben revisar estas opciones:

- Acompañantes adultos: **Sí/No**.
- Límite adultos.
- Menores: **Sí/No**.
- Límite menores.
- Restricción alimentaria: **Sí/No**.
- Teléfono visible: **Sí/No**.
- Mensaje general: **Sí/No**.

No copiar configuraciones entre clientes sin validarlas contra la invitación, el contrato comercial y la capacidad real del evento.

## 4. Qué significa cada opción

### Acompañantes adultos

Define si el invitado principal puede cargar acompañantes adultos desde el formulario.

- **Sí**: se muestra la sección de acompañantes adultos y se permite cargar personas adicionales dentro del límite configurado.
- **No**: no se permite cargar acompañantes adultos desde el formulario.

### Límite adultos

Cantidad máxima de acompañantes adultos permitidos por confirmación de formulario. Debe configurarse según la capacidad real del evento y la regla acordada con el cliente.

Ejemplo: si el límite es `2`, el invitado principal puede cargar hasta dos acompañantes adultos adicionales.

### Menores

Define si el formulario permite informar menores asociados a la confirmación.

- **Sí**: se muestra la sección de menores y se validan los datos contra el límite configurado.
- **No**: no se permite cargar menores desde el formulario.

### Límite menores

Cantidad máxima de menores permitidos por confirmación. Debe coincidir con la política del evento y evitar sobrecupos.

### Restricción alimentaria

Define si el formulario solicita información de alimentos, dietas especiales, alergias o restricciones alimentarias.

- **Sí**: se muestra el campo/sección correspondiente y la información se persiste junto con la confirmación cuando la persistencia real está habilitada.
- **No**: no se solicita ni se guarda información alimentaria desde el formulario.

### Teléfono visible

Define si el campo de teléfono se muestra en el formulario público.

- **Sí**: el invitado puede informar teléfono. El teléfono es opcional salvo que otra configuración indique lo contrario.
- **No**: el campo no se muestra y no se guarda teléfono desde el formulario.

### Mensaje general

Define si el formulario permite capturar un comentario o mensaje general del invitado.

- **Sí**: se muestra el campo de mensaje/comentario.
- **No**: no se solicita mensaje adicional.

## 5. Checklist antes de activar

Completar esta checklist antes de habilitar formulario con persistencia real en un cliente:

- [ ] Verificar que los datos del evento estén cargados y correspondan al cliente correcto.
- [ ] Verificar fecha, nombres, textos públicos, estética y datos visibles de la invitación.
- [ ] Verificar que RSVP por código no se esté usando en una campaña activa, envío activo o comunicación ya enviada a invitados.
- [ ] Confirmar con el cliente si se permitirán acompañantes adultos.
- [ ] Confirmar el límite de acompañantes adultos.
- [ ] Confirmar si se permitirán menores.
- [ ] Confirmar el límite de menores.
- [ ] Confirmar si se solicitarán restricciones alimentarias.
- [ ] Confirmar si el teléfono debe estar visible.
- [ ] Confirmar si el mensaje general debe estar visible.
- [ ] Verificar que el formulario visual se vea correcto en desktop y mobile.
- [ ] Probar una confirmación en modo persistencia apagada si corresponde.
- [ ] Revisar que la prueba sin persistencia no haya insertado registros finales.
- [ ] Registrar quién autorizó la activación y cuándo.

## 6. Checklist después de activar

Después de activar `rsvp_modo=form` y `rsvp_form_persist_enabled=1`, realizar una prueba controlada:

- [ ] Hacer una prueba real controlada con datos trazables, por ejemplo un nombre claramente identificable como prueba.
- [ ] Revisar que se haya creado el registro esperado en `invitados`.
- [ ] Revisar que se hayan creado las filas esperadas en `invitados_listado_mesa`, incluyendo titular y acompañantes si aplica.
- [ ] Revisar `invitados_tel` si el teléfono está visible y fue cargado en la prueba.
- [ ] Revisar que `pre_*` siga en `0` para esa prueba si no se usa staging.
- [ ] Confirmar que `ingreso=form_public` aparezca como origen de la confirmación.
- [ ] Verificar que los límites de adultos y menores se respeten en una prueba negativa controlada si el cliente lo requiere.
- [ ] Documentar el resultado de la prueba y cualquier dato de prueba que deba limpiarse manualmente.

## 7. Comandos útiles

### Ver configuración efectiva del proveedor

```bash
php tools/dqs_provider_config.php --show
```

Validar especialmente:

```text
rsvp_modo
rsvp_form_persist_enabled
```

### Ver estado de persistencia final del formulario

```bash
php tools/dqs_rsvp_form_final_persistence_probe.php --status
```

En estado seguro debería reportar que no persistiría por modo o por persistencia deshabilitada.

### Consulta de conteos finales

Usar una consulta equivalente a la siguiente en la base del cliente, ajustando nombres de columnas solo si el schema real lo requiere:

```sql
SELECT 'invitados' AS tabla, COUNT(*) AS total FROM invitados
UNION ALL
SELECT 'invitados_listado_mesa' AS tabla, COUNT(*) AS total FROM invitados_listado_mesa
UNION ALL
SELECT 'invitados_tel' AS tabla, COUNT(*) AS total FROM invitados_tel;
```

### Consulta de últimos registros `form_public`

```sql
SELECT id, nombre, apellido, confirmacion, confirmacion_fecha, ingreso
FROM invitados
WHERE ingreso = 'form_public'
ORDER BY id DESC
LIMIT 20;
```

Si el schema no tiene alguna columna listada, adaptar la consulta sin modificar datos.

### Consulta de detalle para una prueba controlada

```sql
SELECT id, nombre, apellido, confirmacion, ingreso
FROM invitados
WHERE ingreso = 'form_public'
ORDER BY id DESC
LIMIT 5;
```

Luego revisar personas asociadas:

```sql
SELECT *
FROM invitados_listado_mesa
WHERE id_invitados = <ID_DE_PRUEBA>;
```

Y teléfono, si corresponde:

```sql
SELECT *
FROM invitados_tel
WHERE id_invitados = <ID_DE_PRUEBA>;
```

### Consulta de staging `pre_*` para la prueba

Si el cliente no usa staging para este flujo, los conteos relacionados con la prueba controlada deberían permanecer en `0`. Adaptar filtros según columnas disponibles:

```sql
SELECT COUNT(*) AS total_pre_invitados_form_public
FROM pre_invitados
WHERE ingreso = 'form_public';
```

Si `pre_invitados` no tiene columna `ingreso`, verificar por nombre/apellido de prueba o dejar asentado que el schema no permite ese filtro directo.

## 8. Cómo volver a modo seguro

### Desde admin

1. Ingresar a:

   ```text
   admin7WZiwEM3XY/index.php?new=rsvp_config
   ```

2. Cambiar el modo a **Confirmación por código**.
3. Desmarcar el checkbox de guardado real del formulario si está disponible.
4. Guardar.
5. Verificar configuración efectiva.

Estado esperado:

```text
rsvp_modo=codigo
rsvp_form_persist_enabled=0
```

### Comando CLI equivalente

Usar el CLI de configuración del proveedor para dejar explícitamente el modo seguro. La sintaxis exacta puede variar según la versión del helper; verificar ayuda antes de ejecutar en un cliente real:

```bash
php tools/dqs_provider_config.php --help
```

Ejemplo esperado de operación equivalente:

```bash
php tools/dqs_provider_config.php --set rsvp_modo=codigo rsvp_form_persist_enabled=0 --apply
```

Después de ejecutar, confirmar:

```bash
php tools/dqs_provider_config.php --show
php tools/dqs_rsvp_form_final_persistence_probe.php --status
```

## 9. Riesgos conocidos

- Si el teléfono está oculto, no se guarda teléfono desde el formulario.
- Si el teléfono es opcional, no todos los invitados tendrán teléfono cargado y no todos podrán ser contactados por WhatsApp usando ese dato.
- Las confirmaciones por formulario aparecen con `ingreso=form_public` en reportes y consultas como origen de carga.
- Los datos de prueba insertados con persistencia real deben limpiarse manualmente si se desea; esta guía no incluye borrado automático.
- Activar formulario mientras una campaña por código está activa puede generar orígenes mezclados de confirmación.
- Límites de adultos o menores mal configurados pueden permitir menos o más personas de las esperadas por el cliente.

## Cierre operativo

Para considerar activado el RSVP formulario en un cliente real:

- El cliente debe haber autorizado modo formulario y guardado real.
- La configuración efectiva debe haber sido revisada.
- La prueba real controlada debe haber insertado en `invitados*` según lo esperado.
- El origen `ingreso=form_public` debe verse correctamente.
- `pre_*` debe seguir sin uso como destino final del formulario, salvo que exista un flujo de staging separado y documentado.
- Cualquier dato de prueba debe quedar identificado para limpieza manual si el cliente lo solicita.

UNI-034 no cambia código funcional ni activa persistencia real por sí misma; solo deja documentado el procedimiento seguro de activación.
