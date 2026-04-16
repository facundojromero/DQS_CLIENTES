# Análisis técnico del sistema `/clientes/lai` (PHP + MySQL)

Fecha del análisis: 2026-04-16

## 1) Cómo está armado hoy el sistema

### Stack actual
- Backend y frontend monolítico en PHP (sin framework).
- Persistencia en MySQL/MariaDB.
- Renderizado de UI en páginas PHP con HTML/CSS/JS embebido.
- Sesiones PHP para autenticación de cajas/usuarios.

### Entrypoints principales
- `login.php`: autenticación de usuario contra tabla `usuarios`.
- `index.php`: pantalla principal de caja y orquestador de flujos de venta + impresión.
- `ventas_rapidas.php`: UI para venta de un producto/combo con forma de pago.
- `carrito.php`: UI para venta de múltiples productos (carrito).
- `ventas_registradas.php`: historial de ventas por usuario, anulación, cambio de forma de pago y reimpresión.
- `factura_tkt.php`: ticket imprimible (abre ventana y ejecuta `window.print()`).
- `detalle_ticket.php`, `resumen_ventas.php`, `modificar_precios.php`, `modificar_combinaciones.php`, `exportacion_excel.php`: operación y administración.

### Conexión y configuración
- Conexión principal en `conexion.php` con credenciales hardcodeadas (host, usuario, password y DB).
- Existe `conexion(1).php` con parámetros locales alternativos (localhost/root), señal de despliegues manuales por entorno.
- No hay archivo `.env` ni capa de configuración centralizada.

### Modelo de datos (relevante para operación)
- `ventas`: cabecera de venta (fecha, producto textual, precio, forma, usuario, activado).
- `venta_detalles`: detalle por producto cuando la venta es de carrito/combinación.
- `productos`, `combinaciones`, `combinaciones_detalles`, `carrito`, `usuarios`.
- El SQL exportado muestra esquema simple, utilizable sin cambios para una evolución incremental.

## 2) Flujos funcionales detectados

### Flujo de ventas (rápidas)
1. Usuario autenticado entra a `index.php`.
2. Se incluye `ventas_rapidas.php` y se muestran productos/combinaciones activos.
3. Al enviar producto + forma de pago:
   - Si es combinación, distribuye precio proporcional en “tickets” internos.
   - Si es producto simple, registra una fila en `ventas`.
4. Luego dispara impresión automática por JS abriendo una ventana por ticket.

### Flujo de ventas (carrito)
1. Desde `carrito.php` se agregan ítems a tabla `carrito` por usuario.
2. Al “Registrar Venta” (`registrar_venta`), `index.php`:
   - crea cabecera en `ventas` con producto “Carrito”,
   - inserta detalle en `venta_detalles`,
   - calcula total y actualiza cabecera,
   - limpia carrito,
   - imprime tickets secuenciales con `window.open()`.

### Flujo de impresión actual
- `index.php` abre `factura_tkt.php` en popup para cada ticket.
- `factura_tkt.php` ejecuta `window.print()` en `window.onload` y luego `window.close()` con delay.
- `ventas_registradas.php` permite reimprimir abriendo nuevamente `factura_tkt.php`.

## 3) Diagnóstico del problema operativo actual

El cuello de botella no está en MySQL ni en la lógica de ventas, sino en la **dependencia de comportamiento del navegador para imprimir en silencio**:
- Uso de popups (`window.open`) + `window.print` + cierre automático.
- Esto obliga a configuración específica por PC/navegador (ej. Firefox kiosk/print settings).
- La solución depende de permisos/policies del navegador y puede romperse con updates, perfiles o bloqueo de popups.

Adicionalmente, hay acoplamiento entre registro de venta e impresión dentro del mismo request de `index.php`, lo que dificulta escalar o cambiar mecanismo de impresión sin tocar ese flujo.

## 4) Evaluación de alternativas

## Opción 1: PHP + MySQL + app instalable contenedora (Electron/Tauri/WebView)

### Idea
Empaquetar el sistema web dentro de una app de escritorio instalable que cargue la UI y provea APIs nativas para impresión.

### Pros
- Alta reutilización del sistema actual (pantallas, lógica SQL, estructura).
- Instalación “tipo programa” por PC (mejor UX de despliegue).
- Posibilidad de controlar impresión con APIs nativas (sin depender 100% de Firefox).
- Permite modo kiosco y actualización centralizada del cliente.

### Contras
- Hay que desarrollar “bridge” entre frontend web y runtime nativo para imprimir.
- Electron consume más recursos; Tauri requiere más trabajo inicial de integración.
- Si se embebe un servidor PHP local por PC, complica operación; mejor mantener servidor central + cliente contenedor.

### Complejidad / riesgo / tiempo
- Complejidad: media.
- Riesgo: medio.
- Tiempo estimado: medio (4–8 semanas según alcance de impresión y packaging).

## Opción 2: PHP + MySQL + agente local de impresión (recomendada)

### Idea
Mantener la app web casi intacta y agregar un **servicio local por PC** (Windows Service o daemon) que reciba jobs de impresión desde la web y mande ESC/POS o spool al printer local.

### Pros
- Máxima reutilización del código actual (mínimos cambios en ventas).
- Ataca directamente el problema real (impresión silenciosa robusta).
- Mantiene arquitectura cliente-servidor web existente en red local.
- Escalable a múltiples PCs con instalador liviano del agente.
- Permite trazabilidad: cola de impresión, reintentos, auditoría de fallos.

### Contras
- Requiere diseñar protocolo seguro entre navegador y agente (localhost HTTP/WebSocket + token).
- Hay que distribuir y actualizar el agente en cada equipo.
- Dependencia por SO para driver/cola de impresión.

### Complejidad / riesgo / tiempo
- Complejidad: media-baja.
- Riesgo: bajo-medio.
- Tiempo estimado: corto-medio (2–6 semanas para versión productiva).

## Opción 3: Migración total a app desktop (.NET/Java/Python)

### Idea
Reescribir frontend + backend en stack desktop, manteniendo (o adaptando) MySQL.

### Pros
- Control completo sobre impresión, dispositivos y UX offline.
- Menor dependencia de navegador.

### Contras
- Reescritura amplia de negocio, UI y pruebas.
- Alto riesgo funcional (regresiones).
- Mayor costo y plazo.
- Duplicación de lógica actualmente estable.

### Complejidad / riesgo / tiempo
- Complejidad: alta.
- Riesgo: alto.
- Tiempo estimado: largo (3–9+ meses).

## Opción 4: Híbrida (contenedor + agente)

### Idea
Cliente instalable (Tauri/Electron) para operación uniforme + agente/SDK de impresión nativa (interno o separado).

### Pros
- Mejor experiencia final y control operativo.
- Mitiga problemas de navegador definitivamente.

### Contras
- Mayor esfuerzo inicial que opción 2 pura.
- Más componentes para mantener.

### Complejidad / riesgo / tiempo
- Complejidad: media-alta.
- Riesgo: medio.
- Tiempo estimado: medio-largo.

## 5) Respuestas concretas a tus preguntas

### ¿Se puede convertir este sistema PHP en un programa instalable sin rehacer todo?
Sí. Técnicamente es viable con alta reutilización si se evita reescritura total y se incorpora capa cliente (contenedor) y/o agente de impresión.

### ¿Cuál es la mejor alternativa técnica?
Para esta etapa y objetivo operativo, **Opción 2 (PHP + agente local de impresión)** es la mejor relación costo/beneficio/riesgo.

### ¿Cómo seguir usando la base MySQL actual?
- Manteniendo el mismo esquema (`ventas`, `venta_detalles`, `productos`, etc.).
- Servidor MySQL central en red local (o mismo host del servidor PHP).
- Los clientes (navegador o contenedor) siguen consumiendo el PHP central.
- Sin cambios de modelo al inicio; solo posibles ajustes de índices y hardening.

### ¿Cómo sería la instalación en varias PCs?
- **Servidor central**: PHP + MySQL (actual).
- **En cada PC caja**:
  1. acceso al sistema web (URL local),
  2. instalación del agente de impresión,
  3. configuración inicial: impresora por defecto, token de caja, endpoint del servidor.
- Opcional: instalador MSI/EXE con autoupdate del agente.

### ¿Cómo se resolvería la impresión directa/silenciosa?
- La web genera payload de ticket (JSON: producto, precio, fecha, usuario, etc.).
- En lugar de `window.print`, envía job al agente local (`http://127.0.0.1:<puerto>/print`).
- El agente imprime por spool del SO o ESC/POS directo.
- Respuesta de estado para reintento/alerta en UI.

### ¿Qué partes se pueden reutilizar?
- Casi toda la lógica de negocio actual: ventas, carrito, combinaciones, reportes.
- Estructura de base y consultas SQL.
- UI existente con cambios puntuales en puntos de impresión.

### ¿Qué riesgos tiene cada opción?
- Contenedor: complejidad de empaquetado/runtime.
- Agente: operación y seguridad de un servicio local por PC.
- Reescritura desktop: costo, tiempo y regresiones.
- Híbrida: mayor superficie operativa inicial.

### ¿Cuál recomiendo y por qué?
**Recomiendo Opción 2 ahora**, porque resuelve el dolor principal (impresión) con menor impacto y plazo, preservando el sistema que “funciona muy bien”. Luego, si se desea experiencia “aplicación instalada” más uniforme, evolucionar a híbrida (opción 4).

## 6) Arquitectura sugerida (target incremental)

1. **Backend actual PHP/MySQL** (sin romper flujos de venta).
2. **Servicio de impresión local por caja**:
   - API local autenticada por token rotativo.
   - Cola local de jobs (persistente en disco liviano).
   - Driver/adapter por tipo de impresora (ESC/POS o spool).
3. **Cambio mínimo en PHP/JS**:
   - Reemplazar `window.open('factura_tkt.php...')` por llamada a endpoint local de agente.
   - Mantener `factura_tkt.php` como fallback/manual reimpresión.
4. **Monitoreo básico**:
   - Logs de impresión por caja (éxitos/fallas).
   - Endpoint de healthcheck del agente.

## 7) Etapas sugeridas de implementación

### Etapa 0 — Hardening previo (rápido)
- Externalizar credenciales DB a variables de entorno.
- Homogeneizar configuración (eliminar ambigüedad `conexion.php` vs `conexion(1).php`).
- Definir caja/impresora por usuario o estación.

### Etapa 1 — PoC de impresión silenciosa
- Implementar agente local mínimo en 1 PC.
- Integrar un único flujo: venta rápida producto simple.
- Medir latencia, tasa de fallos y recuperación.

### Etapa 2 — Integración completa
- Integrar carrito/combinaciones y reimpresiones.
- Manejo de errores UX (reintentar, imprimir manual, cola pendiente).
- Deploy piloto en 2–3 cajas en red local.

### Etapa 3 — Estandarización multi-PC
- Instalador automático del agente.
- Config centralizada de endpoint/token/impresora.
- Procedimientos de soporte (logs, diagnóstico, actualización).

### Etapa 4 — Evolución opcional a cliente instalable
- Evaluar Tauri/Electron para envolver UI y simplificar operación de usuario final.
- Mantener agente interno o unificar impresión dentro del contenedor.

## 8) Observaciones técnicas importantes detectadas

- SQL dinámico sin parametrización en múltiples operaciones (riesgo de inyección; no bloquea la migración pero conviene corregir).
- Contraseñas en texto plano en el dump de ejemplo (riesgo de seguridad).
- Diferencias horarias resueltas con offsets manuales (`strtotime('-5 hours')`, `'+21 hours'`) en vez de estrategia consistente de timezone.
- En limpieza de carrito dentro de `index.php` se ejecuta `DELETE FROM carrito` sin filtrar por usuario en el flujo de registrar venta de carrito (posible efecto cruzado en multiusuario concurrente).

Estas observaciones no impiden avanzar con la estrategia recomendada, pero sí deben incluirse en el plan de estabilización.
