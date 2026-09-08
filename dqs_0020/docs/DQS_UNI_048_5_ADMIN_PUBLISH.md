# UNI-048.5 — Publicación segura de admin

## Alcance

`tools/dqs_install_admin_publish.php` publica una copia de una plantilla admin y crea exactamente una fila dinámica en `admin_config`. Es exclusivamente CLI, usa dry-run por defecto y solo escribe con `--apply --confirm-admin-publish`. No crea `install.lock`, no modifica `conexion.php`, la plantilla, `admin_tmp`, la tienda, RSVP, WhatsApp, regalos ni datos de invitados/productos/regalos.

La conexión se selecciona con exactamente una de `--connection-file` o `--using-current-connection`. El PHP seleccionado **no se ejecuta**: se extraen con `token_get_all()` únicamente los cuatro literales de conexión. Credenciales, nombre de base y paths completos nunca se muestran.

## Barreras previas

La herramienta exige el schema completo de `database/install/manifest.json`, el trigger `generar_codigo_invitado`, y conteos `user=1`, `cliente=1`, `admin_config=0`, `invitados=0`, `productos=0`, `regalos=0`. El root destino debe existir, ser escribible, estar separado de la plantilla y no ser el root activo del proyecto. Se bloquean `admin_tmp`, destinos/staging existentes, symlinks en la plantilla y rutas que resuelvan fuera del proyecto para la fuente.

El slug comienza con `admin`, contiene solo caracteres alfanuméricos y tiene entre 9 y 33 caracteres. Si se omite se genera con `random_bytes`; al consumir una plantilla JSON sin `--admin-slug`, se reutiliza el slug del JSON.

## `admin_config` dinámico

`--print-template` consulta `SHOW FULL COLUMNS FROM admin_config` y produce JSON. No admite el `id` autoincremental ni campos administrados. Detecta y completa relaciones claras de usuario/cliente, carpeta admin, fechas de creación, estado/activo y email. Los campos `NOT NULL` sin default no reconocidos aparecen como `REEMPLAZAR` y deben completarse.

Los valores se validan según nulabilidad, longitud, entero, decimal, fecha/datetime y enum. Se rechazan columnas desconocidas, HTML, traversal y campos administrados aportados por el operador. El INSERT usa una sentencia preparada.

## Uso recomendado

```bash
rm -rf /tmp/dqs_admin_publish_root
mkdir -p /tmp/dqs_admin_publish_root

php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-slug=adminTest0485 --print-template >/tmp/dqs_admin_publish_template.json
cp /tmp/dqs_admin_publish_template.json /tmp/dqs_admin_publish.json
chmod 600 /tmp/dqs_admin_publish.json

# Dry-run predeterminado
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485

# Escritura confirmada
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485 --apply --confirm-admin-publish

# Salida machine-readable (dry-run)
php tools/dqs_install_admin_publish.php --connection-file=/tmp/dqs_empty_connection.php --admin-template-dir=admin7WZiwEM3XY --target-root=/tmp/dqs_admin_publish_root --admin-config-file=/tmp/dqs_admin_publish.json --admin-slug=adminTest0485 --json
```

El JSON privado debe permanecer fuera del repositorio. `--print-template` no se combina con `--json`, config de entrada ni apply.

## Publicación, rollback y rerun

El apply valida todo, copia a `.slug.pending` omitiendo `.git`, `node_modules`, logs/cache/tmp y `.env`, inicia una transacción, inserta `admin_config`, renombra staging al slug final y confirma. Ante un fallo revierte la transacción y elimina únicamente staging/destino creados por esa ejecución. Después verifica conteos, carpeta no vacía, ausencia de `install.lock` e invariancia de la plantilla. Un rerun queda bloqueado tanto por `admin_config` como por el destino existente.

Estados: `OK`, `WARN`, `BLOCKED`, `FAILED`. Exit `0` corresponde a OK/WARN, `1` a BLOCKED/FAILED y `2` a uso incorrecto.
