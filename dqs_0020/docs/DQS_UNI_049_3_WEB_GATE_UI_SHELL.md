# UNI-049.3 — Gate seguro y UI shell read-only

Esta unidad publica una superficie **sin operaciones de instalación**. No conecta a la base de datos, no solicita credenciales de DB, no ejecuta el executor ni los CLI UNI-048 y no habilita `apply`.

## Modelo de seguridad

- `/install/` está bloqueado por defecto y también cuando existe `install.lock` o `.install.lock.pending` en la raíz del proyecto.
- El gate vive fuera del repositorio y del document root. CLI y web comparten un único resolver: opción explícita del helper, `DQS_INSTALLER_RUNTIME_DIR`, un fallback determinístico junto al directorio que contiene `public_html` (útil en Hostinger) y finalmente `HOME/.dqs_installer_runtime`.
- El directorio debe ser real (no symlink), privado, legible y escribible por PHP. El helper intenta crearlo con modo `0700`; `gate.json` y el lock auxiliar usan `0600` cuando el sistema lo permite.
- El secreto aleatorio solo se imprime al crearlo. `gate.json` conserva únicamente un `password_hash`. Cinco intentos, uso previo, expiración, revocación o un runtime inseguro bloquean el acceso.
- Un login correcto consume el secreto bajo lock, regenera el ID y abre una sesión propia con cookies `HttpOnly`, `SameSite=Strict` y `Secure` bajo HTTPS. La inactividad vence a los 10 minutos y el límite absoluto es 30 minutos.
- Todos los POST requieren CSRF. Login y logout son las únicas acciones reconocidas. Las respuestas usan headers anti-cache, anti-frame, nosniff, no-referrer y una CSP sin scripts.

Los bloqueos y errores web son deliberadamente genéricos: no exponen secretos, hashes ni rutas internas.

## Operación manual

Desde la raíz del proyecto, el operador puede crear un gate de 30 minutos:

```sh
php tools/dqs_install_web_gate_prepare.php --create --ttl-minutes=30
```

Copie el secreto mostrado en un canal privado: no volverá a mostrarse y no debe incluirse en issues, PR o logs compartidos. Puede elegir un directorio privado mediante la variable de entorno o `--runtime-dir=/ruta/privada`; el helper nunca muestra esa ruta.

Estado seguro y revocación:

```sh
php tools/dqs_install_web_gate_prepare.php --status
php tools/dqs_install_web_gate_prepare.php --status --debug-safe
php tools/dqs_install_web_gate_prepare.php --revoke
```

`--debug-safe` informa únicamente la fuente de resolución, el basename, la validez y la presencia del gate. Nunca imprime la ruta completa, el secreto ni su hash.

## Prueba funcional

1. Sin `gate.json`, abra `/install/`: debe responder `403` con bloqueo genérico y sin shell.
2. Cree el gate y recargue: aparece exclusivamente el formulario del secreto, sin campos DB.
3. Envíe el secreto desde el formulario: aparece el shell read-only con Gate en `OK` y los demás pasos no disponibles.
4. Recargue durante la vigencia de la sesión: el shell permanece visible.
5. Pulse **Cerrar sesión**: el token consumido no permite otro login. Cree un gate nuevo para reingresar.
6. Revoque el gate: `/install/` vuelve a quedar bloqueado.
7. Para probar los locks, cree de forma controlada uno de los nombres bloqueantes en la raíz, confirme el `403` y elimine únicamente el archivo temporal de prueba.

## Límites explícitos

La lista visual comprueba solo presencia de archivos internos mediante PHP. No carga ni invoca el executor, no abre procesos, no usa shell, no crea `install.lock`, no escribe DB y no modifica conexión, admin, schema, seeds ni contenido público.
