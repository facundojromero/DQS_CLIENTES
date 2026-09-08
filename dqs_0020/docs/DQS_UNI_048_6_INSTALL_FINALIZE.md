# UNI-048.6 — Finalización segura y auditoría final

## Propósito y límites

`tools/dqs_install_finalize.php` audita una instalación ya terminada y, únicamente con confirmación doble, crea `install.lock` en el root de publicación. Es una herramienta exclusivamente CLI y **dry-run por defecto**. No instala schema, no ejecuta seeds, no inserta ni actualiza filas, no publica carpetas y no modifica `conexion.php`, `admin_tmp`, la tienda pública, RSVP, WhatsApp o regalos.

La conexión se obtiene de exactamente una de estas fuentes:

* `--connection-file=/ruta/conexion.php`; o
* `--using-current-connection` para el `conexion.php` del proyecto.

El archivo PHP se lee con `token_get_all`; nunca se incluye ni se ejecuta. Debe contener asignaciones literales para las cuatro variables esperadas. Las credenciales, el nombre de la base y las rutas absolutas nunca aparecen en el reporte ni en el lock.

## Uso

```bash
# Auditoría sin escrituras
php tools/dqs_install_finalize.php \
  --connection-file=/tmp/dqs_empty_connection.php \
  --target-root=/tmp/dqs_admin_publish_root \
  --admin-slug=adminTest0485

# Auditoría y creación atómica del lock
php tools/dqs_install_finalize.php \
  --connection-file=/tmp/dqs_empty_connection.php \
  --target-root=/tmp/dqs_admin_publish_root \
  --admin-slug=adminTest0485 \
  --apply --confirm-finalize

# Reporte estructurado (también dry-run)
php tools/dqs_install_finalize.php \
  --connection-file=/tmp/dqs_empty_connection.php \
  --target-root=/tmp/dqs_admin_publish_root \
  --admin-slug=adminTest0485 --json
```

`--apply` y `--confirm-finalize` deben aparecer juntos. Usar solo uno, omitir un argumento obligatorio, combinar ambas fuentes de conexión o pasar una opción desconocida es un error de uso (exit `2`). El slug debe comenzar con `admin`, contener únicamente letras/números, tener entre 9 y 33 caracteres y no ser `admin_tmp`.

## Auditoría bloqueante

Antes de escribir, la herramienta comprueba:

* `database/install/manifest.json` legible, JSON válido y todas sus tablas instaladas;
* trigger `generar_codigo_invitado`;
* seeds técnicos: `info_mostrar=8`, `intivados_acompanante=5` e `invitados_prioridad=4`;
* las seis claves técnicas requeridas de `site_settings`;
* `user=1`, `cliente=1`, `admin_config=1`, `invitados=0`, `productos=0` y `regalos=0`;
* root destino existente, legible, fuera del repositorio activo y, solo en apply, escribible;
* carpeta publicada existente, legible, no symlink, no vacía y sin symlinks internos, `.git`, `node_modules`, `.env` o `admin_tmp`;
* ausencia tanto de `install.lock` como de `.install.lock.pending`.

`SHOW FULL COLUMNS FROM admin_config` permite detectar relaciones de usuario/cliente y columnas inequívocas de slug/carpeta/ruta. Las relaciones detectadas deben apuntar a los únicos `user.id` y `cliente.id`, y un slug detectado debe coincidir. Si no hay una columna inequívoca para el slug se emite `WARN` no bloqueante. La ausencia de `index.php` y `login.php` también es solo una advertencia, porque no todos los templates garantizan esos nombres.

Todas las consultas son de inspección: `SHOW TABLES`, `SHOW TRIGGERS`, `SHOW FULL COLUMNS` y `SELECT`. La herramienta no emite DDL ni DML.

## Fingerprint e `install.lock`

El fingerprint contiene cantidad de archivos/directorios y SHA-256 de una lista ordenada formada por ruta **relativa**, tamaño y mtime. No almacena rutas absolutas. En apply se serializa un JSON con versión `UNI-048.6`, fecha UTC, slug, cantidad de tablas del manifest, trigger, los seis conteos de negocio y el fingerprint. No contiene host, base, usuario, contraseña, email ni datos personales.

La creación usa este protocolo:

1. apertura exclusiva de `.install.lock.pending` (sin sobrescribir);
2. escritura completa y `chmod 0600` cuando el sistema lo permite;
3. comprobación nuevamente de que el destino no existe;
4. `rename` atómico a `install.lock`;
5. limpieza del pending ante un fallo.

Después del apply se vuelve a leer y decodificar el lock, se buscan patrones sensibles, se repiten los seis conteos de DB y se confirma que la carpeta admin sigue presente y no vacía. Un rerun encuentra `install.lock`, devuelve `BLOCKED` y no intenta modificarlo.

## Salida y códigos

La salida humana enumera cada check. `--json` devuelve un único objeto, apto para automatización, con rutas reducidas a etiquetas/basenames y sin secretos.

| Estado | Significado | Exit |
|---|---|---:|
| `OK` | Auditoría/finalización correcta | 0 |
| `WARN` | Correcta con advertencia no bloqueante | 0 |
| `BLOCKED` | Precondición de seguridad incumplida | 1 |
| `FAILED` | Fallo durante escritura o post-check | 1 |
| error de uso | Argumentos inválidos | 2 |

## Checklist operativo

1. Ejecutar lint y dry-run.
2. Revisar todos los `WARN` antes de aplicar.
3. Ejecutar apply una sola vez con ambas banderas.
4. Validar JSON y ausencia de secretos en `install.lock`.
5. Confirmar que el rerun queda `BLOCKED`.

El lock es una barrera de reinstalación, no un contenedor de credenciales ni un reemplazo de backups. No debe borrarse ni editarse para forzar reruns.
