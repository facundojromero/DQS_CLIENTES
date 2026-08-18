# UNI-049.7 — Admin publish controlado desde web

## Alcance y contrato real

`/install/` publica el admin exclusivamente mediante `tools/dqs_install_admin_publish.php` y `dqs_install_execute('admin_publish', ...)`; la web no copia carpetas ni ejecuta SQL. El CLI requiere una fuente de conexión, `--admin-template-dir`, `--target-root` y, salvo con `--print-template`, `--admin-config-file`; admite slug opcional y la escritura exige juntos `--apply --confirm-admin-publish`. `--print-template` necesita conexión, template y target válidos, y no se combina con JSON, config ni apply.

El paso no ejecuta Finalize, no crea `install.lock`, no escribe `conexion.php` y no toca schema, seeds, contenido público, regalos ni los CLI existentes.

## Policies privadas

El template no llega por POST: se exige exactamente un directorio real, no symlink, de primer nivel dentro del repositorio, cuyo nombre cumpla `admin[A-Za-z0-9]{4,28}` y no sea `admin_tmp`. Se usa únicamente como fuente y el CLI comprueba que permanezca sin cambios.

El target tampoco llega por POST ni se muestra. Debe ser un directorio real, escribible, no symlink y fuera del repositorio. La allowlist interna contiene el valor exacto de `DQS_INSTALLER_ADMIN_TARGET_ROOT` y, para test/desarrollo, un directorio `admin_target_root` ya existente bajo el runtime privado. Si no hay target válido, la web bloquea antes del executor.

El caller genera criptográficamente un slug `admin` más 16 caracteres hexadecimales, verifica que el destino no exista y lo muestra sin revelar rutas. El CLI vuelve a validar el patrón y que `admin_config` esté vacío antes de publicar.

## Archivo, autorización y ciclo de vida

`admin_publish.json` se crea de forma exclusiva, con permisos `0600` antes de escribir, junto al connection-file en `web_sessions/<id>/`. Contiene el slug y un objeto `admin_config` vacío conforme al schema actual; jamás se renderiza su ruta. Un dry-run nuevo lo reemplaza y ata conexión, archivo, template, target, slug y timestamp mediante un fingerprint SHA-256 no reversible.

Dry-run exige gate/sesión vigentes, CSRF, ausencia de locks, Preflight, Schema apply y Bootstrap apply `OK`/`WARN`, conexión privada vigente y ausencia de un apply previo exitoso. Apply repite esas comprobaciones, valida archivo y fingerprint, y exige checkbox más la frase exacta `PUBLICAR ADMIN`. Solo entonces concede `allow_admin_publish_apply=true` y `confirm_admin_publish=true`. Logout, expiración, gate revocado, nuevo Preflight, nuevo Schema apply y nuevo Bootstrap apply limpian el temporal y estado descendente.

## Salida segura

La UI muestra status, exit code, duración, resumen, checks, errores censurados y slug. Nunca muestra stdout, stderr, comando, fingerprints, credenciales, secreto, paths ni nombres de archivos privados. El executor censura además, sin distinguir mayúsculas, los nombres `admin_publish.json`, `admin_config.json`, `bootstrap.json` y `connection.php`. UNI-049.8 consume el target y slug validados por este paso, sin volver a solicitarlos por POST.
