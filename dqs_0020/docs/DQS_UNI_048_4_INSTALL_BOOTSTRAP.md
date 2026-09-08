# UNI-048.4 — Bootstrap CLI inicial

## Alcance y garantías

`tools/dqs_install_bootstrap.php` completa una base **ya instalada** con un usuario administrador, una fila `cliente` y overrides permitidos de `site_settings`. Es exclusivamente CLI, hace dry-run por defecto y no crea `admin_config`, carpetas admin, `install.lock`, invitados, productos ni regalos. Tampoco modifica `conexion.php` ni ejecuta el archivo de conexión: extrae únicamente las asignaciones literales `$servername`, `$username`, `$password` y `$dbname` mediante `token_get_all()`.

El bootstrap no es un instalador web ni debe apuntarse a una base activa. Para escribir son obligatorios simultáneamente `--apply` y `--confirm-bootstrap`. Antes de escribir exige el manifest completo, el trigger `generar_codigo_invitado`, los conteos de seeds técnicos, `user`, `cliente` y `admin_config` vacíos, y tablas transaccionales InnoDB. El rerun queda bloqueado.

## Archivo privado de entrada

Crear el JSON fuera del repositorio (se rechaza cualquier ubicación dentro de él), limitar sus permisos y eliminarlo al terminar:

```bash
cat >/tmp/dqs_bootstrap.json <<'JSON'
{
  "admin": {
    "email": "admin@example.invalid",
    "password": "Reemplazar-Con-Password-Largo!",
    "password_confirm": "Reemplazar-Con-Password-Largo!"
  },
  "cliente": {
    "nombre": "Nombre demo",
    "apellido": "Apellido demo"
  },
  "settings": {
    "plan_servicio": "oro",
    "rsvp_modo": "codigo",
    "fuente_envios_whatsapp": "invitados",
    "whatsapp_enabled": "1",
    "regalos_enabled": "1",
    "rsvp_form_persist_enabled": "0"
  }
}
JSON
chmod 600 /tmp/dqs_bootstrap.json
```

El password se valida (confirmación idéntica y mínimo 6 caracteres), se transforma con `password_hash(PASSWORD_DEFAULT)` justo antes del INSERT y nunca aparece en la salida. El valor temporal operativo `123456` es válido y debe cambiarse después. No se admite un hash suministrado. El archivo y el password plano no se guardan en el repositorio.

## Cliente dinámico

La herramienta ejecuta `SHOW FULL COLUMNS FROM cliente`; no presupone su forma. El mapa `cliente` usa `columna: valor`. Se bloquean:

- columnas desconocidas, `id` y `user_id` (este último se toma del usuario recién creado);
- columnas bancarias (`cbu*`, aliases bancarios y cotización), reservadas para edición posterior;
- HTML y segmentos de traversal;
- valores que incumplan nulabilidad, longitud o formatos enteros, decimales y de fecha;
- omisión de columnas `NOT NULL` sin default.

Las columnas obligatorias faltantes se enumeran por nombre. Si el esquema no tiene `user_id`, se advierte y solo se continúa si las demás restricciones permiten insertar.

## Settings

Las claves y valores se validan con el contrato sin efectos secundarios de `includes/plan_config.php`. Solo se aceptan claves presentes en `DQS_PLAN_CONFIG_DEFAULTS` y valores aceptados por `dqs_is_valid_plan_config_value()`. El apply hace upsert únicamente de las claves presentes en el JSON; no elimina ni altera otras filas.

## Uso

```bash
# Generar una plantilla basada en las columnas detectadas (sin leer JSON ni escribir DB)
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --print-template

# Dry-run (default)
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json

# Apply atómico
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json --apply --confirm-bootstrap

# Salida JSON (también dry-run por defecto)
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json --json
```

Puede sustituirse `--connection-file` por `--using-current-connection`, pero debe elegirse exactamente una fuente. El path completo de los archivos, nombre de DB y credenciales permanecen ocultos. `--print-template` no escribe un archivo ni la base.

## Estados y códigos

- `OK`: validación/operación correcta.
- `WARN`: operación segura con una advertencia no bloqueante.
- `BLOCKED`: precondición de seguridad incumplida.
- `FAILED`: apply falló y se ejecutó rollback.
- exit `0`: `OK`/`WARN`; exit `1`: `BLOCKED`/`FAILED`; exit `2`: uso inválido.

El apply agrupa `user`, `cliente` y `site_settings` en una sola transacción. Después verifica conteos `user=1`, `cliente=1`, `admin_config=0`, `invitados=0`, `productos=0`, `regalos=0`, presencia de settings solicitados y que `user.password` sea un hash reconocido.

## Verificación recomendada

```bash
php -l tools/dqs_install_bootstrap.php
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json --apply --confirm-bootstrap
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json
php tools/dqs_install_bootstrap.php --connection-file=/tmp/dqs_empty_connection.php --bootstrap-file=/tmp/dqs_bootstrap.json --json >/tmp/dqs_bootstrap.json.out
php -r 'json_decode(file_get_contents("/tmp/dqs_bootstrap.json.out")); echo json_last_error_msg(), PHP_EOL;'
```

Tras una prueba descartable, borrar de forma segura el JSON privado y la conexión temporal según las prácticas del entorno.
