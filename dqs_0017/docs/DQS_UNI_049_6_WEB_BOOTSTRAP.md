# UNI-049.6 — Bootstrap controlado desde web

## Alcance

`/install/` crea el usuario/admin inicial, la fila `cliente` y los `site_settings` que ya controla `tools/dqs_install_bootstrap.php`. La web no contiene SQL ni replica sus escrituras: genera un input JSON privado y ejecuta exclusivamente `dqs_install_execute('bootstrap', ...)`. No publica admin, no crea `admin_config` ni locks, no finaliza, no escribe `conexion.php`, no toca templates admin ni ejecuta schema/seeds.

## Template y formulario

El formulario refleja el template real del CLI para el schema versionado: `admin.email`, password y confirmación; `cliente.nombre`, `apellido`, `telefono`, `telefono2`, `direccion`, `ciudad`, `provincia` y `plan`; y los settings técnicos `plan_servicio`, `rsvp_modo`, `fuente_envios_whatsapp`, `whatsapp_enabled`, `regalos_enabled` y `rsvp_form_persist_enabled`. Los campos bancarios son excluidos por el propio contrato CLI. La web valida email con `filter_var`, coincidencia y mínimo de 6 caracteres para password, requeridos, enteros y longitudes antes de crear el JSON; el CLI vuelve a validar el contrato y las columnas reales. El valor operativo temporal `123456` es válido y debe ser cambiado posteriormente por el cliente o administrador.

## Autorización y estado

Dry-run exige gate y sesión ligada al gate, CSRF, locks ausentes, preflight y Schema apply `OK`/`WARN`, y el connection-file vigente dentro del runtime. `bootstrap.json` se crea exclusivamente con modo `0600`, sin seguir symlinks, en el mismo directorio aleatorio privado que la conexión. Un dry-run nuevo reemplaza el anterior. El resultado reducido y censurado y un fingerprint SHA-256 no reversible atan conexión, contenido y timestamp.

Apply solo aparece tras dry-run `OK`/`WARN`. Se vuelven a validar todos los prerrequisitos, el archivo y fingerprint, el checkbox y la frase exacta `CREAR BOOTSTRAP`. Solo entonces el caller concede `allow_bootstrap_apply=true` y `confirm_bootstrap=true`. Un apply exitoso impide repeticiones; Admin publish y Finalize permanecen no disponibles.

## Privacidad y limpieza

La UI conserva y muestra exclusivamente status, exit code, duración, resumen, checks y errores censurados. Nunca muestra comando, stdout/stderr, paths, credenciales, email, secreto del gate, archivos privados ni fingerprints. Logout, revocación/cambio de gate, expiración, nuevo preflight y nuevo Schema apply eliminan el temporal Bootstrap. La limpieza global del gate elimina tanto `bootstrap.json` como el connection-file sin seguir enlaces.

## Continuidad UNI-049.7

Un Bootstrap apply `OK`/`WARN` habilita el paso Admin publish controlado. Repetir Bootstrap apply invalida y limpia todo temporal, resultado y fingerprint de Admin publish; Bootstrap no concede por sí mismo permiso de publicación ni de Finalize.
