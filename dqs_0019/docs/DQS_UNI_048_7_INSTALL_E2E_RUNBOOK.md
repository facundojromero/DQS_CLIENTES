# UNI-048.7 — Runbook E2E reproducible del instalador CLI

## Objetivo, alcance y reglas de seguridad

Este runbook lleva una instalación de prueba desde una base de datos vacía hasta la creación de `install.lock`, usando una base y un `target-root` descartables. Los comandos se ejecutan desde el root del repositorio. Es una guía operativa: este documento no autoriza a reutilizar una base, un admin o un root reales.

> **PELIGRO:** no ejecutar este flujo sobre una DB productiva, no usar el root de desarrollo como `target-root` y no publicar sobre el admin real durante una prueba. No ejecutar ningún `apply` sin revisar antes el dry-run correspondiente. En una instalación real, nunca borrar `install.lock` para forzar una reinstalación.

Además:

* no pegar passwords ni credenciales en issues, PRs, terminales grabadas o logs;
* no commitear `/tmp/*.php`, `/tmp/*.json` ni ningún otro artefacto temporal;
* ejecutar los comandos desde una sesión privada y evitar que el shell expanda o imprima secretos;
* conservar una copia sin modificar de la plantilla `admin7WZiwEM3XY` y verificarla tras publicar.

## 1. Preparación manual en Hostinger

1. Crear una **DB temporal vacía**, dedicada exclusivamente a esta prueba.
2. Crear un usuario de DB temporal con una contraseña fuerte.
3. Asignar a ese usuario los permisos necesarios sobre **solo esa DB**.
4. Confirmar visualmente que no es la DB productiva y que no contiene tablas.
5. Registrar el nombre de forma censurada en la plantilla de resultados; no copiar credenciales a logs.
6. Acordar desde el inicio que la DB y el usuario se eliminarán al terminar.

## 2. Connection-file temporal

Crear manualmente `/tmp/dqs_empty_connection.php` con asignaciones **literales** (no variables de entorno, funciones ni concatenaciones):

```php
<?php
$servername = 'REEMPLAZAR_HOST';
$username = 'REEMPLAZAR_USUARIO';
$password = 'REEMPLAZAR_PASSWORD';
$dbname = 'REEMPLAZAR_DB_TEMPORAL';
```

Protegerlo inmediatamente:

```bash
chmod 600 /tmp/dqs_empty_connection.php
```

No mostrar el archivo con `cat`, no pegar su contenido en resultados y no moverlo al repositorio.

## 3–6. Preflight y schema

### 3. Preflight contra la DB vacía

```bash
php tools/dqs_install_preflight.php --connection-file=/tmp/dqs_empty_connection.php
```

Esperar un reporte apto para continuar, sin credenciales, y la confirmación de que la conexión y los prerrequisitos son válidos.

### 4. Schema dry-run

```bash
php tools/dqs_install_schema_runner.php --connection-file=/tmp/dqs_empty_connection.php --include-default-content
```

Revisar íntegramente el reporte. Debe indicar modo dry-run, DB vacía y que no hubo escrituras.

### 5. Schema apply

Solo después de aprobar el dry-run:

```bash
php tools/dqs_install_schema_runner.php --connection-file=/tmp/dqs_empty_connection.php --apply --confirm-empty-install --include-default-content
```

**Resultado esperado:**

* 29 tablas y el trigger `generar_codigo_invitado`;
* `info_mostrar = 8`, `intivados_acompanante = 5` e `invitados_prioridad = 4`;
* las claves técnicas de `site_settings` presentes (6 filas técnicas en el contrato actual);
* contenido editorial opcional instalado porque se usó `--include-default-content`;
* `Executed statements: 43` al incluir el contenido predeterminado.

### 6. Rerun de schema bloqueado

```bash
php tools/dqs_install_schema_runner.php --connection-file=/tmp/dqs_empty_connection.php
```

Debe terminar `BLOCKED`/exit `1` porque la DB ya no está vacía. Este resultado es satisfactorio y no debe “corregirse” borrando tablas.

## 7–11. Bootstrap inicial

### 7. Generar la plantilla

```bash
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --print-template > /tmp/dqs_bootstrap_template.json
```

### 8. Crear el JSON privado

```bash
cp /tmp/dqs_bootstrap_template.json /tmp/dqs_bootstrap.json
chmod 600 /tmp/dqs_bootstrap.json
```

Editar `/tmp/dqs_bootstrap.json` y reemplazar email, password, datos de cliente y settings. No imprimirlo ni adjuntarlo a evidencias. Validar su sintaxis antes de continuar:

```bash
php -r '$f="/tmp/dqs_bootstrap.json"; json_decode(file_get_contents($f), true); exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);'
```

### 9. Bootstrap dry-run

```bash
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json
```

### 10. Bootstrap apply

```bash
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json --apply --confirm-bootstrap
```

**Resultado esperado:** `user = 1`, `cliente = 1`, `admin_config = 0`, `invitados = 0`, `productos = 0`, `regalos = 0` y verificación de password hash `OK` (sin revelar el password).

### 11. Rerun de bootstrap bloqueado

```bash
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json
```

Debe terminar `BLOCKED`/exit `1` por la existencia de `user` y/o `cliente`.

## 12–17. Publicación del admin

### 12. Preparar un target-root temporal

El destino debe estar fuera del repositorio activo y no puede ser un admin real:

```bash
rm -rf /tmp/dqs_admin_publish_root
mkdir -p /tmp/dqs_admin_publish_root
```

### 13. Generar la plantilla de publicación

```bash
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-slug=adminTest0485 --print-template > /tmp/dqs_admin_publish_template.json
```

### 14. Crear el JSON privado

```bash
cp /tmp/dqs_admin_publish_template.json /tmp/dqs_admin_publish.json
chmod 600 /tmp/dqs_admin_publish.json
```

Reemplazar cualquier `REEMPLAZAR` requerido, sin publicar el contenido del archivo.

### 15. Admin publish dry-run

```bash
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485
```

### 16. Admin publish apply

```bash
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485 --apply --confirm-admin-publish
```

**Resultado esperado:** `admin_config = 1`; carpeta `/tmp/dqs_admin_publish_root/adminTest0485` publicada y no vacía; `user = 1`, `cliente = 1`; `invitados/productos/regalos = 0`; todavía no existe `install.lock`; la fuente `admin7WZiwEM3XY` permanece sin cambios.

### 17. Rerun de admin publish bloqueado

```bash
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485
```

Debe terminar `BLOCKED`/exit `1` por `admin_config` existente y/o por la carpeta destino ya publicada.

## 18–20. Auditoría final e `install.lock`

### 18. Finalize dry-run

```bash
php tools/dqs_install_finalize.php --connection-file=/tmp/dqs_empty_connection.php --target-root=/tmp/dqs_admin_publish_root --admin-slug=adminTest0485
```

Revisar conteos, manifest, trigger, seeds, admin publicado y ausencia de locks; el dry-run no debe escribir.

### 19. Finalize apply

```bash
php tools/dqs_install_finalize.php --connection-file=/tmp/dqs_empty_connection.php --target-root=/tmp/dqs_admin_publish_root --admin-slug=adminTest0485 --apply --confirm-finalize
```

**Resultado esperado:** se crea `install.lock`, su JSON es válido, el scan devuelve `sensitive_ok`, y la DB conserva exactamente los mismos conteos y datos que antes de finalizar.

### 20. Rerun de finalize bloqueado

```bash
php tools/dqs_install_finalize.php --connection-file=/tmp/dqs_empty_connection.php --target-root=/tmp/dqs_admin_publish_root --admin-slug=adminTest0485
```

Debe terminar `BLOCKED`/exit `1` por `install.lock`. No borrar el lock para repetir la prueba; para un E2E nuevo se crean otra DB y otro root descartables.

## 21. Validaciones JSON de las herramientas

Ejecutar estas variantes dry-run en la etapa indicada y validar cada salida sin imprimir datos privados. **Schema debe capturarse antes del schema apply; bootstrap antes de su apply; admin publish antes de publicar; finalize antes de crear el lock.**

```bash
# Schema runner (DB aún vacía)
php tools/dqs_install_schema_runner.php --connection-file=/tmp/dqs_empty_connection.php --include-default-content --json > /tmp/dqs_schema_report.json
php -r '$f="/tmp/dqs_schema_report.json"; json_decode(file_get_contents($f), true); echo json_last_error() === JSON_ERROR_NONE ? "json_ok\n" : "json_bad\n"; exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);'

# Bootstrap (schema aplicado, antes del bootstrap apply)
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json --json > /tmp/dqs_bootstrap_report.json
php -r '$f="/tmp/dqs_bootstrap_report.json"; json_decode(file_get_contents($f), true); echo json_last_error() === JSON_ERROR_NONE ? "json_ok\n" : "json_bad\n"; exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);'

# Admin publish (bootstrap aplicado, antes del publish apply)
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485 --json > /tmp/dqs_admin_publish_report.json
php -r '$f="/tmp/dqs_admin_publish_report.json"; json_decode(file_get_contents($f), true); echo json_last_error() === JSON_ERROR_NONE ? "json_ok\n" : "json_bad\n"; exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);'

# Finalize (admin publicado, antes del finalize apply)
php tools/dqs_install_finalize.php --connection-file=/tmp/dqs_empty_connection.php --target-root=/tmp/dqs_admin_publish_root --admin-slug=adminTest0485 --json > /tmp/dqs_finalize_report.json
php -r '$f="/tmp/dqs_finalize_report.json"; json_decode(file_get_contents($f), true); echo json_last_error() === JSON_ERROR_NONE ? "json_ok\n" : "json_bad\n"; exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);'
```

Cada validador debe imprimir `json_ok` y devolver exit `0`. Guardar en la plantilla de resultados solo estado, modo, conteos y checks no sensibles; no pegar JSON de entrada.

## 22. Validar `install.lock` sin secretos

Después del finalize apply, ejecutar exactamente:

```bash
php -r '$s=file_get_contents("/tmp/dqs_admin_publish_root/install.lock"); echo json_decode($s,true) ? "json_ok\n" : "json_bad\n"; echo preg_match("/password|dbname|username|servername|u[0-9]{4,}_|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i",$s) ? "sensitive_found\n" : "sensitive_ok\n";'
```

La única salida aceptable es `json_ok` seguida de `sensitive_ok`. No pegar el contenido completo del lock en un issue o PR.

## 23. Limpieza

Solo después de guardar evidencia censurada:

```bash
rm -f /tmp/dqs_bootstrap_template.json /tmp/dqs_bootstrap.json
rm -f /tmp/dqs_admin_publish_template.json /tmp/dqs_admin_publish.json
rm -f /tmp/dqs_schema_report.json /tmp/dqs_bootstrap_report.json /tmp/dqs_admin_publish_report.json /tmp/dqs_finalize_report.json
rm -f /tmp/dqs_empty_connection.php
rm -rf /tmp/dqs_admin_publish_root
```

Por último, borrar manualmente en Hostinger la **DB temporal** y el **usuario temporal**, y confirmar que no conservan permisos. Si el target-root se necesita momentáneamente para investigar, documentar la excepción y eliminarlo en cuanto termine; jamás limpiar una ruta sin verificar que sea exactamente la temporal.

## Matriz final de aceptación

| Etapa | Aceptación |
|---|---|
| Schema apply | 29 tablas; trigger; seeds `8/5/4`; claves técnicas; contenido editorial opcional; 43 statements con default content |
| Bootstrap apply | `user/cliente/admin_config = 1/1/0`; tablas de negocio `0/0/0`; hash OK; rerun bloqueado |
| Admin publish apply | `admin_config = 1`; carpeta publicada; identidades `1/1`; negocio `0/0/0`; sin lock; fuente intacta; rerun bloqueado |
| Finalize apply | lock creado, JSON válido, `sensitive_ok`, DB sin cambios y rerun bloqueado por lock |
| Limpieza | JSON, connection-file, root, DB y usuario temporales eliminados |
