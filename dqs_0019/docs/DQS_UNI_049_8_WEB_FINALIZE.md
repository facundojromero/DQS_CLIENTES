# UNI-049.8 — Finalize controlado desde web

## Contrato real y alcance

`tools/dqs_install_finalize.php` exige una conexión, `--target-root` y `--admin-slug`; opera en dry-run por defecto y solo escribe al recibir juntos `--apply --confirm-finalize`. `/install/` lo invoca exclusivamente mediante `dqs_install_execute('finalize', ...)`. La web no ejecuta SQL, no publica carpetas y no reimplementa las auditorías o creación atómica del lock del CLI.

Finalize valida el schema instalado, el bootstrap único, `admin_config`, los conteos que deben permanecer vacíos y la carpeta admin publicada. Un apply satisfactorio crea `install.lock`; el temporal `.install.lock.pending` pertenece exclusivamente al mecanismo atómico del CLI.

## Encadenamiento y policy privada

El paso requiere gate y sesión vinculados vigentes, CSRF, locks ausentes y estados `OK`/`WARN` de Preflight, Schema apply, Bootstrap apply y Admin publish apply. El connection-file se revalida dentro del runtime privado. Target root y `admin_slug` nunca se aceptan por POST: se reutilizan mediante las resoluciones internas de UNI-049.7. El slug cumple `^admin[A-Za-z0-9]{4,28}$` y su carpeta real, legible, no symlink debe existir directamente bajo el target.

El dry-run guarda exclusivamente el resultado censurado, timestamp y un fingerprint SHA-256 no reversible que ata el contenido de conexión, target, slug y momento de Admin publish apply. Apply recalcula esa vinculación, exige el checkbox y la frase exacta `FINALIZAR INSTALACION`, y recién entonces entrega `confirm_finalize=true` junto con `allow_finalize_apply=true` al executor. No hay flags libres ni confirmación genérica. El probe nunca entrega esa policy.

## Salida y cierre

La respuesta segura contiene status, exit code, duración, resumen, checks y errores, además del slug mostrado por separado. Nunca contiene comando, stdout, stderr, paths, credenciales, secreto o fingerprints. La redacción convierte los basenames privados y los nombres de lock en etiquetas seguras.

Tras un apply `OK`/`WARN`, la respuesta actual puede mostrar el resultado una vez y no ofrece más acciones. Una nueva petición detecta `install.lock` en el target interno antes de autenticar y bloquea `/install/`; nunca elimina el lock.
