# UNI-065 — Fase 6A: regresión de planes, RSVP, contactos y envíos

Fecha de revisión: 2026-07-29.

## 1. Resumen ejecutivo

Se realizó una auditoría estática y de regresión no destructiva del admin PHP activo para los tres perfiles solicitados. No se revisó ni ejecutó Node, no se disparó ningún envío real, no se modificaron datos, configuración, schema, migrations, installer, seeds, importadores ni RSVP.

El enrutamiento, los menús, los guards, la selección de familia de tablas, el contrato normalizado `pre_*`, la baja lógica y el bloqueo de contactos por ID son coherentes con el plan efectivo. No se encontró un bug funcional pequeño que justificara modificar código en esta fase.

La conexión de base de datos no estuvo disponible en este checkout (`dqs_provider_config.php --show` informó tabla/conexión no disponible). Por ello no fue posible completar navegación autenticada ni altas/ediciones/bajas reales mediante UI. Los resultados que dependen de persistencia se califican como **verificados por inspección de código**, no como prueba E2E. Tampoco se efectuaron `--set`/`--apply`, para no alterar un entorno cuya base no estaba accesible.

**Recomendación:** **OK para avanzar**, manteniendo la condición operativa ya documentada por el código: las colas compartidas deben quedar vacías antes de cambiar entre `invitados` y `pre_invitados`. Sin esa condición existe riesgo de colisión de IDs, porque las colas no almacenan la fuente.

## 2. Configuraciones probadas

| Perfil | Configuración objetivo | Tipo de comprobación |
|---|---|---|
| ORO + CÓDIGO | `plan_servicio=oro`, `rsvp_modo=codigo`, `whatsapp_enabled=1`, `fuente_envios_whatsapp=invitados`, `rsvp_form_persist_enabled=0` | Configuración por defecto observada con `--show`; trazado estático de menú, rutas, alta y colas |
| ORO + FORM | `plan_servicio=oro`, `rsvp_modo=form`, `whatsapp_enabled=1`, `fuente_envios_whatsapp=pre_invitados`, `rsvp_form_persist_enabled=1` | Trazado estático de configuración efectiva, menú, guards, CRUD `pre_*` y colas |
| BÁSICO + FORM | `plan_servicio=basico`, `rsvp_modo=form`, `whatsapp_enabled=0`, `rsvp_form_persist_enabled=1` | Trazado estático de normalización efectiva, menú, guards y disponibilidad RSVP |

La normalización efectiva fuerza `rsvp_modo=form` para Básico y fuerza `fuente_envios_whatsapp=ninguno` cuando WhatsApp está deshabilitado. El guard de Contactos exige simultáneamente Oro, FORM, WhatsApp activo y fuente `pre_invitados`; el guard general de envíos exige Oro y WhatsApp activo.

## 3. Resultado ORO + CÓDIGO

**Resultado: conforme por inspección estática; persistencia/UI no ejecutadas por falta de BD.**

- El menú siempre incorpora **Lista de invitados**.
- Al no habilitarse el guard estricto de Contactos, el alta principal es **Nuevo invitado** (`?new=invitados&nuevo=0`).
- Con Oro y WhatsApp activo aparece **Enviar Invitaciones**.
- No aparecen **Contactos de envío** ni **Cargar contacto de envío**.
- `?new=invitados` incluye la pantalla histórica y `?new=invitados&nuevo=0` deriva al alta definitiva.
- El contexto de alta está fijado en servidor como `invitado`; su contrato cerrado selecciona exclusivamente `invitados`, `invitados_tel` e `invitados_listado_mesa`. Un parámetro GET/POST no puede cambiar esa familia a `pre_*`.
- `gestionar_envios.php` selecciona el mapa `invitados`, valida que el teléfono pertenezca a `invitados_tel` y arma Pendientes/A Enviar/Enviados contra esa familia.
- La persistencia RSVP FORM queda deshabilitada por modo y por flag en este perfil; la verificación fue read-only.

No se observó ninguna escritura desde el alta definitiva hacia `pre_*`.

## 4. Resultado ORO + FORM

**Resultado: conforme por inspección estática; CRUD/UI no ejecutado por falta de BD.**

- El menú muestra **Lista de invitados**, **Cargar contacto de envío**, **Enviar Invitaciones** y **Contactos de envío**.
- **Nuevo invitado** deja de mostrarse como acceso operativo principal.
- Tanto `?new=contactos_envio` como `?new=contactos_envio&accion=nuevo` y `accion=editar&id=<id>` pasan primero por el guard estricto del router y vuelven a validarlo dentro de los módulos.
- El alta fija el contexto de servidor `contacto_envio`; este contrato solo admite `pre_invitados`, `pre_invitados_tel` y `pre_invitados_listado_mesa`.
- En el contrato normalizado, el rótulo/apodo se guarda en `nombre_invitado`; nombre y apellido reales se replican en `nombre2` y `apellido2`; el teléfono se guarda en `telefono` y, si es numéricamente representable, también en `tel_enviar`; se completan las FK soportadas, incluido `id_invitados`.
- La edición actualiza `pre_invitados` y reconstruye únicamente las filas relacionadas de `pre_invitados_tel` y `pre_invitados_listado_mesa`, dentro de una transacción.
- Inactivar/reactivar ejecuta solamente `UPDATE pre_invitados SET activo = 0/1 WHERE id = ?`; no borra ni altera las tablas hijas.
- Pendientes exige `pre_invitados.activo = 1`. Además, los POST `agregar_a_enviar` y `mover_a_enviar_de_enviado` vuelven a consultar `activo=1`, por lo que un contacto inactivo no puede entrar ni reingresar a A Enviar.
- Edición e inactivación consultan `invitados_a_enviar` e `invitados_enviados` mediante `id_invitados = <ID del contacto>`. No usan el número telefónico: dos contactos diferentes que comparten teléfono no se bloquean entre sí.
- Las acciones de cola resuelven el teléfono desde el ID de teléfono y el ID del contacto dentro de la fuente efectiva; no confían en el teléfono enviado por el navegador.
- Los POST históricos de confirmación/toggle sobre `invitados` se rechazan cuando la fuente efectiva no es `invitados`.

No se observó ninguna escritura del módulo Contactos hacia `invitados`, `invitados_tel` o `invitados_listado_mesa`.

## 5. Resultado BÁSICO + FORM

**Resultado: conforme por inspección estática; navegación pública/admin no ejecutada por falta de BD.**

- La configuración efectiva de Básico fuerza FORM.
- Con `whatsapp_enabled=0`, la fuente efectiva se normaliza a `ninguno`.
- El menú no muestra **Enviar Invitaciones**, **Contactos de envío** ni **Cargar contacto de envío**.
- El acceso directo a `?new=envioinvitaciones` responde 403 antes de incluir `gestionar_envios.php`.
- Todos los accesos directos y POST de `?new=contactos_envio...` responden 403 antes de consultar o modificar staging.
- El endpoint público RSVP FORM no depende del guard de WhatsApp; con modo FORM y persistencia habilitada continúa su validación/persistencia normal si la conexión y el contrato definitivo están disponibles.
- Al no poder entrar en `gestionar_envios.php`, Básico no puede escribir las colas WhatsApp desde el flujo admin revisado.

Nota: el menú conserva el alta histórica **Nuevo invitado** cuando Contactos no está habilitado. Esto no contradice el alcance solicitado para Básico, que solo exige ocultar/bloquear Contactos y Enviar Invitaciones.

## 6. Rutas protegidas revisadas

| Ruta/acción | Guard y resultado esperado |
|---|---|
| `?new=contactos_envio` | `dqs_require_admin_contactos_envio`; 403 salvo combinación exacta Oro + FORM + WhatsApp + `pre_invitados` |
| `?new=contactos_envio&accion=nuevo` | Mismo guard en router, listado controlador y módulo de alta |
| `?new=contactos_envio&accion=editar&id=<id>` | Mismo guard; además valida ID, existencia y bloqueo en colas por ID |
| POST `accion=inactivar` / `accion=reactivar` | Mismo guard antes del controlador, método POST, CSRF, ID entero, existencia y estado de colas por ID |
| `?new=envioinvitaciones` | `dqs_require_admin_whatsapp_enabled`; solo Oro con WhatsApp activo; defensa adicional dentro de `gestionar_envios.php` |
| `?new=invitaciones` | 410 mediante `dqs_disable_legacy_whatsapp_endpoint` |
| `invitados_invitaciones.php` | 410 antes de sesión, conexión o consultas |
| `invitaciones/index.php` | 410 antes de conexión o consultas |
| `invitaciones/generador_masivo.php` | 410 antes de conexión o consultas |
| `whatsapp/envio_invitaciones.php` | 410 JSON; sin conexión, lecturas, escrituras ni API |
| `whatsapp/reenvio_invitaciones_erroneas.php` | 410 JSON; sin conexión, lecturas, escrituras ni API |

## 7. Tablas leídas/escritas esperadas por modo

| Modo/flujo | Lecturas principales | Escrituras permitidas por el flujo |
|---|---|---|
| ORO + CÓDIGO — alta | catálogos de acompañamiento/prioridad y columnas del contrato | `invitados`, `invitados_tel`, `invitados_listado_mesa` |
| ORO + CÓDIGO — Enviar Invitaciones | `invitados`, `invitados_tel`, `invitados_listado_mesa`, colas compartidas | `invitados_a_enviar`, `invitados_enviados`; acciones históricas de tarjeta pueden actualizar `invitados` |
| ORO + FORM — Contactos | `pre_invitados`, `pre_invitados_tel`, `pre_invitados_listado_mesa`, catálogos y colas para bloqueo | alta/edición en las tres `pre_*`; baja lógica solo en `pre_invitados.activo` |
| ORO + FORM — Enviar Invitaciones | las tres `pre_*` y colas compartidas | `invitados_a_enviar`, `invitados_enviados`; no actualiza `invitados*` |
| BÁSICO + FORM — admin WhatsApp/Contactos | ninguna tabla operativa: guard previo | ninguna |
| FORM público | configuración y contrato RSVP definitivo (verificación read-only en esta fase) | fuera del alcance de cambios; permanece condicionado a FORM + persist flag + contrato listo |

Las colas `invitados_a_enviar` e `invitados_enviados` son deliberadamente compartidas. Durante una request, sus IDs se interpretan únicamente contra el mapa de la fuente efectiva y el join también exige que el ID de teléfono pertenezca al contacto de esa familia.

## 8. Hallazgos

1. **Aislamiento correcto dentro de una configuración estable.** El mapa de fuente está cerrado en servidor y se usa tanto para resolver teléfonos como para listar las tres columnas.
2. **Defensa de inactivos en dos capas.** Un inactivo se excluye de Pendientes y también se rechaza en las dos acciones que podrían insertarlo/reinsertarlo en A Enviar.
3. **Bloqueo por identidad.** Edición y baja consultan colas por ID del contacto, no por teléfono; el teléfono compartido no genera bloqueo cruzado.
4. **Endpoints históricos efectivamente retirados.** Todos los entry points PHP localizados terminan en 410 antes de abrir conexión o ejecutar lógica heredada.
5. **Limitación de las colas compartidas.** Las tablas de cola carecen de discriminador de fuente. Si se cambia de `invitados` a `pre_invitados` (o viceversa) con colas pobladas y coinciden tanto IDs de contacto como de teléfono, una fila anterior puede interpretarse como perteneciente a la fuente nueva. El propio código advierte esta condición. Corregirla estructuralmente requeriría schema/migración, expresamente fuera de UNI-065.
6. **Limitación del entorno de prueba.** No hubo base de datos accesible, así que no se validaron por UI los valores persistidos ni respuestas HTTP autenticadas. Antes de producción conviene ejecutar el mismo checklist en un entorno integrado usando exclusivamente la UI y consultas SELECT de verificación.

## 9. Bugs encontrados

No se detectó un bug nuevo, claro y pequeño corregible dentro de las restricciones de esta fase. Por tanto, no se hicieron cambios funcionales.

El posible cruce al cambiar de fuente con colas pobladas se registra como limitación arquitectónica preexistente, no como corrección mínima: solucionarlo correctamente necesita distinguir la fuente en las colas (cambio de schema/migración) o definir un procedimiento de transición. Ninguna de esas operaciones está permitida en UNI-065.

## 10. Recomendación final

**OK para avanzar**, con estas condiciones:

1. Mantener estable la fuente efectiva durante la operación normal.
2. Antes de cambiar entre CÓDIGO/`invitados` y FORM/`pre_invitados`, comprobar mediante SELECT que `invitados_a_enviar` e `invitados_enviados` estén vacías, o resolver formalmente la procedencia de sus filas.
3. Completar en un ambiente con BD el smoke test manual pendiente: menú, 403/410, alta, edición, inactivar/reactivar y presencia/ausencia en Pendientes; comparar antes/después únicamente con SELECT.
4. Mantener fuera de esta validación Node, envíos reales, importadores, schema, migrations, installer, seeds y cambios RSVP, tal como exige el alcance.

No se requiere ninguna corrección funcional mínima en este PR documental.
