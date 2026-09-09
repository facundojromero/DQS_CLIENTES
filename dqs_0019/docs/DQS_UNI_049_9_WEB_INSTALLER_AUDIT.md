# UNI-049.9 — Auditoría final del instalador web

Fecha: 2026-07-27. Alcance: UNI-049.1–UNI-049.8 sobre el árbol actual.

## Dictamen

El flujo queda **apto para cerrar UNI-049**, sujeto a ejecutar el runbook E2E en el
host objetivo con una base temporal vacía antes de una instalación de producción.
La auditoría de código, lint y probes negativos no encontró un bypass de apply ni
una exposición deliberada de secretos. Se encontró y corrigió un defecto menor:
la limpieza global de sesiones al crear/revocar el gate eliminaba `connection.php`
y `bootstrap.json`, pero omitía `admin_publish.json`. La limpieza ahora cubre los
tres inputs privados. No se cambió la semántica de ningún CLI UNI-048.

## Matriz auditada

| Área | Resultado | Evidencia / límite |
|---|---|---|
| Gate | OK | Sin gate se responde 403; login es POST+CSRF; GET con `secret` o `action` se bloquea; el hash, nunca el secreto, queda en `gate.json`; consumo atómico marca `used_at_utc`; fingerprint liga la sesión a create/consume/revoke/expiry. |
| Preflight | OK | Los cuatro valores DB nacen del formulario POST, se validan y se guardan en un archivo 0600 fuera de repo/docroot; el resultado pasa por redacción. El CLI decide vacío/no vacío. |
| Schema | OK | UI exige preflight OK/WARN, dry-run vigente, fingerprint, checkbox y `INSTALAR SCHEMA`; apply y rerun se bloquean en caso contrario. Solo el executor invoca al CLI. |
| Bootstrap | OK | Solo aparece tras Schema apply OK/WARN; captura controlada, archivo privado 0600, dry-run+fingerprint y frase `CREAR BOOTSTRAP`; rerun bloqueado. |
| Admin publish | OK | Solo tras Bootstrap apply OK/WARN. Template, target y slug se derivan internamente, no del POST. Exige dry-run+fingerprint y `PUBLICAR ADMIN`; no ejecuta Finalize. |
| Finalize | OK | Solo tras Admin publish apply OK/WARN. Exige dry-run+fingerprint y `FINALIZAR INSTALACION`; la respuesta de cierre se muestra una vez y el siguiente request queda bloqueado por lock/pending. |
| Anti-secretos | OK estático | Executor y preflight redactan claves, emails, paths sensibles, nombres de archivos privados, locks y tokens largos. La UI renderiza solo el contrato seguro, no comando/stdout/stderr. Debe repetirse el escaneo de HTML del runbook con datos canario. |
| Ejecución | OK | No hay primitivas de proceso en web/helpers. `proc_open` vive únicamente en `install_cli_executor.php`, recibe arrays argv, usa `bypass_shell`, allowlist de operaciones/keys y gates de apply por operación. El probe público nunca concede policy de apply. |
| Runtime/sesión | OK | Runtime válido debe estar fuera de repo y docroot. Logout, expiración, gate inválido, ausencia de disponibilidad y create/revoke limpian inputs privados. Cada paso descendente invalida resultados/fingerprints posteriores. |

## Invariantes de datos a verificar en E2E

La revisión confirma que el web no escribe SQL directamente: delega en los CLI
UNI-048. Por ello, las siguientes propiedades se verifican contra la DB temporal,
no se infieren de la UI:

1. Preflight permite avanzar únicamente con DB vacía.
2. Schema dry-run no modifica y apply crea schema/seeds; el segundo apply bloquea.
3. Bootstrap dry-run conserva `user=0`, `cliente=0`, `admin_config=0`; apply deja
   `user=1`, `cliente=1`, `admin_config=0`; el segundo apply bloquea.
4. Admin publish dry-run conserva `admin_config=0` y no crea carpeta; apply deja
   `admin_config=1`, publica una carpeta admin, no crea `install.lock`; rerun bloquea.
5. Finalize dry-run no crea lock; apply crea `install.lock`, no deja
   `.install.lock.pending`, y el siguiente GET `/install/` responde bloqueado.

## Hallazgos y hardening aplicado

### Corregido — temporal de Admin publish residual en limpieza global

**Impacto:** tras create/revoke del gate, un `admin_publish.json` de una sesión
interrumpida podía impedir eliminar su directorio y permanecer en el runtime
privado. No era público ni reutilizable por otra sesión, pero contradecía la
garantía de limpieza.

**Corrección:** `dqs_install_web_cleanup_sessions()` elimina de forma acotada
`admin_publish.json`, además de los otros dos nombres conocidos, sin seguir links.

### Sin cambios funcionales mayores

No se agregaron endpoints, escrituras web directas, parámetros libres ni nuevas
operaciones. El único cambio de runtime es la limpieza del temporal omitido. Se
añadió un auditor CLI-only/read-only para hacer repetibles los controles estáticos.

## Archivos explícitamente fuera de alcance

No se modifican `conexion.php`, `admin_tmp`, el admin activo, `database/install/*`
ni `tools/dqs_install_{preflight,schema_runner,bootstrap,admin_publish,finalize}.php`.

## Recomendación

No se justifica UNI-049.10 por un defecto conocido. Puede cerrarse UNI-049 después
de adjuntar una ejecución completa del runbook en un ambiente desechable. Abrir
UNI-049.10 solo si esa validación dinámica descubre una diferencia específica del
hosting (PHP CLI, permisos, DB o ubicación real de runtime).
