# UNI-045 — Auditoría de recaudación diaria para dashboard admin

## 1. Alcance, método y resultado ejecutivo

Esta es una auditoría **read-only** del admin activo `admin7WZiwEM3XY/` y del flujo
de tienda/regalos. No se modificó código funcional, configuración ni base de datos;
no se agregó el gráfico y no se inspeccionó ni tocó `admin_tmp`, RSVP o WhatsApp.

Se intentó conectar a la base configurada en `conexion.php` desde el entorno de
Codex para ejecutar exclusivamente consultas de lectura. La conexión local a MySQL
en `127.0.0.1` fue rechazada (`Connection refused`), por lo que **no hubo acceso a
datos reales**, no se ejecutó ningún `SHOW`, `DESCRIBE` o `SELECT` contra la base y
no se pueden confirmar tipos, defaults, índices, cardinalidades, rango temporal ni
calidad de datos del ambiente Hostinger. Las conclusiones de estructura que siguen
son inferencias verificables del código activo.

**Conclusión principal:** hoy una recaudación real se representa por la unión de una
cabecera activa en `regalos` con al menos una fila en `regalos_confirmacion`. El importe
es `regalos.monto_total`, la moneda es `regalos.pago_con` (`1 = ARS`, `2 = USD`) y la
fecha apropiada para el gráfico es `regalos_confirmacion.confirm_date`. Una fila de
`regalos` sin confirmación es una intención/pedido pendiente, no recaudación.

La consulta futura debe deduplicar `regalos_confirmacion` por `regalo_id`, excluir
`regalos.activo <> 1`, agrupar por fecha de confirmación y moneda, y **jamás sumar ARS
y USD en una única serie sin una conversión histórica explícita**.

## 2. Archivos revisados

| Área | Archivos activos revisados | Hallazgo relevante |
|---|---|---|
| Entrada y resumen admin | `admin7WZiwEM3XY/index.php`, `admin7WZiwEM3XY/menu.php`, `admin7WZiwEM3XY/dashboard_casamiento.php` | Inicio incluye el dashboard existente; Regalos ofrece Confirmar, Recibidos y Lista de regalos. El dashboard actual contiene KPIs/gráficos de invitados, mensajes y visitas, pero no recaudación. |
| Gestión/cobro de regalos | `admin7WZiwEM3XY/productos_vendidos.php`, `admin7WZiwEM3XY/confirmar_pago.php`, `admin7WZiwEM3XY/deshacer_confirmacion.php`, `admin7WZiwEM3XY/cancelar_regalo.php`, `admin7WZiwEM3XY/deshacer_cancelacion.php`, `admin7WZiwEM3XY/totales.php` | Confirmar inserta en `regalos_confirmacion`; deshacer elimina esa fila; cancelar cambia `regalos.activo`; el total vigente suma cabeceras activas confirmadas y ya separa ARS/USD en la pantalla principal. |
| Catálogo/admin | `admin7WZiwEM3XY/lista_regalos.php`, `admin7WZiwEM3XY/datos.php`, `admin7WZiwEM3XY/datos_modificar.php` | `productos.precio` es precio de catálogo; `cliente.cotizacion_dolar` y cuentas bancarias se administran como datos actuales, no como historial de pagos. |
| Checkout/registro | `tienda/carrito.php`, `tienda/ver_carrito.php`, `tienda/finalizar_compra.php`, `tienda/procesar_compra.php`, `tienda/compra_exitosa.php` | El carrito es temporal. Checkout solo ofrece transferencia; al finalizar crea `regalos` y `regalos_detalles`, aún pendiente hasta confirmación manual. |
| Gift Card y catálogo | `tienda/regalo_libre_helper.php`, `tienda/mostrar_productos.php`, `tienda/paginacion.php` | Gift Card es un producto especial, no una tabla ni circuito de cobro separado; usa `monto_libre` en carrito/detalle. |
| Documentación estructural previa | `docs/DQS_BASELINE_SISTEMA_ACTIVO.md`, `docs/DQS_BASE_DATOS.md` | Confirma el modelo inferido, pero también advierte que no existe un dump/migración inicial completa y que los ambientes pueden divergir. |
| Conexión | `conexion.php` (sin reproducir credenciales) | Apunta a MySQL local; el servidor no estuvo disponible en este entorno. |

La búsqueda global en PHP activo no encontró tablas funcionales independientes llamadas
`pagos`, `compras`, `pedidos`, `transferencias` o `gift_cards`. “Compra” y
“transferencia” son conceptos del flujo: la compra se guarda en `regalos` y la forma
de pago en `regalos.forma_pago`; no hay evidencia en código de conciliación bancaria
automática ni de una tabla de transferencias registradas.

## 3. Tablas y campos detectados

> “Detectado” significa usado explícitamente por el código. Los tipos indicados son
> inferidos; deben confirmarse con `DESCRIBE` en Hostinger antes de UNI-046.

| Tabla | Rol | Campos relevantes detectados | ¿Cuenta como recaudación? |
|---|---|---|---|
| `regalos` | Cabecera de compra/regalo | `id`, `monto_total` (decimal inferido), `forma_pago`, `pago_con`, `activo`, `reg_date`, además de datos del comprador y resumen `productos` | Solo si `activo = 1` y existe confirmación. Sin confirmación es intención pendiente. |
| `regalos_confirmacion` | Evento/marca manual de pago confirmado | `regalo_id`, `confirm_date` | Sí, en unión con `regalos`; es la evidencia de cobro y aporta la fecha recomendada. |
| `regalos_detalles` | Detalle normalizado del regalo | `regalo_id`, `producto_id`, `cantidad`, `monto_libre`, `subtotal` | No debe sumarse para el KPI principal: puede duplicar cabecera y su granularidad es producto. Sirve para desglose/auditoría. |
| `productos` | Catálogo | `id`, `titulo`, `descripcion`, `precio`, `activo` | No: el precio actual de catálogo no prueba una venta ni un pago. |
| `carrito` | Intención temporal por sesión | `id`, `session_id`, `producto_id`, `cantidad`, `monto_libre` | No: aún no hay pedido finalizado ni pago. |
| `cliente` | Configuración vigente del evento | `user_id`, `cotizacion_dolar`, cuentas/alias ARS y USD | No: `cotizacion_dolar` se usa al cotizar el checkout, pero no se observa snapshot histórico por regalo. |
| `imagenes` | Imágenes de catálogo | `producto_id`, `url` | No. |
| `site_settings` | Configuración visual/feature de regalos | `setting_key`, `setting_value`, `updated_at` (creado por helper si falta) | No. |

### Fechas

- `regalos.reg_date`: fecha de creación/registro del pedido, usada para ordenar y
  mostrar pendientes. No prueba recepción de fondos.
- `regalos_confirmacion.confirm_date`: fecha de confirmación manual, usada para ordenar
  y mostrar “Ya confirmados”. Es la mejor aproximación disponible a fecha de pago.
- No se detectó un campo independiente de “fecha efectiva de transferencia” ni uno
  informado por el comprador.
- La UI resta tres horas al mostrar `reg_date` y `confirm_date`, lo que sugiere que la
  base podría guardar UTC y presentar horario argentino. Esto debe verificarse; agrupar
  sin resolver la zona horaria puede asignar confirmaciones cercanas a medianoche al
  día equivocado.

### Montos y monedas

- `regalos.monto_total` es el importe contractual del pedido ya denominado en la
  moneda elegida; es la fuente recomendada para total recaudado.
- `regalos.pago_con = 1` significa ARS y `= 2` significa USD según checkout, página de
  éxito y admin. No se detectaron otros valores soportados.
- `productos.precio` parte de ARS. Si se elige USD, checkout divide el precio (también
  el monto libre de Gift Card) por `cliente.cotizacion_dolar` y guarda el resultado en
  `monto_total`/`subtotal` con `pago_con = 2`.
- La cotización usada no se copia al regalo. `cliente.cotizacion_dolar` es mutable y
  representa el valor actual, por lo que **no permite reconstruir de forma confiable
  una conversión histórica**. Tampoco se observa regla de redondeo financiero más
  precisa que el cálculo en coma flotante y la presentación sin decimales.

## 4. Flujo funcional actual

1. El invitado agrega productos o la Gift Card especial a `carrito`; esto es solo una
   intención por sesión.
2. El checkout lee precios de `productos`, `monto_libre` para Gift Card y la cotización
   actual de `cliente`. La única forma visible es `transferencia`.
3. `tienda/procesar_compra.php` calcula el total, inserta una cabecera activa en
   `regalos` y filas en `regalos_detalles`, vacía el carrito y envía instrucciones de
   transferencia. La propia pantalla de éxito aclara que la confirmación queda
   pendiente hasta recibir la transferencia.
4. El admin ve pedidos activos sin fila en `regalos_confirmacion` bajo “Confirmar”.
5. Al pulsar “Confirmar Pago”, el flujo inserta `regalos_confirmacion(regalo_id)`; desde
   entonces aparece en “Recibidos” y entra en el total recaudado actual.
6. “Deshacer confirmación” elimina la marca y deja de contarlo. “Cancelar/Borrar” pone
   `regalos.activo = 0`, también excluido por las consultas actuales.

No existe un estado textual de pago detectado. El estado se deriva de dos dimensiones:

| Estado derivado | Condición | Tratamiento |
|---|---|---|
| Confirmado/cobrado | `regalos.activo = 1` y existe `regalos_confirmacion` | **Incluir**. |
| Pendiente | `regalos.activo = 1` y no existe confirmación | **Excluir**. Es intención/pedido. |
| Cancelado/inactivo | `regalos.activo <> 1`, haya o no confirmación | **Excluir** del gráfico operativo, igual que el total admin vigente. Auditar aparte si aparecen confirmaciones huérfanas/inactivas. |
| Carrito | Existe solo en `carrito` | **Excluir**. |
| Producto/Gift Card disponible | Existe en catálogo | **Excluir** hasta que la cabecera quede confirmada. |

`forma_pago = 'transferencia'` describe el medio, no confirma el pago. Como actualmente
es la única opción visible, no conviene usarla como sustituto de la confirmación ni
como filtro obligatorio que podría ocultar futuros medios válidos.

## 5. Respuestas a las preguntas de auditoría

1. **¿Qué tabla representa recaudación real?** La pareja `regalos` +
   `regalos_confirmacion`; ninguna de las dos aislada contiene monto, moneda, estado y
   fecha suficientes.
2. **¿Qué representa intención/pedido pendiente?** `carrito` antes del checkout y
   `regalos` activo sin confirmación después del checkout.
3. **¿Campo de monto?** `regalos.monto_total`; `regalos_detalles.subtotal` es solo
   detalle y `productos.precio` catálogo.
4. **¿Fecha de pedido?** `regalos.reg_date`.
5. **¿Fecha de pago/confirmación?** `regalos_confirmacion.confirm_date`; es confirmación
   administrativa, no necesariamente timestamp bancario efectivo.
6. **¿Qué estados cuentan?** Regalo activo con confirmación existente, una sola vez por
   `regalo_id`.
7. **¿Qué estados no cuentan?** Carrito, regalo pendiente, cancelado/inactivo y cualquier
   catálogo. Las confirmaciones huérfanas o duplicadas requieren auditoría, no suma.
8. **¿Cómo tratar ARS/USD?** Series, totales, ejes y tooltips separados y claramente
   rotulados. No producir un “total combinado”.
9. **¿Existe cotización/conversión?** Existe `cliente.cotizacion_dolar` y se aplica al
   crear pedidos USD, pero no se detectó cotización histórica guardada por operación.
10. **¿Qué fecha graficar?** `confirm_date`, ajustada a la zona horaria de negocio una
    vez validado cómo se almacena. `reg_date` solo serviría para un gráfico distinto de
    pedidos creados.
11. **¿Hay datos suficientes?** El modelo aparente sí permite total diario confirmado
    por moneda. Falta validar schema/datos reales, timezone, duplicados y defaults en
    Hostinger antes de afirmar que todas las filas históricas son utilizables.
12. **¿Total diario y acumulado?** Sí: barras diarias y acumulado opcional, siempre por
    moneda. El acumulado debe calcularse sobre el mismo rango/serie y explicar si su
    punto inicial incluye historia previa o solo la ventana visible.
13. **¿SQL futuro?** Las consultas recomendadas están en las secciones 7 y 8.

## 6. Validación read-only previa en Hostinger

Ejecutar primero estas consultas, sin exponer datos personales. No usar `SELECT *` de
filas de compradores: para esta auditoría bastan ids, estados, fechas, montos y moneda.

```sql
SHOW TABLES;

DESCRIBE regalos;
DESCRIBE regalos_confirmacion;
DESCRIBE regalos_detalles;
DESCRIBE productos;
DESCRIBE carrito;
DESCRIBE cliente;

SELECT 'regalos' tabla, COUNT(*) cantidad FROM regalos
UNION ALL SELECT 'regalos_confirmacion', COUNT(*) FROM regalos_confirmacion
UNION ALL SELECT 'regalos_detalles', COUNT(*) FROM regalos_detalles
UNION ALL SELECT 'productos', COUNT(*) FROM productos
UNION ALL SELECT 'carrito', COUNT(*) FROM carrito;

SELECT activo, pago_con, forma_pago, COUNT(*) cantidad,
       MIN(reg_date) primera_fecha, MAX(reg_date) ultima_fecha,
       SUM(monto_total) total
FROM regalos
GROUP BY activo, pago_con, forma_pago
ORDER BY activo, pago_con, forma_pago;

SELECT regalo_id, confirm_date
FROM regalos_confirmacion
ORDER BY confirm_date DESC
LIMIT 20;

SELECT id, activo, pago_con, forma_pago, monto_total, reg_date
FROM regalos
ORDER BY reg_date DESC
LIMIT 20;
```

### Controles de calidad bloqueantes para UNI-046

```sql
-- Confirmaciones duplicadas: deben revisarse porque un JOIN directo inflaría montos.
SELECT regalo_id, COUNT(*) cantidad
FROM regalos_confirmacion
GROUP BY regalo_id
HAVING COUNT(*) > 1;

-- Confirmaciones sin cabecera.
SELECT COUNT(*) confirmaciones_huerfanas
FROM regalos_confirmacion c
LEFT JOIN regalos r ON r.id = c.regalo_id
WHERE r.id IS NULL;

-- Valores inválidos o ambiguos para moneda/monto/fecha.
SELECT
  SUM(pago_con IS NULL OR pago_con NOT IN (1, 2)) moneda_invalida,
  SUM(monto_total IS NULL OR monto_total < 0) monto_invalido,
  SUM(reg_date IS NULL) pedido_sin_fecha
FROM regalos;

SELECT
  SUM(confirm_date IS NULL) confirmacion_sin_fecha,
  MIN(confirm_date) primera_confirmacion,
  MAX(confirm_date) ultima_confirmacion
FROM regalos_confirmacion;

-- Casos contradictorios que el gráfico excluiría por activo.
SELECT COUNT(*) confirmados_inactivos
FROM regalos r
INNER JOIN regalos_confirmacion c ON c.regalo_id = r.id
WHERE r.activo <> 1;

-- Comprobar zona horaria de sesión/servidor antes de elegir el corte diario.
SELECT NOW() hora_sesion, UTC_TIMESTAMP() hora_utc,
       @@session.time_zone zona_sesion, @@global.time_zone zona_global;
```

También se recomienda comprobar que `regalos_confirmacion.regalo_id` tenga una
restricción/índice único. Esta auditoría no propone aplicarlo todavía: primero hay que
medir duplicados y comprender el historial.

## 7. SQL read-only recomendado para total diario

### 7.1 Consulta base segura por moneda (MySQL 5.7+)

Esta versión deduplica confirmaciones usando la primera confirmación registrada. Es
preferible al `INNER JOIN` directo usado por el total actual porque una eventual fila
duplicada no multiplicará `monto_total`.

```sql
SELECT
    DATE(c.confirm_date) AS dia,
    r.pago_con AS moneda_codigo,
    CASE r.pago_con WHEN 1 THEN 'ARS' WHEN 2 THEN 'USD' END AS moneda,
    COUNT(*) AS pagos_confirmados,
    SUM(r.monto_total) AS total_diario
FROM regalos r
INNER JOIN (
    SELECT regalo_id, MIN(confirm_date) AS confirm_date
    FROM regalos_confirmacion
    WHERE confirm_date IS NOT NULL
    GROUP BY regalo_id
) c ON c.regalo_id = r.id
WHERE r.activo = 1
  AND r.pago_con IN (1, 2)
  AND c.confirm_date >= :desde_inclusivo
  AND c.confirm_date <  :hasta_exclusivo
GROUP BY DATE(c.confirm_date), r.pago_con
ORDER BY dia ASC, moneda_codigo ASC;
```

`:desde_inclusivo` y `:hasta_exclusivo` deben ser parámetros preparados. Usar límite
superior exclusivo evita errores con fracciones de segundo y permite rangos 7/15/30
días consistentes.

### 7.2 Zona horaria

La consulta base supone que `confirm_date` ya está en la zona de negocio. Si Hostinger
confirma que almacena UTC, convertir **antes** de aplicar `DATE()` y construir los
límites en UTC. Cuando las tablas de zona horaria MySQL estén disponibles:

```sql
DATE(CONVERT_TZ(c.confirm_date, '+00:00', 'America/Argentina/Buenos_Aires'))
```

Si no están cargadas, puede usarse temporalmente `DATE(DATE_SUB(c.confirm_date,
INTERVAL 3 HOUR))`, pero solo tras confirmar que todos los datos son UTC y que el
negocio usa UTC-03. Es mejor centralizar esta decisión en UNI-046 que copiar la resta
de tres horas observada en la vista.

## 8. SQL read-only recomendado para acumulado diario

MySQL 8.0+ permite calcular el acumulado a partir de la misma serie diaria:

```sql
WITH confirmaciones_unicas AS (
    SELECT regalo_id, MIN(confirm_date) AS confirm_date
    FROM regalos_confirmacion
    WHERE confirm_date IS NOT NULL
    GROUP BY regalo_id
), diario AS (
    SELECT
        DATE(c.confirm_date) AS dia,
        r.pago_con AS moneda_codigo,
        CASE r.pago_con WHEN 1 THEN 'ARS' WHEN 2 THEN 'USD' END AS moneda,
        COUNT(*) AS pagos_confirmados,
        SUM(r.monto_total) AS total_diario
    FROM regalos r
    INNER JOIN confirmaciones_unicas c ON c.regalo_id = r.id
    WHERE r.activo = 1
      AND r.pago_con IN (1, 2)
      AND c.confirm_date >= :desde_inclusivo
      AND c.confirm_date <  :hasta_exclusivo
    GROUP BY DATE(c.confirm_date), r.pago_con
)
SELECT
    dia,
    moneda_codigo,
    moneda,
    pagos_confirmados,
    total_diario,
    SUM(total_diario) OVER (
        PARTITION BY moneda_codigo
        ORDER BY dia
        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
    ) AS acumulado_en_rango
FROM diario
ORDER BY dia ASC, moneda_codigo ASC;
```

Esto devuelve solo días con movimientos. Si UNI-046 necesita barras explícitas en cero,
debe completar el calendario en PHP/JavaScript (simple para 7/15/30 días) o usar un CTE
recursivo en MySQL 8. No mezclar esa necesidad visual con la definición contable.

`acumulado_en_rango` comienza en el primer día solicitado. Si el producto espera un
saldo histórico total al iniciar la ventana, la consulta deberá sumar además todas las
confirmaciones anteriores a `:desde_inclusivo` por moneda y documentar ese significado.

## 9. Propuesta visual para UNI-046

- Añadir al dashboard de Inicio una tarjeta “Recaudación confirmada”. No modificar la
  pantalla operativa de regalos.
- Gráfico de **barras por día** para `total_diario`; selector simple de últimos
  **7 / 15 / 30 días**, con 30 como default razonable si producto no define otro.
- Mostrar ARS y USD como series separadas. Preferencia: selector de moneda o dos paneles
  pequeños; evitar un único eje con escalas incomparables. Etiquetas inequívocas
  `ARS $` y `USD US$` en título, eje, tooltip y total.
- Opcional: línea de acumulado de la moneda seleccionada o KPI separado sobre el
  gráfico. No superponer acumulados ARS/USD en un mismo eje.
- Tooltip: fecha local, moneda, total diario y cantidad de pagos confirmados. No mostrar
  nombres, emails, teléfonos, mensajes ni productos.
- Días sin confirmaciones deben aparecer con barra cero para continuidad temporal.
- Estado vacío explícito (“No hay pagos confirmados en este período”), loading y error
  no deben confundirse con total cero.
- El endpoint futuro debe exigir sesión admin y feature de regalos activa, usar consulta
  preparada, devolver números sin formato y dejar el formateo localizado a la UI.
- Reutilizar la librería/patrón visual que ya emplea el dashboard, sin tocar tienda
  pública, RSVP, WhatsApp ni la lógica de confirmación.

## 10. Riesgos, dudas y decisiones pendientes

1. **DB no validada:** el bloqueo principal es confirmar en Hostinger columnas, tipos,
   defaults, índices y datos; `confirm_date`/`reg_date` se usan en código aunque no hay
   schema versionado completo en el repositorio.
2. **Timezone:** la resta visual de tres horas sugiere UTC, pero no lo demuestra. Debe
   definirse una zona canónica antes de agrupar.
3. **Duplicados:** no se observa protección explícita contra múltiples confirmaciones
   del mismo regalo. La consulta propuesta deduplica, pero UNI-046 debe medir y reportar
   el problema; elegir `MIN(confirm_date)` preserva el primer momento confirmado.
4. **Deshacer/reconfirmar:** al borrar una confirmación se pierde la fecha anterior. Una
   reconfirmación queda fechada de nuevo, por lo que no hay auditoría histórica completa.
5. **Confirmados inactivos:** el flujo puede conservar confirmación al cancelar. Se
   excluyen para coincidir con el total actual, pero negocio debe confirmar esa regla.
6. **Cotización no historizada:** no se puede crear hoy un total consolidado convertido
   de manera auditable. Usar la cotización actual alteraría retrospectivamente el gráfico.
7. **Semántica de USD:** `monto_total` USD surge de dividir precios ARS por la cotización
   vigente al checkout, pero no queda guardada la tasa. Validar con ejemplos reales y
   política de redondeo.
8. **Multi-tenant:** varias consultas actuales asumen `user_id = 1` o tablas singleton y
   `regalos` no muestra un `cliente_id` usado por código. Confirmar que cada despliegue
   tiene una base aislada antes de reutilizar SQL en una instalación compartida.
9. **Seguridad existente fuera de alcance:** algunos endpoints y consultas admin
   interpolan ids directamente y los endpoints auxiliares no muestran guard de sesión.
   No se modificaron por la regla read-only, pero una futura implementación no debe
   copiar ese patrón.
10. **“Fecha de pago” real:** `confirm_date` mide el clic administrativo. Si negocio
    necesita conciliación bancaria efectiva, el modelo actual no la registra y hará
    falta definir otro campo/evento en una unidad separada.

## 11. Recomendación final

UNI-046 puede avanzar después de ejecutar el checklist read-only en Hostinger y cerrar
timezone/duplicados. La fuente debe ser `regalos` activo unido a una confirmación única;
importe `monto_total`, fecha `confirm_date` y moneda `pago_con`. Implementar primero
barras diarias 7/15/30 con ARS y USD separados. Añadir acumulado solo como dato/línea
opcional y claramente “en el período”; no incorporar conversión ni tocar el flujo de
regalos hasta que exista una cotización histórica por operación.
