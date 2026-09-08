# UNI-058 — Auditoría de carga de contactos `pre_*` para Oro + FORM

> **Alcance:** Fase 4A, exclusivamente diagnóstico y propuesta. Este documento no
> implementa cambios, no presupone migraciones y no autoriza escrituras, cambios de
> esquema, seeds ni modificaciones del instalador.

## 1. Resumen ejecutivo

### Conclusión

La opción más segura para la Fase 4B es **C: crear un contexto administrativo
separado y explícito para “Contactos de envío”**, disponible solamente cuando la
configuración efectiva habilite WhatsApp y su fuente sea `pre_invitados`. La ruta
propuesta es `?new=contactos_envio` (nombre a confirmar al implementar), con alta,
edición y, en una entrega posterior o subfase independiente, importación masiva.

No se recomienda hacer source-aware a `?new=invitados`. Esa pantalla no es sólo un
listado: también confirma/desconfirma, activa, elimina lógicamente, genera piezas y
exporta información definitiva. Cambiar silenciosamente su familia de tablas
mezclaría los conceptos “contacto todavía no confirmado” e “invitado confirmado” y
ampliaría mucho el radio de regresión.

La separación propuesta preserva estos invariantes:

1. `?new=invitados` sigue representando **siempre** la lista definitiva y opera
   exclusivamente sobre `invitados*`, en todos los planes.
2. Oro + código sigue usando `invitados*` para alta, listado, edición y cualquier
   importación futura.
3. Oro + FORM usa `pre_*` exclusivamente como agenda/staging de contactos para
   WhatsApp, sin fallback a `invitados*`.
4. El RSVP FORM público confirma creando la entidad definitiva en `invitados`,
   `invitados_tel` e `invitados_listado_mesa`; cargar `pre_*` no equivale a RSVP.
5. Básico + FORM no ve gestión ni envíos WhatsApp y no necesita precargar `pre_*`
   para que el RSVP público funcione.
6. `?new=envioinvitaciones` no requiere cambios para esta iniciativa: ya consume la
   fuente efectiva y debe conservarse como gestión de colas/drag & drop, no como ABM.

### Hallazgo crítico previo a 4B: hay dos contratos históricos de `pre_*`

No debe copiarse literalmente el ABM de la referencia Oro + FORM. La referencia
histórica usa en las tablas hijas las columnas `id_invitados`, `tel_enviar`,
`nombre_invitado`, `nombre2` y `apellido2`. En cambio, el esquema documentado actual
y el flujo moderno usan `id_pre_invitado`, `telefono`, `nombre`, `apellido`, etc.
Incluso `guest_source.php` conserva un mapa del contrato histórico para las hijas,
mientras `gestionar_envios.php` usa el contrato moderno. Antes de escribir una sola
fila en 4B se debe fijar y probar **un contrato de columnas único para el ambiente
objetivo**, sin fallback cruzado y sin alterar el schema.

## 2. Estado actual de alta, edición e importación

### 2.1 Alta manual actual

| Pregunta | Respuesta auditada |
|---|---|
| Ruta | `/admin7WZiwEM3XY/index.php?new=invitados&nuevo=0` |
| Enrutamiento | `index.php` incluye `invitados.php`; éste incluye `nuevo_invitado.php` cuando `nuevo=0`. |
| Procesador | El mismo `admin7WZiwEM3XY/nuevo_invitado.php` procesa GET y POST. |
| Escrituras | `invitados`, luego titular/acompañantes en `invitados_listado_mesa` y teléfonos en `invitados_tel`. |
| Código | Genera un código de seis dígitos y comprueba unicidad solamente en `invitados`. |
| Efectos adicionales | Tras el alta redirige con `open_card=1`, y `invitados.php` dispara asíncronamente `invitaciones/index.php` para el nuevo ID. |
| Atomicidad | Las tres familias de INSERT no están dentro de una transacción; fallos de hijos pueden dejar un alta parcial. |

**¿Es seguro volverla source-aware?** Técnicamente podría construirse una capa de
escritura parametrizada, pero **no es seguro hacerlo dentro de la ruta actual**. El
formulario supone el modelo definitivo (prioridad, ingreso, cantidades, código,
menores), genera una invitación después de guardar y redirige al listado definitivo.
Además, las columnas de `pre_*` actuales no son isomorfas a `invitados*`.

**Qué se rompería o quedaría ambiguo si en Oro + FORM esa ruta escribiera `pre_*`:**

- el alta no aparecería en el listado que hoy consulta `invitados*`, o forzaría a
  convertir también todo el listado a source-aware;
- `open_card` enviaría un ID de `pre_invitados` a un generador productivo que hoy
  consulta `invitados*`, con riesgo de colisión entre espacios de IDs;
- los botones de confirmar/desconfirmar, activar y borrar seguirían escribiendo
  `invitados`, potencialmente sobre otra persona con el mismo ID;
- edición y exportación buscarían el registro en `invitados*`;
- las métricas RSVP y la semántica “Nuevo invitado” pasarían a incluir no confirmados;
- aparecería una tentación de fallback automático a `invitados`, expresamente
  prohibida por el criterio funcional.

### 2.2 Edición actual

| Pregunta | Respuesta auditada |
|---|---|
| Ruta | `/admin7WZiwEM3XY/index.php?new=invitados&id=<id>` |
| Enrutamiento/procesador | `invitados.php` incluye `editar_invitado.php`; el mismo archivo carga y procesa POST. |
| Lecturas | Principal desde `invitados`, integrantes desde `invitados_listado_mesa`, teléfonos desde `invitados_tel`; catálogos auxiliares de acompañante y prioridad. |
| Escrituras | UPDATE de `invitados`; DELETE total y reinserción de integrantes; DELETE total y reinserción de teléfonos. |
| Efecto posterior | Redirige con `activadisimo=1`; el listado invoca el generador de invitación para ese ID. |
| Atomicidad | No hay transacción que agrupe UPDATE, DELETE e INSERT. |

Editar `pre_*` con esa misma pantalla tiene riesgos superiores al alta: el ID por sí
solo no identifica la familia; un parámetro manipulable podría hacer edición cruzada;
la estrategia de borrar/reinsertar hijos podría perder datos si una inserción falla;
el modelo moderno `pre_*` no ofrece las mismas columnas; y el efecto posterior de
generación usa la familia definitiva. Un modo explícito oculto en query string no es
suficiente: el servidor debe derivar y autorizar el contexto desde el plan efectivo.

### 2.3 Importación / carga masiva

La búsqueda por nombres, extensiones y referencias SQL no encontró un importador
productivo de invitados Excel/CSV en el admin. Existe `exportar_invitados.php`, que
es de salida, no de entrada. Los archivos denominados `generador_masivo.php` generan
piezas de invitación en lote; tampoco importan contactos. Fuera del admin existe un
lector CSV del proceso de **reenvío**, pero no constituye un importador de invitados
ni debe reutilizarse implícitamente como tal.

Por lo tanto, en el estado auditado:

- no existe importador que escriba `invitados*` o `pre_*`;
- no hay soporte productivo de importación de teléfonos;
- no hay soporte productivo de importación de integrantes/listado de mesa;
- no hay compatibilidad previa que preservar en entrada masiva, pero crear un
  importador completo (principal + teléfonos + integrantes) es un alcance nuevo;
- adaptar sólo “importadores existentes” (opción D) no resuelve el alta manual porque
  no existe tal importador en este árbol y obligaría a operar siempre por archivo.

Una importación futura sí puede apuntar a `pre_*` sin tocar `invitados*`, siempre que
sea una acción separada, transaccional, validada por el guard Oro + FORM + WhatsApp,
use el contrato moderno verificado y nunca elija destino por fallback. Conviene
separarla del primer corte de 4B para reducir riesgo.

## 3. Mapa de archivos y rutas

| Ruta / archivo | Responsabilidad actual | Decisión para 4B |
|---|---|---|
| `admin7WZiwEM3XY/index.php?new=invitados` | Enruta al listado definitivo. | Conservar sin source-awareness. |
| `admin7WZiwEM3XY/invitados.php` | Lista, filtra, confirma/desconfirma, activa y baja lógica `invitados`; incluye alta/edición. | No modificar para cambiar su familia. |
| `admin7WZiwEM3XY/nuevo_invitado.php` | Alta directa de principal, mesa y teléfonos definitivos. | No parametrizar ni reutilizar por include para `pre_*`. |
| `admin7WZiwEM3XY/editar_invitado.php` | Lee y reemplaza datos definitivos. | No apuntar a `pre_*`. |
| `admin7WZiwEM3XY/exportar_invitados.php` | Exporta datos definitivos. | Conservar como exportación definitiva; una exportación de contactos, si se pide, debe ser explícita. |
| `admin7WZiwEM3XY/invitaciones/index.php` y `generador_masivo.php` | Generan invitaciones desde `invitados*`. | No invocarlos desde el ABM de staging en el primer corte. |
| `admin7WZiwEM3XY/index.php?new=envioinvitaciones` | Enruta a `gestionar_envios.php`, con guard WhatsApp previo. | No agregar ABM ni modificar en 4B salvo evidencia de integración imprescindible. |
| `admin7WZiwEM3XY/gestionar_envios.php` | Listado y colas según fuente efectiva; para `pre_*` usa FK/columnas modernas. | Mantener estable; usarlo como prueba de consumo, no como formulario de carga. |
| `admin7WZiwEM3XY/menu.php` | Siempre ofrece lista/nuevo definitivo; muestra envío sólo con capacidad WhatsApp. | En 4B añadir “Contactos de envío” sólo bajo guard exacto, sin renombrar lista definitiva. |
| `includes/plan_config.php` | Resuelve plan/mode/fuente efectiva y fuerza fuente `ninguno` al deshabilitar WhatsApp. | Reutilizar lectura efectiva; no agregar defaults ni persistencia. |
| `includes/guest_source.php` | Helper de lectura para ambas familias. | No usar como escritor; corregir/encapsular su contrato histórico sólo en una tarea explícita si afecta al nuevo lector. |
| `includes/admin_feature_guard.php` | Capacidad y guard de WhatsApp. | Extender o componer un guard específico: Oro + FORM + fuente `pre_invitados`. |
| `includes/rsvp_form_final_persistence.php` | Persistencia transaccional definitiva del RSVP en `invitados*`. | No desviar a `pre_*`; conservar como frontera funcional. |
| `includes/rsvp_form_persistence.php` | Helper experimental antiguo para `pre_*`, documentado como no final. | No reutilizar para cargar agenda: su semántica es payload RSVP y su API de guardado no es la definitiva. |
| `database/install/schema.sql` y archivos de instalación/migración | Contrato de instalación. | Fuera de alcance: no tocar en 4A ni en el plan acotado de 4B. |
| Nueva ruta sugerida `?new=contactos_envio` | No existe. | Pantalla separada, con controlador/servicio separado y destino fijo `pre_*`. |

## 4. Mapa de tablas leídas y escritas

### 4.1 Flujos productivos actuales relevantes

| Tabla | Lista `?new=invitados` | Alta actual | Edición actual | RSVP FORM final | Envíos Oro + FORM |
|---|---:|---:|---:|---:|---:|
| `invitados` | Lee / actualiza estado RSVP y activo | Inserta | Lee / actualiza | Inserta | No debe mezclar; se usa en Oro + código |
| `invitados_tel` | Lee | Inserta | Lee / borra / reinserta | Inserta si hay teléfono | Fuente Oro + código |
| `invitados_listado_mesa` | Lee | Inserta titular e integrantes | Lee / borra / reinserta | Inserta titular e integrantes | Fuente Oro + código |
| `pre_invitados` | No | No | No | No | Lee en Oro + FORM |
| `pre_invitados_tel` | No | No | No | No | Lee en Oro + FORM |
| `pre_invitados_listado_mesa` | No | No | No | No | Lee en Oro + FORM |

### 4.2 Escrituras propuestas para el contexto nuevo

El nuevo ABM fijará su destino, sin selector libre de tabla:

- principal: `pre_invitados`;
- uno o más teléfonos: `pre_invitados_tel` por `id_pre_invitado` y `telefono`;
- titular/integrantes necesarios para personalización: `pre_invitados_listado_mesa`
  por `id_pre_invitado`, usando las columnas realmente disponibles;
- **cero escrituras** a las tres tablas `invitados*`;
- **cero escrituras** a colas durante el alta/edición: el usuario las gestiona luego
  en `envioinvitaciones` mediante el drag & drop ya aprobado.

Las tres escrituras deben ser una unidad transaccional. La edición debe comprobar
que el principal pertenece a `pre_invitados`, y toda consulta hija debe incluir la
FK al principal; nunca debe aceptar una tabla o `source` enviada por el navegador.

## 5. Comparación con las referencias funcionales

### 5.1 `docs/referencia_planes/oro_form`

La referencia valida la **separación conceptual** buscada:

- su alta histórica escribe `pre_invitados`, `pre_invitados_listado_mesa` y
  `pre_invitados_tel`;
- su pantalla general de invitados, edición, exportación y dashboard permanecen en
  `invitados*`;
- su RSVP público inserta la confirmación en `invitados` y
  `invitados_listado_mesa`;
- su gestión/generación para envíos consume `pre_*`.

Pero no es código portable: implementa la opción riesgosa de reutilizar “Nuevo
invitado” con destino cambiado, carece de una frontera de contexto clara, usa SQL
construido directamente, no agrupa todas las escrituras en una transacción y su
schema `pre_*` histórico es similar a `invitados*`, no al contrato moderno instalado.
Debe tomarse como evidencia funcional, no como plantilla.

### 5.2 `docs/referencia_planes/dqs envios invitaciones_form`

El proceso Node demuestra que el emisor externo obtiene principal, nombres y teléfono
desde `pre_*`, pero mantiene las tablas de cola `invitados_a_enviar` e
`invitados_enviados`. También espera el contrato histórico (`id_invitados`,
`tel_enviar`). El PHP productivo auditado ya fue adaptado al contrato moderno
(`id_pre_invitado`, `telefono`). Por ello, 4B no debe inferir columnas del ejemplo
Node ni modificar el envío: debe validar contra el contrato efectivo que el emisor
real ya consume en el ambiente aprobado.

## 6. Riesgos de hacer source-aware `?new=invitados`

| Riesgo | Severidad | Motivo |
|---|---|---|
| Confusión semántica | Alta | Un contacto cargado aparecería como “invitado” antes de confirmar, o desaparecería de la lista tras el alta. |
| Escritura cruzada por ID | Crítica | Los IDs de ambas tablas pueden coincidir; perder el contexto en POST puede modificar otra entidad. |
| Acciones incompatibles | Crítica | Confirmar/desconfirmar manualmente, generación, activo/baja y exportación están cableados a `invitados*`. |
| Contratos no isomorfos | Crítica | El schema moderno `pre_*` no tiene todas las columnas de la pantalla definitiva y renombra FKs/campos hijos. |
| Regresión Oro + código | Alta | Un helper compartido o una condición incorrecta cambia el flujo histórico aprobado. |
| Fallback silencioso | Crítica | La fuente efectiva inválida/ausente podría terminar en `invitados`, mezclando familias. |
| Métricas falsas | Alta | Staging contaminaría confirmaciones, dashboard y exportación si se unifica el listado. |
| Alcance creciente | Alta | Habría que adaptar listado, acciones, edición, alta, exportación y generadores como un solo cambio grande. |

**Decisión:** `?new=invitados` debe seguir mostrando siempre `invitados*`. Para el
admin esto deja dos conceptos visibles y estables: “Lista/Nuevo invitado” (definitivo)
y “Contactos de envío” (staging, sólo cuando corresponde). Si en cambio mostrara
`pre_*` en Oro + FORM, el administrador perdería acceso claro a confirmaciones reales
y los indicadores/acciones existentes dejarían de describir lo que se ve.

## 7. Riesgos de una pantalla separada y mitigaciones

Una pantalla separada es la opción más segura, pero no es gratuita:

| Riesgo | Mitigación exigida |
|---|---|
| Duplicación de UI/validación | Extraer sólo utilidades neutrales validadas; no incluir ni parametrizar los procesadores actuales. Mantener un formulario pequeño orientado al contrato staging. |
| Confusión entre dos listas | Etiquetas inequívocas: “Contactos de envío (no confirmados)” y aviso “Cargar aquí no confirma asistencia”. |
| Acceso por URL directa | Guard server-side antes de toda consulta: plan Oro, RSVP FORM, WhatsApp habilitado y fuente efectiva exactamente `pre_invitados`. Ocultar menú no basta. |
| Schema histórico vs moderno | Preflight read-only o mapa cerrado del perfil desplegado; ante incompatibilidad, fail closed con diagnóstico. Nunca probar una familia y caer en la otra. |
| Datos parciales | Transacción, rollback ante cualquier hijo inválido/fallido y validación previa de al menos un teléfono utilizable. |
| Duplicados | Política explícita y conservadora (advertir por teléfono normalizado); no fusionar ni convertir automáticamente. |
| Crecimiento de alcance | Primer corte: lista + alta + edición/baja lógica de staging. Importación en paso posterior, sin tocar colas ni envío. |

Frente a un subcontexto dentro de `?new=invitados` (opción B), la pantalla separada
reduce el riesgo de que se pierda el contexto en redirects/POST y evita que acciones
definitivas se rendericen sobre staging. Si se desea coherencia visual, puede compartir
componentes de presentación, pero no la selección implícita de tablas.

Mantener sólo importación tampoco es recomendable como solución completa: obliga a
crear archivos aun para un contacto, dificulta corregir errores y no satisface el
caso de alta manual planteado. La importación es complemento, no frontera funcional.

## 8. Recomendación concreta para Fase 4B

Implementar un **módulo mínimo de Contactos de envío**, aislado del ABM definitivo:

1. ruta explícita `?new=contactos_envio`;
2. guard exacto, fail-closed, basado en configuración efectiva:
   `plan_servicio=oro`, `rsvp_modo=form`, WhatsApp habilitado y
   `fuente_envios_whatsapp=pre_invitados`;
3. repositorio/servicio de escritura cuyo destino sea constante `pre_invitados` y
   sus dos hijas; no aceptar `source` ni nombres de tabla por GET/POST;
4. contrato de columnas moderno verificado (`id_pre_invitado`, `telefono`, etc.),
   documentando cualquier adaptación requerida por el ambiente real antes de merge;
5. transacciones para alta/edición; prepared statements; validación de cantidades,
   teléfono e integrantes; CSRF según el patrón de seguridad que se defina;
6. listado y acciones propios, sin confirmar RSVP, generar piezas, insertar en colas,
   consumir, mover ni borrar automáticamente contactos al confirmarse;
7. enlace de menú sólo bajo la misma capacidad del guard;
8. ningún cambio en `envioinvitaciones`, endpoints PHP internos de envío, RSVP final,
   schema, seeds o installer;
9. importación CSV/Excel como subfase separada después de estabilizar alta/edición.

**Fail closed:** si la fuente efectiva no es exactamente `pre_invitados`, o faltan
tablas/columnas, el módulo debe negar la operación con un diagnóstico. No debe usar
`invitados` “para que funcione”, ni seleccionar el contrato histórico como segundo
intento de escritura.

## 9. Plan acotado por pasos para Fase 4B

### Corte 4B.1 — contrato y guard (sin alterar schema)

1. Confirmar mediante introspección read-only en el ambiente objetivo las columnas
   de las tres tablas `pre_*` y el contrato esperado por el Node realmente desplegado.
2. Resolver la inconsistencia de mapas sólo en la capa nueva o en un helper común con
   pruebas; no tocar migraciones/installer.
3. Crear pruebas unitarias de la matriz de capacidad: únicamente Oro + FORM +
   WhatsApp + fuente `pre_invitados` obtiene acceso.
4. Incorporar ruta y guard antes de consultas operativas, siguiendo el patrón de
   `envioinvitaciones`.

### Corte 4B.2 — alta/lista mínima de staging

5. Crear repositorio dedicado con consultas preparadas y destino fijo.
6. Implementar transacción de alta de principal, teléfono(s) e integrantes.
7. Implementar listado propio con identidad visual explícita de “no confirmado”.
8. Agregar el enlace condicional al menú.
9. Verificar que alta no modifica tablas definitivas, colas ni archivos generados.

### Corte 4B.3 — edición segura

10. Agregar edición propia con autorización de pertenencia a `pre_invitados`.
11. Reemplazar hijos dentro de la misma transacción o aplicar diff seguro.
12. Si se necesita baja, preferir `activo`/baja lógica; no consumir ni borrar por
    RSVP ni implementar conversiones automáticas.

### Corte 4B.4 opcional — importación

13. Definir una plantilla versionada con columnas inequívocas para principal,
    teléfono e integrantes.
14. Implementar preview/validación sin escritura y reporte por fila.
15. Ejecutar cada grupo familiar de forma transaccional; destino fijo `pre_*`.
16. Probar duplicados, rollback, archivos parciales y ausencia total de escrituras a
    `invitados*`.

Cada corte debería ser revisable y reversible por separado. No agrupar en el mismo
PR refactors del ABM definitivo, cambios de envío o cambios de persistencia RSVP.

## 10. Pruebas manuales recomendadas

### Matriz de acceso

1. **Oro + código:** no aparece “Contactos de envío”; URL directa es rechazada;
   `?new=invitados` permite alta/edición definitiva sin cambios; envío lista
   `invitados*`.
2. **Oro + FORM + `pre_invitados`:** aparece el módulo; alta/edición se refleja en
   `envioinvitaciones`; la lista definitiva no cambia.
3. **Oro + FORM con fuente inválida/`ninguno`:** módulo bloqueado, sin fallback.
4. **Básico + FORM:** no aparece envío ni contactos de envío; URL directa bloqueada;
   RSVP público continúa disponible.
5. **WhatsApp deshabilitado:** menú y ruta del módulo bloqueados aunque haya datos
   `pre_*`.

### Alta de contactos

6. Alta de titular con un teléfono: comprobar principal, FK hija y valor normalizado.
7. Alta con múltiples teléfonos e integrantes: comprobar asociación exclusiva al
   ID de staging correcto.
8. Error deliberado en un hijo: comprobar rollback total, sin principal huérfano.
9. Teléfono duplicado/inválido: comprobar aviso o rechazo definido, sin fusión
   automática.
10. Manipular POST agregando `source=invitados` o nombre de tabla: debe ignorarse o
    rechazarse y no escribir definitivos.

### Edición y aislamiento de IDs

11. Crear `invitados.id=N` y `pre_invitados.id=N`; editar staging y comprobar que el
    definitivo permanece byte a byte/lógicamente igual.
12. Editar nombres, menores/integrantes y teléfonos; provocar un fallo intermedio y
    verificar rollback.
13. Intentar editar un ID inexistente o inactivo; respuesta controlada, sin afectar
    filas hijas ajenas.
14. Verificar que editar staging no dispara `invitaciones/index.php` ni modifica
    colas.

### Separación RSVP

15. Cargar un contacto en `pre_*`: confirmar que no aparece confirmado ni crea filas
    en `invitados*`.
16. Completar el RSVP FORM público con datos equivalentes: confirmar nuevas filas en
    `invitados`, `invitados_tel` e `invitados_listado_mesa` según payload.
17. Confirmar que después del RSVP las filas `pre_*` siguen presentes e inalteradas;
    no hay consumo, borrado, conversión ni enlace automático en esta fase.
18. Repetir RSVP para validar la deduplicación propia del flujo final, sin usar
    `pre_*` como fallback.

### Regresión de flujos aprobados

19. Oro + código: alta, listado, edición, exportación, generación y drag & drop sobre
    `invitados*`.
20. Oro + FORM: drag & drop exclusivamente sobre `pre_*`; ningún `invitados*`
    aparece aunque comparta ID/teléfono.
21. Confirmar que los endpoints PHP internos de envío siguen guardados/bloqueados y
    que el Node externo continúa siendo el único emisor real para Oro + FORM.

## 11. Respuestas cerradas a las decisiones funcionales

- **¿Cargar `pre_*` confirma?** No. Es alta de contacto/staging, no un evento RSVP.
- **¿Dónde persiste el RSVP FORM final?** Exclusivamente en `invitados*`, mediante el
  flujo final existente.
- **¿Se consume o borra staging al confirmar?** No en 4B; tampoco se enlaza ni
  convierte automáticamente.
- **¿Qué muestra `?new=invitados`?** Siempre `invitados*`.
- **¿Dónde se cargan contactos Oro + FORM?** En un módulo administrativo separado y
  explícito, no en `envioinvitaciones`.
- **¿Qué pasa con Oro + código?** Conserva alta/listado/edición/exportación en
  `invitados*`; no se tocan sus archivos de ABM/generación para esta iniciativa.
- **¿Qué pasa con Básico + FORM?** Puede y debe conservar RSVP FORM público, pero no
  necesita una agenda `pre_*` porque no tiene WhatsApp. El módulo queda inaccesible.
- **¿Importación solamente?** No como solución primaria. Primero alta/edición segura;
  importación como subfase independiente.
- **¿Fallback automático?** Prohibido. Toda incompatibilidad debe fallar de manera
  cerrada y visible.

## 12. Archivos que no deben tocarse para preservar compatibilidad

En el alcance recomendado de 4B no es necesario modificar:

- `admin7WZiwEM3XY/invitados.php`;
- `admin7WZiwEM3XY/nuevo_invitado.php`;
- `admin7WZiwEM3XY/editar_invitado.php`;
- `admin7WZiwEM3XY/exportar_invitados.php`;
- `admin7WZiwEM3XY/invitaciones/index.php`;
- `admin7WZiwEM3XY/invitaciones/generador_masivo.php`;
- `admin7WZiwEM3XY/gestionar_envios.php` y la pantalla `envioinvitaciones`;
- `includes/rsvp_form_final_persistence.php` y el endpoint RSVP público;
- schema, seeds, migraciones e installer;
- endpoints PHP internos de envío ya dados de baja/guardados.

Los cambios mínimos esperables se limitan a la nueva ruta/controlador/vista/servicio,
el guard correspondiente y una entrada condicional en `menu.php`. Cualquier necesidad
de tocar uno de los archivos anteriores debe tratarse como hallazgo nuevo, justificarse
con una prueba reproducible y salir a un PR separado.
