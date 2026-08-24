# UNI-048.7 — Plantilla de resultados E2E (sin secretos)

> Completar únicamente con resultados censurados. No pegar passwords, hosts, usuarios, emails, nombres completos, connection-files, JSON de entrada ni contenido íntegro de `install.lock`.

## Identificación

* **Fecha/hora UTC:**
* **Responsable:**
* **Commit/tag probado:**
* **Entorno (Hostinger/plan/PHP, sin host):**
* **DB temporal (nombre oculto, por ejemplo `u****_dqs_****`):**
* **Admin slug temporal:**
* **Target-root:** `/tmp/dqs_admin_publish_root`

## Schema

### Dry-run

* Comando ejecutado: sí / no
* Exit code:
* Estado:
* DB vacía confirmada: sí / no
* Statements planificados con default content:
* Observaciones no sensibles:

### Apply

* Exit code:
* Estado:
* Tablas (esperado 29):
* Trigger `generar_codigo_invitado`: OK / no OK
* `info_mostrar` (esperado 8):
* `intivados_acompanante` (esperado 5):
* `invitados_prioridad` (esperado 4):
* Claves técnicas de `site_settings`: OK / no OK
* Default content incluido: sí / no
* Executed statements (esperado 43 con default content):

## Bootstrap

### Dry-run

* Exit code:
* Estado:
* Input validado sin exponer valores: sí / no
* Observaciones no sensibles:

### Apply

* Exit code:
* Estado:
* `user` (esperado 1):
* `cliente` (esperado 1):
* `admin_config` (esperado 0):
* `invitados/productos/regalos` (esperado `0/0/0`):
* Password hash: OK / no OK

## Admin publish

### Dry-run

* Exit code:
* Estado:
* Fuente y destino validados: sí / no
* Observaciones no sensibles:

### Apply

* Exit code:
* Estado:
* `admin_config` (esperado 1):
* Carpeta publicada/no vacía: sí / no
* `user/cliente` (esperado `1/1`):
* `invitados/productos/regalos` (esperado `0/0/0`):
* `install.lock` ausente: sí / no
* Template source sin cambios: sí / no

## Finalize

### Dry-run

* Exit code:
* Estado:
* Auditoría completa aprobada: sí / no
* DB sin escrituras: sí / no
* Observaciones no sensibles:

### Apply

* Exit code:
* Estado:
* `install.lock` creado: sí / no
* DB sin cambios: sí / no

## Reruns bloqueados

| Rerun | Exit esperado | Resultado observado | Motivo observado (sin datos sensibles) |
|---|---:|---|---|
| Schema | 1 | | DB no vacía |
| Bootstrap | 1 | | `user`/`cliente` existentes |
| Admin publish | 1 | | `admin_config` y/o destino existente |
| Finalize | 1 | | `install.lock` existente |

## Validaciones JSON

| Salida | Resultado esperado | Resultado observado |
|---|---|---|
| Schema runner `--json` | `json_ok` | |
| Bootstrap `--json` | `json_ok` | |
| Admin publish `--json` | `json_ok` | |
| Finalize `--json` | `json_ok` | |
| `install.lock` | `json_ok` | |

## Scan sensible de `install.lock`

* Resultado esperado: `sensitive_ok`
* Resultado observado:
* ¿Se evitó adjuntar el contenido del lock?: sí / no

## Limpieza realizada

Marcar solo después de verificar cada acción:

* [ ] JSON de bootstrap y plantillas eliminados.
* [ ] JSON de reportes eliminados.
* [ ] `/tmp/dqs_empty_connection.php` eliminado.
* [ ] `/tmp/dqs_admin_publish_root` eliminado o excepción temporal documentada.
* [ ] DB temporal eliminada en Hostinger.
* [ ] Usuario temporal eliminado en Hostinger.
* [ ] Ningún artefacto temporal fue commiteado.

## Observaciones

Incluir aquí warnings, diferencias o incidentes **sin secretos ni datos personales**:

* <!-- Observaciones -->

## Aprobación

* **Resultado global:** APROBADO / RECHAZADO
* **Revisado por:**
* **Fecha de revisión UTC:**
