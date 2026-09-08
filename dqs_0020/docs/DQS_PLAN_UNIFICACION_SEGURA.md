> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Plan de unificación segura

## Principio rector

La primera etapa debe seleccionar comportamiento por configuración y adaptadores, no mezclar datos. El objetivo es que el mismo código pueda comportarse como básico/oro, código/form y WhatsApp invitados/pre_ sin romper instalaciones existentes.

## Configuración recomendada

```ini
plan_servicio=basico|oro
rsvp_modo=codigo|form
fuente_envios_whatsapp=ninguno|invitados|pre_invitados
whatsapp_enabled=0|1
regalos_enabled=0|1
```

Agregar metadatos futuros:

- `admin_folder_name` o seguir usando `admin_config.nombre_carpeta`.
- `rsvp_source_table` derivado, no editable manualmente salvo soporte.
- `feature_regalo_libre` dentro de `site_settings` solo si `regalos_enabled=1`.

## Qué cambios hacer primero

1. Documentar contratos de datos para `invitados` y `pre_invitados`.
2. Crear capa de lectura `GuestSource` con implementaciones `InvitadosSource` y `PreInvitadosSource`.
3. Crear selector RSVP por `rsvp_modo` sin cambiar SQL existente.
4. Crear selector WhatsApp por `fuente_envios_whatsapp`.
5. Encapsular regalos/carrito detrás de `regalos_enabled`.
6. Agregar pruebas de caracterización sobre consultas y salidas actuales antes de refactorizar.

## Qué cambios evitar

- No fusionar `pre_invitados` con `invitados` como primer paso.
- No renombrar tablas históricas.
- No ejecutar instaladores sobre entornos con datos.
- No consolidar SQL si todavía no existe mapa completo de columnas.
- No eliminar archivos `.bkp` de referencia en esta etapa.
- No decidir canónicos solo por código activo actual.

## Qué tablas mantener separadas inicialmente

- `invitados` y `pre_invitados`.
- `invitados_listado_mesa` y `pre_invitados_listado_mesa`.
- `invitados_tel` y `pre_invitados_tel`.
- Estados de envío deben sumar columna/fuente antes de mezclar historiales.

## Lógica a convertir en selector de flujo

| Área | Selector | Resultado |
|---|---|---|
| Front RSVP | `rsvp_modo` | Modal por código o formulario. |
| Fuente invitados | `fuente_envios_whatsapp` | Consultar `invitados*` o `pre_invitados*`. |
| Plan | `plan_servicio` | Habilitar secciones oro o básico. |
| Regalos | `regalos_enabled` | Mostrar/ocultar tienda, carrito, admin regalos. |
| WhatsApp | `whatsapp_enabled` | Mostrar/ocultar gestión/envío. |

## Comportamiento admin por plan

- Básico sin WhatsApp: invitados y RSVP, sin gestión de regalos ni envíos.
- Básico con WhatsApp: gestión de cola y teléfonos según fuente configurada.
- Oro código: invitados normales, regalos/carrito, WhatsApp opcional.
- Oro form: preinvitados para envíos, formulario RSVP, regalos/carrito.

## Comportamiento front por plan

- `basico + codigo`: front simple, RSVP por código, sin tienda.
- `basico + form`: front simple, formulario con búsqueda/código de URL.
- `oro + codigo`: front completo con regalos, RSVP por código.
- `oro + form`: front completo con regalos, RSVP formulario/pre_.

## Comportamiento WhatsApp por plan

- `fuente_envios_whatsapp=ninguno`: no exponer envíos.
- `invitados`: usar tablas normales y mensajes de código.
- `pre_invitados`: usar tablas `pre_` y mantener trazabilidad de origen.

## Etapas recomendadas

### Etapa 0 - Seguridad documental

- Mantener referencias congeladas.
- Detectar secretos sin copiarlos.
- Crear inventario de SQL por tabla/columna.

### Etapa 1 - Configuración y selectores

- Agregar configuración sin cambiar comportamiento por defecto.
- Crear adaptadores de consulta.
- Usar feature flags para UI/admin.

### Etapa 2 - Pruebas de caracterización

- Tests por plan con fixtures mínimos.
- Validar HTML/acciones principales.
- Validar SQL generado sin ejecutar contra base real en CI de auditoría.

### Etapa 3 - Unificación controlada

- Reemplazar duplicados por includes/adaptadores.
- Mantener tablas separadas.
- Consolidar WhatsApp detrás de interfaz común.

### Etapa 4 - Instalador unificado

- Migraciones versionadas.
- Admin folder seguro con manifest.
- Dry-run obligatorio para instalaciones existentes.

### Etapa 5 - Migraciones opcionales

Solo después de estabilizar selectores, evaluar migraciones de datos opcionales y reversibles.
