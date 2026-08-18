> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Próximos issues sugeridos para unificación

## Prioridad Alta

1. **Inventariar columnas por tabla y por SQL**  
   Generar matriz `tabla -> columnas -> archivo SQL -> plan` sin ejecutar SQL.

2. **Definir contrato `GuestSource`**  
   Especificar métodos mínimos para leer invitado, integrantes, teléfonos, código y estado RSVP desde `invitados` o `pre_invitados`.

3. **UNI-001 implementada/preparada: configuración de plan sin cambiar comportamiento**  
   Lectura central documentada en `docs/DQS_UNI_001_CONFIG_PLANES.md` y helper en `includes/plan_config.php`. Queda pendiente aplicar estas claves en PRs futuros.

4. **UNI-002 implementada/preparada: roles y configuración efectiva**  
   Capa efectiva documentada en `docs/DQS_UNI_002_ROLES_CONFIG_EFECTIVA.md` y funciones disponibles en `includes/plan_config.php`. No cambia comportamiento visible; queda pendiente aplicar los helpers en PRs futuros.

5. **UNI-003 implementada/preparada: configuración del proveedor por CLI**  
   Herramienta interna documentada en `docs/DQS_UNI_003_CONFIG_PROVEEDOR_CLI.md` y script en `tools/dqs_provider_config.php`. Permite `--show`, dry-run por defecto y aplicación explícita con `--apply`; no agrega pantallas ni cambia comportamiento visible.

6. **UNI-004 implementada/preparada: GuestSource read-only**  
   Capa interna documentada en `docs/DQS_UNI_004_GUEST_SOURCE.md` y helper en `includes/guest_source.php`. Abstrae lectura de `invitados` y `pre_invitados` sin aplicar cambios a pantallas, admin, WhatsApp ni consultas actuales.

7. **UNI-005 implementada/preparada: `whatsapp_enabled` aplicado al admin activo**  
   Control documentado en `docs/DQS_UNI_005_WHATSAPP_ENABLED_ADMIN.md` y guard en `includes/admin_feature_guard.php`. Oculta opciones y bloquea accesos directos de envíos WhatsApp cuando `whatsapp_enabled = 0`, manteniendo el comportamiento actual con `whatsapp_enabled = 1`.

8. **UNI-007 implementada/preparada: baseline RSVP código**  
   Flujo actual por código documentado en `docs/DQS_UNI_007_RSVP_CODIGO_BASELINE.md`, checklist manual en `docs/DQS_UNI_007_RSVP_CODIGO_CHECKLIST.md` y probe read-only opcional en `tools/dqs_rsvp_codigo_probe.php`. No aplica todavía `rsvp_modo` al front ni cambia comportamiento visible.

9. **UNI-008 implementada/preparada: baseline RSVP formulario/pre_**  
   Flujo RSVP formulario y fuente `pre_invitados` documentados en `docs/DQS_UNI_008_RSVP_FORM_PRE_BASELINE.md`, checklist futuro en `docs/DQS_UNI_008_RSVP_FORM_PRE_CHECKLIST.md` y probe read-only opcional en `tools/dqs_rsvp_form_pre_probe.php`. No activa `rsvp_modo=form`, no cambia `fuente_envios_whatsapp` y no requiere que existan tablas `pre_` en la base activa.

10. **UNI-009 implementada/preparada: selector interno de modo RSVP**  
   Selector read-only documentado en `docs/DQS_UNI_009_RSVP_MODE_SELECTOR.md`, helper en `includes/rsvp_mode.php` y CLI opcional en `tools/dqs_rsvp_mode_check.php`. Interpreta `rsvp_modo`, mapea `codigo -> invitados` y `form -> pre_invitados`, y diagnostica tablas `pre_` ausentes como advertencias no fatales sin aplicar cambios al front.

11. **UNI-010 implementada/preparada: selector `rsvp_modo` en entrada pública RSVP**  
   Entrada pública documentada en `docs/DQS_UNI_010_RSVP_MODE_PUBLIC_ENTRY.md` y aplicada de forma mínima en `index.php`. Mantiene sin cambios visibles el flujo actual con `rsvp_modo=codigo`; con `rsvp_modo=form` muestra un mensaje controlado sin consultar tablas `pre_`, sin abrir el modal actual y sin modificar datos.

12. **UNI-011 implementada/preparada: shell pública aislada para RSVP formulario**
   Shell pública documentada en `docs/DQS_UNI_011_RSVP_FORM_PUBLIC_SHELL.md` y componente creado en `includes/rsvp_form_public.php`. Con `rsvp_modo=form`, `index.php` renderiza un formulario visual deshabilitado que no postea, no guarda datos y no consulta tablas `pre_`; con `rsvp_modo=codigo` se conserva intacto el flujo vigente por código.

13. **UNI-012 implementada/preparada: modal visual pública para RSVP formulario**
   Modal pública documentada en `docs/DQS_UNI_012_RSVP_FORM_MODAL_SHELL.md` y aplicada en `includes/rsvp_form_public.php`. Con `rsvp_modo=form`, `index.php` muestra una llamada a la acción que abre una modal Bootstrap con campos completables visualmente y submit interceptado sin persistencia; con `rsvp_modo=codigo` se conserva intacto el flujo vigente por código.

14. **UNI-013 implementada/preparada: modal RSVP formulario con acompañantes dinámicos**
   Modal pública documentada en `docs/DQS_UNI_013_RSVP_FORM_ACOMPANANTES_SHELL.md` y aplicada en `includes/rsvp_form_public.php`. Con `rsvp_modo=form`, la modal permite completar invitado principal, cantidad de acompañantes y campos dinámicos por acompañante, manteniendo el submit interceptado sin persistencia; con `rsvp_modo=codigo` se conserva intacto el flujo vigente por código.

15. **UNI-014 implementada/preparada: contrato interno puro para RSVP formulario**
   Contrato documentado en `docs/DQS_UNI_014_RSVP_FORM_CONTRACT.md`, helper puro en `includes/rsvp_form_contract.php` y CLI de prueba en `tools/dqs_rsvp_form_contract_check.php`. Define payload, normalización, validaciones y plan documental de persistencia futura sin abrir DB, sin endpoints, sin consultar tablas `pre_*` y sin modificar el flujo activo por código.

16. **UNI-015 implementada/preparada: endpoint dry-run para RSVP formulario**
   Endpoint dry-run documentado en `docs/DQS_UNI_015_RSVP_FORM_DRY_RUN_ENDPOINT.md`, helper en `includes/rsvp_form_dry_run.php`, endpoint público en `rsvp_form_validate.php` y CLI de prueba en `tools/dqs_rsvp_form_endpoint_probe.php`. Valida payloads del futuro formulario con el contrato UNI-014 sin abrir DB, sin persistir, sin consultar tablas `pre_*`, sin conectar la modal pública y sin modificar el flujo activo por código.

17. **UNI-016 implementada/preparada: modal RSVP formulario conectada al endpoint dry-run**
   Conexión documentada en `docs/DQS_UNI_016_RSVP_FORM_CONNECT_DRY_RUN.md` y aplicada en `includes/rsvp_form_public.php`. Con `rsvp_modo=form`, la modal envía `FormData` por `fetch` a `rsvp_form_validate.php`, muestra respuestas válidas, errores 422 y warnings dentro de la modal, y mantiene `dry_run=true`/`persisted=false` sin guardar datos; con `rsvp_modo=codigo` se conserva intacto el flujo vigente por código.

18. **UNI-017 implementada/preparada: plan interno de persistencia futura para RSVP formulario**
   Plan documentado en `docs/DQS_UNI_017_RSVP_FORM_PERSISTENCE_PLAN.md`, helper en `includes/rsvp_form_persistence_plan.php` y CLI en `tools/dqs_rsvp_form_persistence_probe.php`. Audita tablas `pre_*` en modo read-only y genera planes no ejecutables con `executable=false`/`write_enabled=false`, sin guardar datos, sin crear o alterar tablas y sin modificar endpoint, front, admin, tienda, regalos, WhatsApp, Node ni `admin_tmp`.

19. **UNI-018 implementada/preparada: perfiles de schema RSVP formulario**
   Perfiles documentados en `docs/DQS_UNI_018_RSVP_FORM_SCHEMA_PROFILES.md`, helper en `includes/rsvp_form_schema_profiles.php` y CLI en `tools/dqs_rsvp_form_schema_profiles_probe.php`. Define `contract_v1` y `legacy_pre_v1`, diagnostica schema `pre_*` en modo read-only y genera planes de mapeo no ejecutables con gaps controlados, sin escribir datos ni modificar endpoint, front, admin, tienda, regalos, WhatsApp, Node ni `admin_tmp`.

20. **UNI-019 implementada/preparada: migración controlada de schema RSVP formulario**
   Migración documentada en `docs/DQS_UNI_019_RSVP_FORM_SCHEMA_MIGRATION.md`, helper en `includes/rsvp_form_schema_migration.php` y CLI en `tools/dqs_rsvp_form_schema_migration.php`. Genera un plan seguro para crear/completar tablas `pre_*` del perfil `contract_v1`, con dry-run por defecto y aplicación manual solo con `--apply --i-understand-this-changes-db`, sin insertar datos ni modificar front, endpoint, admin, tienda, regalos, WhatsApp, Node ni `admin_tmp`.

## Prioridad Media

11. **Separar adaptadores WhatsApp**  
   Un adaptador para `invitados*` y otro para `pre_invitados*`, manteniendo salida actual.

12. **Mapear admin normal vs admin pre_**  
   Comparar `admin_tmp/invitados.php` y `admin_tmp/gestionar_envios.php` entre planes.

13. **UNI-006 implementada/preparada — Encapsular regalos/carrito por feature flag**  
   `regalos_enabled` controla front, tienda, carrito, checkout y pantallas admin activas de regalos sin tocar datos históricos.

14. **Diseñar `admin_tmp` v2**  
   Instalador idempotente, recursivo, con manifest y dry-run.

15. **Detectar secretos en referencias**  
   Reportar rutas y tipos de secreto sin copiar valores.

## Prioridad Baja

16. **Normalizar nombres históricos**  
   Evaluar si `intivados_acompanante` se conserva, se envuelve con vista o se migra.

17. **Consolidar assets duplicados**  
   CSS/JS/images comunes, solo después de estabilizar funcionalidad.

18. **Revisar archivos `.bkp` y pruebas**  
   Decidir qué conservar como historial y qué excluir del instalador.

19. **Unificar documentación de operación WhatsApp**  
   Dependencias Node, PHP/cURL, plantillas, credenciales y límites de envío.

20. **Planificar migraciones opcionales**  
   Después de selectores y pruebas; nunca como primer paso.

## Issue inicial recomendado

**Título:** Aplicar progresivamente configuración central de plan y fuente RSVP/WhatsApp sin cambiar comportamiento por defecto.

**Alcance:**

- Partir de la estructura de configuración central ya creada en UNI-001.
- No cambiar consultas existentes.
- No migrar datos.
- Definir defaults equivalentes al comportamiento actual.
- Añadir documentación de cómo seleccionar `basico/oro`, `codigo/form`, `invitados/pre_invitados`.

**Criterio de aceptación:** la aplicación sigue comportándose igual con defaults y cada aplicación futura de flags queda cubierta por verificación/regresión puntual.

## UNI-020 — RSVP formulario: persistencia contract_v1 preparada

Estado: implementada/preparada.

- Se agregó `rsvp_form_persist_enabled` con default `0`.
- El endpoint `rsvp_form_validate.php` continúa dry-run por defecto.
- La persistencia real queda detrás de `rsvp_modo=form`, flag encendido, payload válido, POST válido y schema `contract_v1` ready.
- Se creó helper transaccional `includes/rsvp_form_persistence.php` para guardar en `pre_invitados`, `pre_invitados_tel` y `pre_invitados_listado_mesa`.
- Se creó probe CLI solo lectura `tools/dqs_rsvp_form_persistence_probe.php`.
- No se activó el flag, no se cambió `rsvp_modo` y no se insertaron datos como parte de UNI-020.


## UNI-022 — RSVP formulario: persistencia final hacia invitados*

Estado: implementada/preparada.

- Se creó `includes/rsvp_form_final_persistence.php` para diagnosticar schema `invitados*`, generar previews sin SQL y guardar transaccionalmente en `invitados`, `invitados_tel` e `invitados_listado_mesa` solo cuando el endpoint esté habilitado.
- Se creó `tools/dqs_rsvp_form_final_persistence_probe.php` como probe CLI read-only con `--status`, `--schema` y samples.
- `rsvp_form_validate.php` deja de depender de persistencia `pre_*` y usa el helper final hacia `invitados*`.
- La configuración segura permanece `rsvp_modo=codigo` y `rsvp_form_persist_enabled=0`; por defecto no escribe datos.
- UNI-023 queda como prueba real controlada, fuera de este PR.
