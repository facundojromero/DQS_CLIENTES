# UNI-048.3 — Runner CLI seguro de esquema y seeds

## Alcance

`tools/dqs_install_schema_runner.php` es un paso técnico previo al futuro instalador web. Es **solo CLI**, usa dry-run por defecto y únicamente lee el paquete canónico `database/install/`: `manifest.json`, `schema.sql`, `seed.sql` y `seed_default_content.sql`.

Valida el entorno, el contrato del manifest, los archivos y reglas de seguridad del SQL; carga de forma segura los literales de la fuente elegida sin mostrar credenciales; configura `mysqli` con `utf8mb4`; y exige que `SHOW TABLES` confirme una base totalmente vacía. El parser entiende directivas `DELIMITER`, conserva el cuerpo del trigger y ejecuta cada sentencia individualmente.

No es un instalador completo: no crea usuarios ni clientes, no inserta configuración de administrador, no crea/mueve carpetas, no toca `admin_tmp`, el admin activo, `conexion.php`, la tienda, RSVP, WhatsApp ni regalos funcionales, y no crea `install.lock`. Tampoco importa dumps históricos (`nuevo_cliente_v2.sql`, `base_datos.sql` o `editado.sql`).

## Comandos

Dry-run (predeterminado, nunca escribe):

```bash
php tools/dqs_install_schema_runner.php --using-current-connection
php tools/dqs_install_schema_runner.php --using-current-connection --include-default-content
php tools/dqs_install_schema_runner.php --connection-file=/tmp/dqs_empty_connection.php
```

Apply requiere **las dos confirmaciones**; `--apply` solo es uso incorrecto:

```bash
php tools/dqs_install_schema_runner.php --using-current-connection --apply --confirm-empty-install
php tools/dqs_install_schema_runner.php --using-current-connection --apply --confirm-empty-install --include-default-content
php tools/dqs_install_schema_runner.php --connection-file=/tmp/dqs_empty_connection.php --apply --confirm-empty-install --include-default-content
```

Agregar `--json` a cualquiera de esos comandos produce un único documento JSON. Los estados son `OK`, `WARN`, `BLOCKED` y `FAILED`; la salida es 0 para éxito, 1 para bloqueo/fallo y 2 para uso incorrecto.

Debe elegirse exactamente una fuente: `--using-current-connection` lee el `conexion.php` actual, mientras que `--connection-file=/ruta/archivo.php` permite usar una configuración temporal. Pasar ambas, ninguna o una ruta vacía es uso incorrecto. La opción alternativa no cambia qué SQL puede leer el runner ni relaja las confirmaciones de apply.

`--include-default-content` selecciona placeholders editoriales editables y neutrales. Sin la opción se aplica solamente el seed técnico obligatorio y no se exige contenido en las tablas editoriales.

## Protección de una instalación existente

Antes de ejecutar una sola sentencia, el runner completa todas las validaciones y consulta `SHOW TABLES`. Cualquier tabla produce `BLOCKED`, tanto en dry-run como en apply, con cero sentencias ejecutadas. Por ello la base actual instalada (31 tablas en la verificación de UNI-048.2) **no debe usarse**: hay que crear una base descartable vacía y apuntar temporalmente una copia aislada de `conexion.php` a ella.

## Prueba sobre una base vacía descartable

1. Crear una base MySQL/MariaDB vacía y un usuario limitado a esa base.
2. Crear fuera del repositorio `/tmp/dqs_empty_connection.php`, con permisos restrictivos, usando cuatro asignaciones literales:

   ```php
   <?php
   $servername = 'localhost';
   $username = 'usuario_test';
   $password = 'password_test';
   $dbname = 'db_test_vacia';
   ```

3. Ejecutar primero el dry-run con `--connection-file=/tmp/dqs_empty_connection.php` y comprobar `database_empty: true`, los conteos del parser, 29 tablas esperadas y el trigger esperado.
4. Ejecutar apply con la misma fuente y ambas opciones de confirmación.
5. Repetir en otra base descartable con `--include-default-content` si se desea validar el contenido editable.
6. Eliminar la base y el archivo temporal al finalizar.

El archivo alternativo se lee como tokens mediante `token_get_all`; nunca se ejecuta, incluye o copia. Sus credenciales no aparecen en la salida humana o JSON y no se guardan. No debe ubicarse dentro del repositorio ni commitearse. El reporte muestra como máximo el basename del archivo.

PHP 7.4 es el mínimo CLI. Otras versiones que cumplan el mínimo no bloquean, pero generan una advertencia para recordar que el runtime histórico de la tienda es PHP 7.4 y debe validarse antes de producción.

## Verificación posterior al apply

El runner se detiene ante el primer error e informa archivo y número de sentencia. Después comprueba:

- todas las tablas declaradas en el manifest;
- el trigger `generar_codigo_invitado`;
- filas técnicas exactas: `info_mostrar` 8, `intivados_acompanante` 5, `invitados_prioridad` 4 y `site_settings` 6;
- cuando se seleccionó contenido inicial: `info_casamiento` 1, `info_nosotros` 2, `info_historia` 4, `info_eventos` 3 e `info_otra` 3.

Sin `--include-default-content`, las tablas editoriales pueden permanecer vacías o conservar únicamente lo que defina el esquema; sus conteos no se imponen.
