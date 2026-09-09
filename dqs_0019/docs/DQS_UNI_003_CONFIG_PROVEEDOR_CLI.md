# DQS UNI-003 - Configuración del proveedor por CLI

## Propósito

UNI-003 agrega una herramienta interna de consola para que el proveedor del servicio / dueño de DQS configure el plan comercial del cliente desde SSH, sin crear pantallas visibles ni cambiar el comportamiento público de la aplicación.

Archivo de la herramienta:

```bash
php tools/dqs_provider_config.php
```

La herramienta usa las claves centralizadas ya definidas en `includes/plan_config.php`:

- `plan_servicio`
- `rsvp_modo`
- `fuente_envios_whatsapp`
- `whatsapp_enabled`
- `regalos_enabled`

Las validaciones de claves y valores se apoyan en los dominios existentes del helper de plan.

## Alcance y seguridad

- Es solo para el proveedor del servicio.
- No queda visible para el cliente/admin de los novios.
- No aparece en el menú admin.
- No agrega links en ninguna pantalla.
- No cambia comportamiento visible por sí misma.
- Solo escribe en `site_settings` cuando se usa `--apply`.
- Sin `--apply`, funciona como dry-run y no escribe en base.
- No crea tablas.
- No altera tablas.
- No migra datos.
- No toca invitados.
- No toca regalos/productos/tienda.
- No ejecuta WhatsApp.
- No ejecuta Node.
- No imprime credenciales, passwords, tokens ni secretos.

La herramienta bloquea acceso web porque valida `PHP_SAPI !== 'cli'`. Además, `tools/.htaccess` bloquea acceso HTTP a la carpeta `tools/` en Apache.

## Admin activo y referencias

El admin activo del cliente es:

```text
admin7WZiwEM3XY/
```

La carpeta `admin_tmp` pertenece al instalador/referencia y no se toca para UNI-003.

## Ver ayuda

```bash
php tools/dqs_provider_config.php --help
```

Muestra comandos disponibles, claves permitidas, valores permitidos y ejemplos.

## Ver configuración actual

```bash
php tools/dqs_provider_config.php --show
```

Muestra:

- Configuración guardada en `site_settings`, si existe y está disponible.
- Defaults en memoria cuando faltan claves.
- Configuración base calculada por `dqs_get_plan_config()`.
- Configuración efectiva calculada por `dqs_get_effective_plan_config()`.

## Dry-run por defecto

```bash
php tools/dqs_provider_config.php --set plan_servicio=basico rsvp_modo=form whatsapp_enabled=0 regalos_enabled=1 fuente_envios_whatsapp=ninguno
```

Sin `--apply`, la herramienta:

- Valida claves.
- Valida valores.
- Muestra qué cambiaría.
- Muestra la configuración resultante base y efectiva.
- No escribe en base de datos.

## Aplicar cambios

```bash
php tools/dqs_provider_config.php --set plan_servicio=basico rsvp_modo=form whatsapp_enabled=0 regalos_enabled=1 fuente_envios_whatsapp=ninguno --apply
```

Con `--apply`, la herramienta inserta o actualiza solo las claves indicadas explícitamente en `--set` dentro de `site_settings` usando prepared statements.

## Ejemplos soportados

### Plan Básico

```bash
php tools/dqs_provider_config.php --set plan_servicio=basico rsvp_modo=form whatsapp_enabled=0 regalos_enabled=1 fuente_envios_whatsapp=ninguno
```

### Plan Oro con código

```bash
php tools/dqs_provider_config.php --set plan_servicio=oro rsvp_modo=codigo whatsapp_enabled=1 regalos_enabled=1 fuente_envios_whatsapp=invitados
```

### Plan Oro con formulario

```bash
php tools/dqs_provider_config.php --set plan_servicio=oro rsvp_modo=form whatsapp_enabled=1 regalos_enabled=1 fuente_envios_whatsapp=pre_invitados
```

Para aplicar cualquiera de estos ejemplos, agregar `--apply` al final.
