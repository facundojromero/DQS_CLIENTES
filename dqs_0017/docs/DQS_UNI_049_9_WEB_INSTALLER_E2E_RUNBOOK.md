# UNI-049.9 — Runbook E2E real del instalador web

Este procedimiento es destructivo **solo para la DB temporal elegida**. No usar una
DB con datos. Guardar evidencias redactadas; nunca capturar secretos, credenciales,
comandos completos ni paths absolutos.

## 1. Preparación

1. Crear una DB temporal vacía y un usuario limitado a ella.
2. Configurar `DQS_INSTALLER_RUNTIME_DIR` fuera del repo y del document root, crear
   el directorio con permisos privados y confirmar que no existen `install.lock`
   ni `.install.lock.pending` en los destinos relevantes.
3. Registrar conteos iniciales de tablas, `user`, `cliente` y `admin_config`, y un
   listado de carpetas admin. El esperado inicial es DB vacía.
4. Ejecutar lint, auditor estático, process check y probes de la sección 9.

## 2. Gate

1. Sin gate, abrir `/install/`: esperar 403 y pantalla bloqueada.
2. Crear gate con `php tools/dqs_install_web_gate_prepare.php --create`; copiar el
   secreto una vez sin registrarlo en evidencia.
3. Abrir `/install/`: esperar formulario de login. Inspeccionar HTML y confirmar
   que no contiene el secreto.
4. Probar `GET /install/?secret=CANARY` y `GET /install/?action=login`: esperar
   request bloqueado y ninguna acción.
5. Login por POST. En otro navegador intentar el mismo secreto: debe fallar.
6. Crear un gate nuevo o revocar el actual desde CLI y refrescar la sesión previa:
   debe quedar invalidada. Confirmar que `web_sessions` no conserva los tres
   temporales privados.
7. Crear/consumir un gate final para continuar.

## 3. DB / Preflight

1. Enviar credenciales solo mediante el formulario. Confirmar que URL, HTML y
   resultado no las contienen.
2. Contra una DB no vacía controlada, esperar `BLOCKED` y Schema no disponible.
3. Iniciar nuevo preflight con la DB vacía: esperar OK/WARN y Schema disponible.
4. Confirmar que el nuevo preflight eliminó temporales/resultados posteriores de
   intentos anteriores y que `connection.php` existe solo en runtime privado, 0600.

## 4. Schema

1. Ejecutar dry-run y comparar conteo/listado DB: sin cambios.
2. Intentar apply sin dry-run vigente, sin checkbox y con frase incorrecta: cada
   caso debe responder `BLOCKED` sin cambios.
3. Repetir dry-run y apply con `INSTALAR SCHEMA`: esperar OK/WARN y schema/seeds.
4. Reintentar apply: esperar `BLOCKED`. Confirmar invalidación de estados de
   Bootstrap/Admin publish/Finalize ante un nuevo Schema apply autorizado.

## 5. Bootstrap

1. Confirmar que el paso solo aparece tras Schema apply OK/WARN.
2. Capturar datos canario y ejecutar dry-run. Consultar DB: `user=0`, `cliente=0`,
   `admin_config=0`. Confirmar `bootstrap.json` privado 0600.
3. Probar frase incorrecta: `BLOCKED`, mismos conteos.
4. Aplicar con `CREAR BOOTSTRAP`: esperar `user=1`, `cliente=1`, `admin_config=0`.
5. Reintentar: `BLOCKED` y mismos conteos.

## 6. Admin publish

1. Confirmar que solo aparece tras Bootstrap apply OK/WARN.
2. Añadir manualmente `template`, `target` y `slug` al POST de dry-run: no deben
   influir; los valores se resuelven por policy interna.
3. Dry-run: `admin_config=0`, sin carpeta nueva y sin lock.
4. Frase incorrecta: `BLOCKED`. Aplicar con `PUBLICAR ADMIN`: esperar
   `admin_config=1`, exactamente una carpeta publicada y ausencia de install lock.
5. Reintentar: `BLOCKED`.

## 7. Finalize

1. Confirmar que solo aparece tras Admin publish apply OK/WARN.
2. Dry-run: no hay `install.lock` ni `.install.lock.pending` residual.
3. Frase incorrecta: `BLOCKED`, sin lock.
4. Aplicar con `FINALIZAR INSTALACION`: esperar pantalla de cierre e
   `install.lock`. Confirmar que `.install.lock.pending` no existe.
5. Refrescar/reabrir `/install/`: esperar 403/pantalla bloqueada.

## 8. Barrido anti-secretos

Guardar el HTML de cada respuesta en un directorio de evidencia fuera del repo y
buscar (con canarios únicos cuando aplique): password, host, DB name/user, gate
secret, emails, `/home`, `/tmp`, nombres `connection.php`, `bootstrap.json`,
`admin_publish.json`, `admin_config.json`, paths absolutos de lock, comandos,
stdout/stderr y tokens de 32+ caracteres. Todo hallazgo debe ser cero; excluir solo
labels descriptivos estáticos como “Password DB”, nunca valores.

## 9. Checks reproducibles

```bash
php -l includes/install/install_cli_executor.php
php -l includes/install/install_web_gate.php
php -l includes/install/install_web_preflight.php
php -l includes/install/install_web_schema.php
php -l includes/install/install_web_bootstrap.php
php -l includes/install/install_web_admin_publish.php
php -l includes/install/install_web_finalize.php
php -l install/index.php
php -l tools/dqs_install_web_gate_prepare.php
php -l tools/dqs_install_executor_probe.php
php -l tools/dqs_install_web_audit.php
php tools/dqs_install_web_audit.php

grep -RInE 'shell_exec|passthru|system\(|exec\(|proc_open|popen' \
  install/index.php includes/install/install_web_preflight.php \
  includes/install/install_web_schema.php includes/install/install_web_bootstrap.php \
  includes/install/install_web_admin_publish.php includes/install/install_web_finalize.php \
  includes/install/install_web_gate.php tools/dqs_install_web_gate_prepare.php \
  || echo process_calls_ok

php tools/dqs_install_executor_probe.php --operation=schema_runner --apply --json
php tools/dqs_install_executor_probe.php --operation=bootstrap --apply --json
php tools/dqs_install_executor_probe.php --operation=admin_publish --apply --json
php tools/dqs_install_executor_probe.php --operation=finalize --apply --json
```

Los cuatro probes deben devolver `status=BLOCKED`. Comparar también el diff contra
la rama base disponible y abortar si aparece cualquier archivo fuera de scope.

## 10. Registro de resultado

Anotar fecha/host/PHP sin paths sensibles; estados de cada paso; conteos antes y
después; ausencia de secretos; limpieza de temporales; scope diff; process check;
salida redactada de probes; y decisión **GO/NO-GO**. Destruir DB, carpeta publicada,
runtime y evidencias sensibles al terminar la prueba.
