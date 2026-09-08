# UNI-049.4 — DB / Preflight web

## Alcance y garantías

El segundo paso de `/install/` recibe por `POST` y con CSRF las cuatro credenciales de una base temporal. La web crea un archivo de conexión privado y ejecuta exclusivamente `dqs_install_execute('preflight', ...)` en modo dry-run. No invoca schema runner, bootstrap, publicación, finalize ni apply; no crea locks ni escribe en la base.

El archivo se crea bajo `web_sessions/<id aleatorio>/connection.php` en el runtime privado validado por UNI-049.3, fuera del repositorio y del document root. Los directorios usan modo `0700`, el archivo `0600`, y la apertura usa creación exclusiva. Los literales se escriben con `var_export`, sin construir comandos de shell.

Un resultado `OK` o `WARN` conserva temporalmente el archivo para la siguiente unidad y guarda en sesión solamente su referencia privada. `BLOCKED` y `FAILED` lo eliminan. Reemplazar la conexión, cerrar o expirar la sesión, revocar el gate o crear otro gate limpia los temporales cuando están disponibles.

## Privacidad y estados

La sesión conserva únicamente resultado normalizado/censurado, estado, fecha UTC y, para resultados válidos, la referencia interna. Nunca conserva host, base, usuario o contraseña. El formulario nunca repuebla campos. Una segunda capa de redacción elimina claves sensibles, correos, usuarios estilo Hostinger, rutas absolutas y tokens largos. La UI no expone errores internos, stdout, stderr, comando ni rutas.

Un refresh usa el resultado censurado en sesión y no vuelve a ejecutar el CLI. `Schema` y todos los pasos posteriores siguen como **Próxima fase / no disponible**.

## Prueba manual

1. Ejecute `php tools/dqs_install_web_gate_prepare.php --create --ttl-minutes=30` y copie el secreto one-time.
2. Abra `/install/`, autentíquese y compruebe que aparece **DB / Preflight**.
3. Envíe campos vacíos, con controles o con `<script>`: debe verse `BLOCKED`, sin avanzar ni conservar archivo.
4. Envíe credenciales incorrectas: el resultado debe ser seguro (`BLOCKED` o `FAILED`) y nunca repetir valores ingresados.
5. Envíe credenciales de una DB temporal vacía: debe obtener `OK` o `WARN` según los checks del entorno. Compruebe por separado que la base continúa sin tablas nuevas.
6. Recargue: el resultado permanece y no se ejecuta otra vez. Cierre sesión: vuelve al gate y se elimina el temporal.
7. Autentique una sesión nueva y ejecute `php tools/dqs_install_web_gate_prepare.php --revoke`; `/install/` debe responder con el bloqueo genérico y los temporales deben desaparecer.

Para la prueba anti-secretos, guarde el HTML de respuesta y busque el password, usuario, base, host, secreto del gate, `/home`, `/tmp` y el nombre `connection.php`: ninguna credencial, ruta ni secreto debe aparecer. No use credenciales productivas para esta comprobación.

## Regresión del contrato del executor

El preflight emite IDs `manifest.reference.<sha1>` para identificar referencias del manifest sin publicar su ruta como ID. El executor reconoce estrictamente ese formato como un digest no secreto; cualquier otro token largo, control, email, path o carácter ajeno al alfabeto de IDs sigue fallando cerrado con `Invalid check at index N.`. Esto permite normalizar todos los checks reales del preflight sin relajar los estados permitidos ni copiar el check rechazado al diagnóstico.

Ejecute el probe con un connection-file privado fuera del repositorio:

```bash
php tools/dqs_install_executor_probe.php --operation=preflight --connection-file=/tmp/dqs_empty_connection.php --json > /tmp/dqs_executor_preflight_tmp.json
php -r '$j=json_decode(file_get_contents("/tmp/dqs_executor_preflight_tmp.json"), true); echo json_last_error_msg(), PHP_EOL; echo "status=",($j["status"]??""),PHP_EOL; echo "checks=",($j["summary"]["check_count"]??"NA"),PHP_EOL; echo "errors=",implode(" | ", $j["errors"] ?? []),PHP_EOL;'
```

El JSON debe ser válido, `checks` debe ser mayor que cero y `errors` debe estar vacío. Una conexión falsa normalmente termina `BLOCKED`; una DB temporal vacía puede terminar `OK` o `WARN`. La UI muestra errores locales ya normalizados bajo **Diagnóstico seguro**, después de aplicar nuevamente la redacción web, y nunca muestra stdout, stderr ni el comando.

### PHP CLI desde FPM/CGI

El executor no presupone que `PHP_BINARY` sea un CLI cuando corre bajo web. Resuelve y verifica un ejecutable CLI en este orden: policy interna `php_binary`, `DQS_INSTALLER_PHP_CLI`, `PHP_BINARY` solo bajo SAPI CLI, `PHP_BINDIR/php` y rutas conocidas del hosting. Cada candidato debe ser absoluto, ejecutable y externo al repositorio, y responder exactamente `cli` a un probe `proc_open` con argumentos en array, timeout corto y shell deshabilitado. El contrato solo publica el origen seguro (`policy`, `env`, `php_binary`, `php_bindir` o `known_path`), nunca el path. Si ninguno valida, falla localmente con `PHP CLI binary could not be resolved.` sin invocar un CLI UNI-048.

## Continuidad con Schema (UNI-049.5)

Un preflight `OK`/`WARN` conserva su connection-file dentro del runtime privado para el paso Schema; el path nunca se renderiza. Un preflight nuevo invalida los resultados y el fingerprint de Schema. Logout, revocación o expiración eliminan el temporal y limpian toda la sesión.
