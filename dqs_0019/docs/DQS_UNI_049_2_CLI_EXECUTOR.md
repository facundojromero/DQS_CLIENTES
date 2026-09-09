# UNI-049.2 — executor CLI seguro

## Alcance y arquitectura

`includes/install/install_cli_executor.php` es una frontera interna entre una futura interfaz web y los CLI de UNI-048. Recibe una **operación interna** y parámetros tipados, traduce únicamente esa combinación a argumentos conocidos y devuelve el contrato V1. No recibe un comando, fragmentos de `argv`, flags libres ni variables de entorno del usuario.

La allowlist fija relaciona `preflight`, `schema_runner`, `bootstrap`, `admin_publish` y `finalize` con sus scripts del repositorio. Todas las ejecuciones agregan `--json`; UNI-049.2 acepta exclusivamente `dry-run`. `apply` y sus confirmaciones ni siquiera forman parte del API del executor. El probe los reconoce solamente para devolver un bloqueo local demostrable.

## Ejecución controlada

Se usa `proc_open` con un **array de argumentos**, `PHP_BINARY`, `bypass_shell`, el root real del repositorio como `cwd` y un entorno mínimo (`PATH`, `LANG`, `LC_ALL`). No se interpola un string de shell. `stdin` se cierra inmediatamente y `stdout`/`stderr` se capturan por separado y nunca se devuelven al caller.

El timeout predeterminado es 30 segundos (política interna limitada a 1–120) y la salida combinada se limita a 256 KiB (configurable internamente entre 1 KiB y 1 MiB). Timeout o exceso terminan el hijo y fallan cerrado. `stderr`, salida vacía, JSON inválido/múltiple, checks inválidos y discrepancias entre status/exit code producen `FAILED` local.

`shell_exec` directo no permite esta separación fiable de canales, control de stdin, timeout, terminación ni construcción de argumentos sin shell; por eso no se usa.

## Validación default-deny

* Cada operación tiene su propia allowlist de claves; una clave desconocida bloquea antes de `proc_open`.
* Los flags deben ser booleanos PHP reales. `admin_slug` debe comenzar por `admin`, ser alfanumérico, tener entre 9 y 33 caracteres y no ser `admin_tmp`.
* Los archivos privados (`connection`, `bootstrap`, `admin-config`) deben existir, ser legibles, no ser symlinks y resolver fuera del repositorio.
* El template admin debe ser un directorio real dentro del repositorio y no `admin_tmp`.
* `target-root` debe ser un directorio real fuera del repositorio, no un symlink y coincidir con una allowlist proporcionada por el caller interno.
* Los paths se pasan como valores separados. Los errores nunca contienen el valor ni un path absoluto.
* `using-current-connection` solo puede habilitarlo una política interna explícita; el probe no lo habilita.

## Probe y futuro

`tools/dqs_install_executor_probe.php` es exclusivamente CLI. Permite verificar dry-runs y el JSON normalizado sin publicar superficie HTTP. No existe carpeta `install`, endpoint ni cambio de base de datos en esta unidad. Una fase posterior podrá integrar el executor tras autenticar y autorizar al caller; habilitar mutaciones requerirá un diseño nuevo, no simplemente pasar `apply`.

Ejemplo seguro:

```bash
php tools/dqs_install_executor_probe.php --operation=preflight --no-db --json
```

Pruebas negativas previstas: operación/parámetro desconocido, `apply`, confirmaciones, slug inválido, archivo privado dentro del repo y target fuera de allowlist se bloquean localmente. JSON inválido o múltiple, exit code incoherente, timeout, stderr y salida excesiva fallan cerrado. La redacción se vuelve a aplicar incluso si el CLI fuente afirma haber censurado sus datos.

## Extensión acotada UNI-049.5

`schema_runner` conserva `dry-run` como modo predeterminado. La única mutación admitida por el executor es `schema_runner` con la combinación exacta `mode=apply`, `confirm_empty_install=true` y la policy interna `allow_schema_apply=true`. El executor traduce esa combinación a `--apply --confirm-empty-install`; no acepta argumentos libres ni una confirmación genérica. Sin la policy, el intento queda `BLOCKED` antes de iniciar un proceso.

En esa extensión, `admin_publish` y `finalize` continuaban sin apply. El probe tampoco concede policies de escritura. El endpoint autenticado de UNI-049.5 es el único caller que concede `allow_schema_apply`.

## Extensión acotada UNI-049.6

Bootstrap apply solo admite la combinación exacta `operation=bootstrap`, `mode=apply`, `confirm_bootstrap=true` y la policy interna `allow_bootstrap_apply=true`. Se traduce únicamente a `--connection-file`, `--bootstrap-file`, `--apply`, `--confirm-bootstrap` y `--json`. No existe confirmación genérica ni se habilitan apply de Admin publish o Finalize. El probe nunca concede esta policy, por lo que `--operation=bootstrap --apply` sigue bloqueado localmente.

## Extensión acotada UNI-049.7

Admin publish apply solo admite la combinación exacta `operation=admin_publish`, `mode=apply`, `confirm_admin_publish=true` y la policy interna `allow_admin_publish_apply=true`. El executor traduce únicamente los cinco inputs tipados (`connection-file`, `admin-template-dir`, `target-root`, `admin-config-file`, `admin-slug`) más `--apply --confirm-admin-publish --json`. El caller debe incluir el target real en `target_roots`; no hay argumentos libres, confirmación genérica ni apply de Finalize. El probe no concede la policy y por ello su intento de apply permanece `BLOCKED` antes de ejecutar el CLI.

## Extensión acotada UNI-049.8

Finalize apply solo admite la combinación exacta `operation=finalize`, `mode=apply`, `confirm_finalize=true` y la policy interna `allow_finalize_apply=true`. Se traduce únicamente a `--connection-file`, `--target-root`, `--admin-slug`, `--apply --confirm-finalize --json`. La web obtiene conexión, target y slug del estado privado validado; no acepta esos valores por POST. El probe no concede la policy, por lo que Finalize apply continúa `BLOCKED` por esa superficie.
