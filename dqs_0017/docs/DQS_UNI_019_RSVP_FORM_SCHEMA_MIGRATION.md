# UNI-019 — RSVP formulario: migración controlada de schema `pre_*`

## Propósito

UNI-019 prepara una herramienta CLI segura para crear el schema mínimo de tablas `pre_*` que necesitará el futuro RSVP por formulario. La migración queda en **dry-run por defecto** y solo modifica la base si se ejecuta manualmente con `--apply --i-understand-this-changes-db`.

La configuración esperada del proyecto sigue siendo:

- `plan_servicio = oro`
- `rsvp_modo = codigo`
- `fuente_envios_whatsapp = invitados`
- `whatsapp_enabled = 1`
- `regalos_enabled = 1`

## Diferencia con UNI-018

UNI-018 definió y diagnosticó perfiles de schema (`contract_v1` y `legacy_pre_v1`) en modo solo lectura. UNI-019 toma el perfil futuro `contract_v1` y genera un plan SQL controlado para preparar las tablas `pre_*`, sin activar el formulario ni persistir invitados.

## Perfil usado: `contract_v1`

Se usa `contract_v1` porque es el contrato limpio alineado al payload normalizado del RSVP formulario y evita acoplar el futuro flujo público a nombres históricos. `legacy_pre_v1` permanece como compatibilidad/diagnóstico para instalaciones antiguas, pero UNI-019 no lo migra.

## Tablas preparadas

### `pre_invitados`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `nombre VARCHAR(100) NOT NULL`
- `apellido VARCHAR(100) NOT NULL`
- `confirmacion VARCHAR(10) NOT NULL`
- `restriccion_alimentaria VARCHAR(50) DEFAULT 'No'`
- `comentario TEXT NULL`
- `cantidad_acompanantes INT DEFAULT 0`
- `total_personas INT DEFAULT 0`
- `fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP`
- `origen VARCHAR(30) DEFAULT 'form_public'`
- `activo TINYINT(1) DEFAULT 1`

### `pre_invitados_listado_mesa`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `id_pre_invitado INT NOT NULL`
- `nombre VARCHAR(100) NOT NULL`
- `apellido VARCHAR(100) NOT NULL`
- `restriccion_alimentaria VARCHAR(50) DEFAULT 'No'`
- `comentario TEXT NULL`
- `orden INT DEFAULT 0`
- `fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP`
- `INDEX id_pre_invitado (id_pre_invitado)`

### `pre_invitados_tel`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `id_pre_invitado INT NOT NULL`
- `telefono VARCHAR(30) NOT NULL`
- `fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP`
- `INDEX id_pre_invitado (id_pre_invitado)`

No se agregan foreign keys en UNI-019 para evitar problemas de compatibilidad MySQL/MariaDB y para mantener el paso reversible operativamente sin imponer relaciones sobre datos futuros.

## Archivos creados

- `includes/rsvp_form_schema_migration.php`: helper seguro de incluir; no imprime, no abre DB y no ejecuta SQL al incluirse.
- `tools/dqs_rsvp_form_schema_migration.php`: CLI-only para planificar o aplicar explícitamente la migración.

## Cómo correr dry-run

```bash
php tools/dqs_rsvp_form_schema_migration.php --help
php tools/dqs_rsvp_form_schema_migration.php --plan
php tools/dqs_rsvp_form_schema_migration.php --profile=contract_v1 --plan
```

El dry-run imprime JSON con `dry_run`, `apply`, `profile`, `operations`, `sql_preview`, `executed`, `skipped`, `warnings` y `destructive=false`.

## Cómo aplicar manualmente

```bash
php tools/dqs_rsvp_form_schema_migration.php --profile=contract_v1 --apply --i-understand-this-changes-db
```

`--apply` sin `--i-understand-this-changes-db` corta antes de abrir la base y no ejecuta SQL.

## Cómo verificar tablas

Después de una aplicación real, usar los probes de UNI-018:

```bash
php tools/dqs_rsvp_form_schema_profiles_probe.php --schema
php tools/dqs_rsvp_form_schema_profiles_probe.php --detect
```

La detección debería indicar `contract_v1` o un score alto para `contract_v1`.

## Cómo confirmar que `rsvp_modo` queda en `codigo`

```bash
php tools/dqs_provider_config.php --show
```

Confirmar en la salida:

- `plan_servicio: oro`
- `rsvp_modo: codigo`
- `fuente_envios_whatsapp: invitados`
- `whatsapp_enabled: 1`
- `regalos_enabled: 1`

## Qué NO hace UNI-019

- No modifica `index.php`.
- No modifica `includes/rsvp_form_public.php`.
- No modifica `rsvp_form_validate.php`.
- No modifica endpoint, modal, admin, tienda, regalos, WhatsApp, Node ni `admin_tmp`.
- No inserta invitados.
- No actualiza invitados.
- No borra datos.
- No confirma asistencias reales.
- No cambia `rsvp_modo` ni `fuente_envios_whatsapp`.
- No ejecuta migraciones automáticamente.
- No hace `DROP`, `TRUNCATE` ni renombra tablas.
- No altera tablas existentes de forma destructiva.

## Pendiente para UNI-020

UNI-020 podrá conectar una persistencia real del RSVP formulario contra este schema, manteniendo feature flags, validaciones del contrato y una estrategia explícita de rollback/observabilidad antes de activar `rsvp_modo=form`.
