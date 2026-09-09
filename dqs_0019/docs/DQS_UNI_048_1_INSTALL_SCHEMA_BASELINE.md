# UNI-048.1 — baseline del esquema canónico de instalación

## Alcance y fuente de verdad

Esta unidad define el primer paquete técnico **schema-only** para una instalación nueva. La única fuente estructural utilizada fue `docs/DQS_SCHEMA_SNAPSHOT_HOSTINGER_20260719_012551.sql`, generado el 19 de julio de 2026 sobre MariaDB 11.8.8. El snapshot declara 31 tablas, no contiene filas ni credenciales y fue validado contra el inventario solicitado.

No se ejecutó SQL, no se conectó ni modificó una base, no se importaron dumps y no se modificó código funcional, `conexion.php`, el admin, `admin_tmp`, RSVP, WhatsApp, regalos ni la tienda pública.

## Inventario validado y clasificación

La clasificación diferencia el núcleo necesario, dominios funcionales opcionales y objetos que requieren una decisión explícita. “Opcional/feature” significa que la tabla se conserva en el paquete para compatibilidad estructural con la aplicación existente, no que UNI-048.1 habilite la función.

| Dominio | Clasificación | Tablas detectadas | Decisión en `schema.sql` |
|---|---|---|---|
| Núcleo | Requeridas | `user`, `admin_config`, `cliente`, `site_settings` | Incluidas |
| Contenido público | Requeridas para el sitio actual | `info_casamiento`, `info_mostrar`, `info_eventos`, `info_historia`, `info_nosotros`, `info_otra`, `imagenes`, `visitas` | Incluidas |
| RSVP | Requeridas para RSVP actual | `invitados`, `invitados_listado_mesa`, `invitados_prioridad`, `invitados_tel`, `intivados_acompanante`, `invitaciones_estado` | Incluidas |
| RSVP / staging | Opcionales/feature | `pre_invitados`, `pre_invitados_listado_mesa`, `pre_invitados_tel` | Incluidas |
| WhatsApp / envíos | Opcionales/feature | `invitados_a_enviar`, `invitados_enviados`, `registro_mensajes_enviados` | Incluidas |
| Regalos / tienda | Opcionales/feature | `productos`, `carrito`, `regalos`, `regalos_confirmacion`, `regalos_detalles` | Incluidas |
| Respaldo operativo | Legacy/backups/revisar | `invitados_listado_mesa_bkp` | Excluida; una instalación limpia no debe crear un backup operativo |
| Pagos de invitados | Legacy/backups/revisar | `invitados_pagos` | Excluida hasta identificar dueño funcional, retención y uso actual |

**Resultado:** 31 tablas inventariadas, 29 incluidas y 2 excluidas de la primera baseline. La exclusión no elimina nada del entorno existente: únicamente evita crear esos objetos en instalaciones nuevas hasta completar la revisión.

### Restricciones relevantes preservadas

- `user.email` conserva su índice `UNIQUE`.
- `invitados.codigo` conserva su índice `UNIQUE`.
- Se preservan los tipos, nulabilidad, defaults, índices y claves foráneas declarados en el snapshot, salvo los ajustes de portabilidad documentados abajo.
- Las tablas están ordenadas para que las referencias declaradas existan antes de crear `imagenes`, `carrito`, `regalos_confirmacion`, `regalos_detalles` y `registro_mensajes_enviados`.
- No se agregaron claves foráneas inferidas: hacerlo sin auditar datos y contratos sería un cambio agresivo.

## Propuesta canónica instalable

El paquete inicial vive en `database/install/`:

1. `schema.sql` crea las 29 tablas aceptadas y el trigger, sin seleccionar ni crear una base hardcodeada.
2. `seed.sql` aporta los catálogos técnicos obligatorios y los defaults de configuración confirmados.
3. `seed_default_content.sql` aporta, de forma opcional, contenido público inicial editable.
4. `manifest.json` permite al futuro preflight conocer versión, procedencia, objetos esperados, ambos seeds y extensiones PHP sugeridas.

El consumidor debe crear o seleccionar la base destino y gestionar permisos antes de aplicar el paquete. UNI-048.1 no proporciona todavía un ejecutor web.

### Portabilidad y collations

Se priorizó fidelidad: se mantuvo la mezcla existente de `latin1`, `utf8mb4_unicode_ci` y `utf8mb4_general_ci`. Solo se hicieron dos sustituciones puntuales porque los nombres originales no son comunes a MySQL y MariaDB:

- `utf8mb4_uca1400_ai_ci` → `utf8mb4_unicode_ci` en las cuatro tablas afectadas (`site_settings` y las tres `pre_invitados*`).
- `latin1_swedish_nopad_ci` → `latin1_swedish_ci` en `info_casamiento`.

No se convirtió globalmente a `utf8mb4`. La normalización integral, sus comparaciones, tamaños de índices y conversión de datos quedan como deuda separada.

## Trigger `generar_codigo_invitado`

El snapshot contiene un `BEFORE INSERT` que genera un código aleatorio de seis dígitos y consulta hasta encontrar uno no usado. La versión canónica mantiene ese comportamiento y elimina por completo `DEFINER=...`; el trigger se crea bajo el contexto del usuario que instala, sin usuario Hostinger ni host hardcodeados.

El chequeo previo no elimina una carrera entre dos transacciones: ambas podrían elegir el mismo valor antes del commit. El `UNIQUE` de `invitados.codigo` es la salvaguarda definitiva, pero una inserción puede fallar excepcionalmente y el llamador debe poder reintentar. UNI-048.2 debe comprobar privilegio `TRIGGER` y reportar claramente fallos al crearlo. Una estrategia de códigos con mayor entropía o reintento transaccional queda pendiente, sin cambiar la lógica en esta unidad.

## Seeds técnicos y contenido inicial editable

### Seed técnico obligatorio: `seed.sql`

Este archivo prepara una instalación limpia con datos técnicos determinísticos:

- ocho switches de `info_mostrar`: `about`, `story`, `gallery`, `events`, `wedding`, `contact`, `cronometro` y `logo`;
- cinco categorías históricas de `intivados_acompanante`: `Solo/a`, `Flia`, `Novio/a`, `Sr/a` y `Amigo/a`;
- cuatro categorías históricas de `invitados_prioridad`: `Importante`, `Medio Importante`, `Normal` y `No necesario`;
- seis defaults de `site_settings`: `plan_servicio=oro`, `rsvp_modo=codigo`, `fuente_envios_whatsapp=invitados`, `whatsapp_enabled=1`, `regalos_enabled=1` y `rsvp_form_persist_enabled=0`.

Las seis claves y sus valores están declarados tanto en `DQS_PLAN_CONFIG_DEFAULTS` como en el contrato de valores permitidos de `includes/plan_config.php`; por eso se consideran soportados y no inventados. El valor `0` de persistencia conserva el formulario RSVP desactivado para escrituras por defecto.

El snapshot actual exige `invitados_prioridad.categoria_precio`, aunque las lecturas actuales del catálogo no consumen esa columna. Se usa `0` como valor técnico neutral para las cuatro prioridades; cualquier semántica comercial futura requiere una unidad separada.

### Contenido inicial editable opcional: `seed_default_content.sql`

Este archivo crea una portada neutral, dos presentaciones de personas, cuatro hitos genéricos, tres eventos y tres ítems de información adicional. Usa nombres, textos, fechas y campos vacíos claramente editables; no incluye enlaces externos ni depende de imágenes. Las fechas `2000-01-01` a `2000-01-04` son placeholders explícitos porque `info_historia.fecha` no acepta `NULL`.

Los dumps históricos `nuevo_cliente_v2.sql` bajo `docs/referencia_planes/` se consultaron solamente para identificar qué tablas se inicializaban y con qué propósito. No se importaron ni copiaron literalmente sus datos, historias, ubicaciones, enlaces o archivos.

El futuro instalador puede ofrecer el checkbox **“Cargar contenido inicial editable”**, activado por defecto para replicar el flujo actual de alta. Si se acepta, aplicará el archivo opcional después del seed técnico; el cliente o proveedor podrá reemplazar luego el contenido desde el admin. Si se desmarca, la instalación conserva los catálogos y defaults técnicos pero comienza sin contenido editorial.

Ninguno de los dos seeds inserta usuarios, registros de cliente, configuración de carpetas admin, invitados, teléfonos, emails, productos, carrito, regalos, imágenes, credenciales ni datos bancarios.

## Riesgos y decisiones pendientes

1. **Contratos sin FK:** varias columnas con nombres relacionales carecen de constraints en el snapshot; el preflight debe validar estructura real, no asumir relaciones nuevas.
2. **Catálogos técnicos:** conservar los IDs determinísticos porque forman parte del contrato histórico; definir por separado cualquier semántica futura de `categoria_precio`.
3. **Contenido opcional:** el instalador debe distinguir claramente datos técnicos obligatorios de placeholders editoriales omitibles.
4. **Legacy:** buscar usos de `invitados_pagos` y establecer si es una feature vigente; definir política de migración/archivo para `invitados_listado_mesa_bkp`.
5. **Charsets:** planificar una unidad independiente de normalización y pruebas con acentos, joins y comparaciones; no mezclarla con el instalador.
6. **Compatibilidad de motor:** validar el paquete en las versiones mínimas de MySQL y MariaDB que se soportarán, no solamente en el origen MariaDB 11.8.
7. **Idempotencia:** estos archivos representan una instalación vacía; no usan `IF NOT EXISTS` ni upserts para ocultar instalaciones parciales. El instalador debe detectar y detenerse ante colisiones.
8. **Datos sensibles:** `cliente` conserva columnas bancarias por compatibilidad estructural, pero el paquete no contiene valores. Su tratamiento y protección requieren revisión de seguridad aparte.

## Insumos y próximos pasos para UNI-048.2

El preflight del instalador debería, antes de ejecutar cualquier DDL:

- verificar versión y familia del motor, InnoDB y collations requeridas;
- comprobar las extensiones PHP (`pdo_mysql` y/o `mysqli`) que realmente use la implementación;
- comprobar conexión, base seleccionada, charset de sesión y privilegios `CREATE`, `ALTER`, `INDEX`, `REFERENCES` y `TRIGGER` sin crear usuarios;
- validar que el destino esté vacío o detenerse con un diagnóstico; nunca sobrescribir tablas ni importar dumps históricos;
- leer y validar `manifest.json`, y comparar las 29 tablas y el trigger esperados;
- ejecutar `schema.sql` y luego el `seed.sql` obligatorio con registro de pasos, manejo explícito de fallos y sin exponer credenciales; ofrecer `seed_default_content.sql` como paso opcional;
- verificar después de instalar constraints críticas (`user.email`, `invitados.codigo`), tablas, trigger, 23 filas técnicas y, si se eligió, 13 filas editoriales;
- definir rollback seguro para una instalación nueva parcialmente fallida, sin aplicarlo a una base preexistente;
- mantener separadas las futuras ampliaciones de configuración y contenido, validándolas contra helpers y contratos vigentes.

UNI-048.2 no debe recuperar `registrar_usuario.php`, mover `admin_tmp`, cambiar `conexion.php` ni usar un dump con datos reales.
