# DQS UNI-035 — Auditoría read-only de datos de prueba RSVP formulario

UNI-035 documenta la auditoría segura, **solo lectura**, de registros generados por pruebas del flujo RSVP por formulario público. El objetivo es preparar una limpieza posterior controlada, sin ejecutar cambios sobre la base de datos.

## Alcance y reglas críticas

Tablas revisadas o incluidas en el alcance de revisión:

- `invitados`
- `invitados_listado_mesa`
- `invitados_tel`
- `pre_invitados`
- `pre_invitados_listado_mesa`
- `pre_invitados_tel`

Reglas obligatorias de esta auditoría:

- No borrar datos.
- No modificar DB.
- No ejecutar `DELETE`.
- No ejecutar `UPDATE`.
- No cambiar configuración.
- No activar `rsvp_modo=form`.
- No activar `rsvp_form_persist_enabled=1`.
- No tocar código funcional.

## Estado de ejecución en este entorno

Se intentó ejecutar una auditoría read-only contra la conexión definida por la aplicación en `conexion.php`, usando únicamente consultas `SELECT`. La conexión configurada apunta a `127.0.0.1` y en este entorno no hay servicio MySQL/MariaDB escuchando, por lo que la ejecución devolvió `Connection refused` antes de consultar tablas.

Resultado operativo de UNI-035 en este entorno:

| Ítem | Resultado |
| --- | --- |
| DB modificada | No |
| Datos borrados | No |
| Configuración cambiada | No |
| `rsvp_modo=form` activado | No |
| `rsvp_form_persist_enabled=1` activado | No |
| Consultas de escritura ejecutadas | No |
| Auditoría con datos reales completada | No, bloqueada por falta de conexión DB local |

## Criterios de búsqueda para candidatos

La búsqueda debe considerar registros con `ingreso = 'form_public'` y nombres o apellidos claramente creados durante pruebas UNI, incluyendo estos patrones:

- `TestUNI%`
- `TestWEBUNI%`
- `TestUNI023%`
- `TestUNI024%`
- `TestWEBUNI025%`
- `TestUNI026%`
- `TestUNI028%`
- `Prueba%`
- `PruebaFinalUNI029%`
- `Acompanante%`
- `Menor%`
- cualquier otro nombre claramente atribuible a pruebas UNI

## Resultados detectados

Como la base de datos local no aceptó conexión, no fue posible obtener cantidades reales en este entorno. Las cantidades quedan pendientes de ejecutar en un entorno con acceso read-only a la DB del cliente o staging correspondiente.

| Tabla | Cantidad candidata detectada | Estado |
| --- | ---: | --- |
| `invitados` | Pendiente | Requiere conexión DB |
| `invitados_listado_mesa` | Pendiente | Requiere conexión DB |
| `invitados_tel` | Pendiente | Requiere conexión DB |
| `pre_invitados` | Pendiente | Requiere conexión DB |
| `pre_invitados_listado_mesa` | Pendiente | Requiere conexión DB |
| `pre_invitados_tel` | Pendiente | Requiere conexión DB |

## Listado de invitados candidatos

Pendiente de completar con datos reales luego de ejecutar la auditoría read-only en un entorno con conexión DB.

Formato esperado:

| id | nombre | apellido | codigo | confirmacion | confirmacion_fecha | fecha_registro | ingreso | clasificación |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- |
| Pendiente | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente | Pendiente | `form_public` | Pendiente |

## Teléfonos asociados en `invitados_tel`

Pendiente de completar. Deben listarse solo teléfonos asociados por `id_invitados` a invitados candidatos de prueba confirmados.

Cantidad detectada en este entorno: **pendiente por falta de conexión DB**.

## Personas asociadas en `invitados_listado_mesa`

Pendiente de completar. Deben listarse solo personas asociadas por `id_invitados` a invitados candidatos de prueba confirmados.

Cantidad detectada en este entorno: **pendiente por falta de conexión DB**.

## Estado de tablas `pre_*`

Pendiente de completar con conteos reales:

| Tabla | Conteo total | Observación |
| --- | ---: | --- |
| `pre_invitados` | Pendiente | Requiere conexión DB |
| `pre_invitados_listado_mesa` | Pendiente | Requiere conexión DB |
| `pre_invitados_tel` | Pendiente | Requiere conexión DB |

Según el diseño final del flujo RSVP formulario, la persistencia final debe ocurrir en `invitados*`; por eso cualquier dato en `pre_*` debe revisarse manualmente antes de decidir limpieza.

## Consultas read-only sugeridas para completar la auditoría

> Estas consultas son únicamente de lectura. No modifican datos.

```sql
SELECT id, nombre, apellido, codigo, confirmacion, confirmacion_fecha, fecha_registro, ingreso
FROM invitados
WHERE ingreso = 'form_public'
ORDER BY id DESC;
```

```sql
SELECT id, nombre, apellido, codigo, confirmacion, confirmacion_fecha, fecha_registro, ingreso
FROM invitados
WHERE ingreso = 'form_public'
  AND (
    nombre LIKE 'TestUNI%' OR apellido LIKE 'TestUNI%'
    OR nombre LIKE 'TestWEBUNI%' OR apellido LIKE 'TestWEBUNI%'
    OR nombre LIKE 'TestUNI023%' OR apellido LIKE 'TestUNI023%'
    OR nombre LIKE 'TestUNI024%' OR apellido LIKE 'TestUNI024%'
    OR nombre LIKE 'TestWEBUNI025%' OR apellido LIKE 'TestWEBUNI025%'
    OR nombre LIKE 'TestUNI026%' OR apellido LIKE 'TestUNI026%'
    OR nombre LIKE 'TestUNI028%' OR apellido LIKE 'TestUNI028%'
    OR nombre LIKE 'Prueba%' OR apellido LIKE 'Prueba%'
    OR nombre LIKE 'PruebaFinalUNI029%' OR apellido LIKE 'PruebaFinalUNI029%'
    OR nombre LIKE 'Acompanante%' OR apellido LIKE 'Acompanante%'
    OR nombre LIKE 'Menor%' OR apellido LIKE 'Menor%'
  )
ORDER BY id DESC;
```

```sql
SELECT lm.*
FROM invitados_listado_mesa lm
INNER JOIN invitados i ON i.id = lm.id_invitados
WHERE i.ingreso = 'form_public'
  AND (
    i.nombre LIKE 'TestUNI%' OR i.apellido LIKE 'TestUNI%'
    OR i.nombre LIKE 'TestWEBUNI%' OR i.apellido LIKE 'TestWEBUNI%'
    OR i.nombre LIKE 'TestUNI023%' OR i.apellido LIKE 'TestUNI023%'
    OR i.nombre LIKE 'TestUNI024%' OR i.apellido LIKE 'TestUNI024%'
    OR i.nombre LIKE 'TestWEBUNI025%' OR i.apellido LIKE 'TestWEBUNI025%'
    OR i.nombre LIKE 'TestUNI026%' OR i.apellido LIKE 'TestUNI026%'
    OR i.nombre LIKE 'TestUNI028%' OR i.apellido LIKE 'TestUNI028%'
    OR i.nombre LIKE 'Prueba%' OR i.apellido LIKE 'Prueba%'
    OR i.nombre LIKE 'PruebaFinalUNI029%' OR i.apellido LIKE 'PruebaFinalUNI029%'
    OR i.nombre LIKE 'Acompanante%' OR i.apellido LIKE 'Acompanante%'
    OR i.nombre LIKE 'Menor%' OR i.apellido LIKE 'Menor%'
  )
ORDER BY lm.id DESC;
```

```sql
SELECT t.*
FROM invitados_tel t
INNER JOIN invitados i ON i.id = t.id_invitados
WHERE i.ingreso = 'form_public'
  AND (
    i.nombre LIKE 'TestUNI%' OR i.apellido LIKE 'TestUNI%'
    OR i.nombre LIKE 'TestWEBUNI%' OR i.apellido LIKE 'TestWEBUNI%'
    OR i.nombre LIKE 'TestUNI023%' OR i.apellido LIKE 'TestUNI023%'
    OR i.nombre LIKE 'TestUNI024%' OR i.apellido LIKE 'TestUNI024%'
    OR i.nombre LIKE 'TestWEBUNI025%' OR i.apellido LIKE 'TestWEBUNI025%'
    OR i.nombre LIKE 'TestUNI026%' OR i.apellido LIKE 'TestUNI026%'
    OR i.nombre LIKE 'TestUNI028%' OR i.apellido LIKE 'TestUNI028%'
    OR i.nombre LIKE 'Prueba%' OR i.apellido LIKE 'Prueba%'
    OR i.nombre LIKE 'PruebaFinalUNI029%' OR i.apellido LIKE 'PruebaFinalUNI029%'
    OR i.nombre LIKE 'Acompanante%' OR i.apellido LIKE 'Acompanante%'
    OR i.nombre LIKE 'Menor%' OR i.apellido LIKE 'Menor%'
  )
ORDER BY t.id DESC;
```

```sql
SELECT COUNT(*) AS total FROM pre_invitados;
SELECT COUNT(*) AS total FROM pre_invitados_listado_mesa;
SELECT COUNT(*) AS total FROM pre_invitados_tel;
```

## Separación recomendada: prueba clara vs dudoso

Clasificar como **claramente de prueba** solo registros que cumplan todas estas condiciones:

1. `ingreso = 'form_public'`.
2. Nombre o apellido coincide con patrones UNI de prueba, por ejemplo `TestUNI%`, `TestWEBUNI%`, `PruebaFinalUNI029%`, `Acompanante%` o `Menor%`.
3. Sus filas asociadas en `invitados_listado_mesa` e `invitados_tel`, si existen, están vinculadas por `id_invitados` al mismo registro candidato.

Clasificar como **dudoso** cualquier registro con `ingreso = 'form_public'` que no tenga nombre evidentemente de prueba, aunque haya sido creado durante la ventana de pruebas. Esos registros no deben incluirse en limpieza automática sin revisión manual.

## SQL sugerido para una futura limpieza controlada

> Advertencia: el siguiente SQL queda documentado para UNI-036 o una tarea posterior. **No fue ejecutado en UNI-035**. Antes de usarlo, reemplazar la lista de IDs por IDs auditados, confirmar backup vigente y obtener aprobación manual.

```sql
-- Ejemplo: reemplazar por IDs confirmados como prueba clara.
SET @ids_prueba = 'ID1,ID2,ID3';

-- Revisar antes de borrar.
SELECT *
FROM invitados
WHERE FIND_IN_SET(id, @ids_prueba);

SELECT *
FROM invitados_listado_mesa
WHERE FIND_IN_SET(id_invitados, @ids_prueba);

SELECT *
FROM invitados_tel
WHERE FIND_IN_SET(id_invitados, @ids_prueba);

-- Limpieza futura, NO ejecutar sin aprobación manual y backup.
-- DELETE FROM invitados_tel
-- WHERE FIND_IN_SET(id_invitados, @ids_prueba);
--
-- DELETE FROM invitados_listado_mesa
-- WHERE FIND_IN_SET(id_invitados, @ids_prueba);
--
-- DELETE FROM invitados
-- WHERE FIND_IN_SET(id, @ids_prueba);
```

## Recomendación para UNI-036

Crear UNI-036 como tarea de limpieza segura únicamente después de completar esta auditoría con conexión real a DB. UNI-036 debería exigir:

- Export/backup previo de las filas candidatas.
- Lista cerrada de IDs confirmados como prueba clara.
- Revisión manual de registros dudosos.
- Transacción explícita si el motor lo permite.
- Validación posterior con conteos `SELECT`.
- Prohibición de borrar cualquier registro fuera de la lista aprobada.

## Confirmación final UNI-035

En esta ejecución:

- No se ejecutó ningún `DELETE`.
- No se ejecutó ningún `UPDATE`.
- No se cambió configuración.
- No se activó `rsvp_modo=form`.
- No se activó `rsvp_form_persist_enabled=1`.
- No se modificó código funcional.
- La DB no fue modificada.
