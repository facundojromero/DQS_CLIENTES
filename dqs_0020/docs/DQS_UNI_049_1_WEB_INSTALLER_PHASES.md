# UNI-049.1 — Fases futuras del instalador web

## 1. Estrategia de entrega

El instalador se entregará por incrementos pequeños, auditables y cerrados por defecto. Ninguna unidad habilita la siguiente en producción sin pruebas de seguridad, JSON, rerun e invariantes. Los primeros incrementos son read-only; cada mutación se habilita separadamente. En todas las fases se mantiene la regla: la web orquesta los CLIs UNI-048 y no duplica su lógica crítica.

La escritura permanente de `conexion.php` no se incluye por accidente en ninguna fase: requiere una unidad específica, revisión de secretos/permisos, escritura atómica, backup/recuperación y aceptación explícita antes del cierre productivo.

## 2. Unidades propuestas

### UNI-049.2 — Executor seguro CLI + contrato JSON

**Objetivo:** construir y probar el único puente permitido entre web y CLIs.

**Entregables:**

* allowlist por operación, argumentos tipados y resolución canónica de paths permitidos;
* wrapper con `proc_open` o equivalente, argumentos escapados individualmente, entorno/CWD fijos, timeout, límites de salida y captura separada;
* contrato JSON versionado para `OK`, `WARN`, `BLOCKED`, `FAILED`, checks y exit codes; adaptaciones documentadas a CLIs si fueran necesarias;
* redacción estructural de logs y tests que inyecten metacaracteres, paths externos, symlinks, timeout, JSON roto y secretos señuelo.

**Criterio de salida:** no existe API de comando arbitrario, ningún secreto aparece en resultados/logs y toda discordancia JSON/exit code falla cerrada. Aún no hay endpoint público.

### UNI-049.3 — Gate + UI shell read-only

**Objetivo:** crear la superficie mínima, todavía sin conexión DB ni apply.

**Entregables:** habilitación externa desactivada por defecto, verificación continua de locks, secreto one-time, sesión aislada, CSRF, HTTPS, rate limit, timeouts de sesión, cabeceras seguras, máquina de estados inicial y shell de pasos.

**Criterio de salida:** con configuración ausente o `install.lock`/pending presente no se inicia sesión; refresh/doble envío no duplica transiciones; no hay comandos mutantes accesibles.

### UNI-049.4 — Paso DB/preflight desde web

**Objetivo:** validar conexión y entorno sin mutaciones.

**Entregables:** formulario `host`/`dbname`/`username`/`password`; connection-file privado fuera del repo, creación exclusiva y `0600`; integración `preflight --json`; limpieza por éxito/error/cancelación/TTL; resumen y reporte censurados.

**Criterio de salida:** solo una DB vacía con resultado admisible llega a `db_validated`; password, archivo y path completo no aparecen en HTML, sesión persistida, logs ni reportes.

### UNI-049.5 — Paso schema dry-run/apply desde web

**Objetivo:** integrar el schema runner, habilitando primero dry-run y después apply bajo feature flag separado.

**Entregables:** selector permitido de contenido default, fingerprint del dry-run, doble confirmación, revalidación inmediata, mensajes sobre DDL/autocommit y reconciliación tras timeout.

**Criterio de salida:** `BLOCKED` deshabilita Apply; apply usa ambas confirmaciones CLI; base no vacía/rerun se bloquea; fallos parciales no disparan rollback web ni reintento automático.

### UNI-049.6 — Paso bootstrap desde web

**Objetivo:** crear el usuario/cliente/settings mediante el CLI transaccional.

**Entregables:** formularios tipados, validación de password sin eco, bootstrap-file efímero `0600`, dry-run obligatorio, doble confirmación y resumen censurado de invariantes.

**Criterio de salida:** solo se aceptan campos/settings del contrato, el password nunca se persiste en claro, el resultado verifica `user=1`, `cliente=1`, `admin_config=0` y el rollback/reintento se prueba.

### UNI-049.7 — Paso admin publish desde web

**Objetivo:** publicar un admin y su fila `admin_config` sin aceptar filesystem arbitrario.

**Entregables:** slug seguro generado/validado, catálogos server-side de templates y target roots, config privado, dry-run/apply, mensajes de creación de archivos y pruebas de staging/symlinks/concurrencia.

**Criterio de salida:** ningún path del request pasa directamente al executor; la herramienta deja exactamente una carpeta y fila válidas o limpia solo lo creado por ella; el rerun queda bloqueado.

### UNI-049.8 — Paso finalize desde web

**Objetivo:** auditar y cerrar irreversiblemente la instalación web.

**Entregables:** finalize dry-run, revisión final, doble confirmación, apply con pending+rename, verificación posterior, invalidación de secreto/sesión y limpieza de todos los temporales.

**Criterio de salida:** solo una auditoría limpia permite crear `install.lock`; después del lock toda ruta queda bloqueada y no existe acción web de borrado/reapertura.

### UNI-049.9 — Hardening, logs censurados, cierre y manual operativo

**Objetivo:** preparar operación soportable y revisar el conjunto como una frontera expuesta.

**Entregables:** threat model, pruebas de abuso/concurrencia/TOCTOU, límites de recursos, reporte descargable censurado, recolector de temporales, runbooks de timeout/pending/DB parcial, observabilidad con allowlist y procedimiento para retirar/deshabilitar el instalador.

**Criterio de salida:** revisión de seguridad aprobada; pruebas automáticas demuestran ausencia de secretos y comandos/paths arbitrarios; operadores pueden recuperar cada fallo documentado sin borrar datos desde la UI.

### UNI-049.10 — Piloto en subdominio temporal

**Objetivo:** validar el flujo E2E en infraestructura aislada antes de producción.

**Entregables:** subdominio temporal sin tráfico real, DB vacía descartable, permisos mínimos, ejecución trazable completa, pruebas de rerun/lock/cierre, checklist y evidencia censurada.

**Criterio de salida:** instalación reproducible, auditoría final correcta, temporales ausentes, instalador inaccesible tras lock y aprobación explícita para definir el siguiente despliegue. El piloto no usa DB, admin o dominio activos.

## 3. Gates transversales por fase

Cada unidad debe documentar y probar:

1. **Seguridad:** estado deshabilitado por defecto, locks fail-closed, sesión/CSRF cuando haya HTTP, allowlists y cero secretos en logs.
2. **Contrato:** CLI exacto, inputs controlados, `--json`, severidades y exit codes; `BLOCKED` nunca puede degradarse a warning visual.
3. **Idempotencia operativa:** dry-run repetible, nonce/lock de estado, no auto-retry de apply y bloqueo de rerun según invariantes.
4. **Limpieza:** archivos privados `0600`, fuera de repo/document root, con cleanup en éxito/error/cancelación/expiración.
5. **Evidencia:** tests y reporte censurado, sin stdout/stderr crudo ni líneas de comando con secretos.
6. **Rollback realista:** distinguir transacción, cleanup propio, DDL no reversible y resultado desconocido por timeout.

## 4. Dependencias y orden

El orden recomendado es estricto: `049.2 → 049.3 → 049.4 → 049.5 → 049.6 → 049.7 → 049.8 → 049.9 → 049.10`. El gate y executor preceden cualquier acceso web a los CLIs. Cada paso depende del estado confirmado del anterior.

Antes de considerar producción deberá existir además una decisión/unidad aprobada para la configuración permanente de conexión. No debe resolverse escribiendo `conexion.php` desde una fase cuyo alcance no lo mencione.

## 5. Definición global de terminado

El instalador futuro solo podrá declararse listo cuando:

* no exponga comandos o paths arbitrarios y toda ejecución pase por el executor allowlistado;
* permanezca deshabilitado salvo activación explícita y se bloquee ante cualquier lock/evidencia de instalación;
* no muestre, persista ni registre passwords o connection-files;
* todos los pasos consuman JSON, presenten WARN y bloqueen Apply ante BLOCKED/FAILED;
* se hayan probado los límites de rollback, timeout, concurrencia, doble envío, limpieza y rerun;
* el cierre invalide acceso y el piloto E2E aislado produzca evidencia censurada satisfactoria.

