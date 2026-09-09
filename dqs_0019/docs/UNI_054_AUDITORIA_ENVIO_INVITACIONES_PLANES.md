# UNI-054 — Auditoría del envío de invitaciones WhatsApp por plan

> **Alcance:** auditoría estática y plan de corrección. No se modificó código, base de datos, schema ni seeds. Las referencias históricas describen intención y comportamiento previo, pero no se consideran automáticamente código listo para copiar.

## 1. Resumen ejecutivo

La configuración ya puede expresar los tres escenarios comerciales, pero la aplicación de administración **no aplica `fuente_envios_whatsapp` al flujo visible ni a sus endpoints**. `includes/plan_config.php` normaliza plan/modo/WhatsApp y `includes/guest_source.php` conoce el mapa `invitados*`/`pre_invitados*`; sin embargo, este último es deliberadamente *read-only* y ningún archivo productivo de envío lo invoca.

El resultado actual es:

- `/index.php?new=envioinvitaciones` del admin incluye `gestionar_envios.php`, que lista y muta exclusivamente `invitados`, `invitados_tel` e `invitados_listado_mesa`.
- No existe fallback explícito del tipo «si `pre_invitados` está vacío, consultar `invitados`». El defecto es más directo: la fuente configurada nunca se consulta, por lo que siempre se usa `invitados*`. En ORO FORM esto se comporta como un fallback permanente e incorrecto.
- Alta y edición viven en la pantalla general `?new=invitados`, no dentro de `envioinvitaciones`, y están hardcodeadas a `invitados*`. `gestionar_envios.php` no ofrece hoy botones de alta/edición de contactos.
- Los endpoints PHP de envío inicial y reenvío están hardcodeados a `invitados*`. También lo están la pantalla y el generador del flujo denominado «Envío Automático».
- BÁSICO FORM oculta las dos opciones WhatsApp cuando `whatsapp_enabled=0`; los archivos principales tienen guard. El include ocurre después de comenzar a renderizar el layout, por lo que el bloqueo directo existe pero puede producir una respuesta HTML parcial/semánticamente débil. Deben auditarse y cubrirse como endpoints independientes los generadores de imágenes, que no tienen el mismo guard.
- La persistencia productiva de RSVP FORM sí está separada correctamente: `rsvp_form_validate.php` llama a `includes/rsvp_form_final_persistence.php`, que inserta transaccionalmente en `invitados*`. El helper antiguo `includes/rsvp_form_persistence.php`, orientado a `pre_*`, permanece como artefacto experimental y no es llamado por el endpoint productivo.

**Conclusión:** el criterio fundamental no se cumple. En ORO FORM, la pantalla de gestión continúa siendo una pantalla de `invitados`, no de `pre_invitados`. La corrección debe seleccionar una única fuente efectiva al principio de cada request, validar que sea compatible con el plan, y reutilizar el mismo mapa en listado, mutaciones, alta, edición, envío y reenvío, sin fallback entre familias.

## 2. Matriz de comportamiento esperado

| Escenario | Configuración efectiva | Gestión de envíos | Alta/edición desde gestión | Envío/reenvío | RSVP final |
|---|---|---|---|---|---|
| ORO + código | `oro`, `codigo`, WhatsApp `1`, fuente `invitados` | `invitados` + `invitados_tel` + `invitados_listado_mesa` | Escribe la misma familia | Lee la misma familia | Actualiza el invitado preexistente por código |
| ORO + FORM | `oro`, `form`, WhatsApp `1`, fuente `pre_invitados` | **Solo** `pre_invitados` + `pre_invitados_tel` + `pre_invitados_listado_mesa`; vacía si no hay filas | Escribe **solo** `pre_*` | Lee **solo** `pre_*` | Crea la confirmación definitiva en `invitados*`; no convierte `pre_*` por efecto lateral implícito |
| BÁSICO + FORM | `basico`, `form`, WhatsApp `0`, fuente efectiva `ninguno` | Menú oculto y acceso directo bloqueado | No disponible | No ejecutable | Crea confirmación definitiva en `invitados*` |

Configuraciones objetivo:

```bash
# ORO + código
php tools/dqs_provider_config.php --set plan_servicio=oro --set rsvp_modo=codigo --set whatsapp_enabled=1 --set fuente_envios_whatsapp=invitados --set rsvp_form_persist_enabled=0 --apply

# ORO + FORM
php tools/dqs_provider_config.php --set plan_servicio=oro --set rsvp_modo=form --set whatsapp_enabled=1 --set fuente_envios_whatsapp=pre_invitados --set rsvp_form_persist_enabled=1 --apply

# BÁSICO + FORM
php tools/dqs_provider_config.php --set plan_servicio=basico --set rsvp_modo=form --set whatsapp_enabled=0 --set rsvp_form_persist_enabled=1 --apply
```

La configuración efectiva fuerza `rsvp_modo=form` en Básico y `fuente_envios_whatsapp=ninguno` cuando WhatsApp está apagado. No fuerza por sí misma la pareja ORO/código→`invitados` u ORO/form→`pre_invitados`; depende del valor persistido y, hoy, los consumidores tampoco lo usan.

## 3. Mapa de rutas y archivos

### 3.1 Entrada del admin

| Ruta/acción | Archivo real | Estado actual |
|---|---|---|
| `/index.php` público | `index.php` raíz | Renderiza la web y el RSVP según modo; no enruta el admin de envíos. |
| `/admin7WZiwEM3XY/index.php?new=envioinvitaciones` (la URL abreviada del requerimiento) | `admin7WZiwEM3XY/index.php` incluye `admin7WZiwEM3XY/gestionar_envios.php` | Guard WhatsApp dentro del include; fuente fija `invitados*`. |
| `?new=invitaciones` | incluye `admin7WZiwEM3XY/invitados_invitaciones.php` | Flujo «Envío Automático», separado de la gestión drag-and-drop; fuente fija `invitados*`. |
| `?new=invitados` | incluye `admin7WZiwEM3XY/invitados.php` | Lista definitiva; incluye alta o edición según query string. |
| `?new=invitados&nuevo=0` | `invitados.php` incluye `nuevo_invitado.php` | Alta fija en `invitados*`. |
| `?new=invitados&id=<id>` | `invitados.php` incluye `editar_invitado.php` | Carga/escritura fija en `invitados*`. |

No existe un `index.php?new=envioinvitaciones` en el `index.php` público. La ruta funcional es relativa al directorio admin (o una reescritura externa no presente en el repositorio).

### 3.2 Envío y reenvío

| Disparador | Archivo | Fuente actual |
|---|---|---|
| Botón «Enviar invitaciones» de `invitados_invitaciones.php` | `admin7WZiwEM3XY/whatsapp/envio_invitaciones.php` | `invitados*`, filtrado por `activo=1`, sin envío exitoso previo y con `confirmacion IS NULL`. |
| Botón de reenvío | `admin7WZiwEM3XY/whatsapp/reenvio_invitaciones_erroneas.php` | `invitados*`, unido a pares fallidos en `invitaciones_estado` y excluyendo pares que luego tuvieron éxito. |
| Generación de placas | `admin7WZiwEM3XY/invitaciones/index.php` y `generador_masivo.php` | `invitados*`; no usan el helper de fuente. |
| Gestión manual drag-and-drop | `gestionar_envios.php` | Mueve IDs/teléfonos entre `invitados_a_enviar` e `invitados_enviados`; esta pantalla no llama directamente al endpoint PHP de Meta. |

No existe `reenvio_invitaciones_erroneas.php` en la raíz del admin: solo existe bajo `admin7WZiwEM3XY/whatsapp/`. La carpeta raíz `dqs reenvio invitaciones/` es otra herramienta Node/CSV, no el endpoint web.

### 3.3 Configuración y helpers

- `includes/plan_config.php`: defaults, allowlists, lectura de `site_settings` y normalización efectiva.
- `includes/guest_source.php`: mapa read-only de tabla principal, integrantes, teléfonos y FK. `dqs_guest_source_from_plan_config()` devuelve la fuente efectiva, pero vuelve a `invitados` ante configuración ausente/inválida. Ese fallback sería peligroso en una operación WhatsApp y no debe usarse para convertir `ninguno` o un error en datos reales.
- `includes/rsvp_mode.php`: diagnóstico esperado por modo (`codigo`→`invitados`, `form`→`pre_invitados`), no integra el admin.
- `includes/admin_feature_guard.php`: autorización binaria por `whatsapp_enabled`; no valida plan ORO ni fuente distinta de `ninguno`.

## 4. Flujo real de lectura/escritura

### 4.1 `/index.php?new=envioinvitaciones`

`gestionar_envios.php` ejecuta tres listados:

1. **A enviar:** `invitados` INNER JOIN `invitados_a_enviar` INNER JOIN `invitados_tel`, más agrupación desde `invitados_listado_mesa`.
2. **Enviados:** `invitados` INNER JOIN `invitados_enviados` INNER JOIN `invitados_tel`, más la misma agrupación.
3. **Pendientes:** `invitados` INNER JOIN `invitados_tel`, más integrantes, excluyendo IDs presentes en las otras dos colecciones y aplicando filtros.

Mutaciones de esa pantalla:

- agrega/quita/mueve filas de `invitados_a_enviar` e `invitados_enviados`;
- al devolver un enviado, relee el teléfono de `invitados_tel`;
- «confirmar» actualiza `invitados.confirmacion`;
- activar/desactivar lee y actualiza `invitados.activo`.

No se incluye `guest_source.php`, no se consulta `fuente_envios_whatsapp` y no existe rama para `pre_*`. Si `pre_invitados` está vacío pero `invitados` tiene filas, estas se muestran: no por una consulta condicional de fallback, sino porque `pre_invitados` jamás participa.

Las tablas de cola (`invitados_a_enviar`, `invitados_enviados`) usan nombres/FK históricos y pueden contener IDs de cualquiera de las dos familias en la referencia FORM. Esto crea colisiones posibles si `invitados.id=12` y `pre_invitados.id=12`: no hay columna `source` visible en el código. Antes de reutilizarlas debe verificarse el schema real y definir aislamiento por fuente sin tocar schema en esta fase de auditoría.

### 4.2 Alta

- Procesador: `admin7WZiwEM3XY/nuevo_invitado.php`, incluido solo por `invitados.php`.
- Código único: consulta `invitados.codigo`.
- Principal: INSERT en `invitados`.
- Titular/acompañantes: INSERT en `invitados_listado_mesa` mediante `id_invitados`.
- Teléfonos: INSERT en `invitados_tel` mediante `id_invitados`.
- Decisión de modo/fuente: ninguna.
- Retorno: `?new=invitados...`, no `?new=envioinvitaciones`.

Por tanto, hoy **no hay alta desde la sección de envíos**. El enlace de menú «Nuevo invitado» siempre crea un invitado definitivo. La referencia ORO FORM sí cambia el alta a `pre_*`, pero conserva el retorno hacia `?new=invitados`, lo que demuestra que esa referencia también quedó mezclada y no debe copiarse sin adaptar navegación y contexto.

### 4.3 Edición

- Procesador: `admin7WZiwEM3XY/editar_invitado.php`, incluido por `invitados.php`.
- Carga principal: `invitados`, con catálogos históricos.
- Carga de integrantes/teléfonos: `invitados_listado_mesa` e `invitados_tel`.
- Escritura: UPDATE `invitados`; DELETE + INSERT de integrantes y teléfonos en la familia definitiva.
- Decisión de modo/fuente: ninguna.

No hay endpoint/editor de `pre_*`. Más importante: incluso la copia `docs/referencia_planes/oro_form/admin_tmp/editar_invitado.php` sigue usando `invitados*`; la referencia no resuelve la edición FORM.

### 4.4 Envío inicial PHP

`admin7WZiwEM3XY/whatsapp/envio_invitaciones.php`:

- toma principal de `invitados`;
- compone destinatarios desde `invitados_listado_mesa`;
- toma teléfonos de `invitados_tel`;
- une `cliente` para número/remitente;
- excluye pares con estado previo `enviado`, exige `activo=1` y `confirmacion IS NULL`;
- arma link con `codigo#rsvp` y llama a Graph API;
- registra cada resultado en `invitaciones_estado` con `(id_invitado, id_invitados_tel)`.

No conoce `pre_*`. Con `pre_invitados` vacío seguirá enviando candidatos de `invitados`. El guard solo comprueba WhatsApp encendido.

### 4.5 Reenvío PHP

`admin7WZiwEM3XY/whatsapp/reenvio_invitaciones_erroneas.php` repite las tres lecturas hardcodeadas de `invitados*`. Selecciona combinaciones con un estado distinto de `enviado` que no tengan éxito posterior, vuelve a llamar Graph API y agrega otro registro a `invitaciones_estado`. Está totalmente acoplado al flujo histórico y no comparte una resolución de fuente con el envío inicial.

### 4.6 RSVP FORM definitivo

El POST público llega a `rsvp_form_validate.php`. Solo persiste si:

1. el payload es válido;
2. el modo efectivo es `form`;
3. `rsvp_form_persist_enabled=1`;
4. el schema definitivo requerido está disponible.

Entonces `dqs_rsvp_form_final_persistence_save()` abre una transacción, deduplica confirmaciones recientes e inserta principal en `invitados`, teléfono en `invitados_tel` si corresponde, e integrantes en `invitados_listado_mesa`; hace commit o rollback. No lee ni consume `pre_*`.

Esta separación coincide con el requisito: `pre_*` es agenda/staging de invitación; `invitados*` es registro definitivo de RSVP. El helper viejo `includes/rsvp_form_persistence.php` contiene previews/dedupe para `pre_*`, pero su función `save()` lanza una excepción y no está enlazado al endpoint. Conviene conservar la prohibición explícita o retirarlo en una iniciativa posterior, porque su nombre ambiguo facilita una regresión.

### 4.7 Tabla por tabla

| Tabla | Lectura/escritura productiva actual | Rol correcto |
|---|---|---|
| `invitados` | Gestión, alta, edición, envío, reenvío, RSVP código y RSVP FORM final | Fuente WhatsApp de ORO código y destino final de confirmaciones FORM |
| `invitados_tel` | Gestión, alta, edición, envío/reenvío y persistencia FORM final | Teléfonos definitivos; fuente WhatsApp solo en ORO código |
| `invitados_listado_mesa` | Gestión, alta, edición, composición del mensaje y confirmación código/FORM | Integrantes definitivos; fuente WhatsApp solo en ORO código |
| `pre_invitados` | No participa del admin/envío productivo; helpers diagnósticos/experimentales | Contactos de staging para ORO FORM |
| `pre_invitados_tel` | Igual | Teléfonos de staging para ORO FORM |
| `pre_invitados_listado_mesa` | Igual | Integrantes/nombres a invitar en ORO FORM |

## 5. Comparación con referencias funcionales

### 5.1 `docs/referencia_planes/oro_form`

La referencia confirma parcialmente la arquitectura deseada:

- `gestionar_envios.php` lista, activa/confirma y relee teléfonos desde `pre_invitados*`, manteniendo las colas históricas `invitados_a_enviar`/`invitados_enviados`.
- `nuevo_invitado.php` genera código e inserta principal, integrantes y teléfonos en `pre_*`.
- `invitaciones/index.php` y `generador_masivo.php` generan placas desde `pre_*`.

Pero **no es una referencia integral consistente**:

- `editar_invitado.php` sigue leyendo y escribiendo `invitados*`.
- `whatsapp/envio_invitaciones.php` y el reenvío siguen leyendo `invitados*`.
- `invitados_invitaciones.php` también sigue en `invitados*`.
- El alta `pre_*` redirige a la lista definitiva `?new=invitados`.
- El menú muestra opciones de envío sin guard comercial.

Por ello sirve para validar el modelo de datos `pre_*` y las consultas de gestión/generación, no como parche completo.

### 5.2 `docs/referencia_planes/dqs envios invitaciones_form`

La herramienta Node representa el envío local externo que sí consulta de forma coherente:

- `pre_invitados` como principal;
- `pre_invitados_listado_mesa` para formar `{{invitados}}`;
- `pre_invitados_tel` para destinatarios;
- `invitados_a_enviar` como cola seleccionada;
- `invitados_enviados` y `registro_mensajes_enviados` como historial, eliminando la fila de la cola dentro de una transacción tras enviar.

Construye el recurso de invitación con el ID formateado, usa plantillas singular/plural y envía mediante una sesión WhatsApp Web/Node, no mediante el endpoint PHP Graph API. Confirma que `pre_*` era la base de contactos para envío, pero no define la persistencia RSVP final ni resuelve colisiones entre IDs de fuentes.

La comparación revela la mezcla exacta: el admin productivo actual se parece a ORO código; el `oro_form` histórico solo cambió gestión/generación; y el proceso Node sí cambió la consulta que efectúa el envío. La unificación previa no trasladó esa selección de fuente a todos los consumidores.

## 6. Consultas hardcodeadas y riesgosas

### 6.1 Hardcode que rompe la selección de fuente

**`admin7WZiwEM3XY/gestionar_envios.php`**

- `SELECT tel_enviar FROM invitados_tel WHERE id = ?`.
- `UPDATE invitados SET confirmacion = 'Si' WHERE id = ?`.
- `SELECT activo FROM invitados WHERE id = ?` y `UPDATE invitados SET activo = ? WHERE id = ?`.
- Subconsulta `FROM invitados_listado_mesa`.
- Tres consultas principales `FROM invitados`/`INNER JOIN invitados_tel`.

**`admin7WZiwEM3XY/nuevo_invitado.php`**

- unicidad en `invitados`;
- INSERT en `invitados`;
- INSERT del titular/acompañantes en `invitados_listado_mesa`;
- INSERT de teléfonos en `invitados_tel`.

**`admin7WZiwEM3XY/editar_invitado.php`**

- SELECT/UPDATE principal en `invitados`;
- SELECT/DELETE/INSERT en `invitados_listado_mesa`;
- SELECT/DELETE/INSERT en `invitados_tel`.

**`admin7WZiwEM3XY/invitados_invitaciones.php`**

- todas las mutaciones, listas y joins usan `invitados*`.

**`admin7WZiwEM3XY/whatsapp/envio_invitaciones.php` y `reenvio_invitaciones_erroneas.php`**

- principal, teléfonos e integrantes fijados a `invitados*`.

**`admin7WZiwEM3XY/invitaciones/index.php` y `generador_masivo.php`**

- generación de imagen fijada a `invitados*`.

### 6.2 Riesgos transversales

1. **Fallback inseguro:** `dqs_guest_source_from_plan_config()` convierte fuente ausente, inválida o `ninguno` a `invitados`. Es aceptable como compatibilidad diagnóstica, pero no como autorización/selección de un envío.
2. **Identidad sin namespace:** colas e historiales guardan solo IDs, no la familia. Alternar configuraciones puede reinterpretar filas antiguas contra otra tabla con el mismo ID.
3. **Pantallas separadas:** alta/edición general y gestión de envíos no comparten contexto. Cambiar globalmente `nuevo_invitado.php` a `pre_*` rompería la lista definitiva y RSVP código.
4. **SQL interpolado:** filtros de `gestionar_envios.php` (`status`, `confirmacion`, `ingreso`, `prioridad`, búsqueda) se concatenan; el alta también interpola entradas escapadas de manera desigual. La corrección de fuente no debe ampliar este riesgo y debería parametrizar valores.
5. **Tablas dinámicas:** parametrizar nombres de tabla no es posible con placeholders. Deben salir únicamente de un mapa cerrado, nunca de GET/POST ni del valor crudo de configuración.
6. **Guard tardío:** `index.php` empieza a renderizar antes de incluir el archivo que bloquea; conviene autorizar antes del layout.
7. **Guard incompleto:** solo valida `whatsapp_enabled`; no exige `plan_servicio=oro`, fuente válida y coherencia modo/fuente.
8. **Endpoints auxiliares:** los generadores bajo `invitaciones/` carecen del guard común y podrían accederse directamente aunque WhatsApp esté deshabilitado.
9. **Estado RSVP en staging:** el botón «confirmar invitado» de la referencia actualiza `pre_invitados.confirmacion`. Eso puede confundir selección de envío con confirmación definitiva; en FORM debe definirse si el botón se elimina o solo marca un estado operativo inequívoco, sin crear confirmación en `invitados*`.
10. **Dos perfiles de schema `pre_*`:** los helpers reconocen un perfil moderno (`id_pre_invitado`, `telefono`, `nombre`) y uno legacy (`id_invitados`, `tel_enviar`, `nombre_invitado`). El admin y las referencias asumen legacy. La implementación debe detectar/validar perfil una vez o declarar el perfil soportado; no mezclar columnas.
11. **Dos transportes:** la referencia Node usa WhatsApp Web y la producción PHP usa Graph API. La fuente puede compartirse, pero no se debe mezclar lógica de sesión/transporte.

## 7. Archivos candidatos a modificar después

### Imprescindibles

1. `includes/guest_source.php` — separar resolución estricta de la función con fallback y añadir mapa seguro reutilizable de lectura/escritura (o crear helper específico).
2. `includes/admin_feature_guard.php` — guard de capacidad WhatsApp + plan + fuente coherente.
3. `admin7WZiwEM3XY/index.php` — aplicar guard antes de renderizar rutas protegidas.
4. `admin7WZiwEM3XY/menu.php` — mostrar opciones solo cuando la capacidad efectiva sea válida.
5. `admin7WZiwEM3XY/gestionar_envios.php` — listado y mutaciones sobre la fuente resuelta, sin fallback.
6. `admin7WZiwEM3XY/nuevo_invitado.php` — aceptar contexto explícito y escribir la familia correcta; preservar alta definitiva general.
7. `admin7WZiwEM3XY/editar_invitado.php` — cargar/escribir la misma fuente explícita; preservar edición definitiva general.
8. `admin7WZiwEM3XY/invitados.php` o una nueva pantalla/controladora de contactos de envío — transportar el contexto sin inferirlo de un ID.
9. `admin7WZiwEM3XY/invitados_invitaciones.php` — resolver si sigue siendo superficie soportada y, si lo es, seleccionar fuente/autorizarla.
10. `admin7WZiwEM3XY/whatsapp/envio_invitaciones.php`.
11. `admin7WZiwEM3XY/whatsapp/reenvio_invitaciones_erroneas.php`.
12. `admin7WZiwEM3XY/invitaciones/index.php` y `admin7WZiwEM3XY/invitaciones/generador_masivo.php` si el flujo automático/placas forma parte del envío soportado.

### A revisar, sin cambiar el objetivo funcional

- `includes/plan_config.php` y `tools/dqs_provider_config.php` para validación de combinaciones coherentes.
- `includes/rsvp_form_persistence.php` para renombrar/documentar/deprecar el helper experimental y evitar uso accidental.
- `includes/rsvp_form_final_persistence.php` y `rsvp_form_validate.php`: deben permanecer apuntando a `invitados*`; solo requieren pruebas de regresión, no cambio de fuente.
- `invitaciones_estado`, `invitados_a_enviar`, `invitados_enviados` y `registro_mensajes_enviados`: revisar contrato e identidad. Cualquier cambio de schema sería una iniciativa explícita posterior, no parte automática de esta corrección.

## 8. Plan de corrección por fases

### Fase 1 — Solo listado según fuente

1. Introducir resolución **estricta**: `whatsapp_enabled=1`, plan permitido y fuente exactamente `invitados` o `pre_invitados`; `ninguno`/inválida bloquea.
2. Obtener nombres de tablas únicamente del mapa cerrado.
3. Cambiar las tres consultas de `gestionar_envios.php` en conjunto.
4. No consultar la otra familia ante tabla vacía, ausente o error. Vacía significa tres arrays vacíos; tabla ausente debe ser error operativo explícito, no fallback.
5. Mantener ORO código como baseline y comprobar SQL/resultados equivalentes.

### Fase 2 — Alta y edición según fuente

1. Añadir acciones de alta/edición propias de la gestión o un parámetro de contexto firmado/allowlisted; nunca deducir fuente solo por modo o por ID.
2. Resolver una vez principal/teléfono/integrantes y usar ese mapa durante toda la transacción.
3. Alta FORM escribe `pre_*`; alta general definitiva continúa escribiendo `invitados*`.
4. Edición debe leer y escribir la misma fuente; redirigir a la pantalla que originó la acción.
5. Parametrizar valores y validar perfil de columnas legacy/moderno.

### Fase 3 — Envío y reenvío según fuente

1. Extraer un constructor común del conjunto de destinatarios para que envío y reenvío no diverjan.
2. Aplicar el mismo mapa a principal, teléfonos e integrantes y a generación de placas.
3. Asegurar que el registro de estado conserva identidad inequívoca. Si las tablas existentes no pueden distinguir fuente, impedir conmutaciones peligrosas o diseñar una migración separada y aprobada.
4. Probar que una cola creada bajo una fuente no se reinterpreta bajo otra.

### Fase 4 — Guards para Básico/WhatsApp deshabilitado

1. Ocultar ambos enlaces solo con capacidad efectiva completa, no solo el flag.
2. Bloquear rutas en el router antes de emitir HTML.
3. Aplicar guard a POST, envío, reenvío y generadores directos, con respuesta 403 apropiada por formato.
4. Rechazar `fuente=ninguno`, fuente inválida y combinaciones incoherentes aunque alguien llame el endpoint directamente.

### Fase 5 — Pruebas

1. Pruebas unitarias del resolver estricto y mapa.
2. Pruebas de integración de listados con datasets deliberadamente distintos.
3. Pruebas transaccionales de alta/edición por fuente.
4. Dobles de transporte para envío/reenvío: nunca contactar WhatsApp real.
5. Regresión RSVP código y RSVP FORM final.
6. Pruebas de autorización de todas las rutas directas.

## 9. Pruebas manuales recomendadas

### ORO + código

1. Aplicar configuración objetivo y cargar datos distintos en ambas familias en un entorno descartable.
2. Abrir `?new=envioinvitaciones`: solo deben aparecer filas de `invitados*`.
3. Mover un contacto entre Pendientes/A enviar/Enviados y verificar IDs/teléfono.
4. Alta y edición desde gestión deben afectar `invitados*` y nunca `pre_*`.
5. Envío simulado debe componer nombres/teléfonos de `invitados*`.
6. Confirmar por código y comprobar actualización del registro existente y sus integrantes.

### ORO + FORM

1. Dejar `pre_invitados*` vacío y `invitados*` poblado: las tres columnas de gestión deben quedar vacías.
2. Crear un contacto desde gestión: debe aparecer solo en las tres tablas `pre_*` correspondientes.
3. Editarlo y comprobar que no cambian conteos ni filas de `invitados*`.
4. Agregarlo a cola y ejecutar envío simulado: nombre compuesto y teléfono deben provenir de `pre_*`.
5. Forzar error y reenvío: ambos deben resolver el mismo par pre principal/teléfono.
6. Enviar RSVP FORM público: debe crear únicamente la confirmación definitiva en `invitados*`; las filas `pre_*` permanecen staging salvo que exista una política explícita posterior.
7. Repetir el POST inmediato para comprobar deduplicación final.

### BÁSICO + FORM

1. Confirmar que no aparecen «Enviar Invitaciones» ni «Envío Automático».
2. Acceder directamente a `?new=envioinvitaciones`, `?new=invitaciones`, ambos endpoints `whatsapp/` y ambos generadores: todos deben responder 403/no ejecutar consultas operativas ni transporte.
3. Confirmar que RSVP FORM sí persiste en `invitados*`.

### Casos negativos obligatorios

- WhatsApp `1` con fuente `ninguno` o valor inválido: bloquear, nunca usar `invitados`.
- Tabla `pre_*` ausente: error de configuración visible, nunca fallback.
- IDs iguales en ambas familias: comprobar que cola/estado no cruzan contactos.
- Cambio de ORO código a ORO FORM con colas pendientes: bloquear o aislar según la decisión de diseño.
- Contacto sin teléfono, varios teléfonos, varios integrantes y menores.
- GET/POST con filtros e IDs manipulados; no debe ser posible elegir tabla desde input.

## 10. Riesgos de romper ORO código y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Sustituir globalmente `invitados` por `pre_invitados` | Resolver fuente por request mediante allowlist; fixture de regresión ORO código. |
| Cambiar alta/edición general al modo FORM | Separar contexto «lista definitiva» de «contactos de envío»; no basarse únicamente en `rsvp_modo`. |
| Divergencia entre listado y endpoint | Un único descriptor de fuente compartido y tests de contrato. |
| Interpretar `ninguno` como `invitados` | Resolver estricto para operaciones; sin fallback. |
| Colisión de IDs en colas/estado | Auditar identidad antes de Fase 3; no desplegar selección dual sin solución comprobada. |
| Cambiar RSVP FORM para escribir `pre_*` | Congelar prueba de contrato: endpoint final siempre tiene target `invitados*`. |
| Copiar la referencia ORO FORM literalmente | Usarla solo como evidencia; cubrir sus inconsistencias de edición/endpoints/navegación. |
| Bloquear después de renderizar | Guard en router/controlador antes de salida. |

## 11. Respuestas directas a las preguntas de auditoría

1. **Pantalla:** carga `gestionar_envios.php`; ejecuta tres SELECT sobre `invitados*`; no decide fuente; no hay fallback condicional, sino hardcode permanente a definitivas.
2. **Alta:** la procesa `nuevo_invitado.php` desde `?new=invitados`; inserta principal/teléfonos/integrantes en `invitados*`; no depende del modo y no existe alta propia de envíos.
3. **Edición:** la procesa `editar_invitado.php`; carga y reemplaza principal/teléfonos/integrantes en `invitados*`; no soporta `pre_*`.
4. **WhatsApp:** el endpoint PHP inicial lee las tres tablas definitivas; si `pre_*` está vacío no cambia nada y puede enviar `invitados`.
5. **Reenvío:** usa las tres tablas definitivas y `invitaciones_estado`; está acoplado al histórico y no comparte resolución con envío inicial.
6. **RSVP FORM:** persiste finalmente por `rsvp_form_validate.php` → `rsvp_form_final_persistence.php` en `invitados*`; no usa `pre_*` en producción y debe seguir separado.
7. **BÁSICO FORM:** el menú se oculta en `menu.php` por `whatsapp_enabled`; los tres archivos PHP principales tienen guard, pero el router lo ejecuta tarde y los generadores auxiliares quedan expuestos. El guard actual tampoco comprueba plan/fuente.

## 12. Criterio de aceptación futuro

La corrección solo estará completa cuando pueda demostrarse, con `invitados*` poblado y `pre_*` vacío, que ORO FORM muestra una gestión vacía y no ejecuta ninguna lectura/escritura contra `invitados*` durante listado, alta, edición, envío o reenvío; y, de forma independiente, que el POST de confirmación RSVP FORM continúa insertando el resultado definitivo en `invitados*`. ORO código debe conservar byte por byte el significado funcional histórico, y BÁSICO FORM no debe alcanzar ninguna operación WhatsApp por navegación ni URL directa.
