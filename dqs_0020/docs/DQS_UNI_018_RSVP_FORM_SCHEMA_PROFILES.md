# DQS UNI-018 — Perfiles de schema RSVP formulario

## Propósito

UNI-018 agrega una capa interna para comparar schemas posibles de tablas `pre_*` y mapear payloads normalizados del RSVP formulario hacia planes no ejecutables. No decide una migración definitiva, no activa persistencia real y no conecta el endpoint dry-run a escritura.

## Diferencia entre UNI-017 y UNI-018

- **UNI-017** definió un plan interno de persistencia futura con un esquema limpio esperado y diagnóstico read-only básico.
- **UNI-018** reconoce varios perfiles de schema, compara cuál se parece más a la base actual y genera planes de mapeo por perfil, incluyendo gaps cuando un dato del contrato no tiene columna compatible.

## Perfiles definidos

### `contract_v1`

Perfil limpio/futuro alineado con el contrato UNI-014 y la planificación UNI-017.

Tablas y columnas mínimas:

- `pre_invitados`: `id`, `nombre`, `apellido`, `confirmacion`, `restriccion_alimentaria`, `comentario`.
- `pre_invitados_listado_mesa`: `id`, `id_pre_invitado`, `nombre`, `apellido`, `restriccion_alimentaria`, `comentario`.
- `pre_invitados_tel`: `id`, `id_pre_invitado`, `telefono`.

### `legacy_pre_v1`

Perfil histórico compatible con bases tipo `dqs_0011`, donde las tablas `pre_*` usan nombres heredados.

Tablas y columnas mínimas:

- `pre_invitados`: `id`, `nombre`, `apellido`, `activo`, `acompanado`, `cantidad_mayores`, `id_prioridad`, `ingreso`, `cantidad_menores`, `fecha_registro`, `confirmacion`, `confirmacion_fecha`, `confirmacion_comentario`, `confirmacion_mayores`, `confirmacion_menores`, `alimento`, `codigo`.
- `pre_invitados_listado_mesa`: `id`, `id_invitados`, `nombre_invitado`, `mesa`, `nombre2`, `apellido2`.
- `pre_invitados_tel`: `id`, `id_invitados`, `tel_enviar`.

Este perfil no obliga a que la base activa tenga esas tablas. Solo documenta compatibilidad con estilos históricos observados.

## Diagnóstico read-only

El helper `includes/rsvp_form_schema_profiles.php` expone `dqs_rsvp_form_schema_diagnostics_by_profile(mysqli $conn)`. La función recibe una conexión externa y consulta únicamente `information_schema.columns` mediante `prepare`/`execute` para listar columnas existentes por tabla `pre_*`.

Si faltan tablas `pre_*`, el resultado sigue siendo controlado:

- `ready=false` para perfiles incompletos.
- `warnings` no fatales por tabla o columna faltante.
- `score=0` cuando no hay coincidencias.

## Elección de `best_profile`

`dqs_rsvp_form_schema_detect_profile()` compara los scores por perfil. El score es la proporción de columnas requeridas presentes. Si un perfil tiene score mayor que cero y no hay empate, queda como `best_profile`. Si no hay coincidencias o hay empate, `best_profile` queda en `null` con una razón explícita.

## Mapeo a `contract_v1`

El plan no ejecutable mapea:

- `principal.nombre` → `pre_invitados.nombre`.
- `principal.apellido` → `pre_invitados.apellido`.
- `principal.confirmacion` → `pre_invitados.confirmacion`.
- `principal.restriccion_alimentaria` → `pre_invitados.restriccion_alimentaria`.
- `principal.comentario` → `pre_invitados.comentario`.
- `principal.telefono` → `pre_invitados_tel.telefono`.
- `acompanantes[n].nombre` → `pre_invitados_listado_mesa.nombre`.
- `acompanantes[n].apellido` → `pre_invitados_listado_mesa.apellido`.
- `acompanantes[n].restriccion_alimentaria` → `pre_invitados_listado_mesa.restriccion_alimentaria`.
- `acompanantes[n].comentario` → `pre_invitados_listado_mesa.comentario`.

## Mapeo a `legacy_pre_v1`

El plan no ejecutable mapea:

- `principal.nombre` → `pre_invitados.nombre`.
- `principal.apellido` → `pre_invitados.apellido`.
- `principal.confirmacion` → `pre_invitados.confirmacion`.
- `principal.restriccion_alimentaria` → `pre_invitados.alimento`.
- `principal.comentario` → `pre_invitados.confirmacion_comentario`.
- `total_personas` → `pre_invitados.confirmacion_mayores`.
- `0` → `pre_invitados.confirmacion_menores`.
- `total_personas > 1` → `pre_invitados.acompanado`.
- `principal.telefono` → `pre_invitados_tel.tel_enviar`.
- id futuro del principal → `pre_invitados_tel.id_invitados`.
- `acompanantes[n].nombre` → `pre_invitados_listado_mesa.nombre2`.
- `acompanantes[n].apellido` → `pre_invitados_listado_mesa.apellido2`.
- display del acompañante → `pre_invitados_listado_mesa.nombre_invitado`.
- id futuro del principal → `pre_invitados_listado_mesa.id_invitados`.

## Gaps conocidos de `legacy_pre_v1`

El esquema histórico no incluye columnas claras para guardar por acompañante:

- `restriccion_alimentaria`.
- `comentario`.

Cuando el payload normalizado trae esos datos, el plan agrega entradas en `mapping_gaps`. No es error fatal y no bloquea el diagnóstico.

## Por qué todavía no se escribe nada

UNI-018 solo prepara perfiles, diagnóstico y planes de mapeo. Todos los planes devuelven:

- `executable=false`.
- `write_enabled=false`.
- `contains_sql=false`.

No se crean tablas, no se alteran tablas, no se insertan datos, no se actualizan datos, no se borran datos y no se confirma asistencia real.

## CLI de prueba

Script creado:

```bash
php tools/dqs_rsvp_form_schema_profiles_probe.php --help
php tools/dqs_rsvp_form_schema_profiles_probe.php --profiles
php tools/dqs_rsvp_form_schema_profiles_probe.php --schema
php tools/dqs_rsvp_form_schema_profiles_probe.php --detect
php tools/dqs_rsvp_form_schema_profiles_probe.php --sample=valid --profile=contract_v1
php tools/dqs_rsvp_form_schema_profiles_probe.php --sample=valid --profile=legacy_pre_v1
php tools/dqs_rsvp_form_schema_profiles_probe.php --sample=companions --profile=contract_v1
php tools/dqs_rsvp_form_schema_profiles_probe.php --sample=companions --profile=legacy_pre_v1
php tools/dqs_rsvp_form_schema_profiles_probe.php --sample=no --profile=legacy_pre_v1
php tools/dqs_rsvp_form_schema_profiles_probe.php --sample=companions --profile=all
```

El CLI es solo CLI, usa samples ficticios locales y solo abre conexión al pedir `--schema` o `--detect`.

## Confirmar que `rsvp_modo` queda en `codigo`

La configuración efectiva se verifica con:

```bash
php tools/dqs_provider_config.php --show
```

Debe conservar:

- `plan_servicio: oro`.
- `rsvp_modo: codigo`.
- `fuente_envios_whatsapp: invitados`.
- `whatsapp_enabled: 1`.
- `regalos_enabled: 1`.

## Pendiente para UNI-019

UNI-019 debería decidir, todavía con controles explícitos, si se avanza hacia persistencia real, qué perfil se adopta o migra, cómo se autoriza la escritura, cómo se maneja transaccionalidad/auditoría y cómo se conectaría el endpoint sin afectar el modo activo por código.
