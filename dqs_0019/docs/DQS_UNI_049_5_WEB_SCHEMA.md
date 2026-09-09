# UNI-049.5 — Schema dry-run/apply desde web

## Alcance

El paso Schema reutiliza exclusivamente el connection-file privado que dejó DB / Preflight con estado `OK` o `WARN`. No vuelve a pedir credenciales, no escribe `conexion.php` y no publica admin ni ejecuta bootstrap, admin publish o finalize. El navegador solo envía acciones cerradas; toda ejecución pasa por `dqs_install_execute('schema_runner', ...)`.

## Flujo y autorización

1. `schema_dry_run` exige gate y sesión vigentes, ausencia de locks, CSRF válido, preflight `OK`/`WARN` y un `connection.php` regular, legible, no symlink, situado en el directorio aleatorio propiedad del runtime privado.
2. El formulario incluye contenido predeterminado por defecto. El resultado normalizado y censurado se conserva en sesión mediante POST/Redirect/GET, por lo que refresh no ejecuta nada.
3. Un dry-run `OK`/`WARN` registra un fingerprint no reversible del identificador privado de conexión, opción de contenido, operación, modo y fecha UTC.
4. Solo entonces aparece apply. Exige checkbox y la frase exacta `INSTALAR SCHEMA`; además vuelve a validar conexión, preflight, dry-run y fingerprint.
5. El caller concede `allow_schema_apply=true`, junto con `mode=apply` y `confirm_empty_install=true`. Ninguna otra operación obtiene esa policy.
6. Tras apply `OK`/`WARN`, un nuevo intento queda bloqueado localmente y queda habilitado el paso Bootstrap de UNI-049.6. Admin publish y Finalize siguen indisponibles.

Apply crea tablas y seeds en la DB temporal seleccionada. DDL puede tener autocommit: si se produce un fallo parcial, el operador debe descartar y recrear la DB temporal; no existe rollback web.

## Datos y redacción

La sesión conserva únicamente resultados web reducidos (status, exit code, duración, conteos, checks y errores ya censurados), timestamps, opción booleana y fingerprint. El path operativo del connection-file sigue siendo privado y jamás se renderiza. Se censuran claves sensibles, emails, usuarios de hosting, paths absolutos y tokens largos. Se descartan comando, stdout, stderr y campos fuente no permitidos.

Logout, gate revocado/no disponible y expiración limpian el connection-file y la sesión. Ejecutar un preflight nuevo limpia también todo estado Schema.

La autenticación queda ligada al fingerprint SHA-256 del estado no secreto del gate consumido. En cada request se compara con el gate actual; recreate, revoke o cualquier cambio de identidad invalida la sesión anterior, limpia su connection-file y obliga a ingresar el nuevo secreto. El fingerprint no contiene `secret_hash`, datos de DB ni paths y nunca se renderiza.

## Fail closed

El flujo bloquea por sesión/gate/lock/CSRF inválidos, preflight no aceptable, temporal ausente o fuera del runtime, dry-run no aceptable, fingerprint distinto, doble confirmación incompleta, policy ausente, `BLOCKED` del CLI o fallo local del executor. No reintenta, no avanza y no ejecuta rollback.
