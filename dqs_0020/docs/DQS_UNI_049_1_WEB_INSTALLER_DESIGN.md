# UNI-049.1 — Diseño del instalador web controlado

## 1. Propósito, alcance y principios

Este documento define la arquitectura técnica y operativa de un futuro instalador web para DQS. **UNI-049.1 es únicamente diseño**: no crea endpoints, carpeta `install`, estado, archivos temporales ni `conexion.php`; no ejecuta los instaladores; no modifica la base de datos, el admin activo, `admin_tmp`, la tienda, RSVP, WhatsApp o regalos.

El principio rector es **orquestar, no reimplementar**. La lógica crítica ya validada en UNI-048 debe seguir siendo la autoridad para validaciones, mutaciones, verificaciones y bloqueos.

Se consideran dos alternativas:

* **Opción A — orquestador de CLIs:** la web invoca exclusivamente los cinco CLIs permitidos mediante un executor seguro y consume su salida `--json`. Reduce divergencias y permite avanzar incrementalmente.
* **Opción B — núcleo compartido:** en un refactor posterior se extrae lógica a `includes/install/*.php`; CLI y web llaman a la misma API interna. Puede mejorar testabilidad y evitar procesos hijos, pero amplía superficie web, exige una nueva revisión de seguridad y arriesga cambiar herramientas ya validadas.

**Recomendación:** comenzar con la opción A y una UI read-only que solo ejecute dry-runs. Habilitar cada `apply` en unidades posteriores, después de validar el contrato JSON y el executor. Evaluar la opción B únicamente si aparecen límites operativos medibles; nunca mantener dos implementaciones independientes.

## 2. Arquitectura general y fronteras de confianza

La arquitectura futura tendría cinco capas:

1. **Gate HTTP:** comprueba habilitación explícita, ausencia de `install.lock`, secreto de un solo uso, rate limit y origen de sesión.
2. **Controlador de flujo:** valida CSRF, autoriza la transición de estado, construye un comando tipado y exige confirmación para mutaciones.
3. **Estado y bóveda efímera:** conserva solo metadatos mínimos del proceso y secretos con vida limitada, fuera del document root y del repositorio.
4. **Executor seguro:** traduce una operación conocida a un CLI allowlistado, ejecuta con límites y devuelve un resultado normalizado.
5. **CLIs UNI-048:** realizan las comprobaciones y, cuando corresponda, las mutaciones. Su JSON y exit code son la fuente de verdad.

El navegador nunca conoce paths internos, líneas de comando, contenido de archivos privados ni stdout/stderr crudo. El proceso PHP web no recibe capacidad genérica de shell. La cuenta del servidor debe tener permisos mínimos: lectura del paquete canónico y templates permitidos; escritura solo en el directorio privado de runtime y en el `target-root` aprobado durante las fases que lo requieran.

## 3. Gate y modelo de seguridad

El instalador estará **deshabilitado por defecto**. Una fase futura definirá un mecanismo explícito de habilitación, preferentemente configuración de despliegue fuera del repositorio y con vencimiento. Estar habilitado no basta: toda petición debe comprobar que `install.lock` y `.install.lock.pending` no existen en el root esperado.

### 3.1 Controles obligatorios

* Un secreto one-time de alta entropía inicia la única sesión del instalador. Se compara en tiempo constante, vence pronto, no viaja por query string, no se persiste en claro y se invalida al usarlo, cancelar, finalizar o expirar.
* La sesión es exclusiva del instalador, con cookie `Secure`, `HttpOnly`, `SameSite=Strict`, identificador regenerado tras el gate, timeout absoluto y por inactividad cortos. No reutiliza autenticación del admin.
* Toda acción POST exige token CSRF ligado a sesión y acción; el token rota tras operaciones sensibles.
* Rate limit básico por sesión y origen, con backoff para el secreto; los errores no revelan si un secreto parcial fue correcto.
* Cada proceso tiene timeout corto, límite de salida y terminación controlada. Una expiración produce estado indeterminado que obliga a reauditar antes de reintentar, nunca a asumir éxito o rollback.
* Cabeceras `no-store`, protección contra framing y una CSP restrictiva evitan cache y exposición accidental. Solo se acepta HTTPS.
* Los permisos se verifican otra vez inmediatamente antes de cada `apply` para reducir TOCTOU.

### 3.2 Bloqueos fail-closed

La web bloquea antes de invocar un CLI y también respeta cualquier `BLOCKED` del CLI cuando:

* existe `install.lock` o `.install.lock.pending`, hay evidencia de instalación existente o el instalador no está explícitamente habilitado;
* el estado solicitado no es la siguiente transición válida;
* `target-root` no está en la allowlist, no resuelve al root esperado, contiene symlinks inseguros, coincide con el proyecto activo/plantilla o no cumple permisos;
* la plantilla, el slug o cualquier path no pertenece a la allowlist del servidor;
* `admin_config`, `user` o `cliente` ya tienen filas en una etapa que exige que estén vacíos, o sus conteos no corresponden al estado esperado;
* falla el CSRF, secreto, sesión, rate limit, contrato JSON, timeout, exit code o cualquier precondición.

Un refresh, doble click o petición repetida no debe repetir una mutación. Se usa un nonce por acción y un lock exclusivo del estado; después de cualquier respuesta ambigua se ejecuta el dry-run/auditoría correspondiente.

### 3.3 Executor: nunca `shell_exec` directo

Si la web invoca CLIs, usará un componente dedicado con estas garantías:

* allowlist cerrada de binario PHP, script y opciones admitidas por cada operación;
* argumentos construidos desde valores validados por el servidor, nunca concatenados desde input crudo;
* `escapeshellarg` individual para cada argumento (o una API equivalente de argumentos separados), y `proc_open` o equivalente controlado;
* directorio de trabajo fijo, entorno mínimo, stdin cerrado, timeout, límite de memoria/salida, captura separada de stdout y stderr y terminación del árbol de proceso;
* exit code obligatorio y coherente con el JSON; rechazo de JSON inválido, múltiples documentos, salida truncada o campos desconocidos críticos;
* prohibición de comandos, opciones y paths arbitrarios; los roots/templates se resuelven contra allowlists canónicas y se vuelven a comprobar;
* stdout/stderr crudos se mantienen solo en memoria durante el análisis y nunca se entregan al navegador o logs;
* argumentos secretos y contenido de archivos privados jamás aparecen en logs, reportes, excepciones o métricas.

La allowlist inicial contiene únicamente `tools/dqs_install_preflight.php`, `tools/dqs_install_schema_runner.php`, `tools/dqs_install_bootstrap.php`, `tools/dqs_install_admin_publish.php` y `tools/dqs_install_finalize.php`. No habrá una caja de texto de comando ni una vía de escape de depuración en producción.

## 4. Secretos y credenciales de base de datos

La pantalla DB recibe `host`, `dbname`, `username` y `password` por POST sobre HTTPS. Se validan presencia, tipo, longitud máxima, bytes de control/nulos y formato apropiado; no se intenta “sanear” para formar shell o PHP. La prueba de conexión se delega al preflight mediante un archivo compatible.

El backend crea el **connection-file temporal fuera del repositorio y document root**, en un directorio privado preconfigurado, usando creación exclusiva contra symlinks, nombre aleatorio no predecible y permisos `0600`. Escribe las cuatro asignaciones literales esperadas y nunca:

* muestra el password DB o el contenido completo del archivo;
* registra credenciales, DSN, línea de comando completa o path completo;
* guarda el password en estado JSON, cookie, sesión serializada, HTML o reporte;
* commitea temporales o acepta como ubicación un path enviado por el cliente.

El handle/path opaco queda asociado a la sesión únicamente durante el flujo. Se elimina en éxito, cancelación, error y expiración; un recolector seguro elimina residuos vencidos. Los bootstrap/admin passwords reciben el mismo tratamiento: solo viven lo necesario para producir el input privado del CLI, nunca se muestran de vuelta ni se guardan en claro. Los archivos de bootstrap y `admin_config` también serán privados, `0600`, censurados y eliminados.

UNI-049.1 **no escribe `conexion.php`**. La política, escritura atómica, permisos, formato y verificación de la configuración permanente quedan expresamente para una unidad futura independiente; finalizar debe bloquearse si esa fase futura no ha resuelto cómo la aplicación usará la conexión definitiva.

## 5. Flujo web propuesto

En todas las pantallas se revalida el gate y el estado. Cada dry-run consume `--json`; un apply requiere haber completado recientemente el dry-run con las mismas entradas (comparadas por fingerprint no reversible), doble confirmación y una nueva comprobación de precondiciones.

### Paso 0 — Gate de seguridad

Validar habilitación explícita, HTTPS, secreto one-time, rate limit y ausencia de locks. Crear/regenerar la sesión propia y el estado `started`. Si se detecta instalación existente, mostrar un bloqueo genérico y no ofrecer continuidad.

### Paso 1 — Datos DB y preflight

Capturar y validar las cuatro credenciales, crear el connection-file efímero `0600` y ejecutar preflight con `--connection-file` y `--json`. Solo `OK`/`WARN` y una DB vacía permiten `db_validated`; los `WARN` requieren revisión visible.

### Paso 2 — Schema

Ejecutar schema runner en dry-run, mostrar tablas/seeds/checks previstos y pasar a `schema_dry_run_ok`. “Aplicar schema” muestra explícitamente **“esto modifica la DB”**, exige doble confirmación y ejecuta con `--apply --confirm-empty-install --json`. Tras éxito/verificación pasa a `schema_applied`.

### Paso 3 — Bootstrap

Solicitar email/password admin, cliente mediante formulario dinámico o template allowlistado, y settings de plan/RSVP/WhatsApp/regalos. La UI no activa funcionalidad: solo expresa los valores que admite el contrato validado. Genera un bootstrap-file privado, ejecuta dry-run y luego, con doble confirmación, `--apply --confirm-bootstrap --json`. El password no se vuelve a renderizar. Estados: `bootstrap_dry_run_ok` y `bootstrap_applied`.

### Paso 4 — Admin publish

Generar criptográficamente o validar un `admin_slug`; elegir una plantilla y `target-root` de listas provistas por el servidor. Crear el config privado, ejecutar dry-run y mostrar **“esto crea archivos y una fila admin_config”**. Tras doble confirmación invocar `--apply --confirm-admin-publish --json`. Estados: `admin_publish_dry_run_ok` y `admin_published`.

### Paso 5 — Finalize

Ejecutar auditoría finalize dry-run. Mostrar conteos, checks, WARN y límites de recuperación. Tras doble confirmación ejecutar `--apply --confirm-finalize --json`, que crea `.install.lock.pending` y lo renombra a `install.lock`. Solo después de verificar el lock se marca `finalized`; se invalidan secreto/sesión y se eliminan todos los temporales.

### Paso 6 — Cierre

Mostrar una sola vez un resumen censurado con URL y `admin_slug` (nunca passwords), instrucciones de acceso y recordatorio de retirar el código del instalador o mantenerlo inaccesible. `install.lock` es defensa obligatoria, no sustituto de deshabilitar la ruta. No se ofrece botón para reabrir o borrar el lock.

## 6. Relación y contrato con los CLIs

La web agrega siempre `--json` y usa `--connection-file` privado; no usa `--using-current-connection` durante instalaciones nuevas. Opciones de selección como contenido default, template, slug y root nacen de controles tipados/allowlistados.

| Paso | CLI y entradas controladas | Salida/éxito esperado | Bloqueos que la UI debe respetar |
|---|---|---|---|
| DB | `tools/dqs_install_preflight.php --connection-file=… --json` | checks de runtime, archivos y DB; exit 0 para `OK`/`WARN` | lock presente, paquete inválido, DB no vacía o conexión fallida |
| Schema | `tools/dqs_install_schema_runner.php --connection-file=… [--include-default-content] [--apply --confirm-empty-install] --json` | resumen/parser y schema/seeds verificados | cualquier tabla, manifest/SQL inválido, confirmación incompleta |
| Bootstrap | `tools/dqs_install_bootstrap.php --connection-file=… --bootstrap-file=… [--apply --confirm-bootstrap] --json` | un `user`, un `cliente`, settings y password hasheado | schema incompleto, seeds/trigger incorrectos, `user`/`cliente`/`admin_config` no vacíos, tablas no InnoDB |
| Admin | `tools/dqs_install_admin_publish.php --connection-file=… --admin-template-dir=… --target-root=… --admin-config-file=… --admin-slug=… [--apply --confirm-admin-publish] --json` | carpeta publicada y una fila `admin_config` | conteos inesperados, slug/path/template inseguros, staging/destino existente o lock |
| Finalize | `tools/dqs_install_finalize.php --connection-file=… --target-root=… --admin-slug=… [--apply --confirm-finalize] --json` | auditoría final y lock atómico | schema/conteos/admin inconsistentes, destino inválido, lock/pending existente |

El futuro contrato versionado debe incluir como mínimo `contract_version`, `operation`, `mode`, `status`, `checks[]` con código estable/severidad/mensaje censurado, un resumen tipado y `run_id`. La UI traduce `OK`, `WARN`, `BLOCKED` y `FAILED`; no deduce éxito de texto humano. Exit `0` solo es aceptable para `OK`/`WARN`, exit `1` para `BLOCKED`/`FAILED` y exit `2` es error de uso/programación. Toda incoherencia se trata como `FAILED` local y bloquea.

## 7. Máquina de estados y persistencia

Estados persistentes propuestos:

`started → db_validated → schema_dry_run_ok → schema_applied → bootstrap_dry_run_ok → bootstrap_applied → admin_publish_dry_run_ok → admin_published → finalize_dry_run_ok → finalized`.

Desde cualquier estado no terminal puede llegarse a `failed` o `cancelled`. Un fallo recuperable no habilita el siguiente paso: tras corregirlo se vuelve a ejecutar el dry-run del paso y se registra una nueva transición. `finalized` es terminal. No se permite retroceder una mutación desde la UI.

Cada transición guarda versión, estado anterior/nuevo, timestamps, run ID, códigos censurados, fingerprint de entradas no secretas y resultado; nunca credenciales o passwords. Se usa escritura atómica, permisos mínimos, lock exclusivo y protección de integridad.

### Alternativas

* **JSON privado fuera del repo/document root:** simple, no depende del schema que está instalándose y sirve desde Paso 0. Riesgos: coordinación concurrente, limpieza, permisos y recuperación tras caída.
* **Tabla futura `installer_state`:** facilita locking/consultas y auditoría, pero no existe antes del schema, mezcla control con la DB objetivo y agrega datos/tablas al contrato canónico.

**Recomendación inicial:** archivo de estado JSON en un directorio privado preconfigurado fuera del repositorio, sin secretos y con lock/escritura atómica. Considerar una tabla solo en una fase futura y mediante migración explícita; no crearla implícitamente desde la web.

## 8. UI, observabilidad y reportes

* Mostrar una barra de pasos, estado actual y resumen de checks agrupados; no mostrar logs crudos por defecto ni ofrecerlos sin censura.
* `WARN` se presenta claramente y exige reconocimiento; `BLOCKED`/`FAILED` explica una remediación segura sin datos sensibles. Cualquier `BLOCKED` deshabilita Apply.
* Las acciones Apply tienen estilo destructivo, texto concreto (“modifica DB”, “crea archivos”), resumen de alcance y doble confirmación; no se confirman con parámetros GET.
* Permitir descargar un reporte censurado con versiones, timestamps, códigos de check, estados y fingerprints públicos. Excluir credenciales, passwords, contenido/path completo de archivos, comandos y stdout/stderr.
* Logs estructurados mínimos: run ID, operación, transición, duración, exit code y códigos de check. Aplicar allowlist de campos, no una censura oportunista posterior.

## 9. Errores, reintentos y límites de rollback

Antes de Apply la UI debe explicar:

* **Schema:** DDL puede hacer autocommit; el schema runner no ofrece rollback completo. Un fallo puede dejar una DB parcial. Se bloquea continuidad y se recomienda descartar/recrear una DB nueva, no “desinstalar” desde la web.
* **Bootstrap:** `user`, `cliente` y `site_settings` se escriben en transacción; ante fallo el CLI hace rollback. Aun así, la web reejecuta dry-run antes de permitir reintento.
* **Admin publish:** el CLI revierte DB y limpia exclusivamente staging/destino creados por su propia ejecución. La web jamás elimina rutas por cuenta propia.
* **Finalize:** no cambia DB; crea exclusivamente `.install.lock.pending` y hace rename atómico a `install.lock`. Un pending residual se trata como bloqueo y requiere runbook autorizado.

Ante timeout, desconexión o JSON inválido el resultado es **desconocido**, no fallido asumido. La web conserva solo diagnóstico censurado, bloquea botones y ejecuta una auditoría/dry-run read-only para reconciliar realidad con estado. Nunca repite automáticamente un apply. Cancelar elimina secretos/temporales y marca `cancelled`, pero no promete revertir cambios ya confirmados.

## 10. Fuera de alcance y decisiones pendientes

No se implementa nada de lo anterior en UNI-049.1. Quedan para fases posteriores: contrato JSON versionado, executor, gate/configuración de habilitación, UI, almacenamiento de estado, gestión de temporales, política definitiva de `conexion.php`, tests de concurrencia/fallos y despliegue piloto. La secuencia propuesta está en `DQS_UNI_049_1_WEB_INSTALLER_PHASES.md`.

