# UNI-048.2 — Preflight de instalación read-only

## Propósito

`tools/dqs_install_preflight.php` comprueba si el entorno está preparado para una instalación nueva **antes** de ejecutar un instalador. Es una herramienta exclusivamente CLI y ofrece salida humana o JSON para automatización.

## Uso

```bash
php tools/dqs_install_preflight.php --no-db
php tools/dqs_install_preflight.php --no-db --json
php tools/dqs_install_preflight.php --using-current-connection
php tools/dqs_install_preflight.php --using-current-connection --json
php tools/dqs_install_preflight.php --connection-file=/tmp/dqs_empty_connection.php
php tools/dqs_install_preflight.php --connection-file=/tmp/dqs_empty_connection.php --json
```

Se debe elegir exactamente un modo. Para comprobar una DB, la fuente debe ser exactamente una: `--using-current-connection` o `--connection-file`. Indicar ambas, ninguna o una ruta vacía devuelve código `2`. `--no-db` continúa disponible para comprobaciones sin conexión. El proceso devuelve código `0` para `OK`/`WARN`, `1` para `BLOCKED` y `2` para uso incorrecto.

## Qué valida sin base de datos

- PHP 7.4 o posterior y extensiones requeridas (`mysqli`, `json`, `session`, `filter`, `hash`, `openssl`).
- Extensiones sugeridas (`pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `zip`) y disponibilidad de `ZipArchive`.
- Presencia del esquema, ambos seeds y el manifiesto canónico.
- Sintaxis JSON del manifiesto y existencia segura de cada archivo que referencia.
- Ausencia en `schema.sql` de `DEFINER=`, `CREATE DATABASE`, `USE`, `INSERT INTO` e identificadores estilo Hostinger.
- Ausencia en los seeds de indicadores de datos bancarios, emails, teléfonos, passwords, secrets y usuarios estilo Hostinger.
- Ausencia de `install.lock`.
- Evidencias locales de `admin_config` o de un admin activo, reportadas como advertencias sin modificar esos elementos.

Las búsquedas de contenido son defensivas y pueden requerir revisión humana si un placeholder legítimo coincide con un patrón sensible.

## Validaciones opcionales de base de datos

`--using-current-connection` lee mediante tokens las asignaciones literales esperadas de `conexion.php`, sin ejecutar su código heredado ni mostrar credenciales, y abre una conexión `mysqli` equivalente. Este enfoque evita que un `die()` o warning legado revele metadatos o rompa la salida JSON. Intenta seleccionar `utf8mb4` y ejecuta únicamente:

```sql
SELECT DATABASE();
SELECT VERSION();
SHOW TABLES;
SHOW TRIGGERS WHERE `Trigger` = 'generar_codigo_invitado';
```

Informa si la base está vacía. Una base con tablas queda `BLOCKED` con `status=not_empty`, porque no es limpia para una instalación nueva. La presencia de `admin_config`, `site_settings` o del trigger canónico se informa como evidencia de instalación/configuración previa.

Use `--using-current-connection` solamente para inspeccionar la DB configurada actualmente. Use `--connection-file=/ruta/archivo.php` para una DB de prueba vacía y descartable sin cambiar `conexion.php`. El archivo alternativo debe existir, ser legible y contener las cuatro asignaciones **literales** requeridas:

```php
<?php
$servername = 'localhost';
$username = 'usuario_test';
$password = 'password_test';
$dbname = 'db_test_vacia';
```

La herramienta procesa el texto con `token_get_all`: **no incluye ni ejecuta el archivo**. Tampoco muestra o persiste sus valores; identifica solamente que se usó un connection-file y, como máximo, su nombre base. Cree el archivo con permisos restrictivos, por ejemplo `/tmp/dqs_empty_connection.php`, fuera del repositorio; no lo guarde dentro del proyecto ni lo commitee, y elimínelo al finalizar.

PHP 7.4 es el mínimo de la herramienta CLI. Una versión posterior cumple el mínimo, pero produce un `WARN` no bloqueante porque la tienda históricamente requiere PHP 7.4 y se debe validar por separado el runtime real del dominio antes de producción.

## Estados

- **OK:** la comprobación pasó.
- **WARN:** condición no obligatoria o evidencia que merece revisión; no bloquea por sí sola.
- **BLOCKED:** no se debe iniciar una instalación nueva (por ejemplo, falta un archivo, el manifiesto es inválido, existe `install.lock`, el esquema contiene SQL prohibido o la base no está vacía).

El estado global toma la condición más severa. La salida JSON contiene `read_only: true` y una lista estable de checks con identificador, estado y mensaje.

## Garantía read-only y límites

La herramienta no ejecuta `schema.sql`, `seed.sql` ni `seed_default_content.sql`; solo los lee como texto. No contiene consultas `CREATE`, `INSERT`, `UPDATE`, `DELETE`, `DROP` o `ALTER`. Tampoco crea tablas o usuarios, mueve archivos, crea carpetas admin, escribe `conexion.php`, ni modifica el admin, la tienda pública, RSVP, WhatsApp o regalos. No es un endpoint web y rechaza ejecuciones fuera de CLI.

El cambio de charset afecta únicamente a la sesión de conexión abierta para el preflight; no cambia datos ni configuración persistente. Al terminar, la conexión se cierra.

## Integración futura

El instalador web futuro podrá reutilizar o adaptar el conjunto de checks y consumir la estructura JSON antes de habilitar cualquier paso con escritura. Esta entrega no publica el preflight como endpoint ni implementa la instalación: mantener separada la inspección read-only del futuro flujo mutador reduce el riesgo de actuar sobre una instalación existente.
