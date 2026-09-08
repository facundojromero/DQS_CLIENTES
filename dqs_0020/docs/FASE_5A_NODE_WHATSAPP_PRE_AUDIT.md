# Fase 5A — Auditoría integral del Node WhatsApp para `pre_*`

**Fecha de auditoría:** 2026-07-30

**Alcance:** revisión estática, de punta a punta, de `dqs envios invitaciones a pre/` contra el código y el esquema versionado actuales.
**Naturaleza:** solo auditoría. No se conectó a WhatsApp ni a la base de datos, no se instalaron dependencias y no se ejecutaron envíos, migraciones ni procesos Node/PHP.

## 1. Resumen ejecutivo

**Veredicto:** el módulo **no puede considerarse correcto para ORO + FORM sin cambios**. Su arquitectura básica (Express, una instancia `whatsapp-web.js`, `LocalAuth`, lectura secuencial de cola, plantillas editables y transacción de escrituras) puede conservarse, pero hoy mezcla tres contratos diferentes:

1. lee cabecera y columnas auxiliares del perfil `pre_*` legado normalizado;
2. conserva textos y URL propios de RSVP por código;
3. escribe en colas históricas sin discriminador de fuente y en un registro que mantiene FK hacia `invitados*`.

Los bloqueantes principales son:

- **Colas sin origen:** `invitados_a_enviar` e `invitados_enviados` identifican una fila solo por `(id_invitados, id_invitados_tel)`. Si esos dos IDs coinciden entre `invitados*` y `pre_invitados*`, Node puede consumir una fila del flujo equivocado.
- **Registro incompatible:** `registro_mensajes_enviados.id_invitados` e `id_invitados_tel` tienen FK a `invitados` e `invitados_tel`; registrar IDs `pre_*` falla salvo coincidencia accidental con IDs definitivos, caso todavía peor porque atribuye el envío al registro incorrecto.
- **RSVP incorrecto:** las plantillas predeterminadas y la sustitución especial generan `?busqueda=<codigo>#rsvp` y muestran “Código de Invitación”. El FORM actual es un formulario público genérico, no una búsqueda por código.
- **Contratos divergentes:** el Node usa exclusivamente los alias legados añadidos por UNI-062 (`id_invitados`, `tel_enviar`, `nombre_invitado`, etc.), mientras PHP Contactos prioriza el perfil moderno (`id_pre_invitado`, `telefono`, `nombre`) cuando coexisten ambos. El código PHP de colas también está fijado al moderno.
- **Imagen no garantizada:** Node exige, por defecto, `${base_url}${admin_folder}/invitaciones/NNNN.jpg`; los endpoints activos de generación heredada están deshabilitados y no se demostró un generador activo equivalente para los nuevos Contactos FORM.

La consulta principal puede llegar a ejecutarse **solo si** la migración aditiva UNI-062 fue aplicada y los alias legados están completos. Eso no elimina los problemas de procedencia, RSVP, registro ni sincronización futura entre columnas modernas y legadas.

## 2. Arquitectura actual

### 2.1 Entry point real

- `package.json` declara `main: server.js` y `npm start` ejecuta `node server.js`.
- `ENVIOS WHATSAPP.bat` cambia al directorio del módulo, instala paquetes solo si falta `node_modules` y ejecuta `npm start`; por tanto el entry point operativo es **`server.js`**.
- `server.js` crea la aplicación Express efectiva, habilita CORS, sirve estáticamente todo `__dirname`, acepta JSON y monta los routers de `whatsapp.js` y `sessions.js` en `/`. Escucha siempre en el puerto `3000` y abre `/start-session/1`.
- `app.js` crea otro Express, escucha `process.env.PORT || 3000` y abre la misma URL, pero no monta rutas ni archivos estáticos. No está referenciado por `package.json` ni por el BAT: es un entry point residual/incompleto y ejecutarlo por separado produce una UI sin endpoints.
- `whatsapp.js` crea un `app` local que no se exporta ni escucha; el objeto útil es su `router`.

### 2.2 Componentes

| Componente | Responsabilidad actual |
|---|---|
| `server.js` | Composición HTTP, estáticos, routers, puerto y apertura del navegador. |
| `whatsapp.js` | Sesiones reales de WhatsApp, plantillas, consulta SQL, envío, progreso y escrituras post-envío. |
| `sessions.js` | Expone `/sessions`, pero referencia una variable `sessions` inexistente en ese módulo. |
| `index.html` | Editor de plantillas, selector de imagen, inicio del envío y presentación del progreso. |
| `web-logic.js` | Defaults, inserción de variables/formato, llamadas HTTP y polling cada 2 segundos. |
| `config.js` | Credenciales DB y URL/carpeta admin específicas de una instalación. |
| `LocalAuth` | Persistencia local de autenticación bajo el `clientId` `session_<usuarioId>`. |

No existe autenticación HTTP, autorización ni asociación entre el usuario web y `usuarioId`. El navegador y cualquier origen admitido por CORS pueden invocar los endpoints locales mientras el proceso esté accesible.

## 3. Flujo completo de ejecución

1. El operador abre el BAT; si falta `node_modules`, este intentaría `npm install`; luego corre `npm start`.
2. `server.js` escucha en `3000` y abre `http://localhost:3000/start-session/1`.
3. `/start-session/1` crea un `Client` con `LocalAuth({clientId: 'session_1'})`, Puppeteer visible y flags `--no-sandbox`/`--disable-setuid-sandbox`; registra eventos y llama `initialize()`.
4. El QR se imprime solo en terminal. La página redirigida muestra estado textual; no contiene QR ni polling automático del estado de conexión.
5. Al entrar a `/`, Express sirve `index.html`. `web-logic.js` obtiene `base_url`, carga `template_plural.txt` y `template_singular.txt` si existen, o usa defaults orientados a código.
6. El botón envía ambos textos y `incluir_imagen` a `/start-send/1`.
7. El backend exige que haya cliente y que su estado interno sea `CONNECTED`, abre una conexión MySQL y ejecuta una única consulta de filas encoladas.
8. Responde inmediatamente “Proceso de envío iniciado” y continúa en una IIFE asíncrona en memoria. El front consulta `/progress/1` cada dos segundos.
9. Para cada fila, Node toma `tel_enviar`, forma el destino, selecciona plural/singular según las cantidades, sustituye variables y opcionalmente descarga/envía la imagen.
10. Después de que WhatsApp confirma `sendMessage`, inicia una transacción DB: inserta en `invitados_enviados`, inserta en `registro_mensajes_enviados`, elimina de `invitados_a_enviar` y confirma.
11. Al terminar el bucle marca `completed`, cierra la conexión y el front guarda las plantillas en archivos locales.

El proceso no tiene worker durable, lock de cola, estado “procesando”, idempotency key ni recuperación de trabajos en curso.

## 4. Mapa de archivos

| Archivo | Estado/observación |
|---|---|
| `app.js` | Residual. No monta routers ni estáticos; no es el entry point de `npm start`. |
| `config.js` | Configuración productiva hardcodeada. Contiene secreto DB; este informe lo omite deliberadamente. |
| `ENVIOS WHATSAPP.bat` | Lanzador Windows; instala automáticamente si falta `node_modules` y mantiene la consola abierta. |
| `index.html` | UI útil, pero variables y copy corresponden parcialmente al modelo por código. PHP embebido en el footer se muestra literalmente porque Express sirve HTML estático. |
| `package.json` | Entry point y dependencias. No hay `package-lock.json` en el módulo, ni scripts de test/lint. |
| `README.md` | Nota operativa breve sobre sesiones; recomienda actualizar paquetes, fuera del alcance de esta fase. |
| `server.js` | Entry point real. Puerto y usuario inicial fijos. |
| `sessions.js` | Router roto: `sessions` no está definido ni compartido/exportado desde `whatsapp.js`; `/sessions` responde 500/ReferenceError. |
| `web-logic.js` | Cliente HTTP local, editor, polling y persistencia de plantillas tras completar. |
| `whatsapp.js` | Núcleo funcional y único archivo del módulo con SQL. |

## 5. Mapa completo de tablas y columnas

### 5.1 Consultas del Node

No se encontraron otras consultas SQL fuera de `whatsapp.js`.

| Tipo | Tabla | Columnas Node | Relación/condición |
|---|---|---|---|
| `SELECT` | `pre_invitados a` | `id`, `codigo`, `apellido`, `nombre`, `cantidad_mayores`, `cantidad_menores`, `ingreso`, `activo` | Raíz; `activo = 1`; orden apellido/nombre. |
| `SELECT` | `pre_invitados_listado_mesa a` | `id`, `id_invitados`, `nombre_invitado` | Agrupa por `id_invitados`; une a `pre_invitados b` por `a.id_invitados=b.id`. |
| `SELECT` | `pre_invitados b` (subquery) | `id`, `cantidad_mayores`, `cantidad_menores` | Sus cantidades se usan sin calificar dentro del `CASE`. |
| `SELECT` | `pre_invitados_tel` | `id`, `id_invitados`, `tel_enviar` | Alias `id` → `id_invitados_tel`; calcula `id_unico`, pero nunca lo usa. |
| `JOIN` | `invitados_a_enviar h` | `id_invitados`, `id_invitados_tel` | Coincidencia doble contra los IDs `pre_*`; **no compara `tel_enviar` ni fuente**. |
| `JOIN` | `cliente mi` | `telefono`, `telefono2` | `INNER JOIN` sin `ON`: producto cartesiano; las columnas se seleccionan pero nunca se usan. Con cero clientes elimina todas las filas; con más de uno duplica envíos. |
| `INSERT` | `invitados_enviados` | `id_invitados`, `id_invitados_tel`, `tel_enviar`, `fecha_envio` | Tras envío WhatsApp, dentro de transacción. |
| `INSERT` | `registro_mensajes_enviados` | las mismas cuatro | Tras el insert anterior, dentro de transacción. |
| `DELETE` | `invitados_a_enviar` | filtro por `id_invitados`, `id_invitados_tel` | Dentro de la misma transacción. |

No hay `UPDATE` SQL ni `DELETE` de otras tablas en el Node. `beginTransaction`, ambos `INSERT`, `DELETE` y `COMMIT` forman una transacción; ante error se hace `ROLLBACK`.

### 5.2 Esquema canónico versionado relevante

La referencia principal usada fue el snapshot versionado `docs/DQS_SCHEMA_SNAPSHOT_HOSTINGER_20260719_012551.sql`, complementado por la migración posterior y aditiva `database/migrations/20260729_uni062_normalizar_pre_invitados.sql`.

| Tabla | Contrato actual versionado relevante |
|---|---|
| `pre_invitados` | Snapshot moderno: `id`, `nombre`, `apellido`, `confirmacion`, `restriccion_alimentaria`, `comentario`, `cantidad_acompanantes`, `total_personas`, `fecha_registro`, `origen`, `activo`. UNI-062 añade `acompanado`, `cantidad_mayores`, `cantidad_menores`, `id_prioridad`, `ingreso`, `codigo`. |
| `pre_invitados_tel` | Moderno: `id`, `id_pre_invitado`, `telefono`, `fecha_registro`. UNI-062 añade/copía `id_invitados`, `tel_enviar` e índice sobre `id_invitados`. No se declara FK en el snapshot/migración. |
| `pre_invitados_listado_mesa` | Moderno: `id`, `id_pre_invitado`, `nombre`, `apellido`, `restriccion_alimentaria`, `comentario`, `orden`, `fecha_registro`. UNI-062 añade/copía `id_invitados`, `nombre_invitado`, `nombre2`, `apellido2`, `es_menor`, `asiste`, `confirm_date`, `alimento`, `alimento_comentario`, `mesa` e índice. No se declara FK. |
| `invitados_a_enviar` | `id`, `id_invitados`, `id_invitados_tel`, `tel_enviar`, `fecha_agregado`; unique `(id_invitados,id_invitados_tel)`. Sin columna de fuente. |
| `invitados_enviados` | `id`, `id_invitados`, `id_invitados_tel`, `tel_enviar`, `fecha_envio`; unique `(id_invitados,id_invitados_tel)`. Sin columna de fuente. |
| `registro_mensajes_enviados` | Mismas referencias y fecha; FK explícitas a `invitados(id)` e `invitados_tel(id)`, no a `pre_*`. |
| `cliente` | `user_id`, identidad/contacto/dirección/plan y campos bancarios. Node solo lee `telefono`/`telefono2`; no filtra `user_id`. |

## 6. Node frente al contrato `pre_*` actual

### 6.1 Compatibilidad parcial por UNI-062

La migración UNI-062 fue diseñada como extensión aditiva: conserva el perfil moderno y añade aliases que permiten a consumidores legados leer `id_invitados`, `tel_enviar` y `nombre_invitado`. Por ello la SQL del Node ya no falla por columnas ausentes **si la migración está aplicada**.

Sin embargo, la sincronización hecha por la migración es puntual:

- copia `id_pre_invitado` a `id_invitados` solo donde este último es `NULL`;
- convierte `telefono` a `tel_enviar` solo si es exclusivamente numérico y cabe en BIGINT;
- copia `nombre` a `nombre_invitado`/`nombre2` y `apellido` a `apellido2` solo si el destino está vacío;
- no crea triggers ni constraints que mantengan ambos perfiles sincronizados después.

PHP Contactos detecta ambos perfiles y **prioriza moderno** (`id_pre_invitado`, `telefono`, `nombre`). Sus altas/ediciones compartidas procuran poblar ambos juegos cuando existen, pero otras escrituras modernas o datos previos pueden dejar alias nulos/desactualizados. Node no detecta perfiles ni cae al moderno.

### 6.2 IDs y relaciones

- El ID de cabecera correcto es siempre `pre_invitados.id`.
- Para el perfil moderno, las relaciones correctas son `pre_invitados_tel.id_pre_invitado` y `pre_invitados_listado_mesa.id_pre_invitado`.
- Node usa exclusivamente `id_invitados`, alias legado. No valida que coincida con `id_pre_invitado`.
- El “ID de teléfono” de cola es el PK `pre_invitados_tel.id`; esto sí coincide conceptualmente con PHP actual.
- No hay FK versionada desde las tablas `pre_*` hijas a la cabecera. La integridad depende de aplicación/datos.

## 7. Campos e integrantes

### 7.1 `apodo` y `nombre_invitado`

En el formulario admin, “Apodo” llega como `nombre_invitado`. El helper actual elige `apodo` si fue informado y, si no, el nombre; al insertar integrantes escribe el label disponible en `nombre_invitado` y también en `apodo` si la columna existe. Node no lee una columna `apodo`: usa `pre_invitados_listado_mesa.nombre_invitado`. Esto es correcto para filas creadas/reescritas por el helper actual, pero no cubre un esquema/dato donde solo `apodo` esté poblado.

`pre_invitados` principal no tiene `nombre_invitado` en el esquema canónico; el apodo visible está modelado en la fila del titular dentro de `pre_invitados_listado_mesa`. Por ello es importante conservar esa fila, no intentar leer el apodo desde cabecera.

### 7.2 Cómo arma `{{invitados}}`

Origen: subconsulta agrupada de `pre_invitados_listado_mesa`, ordenada por el PK del integrante.

- Si `cantidad_mayores + cantidad_menores < 2`, devuelve un `nombre_invitado` no agregado. En MySQL/MariaDB, al agrupar puede ser una elección no determinista si hubiera varias filas y cantidades inconsistentes; con `ONLY_FULL_GROUP_BY` puede ser rechazada por columna no agregada.
- Para dos o más personas, construye `A y B`; para tres o más intenta `A, B y C` mediante `GROUP_CONCAT`/`SUBSTRING_INDEX`.
- Con una sola fila y cantidades `>=2`, produce conceptualmente `A y A`: el conteo declarado, no el número real de filas, decide la rama.
- Con cero integrantes, el `LEFT JOIN` deja `e.invitados = NULL`; el reemplazo JS convierte el placeholder en el texto `null`.
- Un `nombre_invitado=NULL` puede hacer que `GROUP_CONCAT` omita esa fila; todos nulos producen `NULL`.
- Los acompañantes se incorporan solo si existe su fila en listado_mesa. Los teléfonos adicionales no determinan integrantes.

PHP Contactos actual crea una fila de listado para el titular (`orden=0`) y luego una por acompañante, de modo que el modelo normalizado sí permite obtener el grupo completo. Datos originados por el FORM público moderno histórico pueden contener solo acompañantes, por lo que la cabecera no necesariamente aparecería en `{{invitados}}` hasta ser normalizada/recreada por el admin.

### 7.3 `es_menor`, mayores/menores y singular/plural

Node selecciona el template por `pre_invitados.cantidad_mayores + cantidad_menores`; **no calcula las cantidades desde `pre_invitados_listado_mesa.es_menor`**. Tampoco expone o usa `es_menor` durante la composición. El helper PHP actual sí valida que mayores + menores sea igual al titular más integrantes cargados, y persiste `es_menor` por persona. En datos coherentes, el resultado coincide; en datos antiguos o editados por otra vía puede divergir.

La condición es estrictamente `total > 1` para plural. `NULL` se transforma a cero por `(valor || 0)` en JS y elige singular, mientras el `CASE` SQL con suma `NULL` cae en la rama plural; esto puede hacer que `{{invitados}}` y el template discrepen.

`es_menor` debería seguir siendo información del contrato y fuente de validación/recuento, pero no requiere necesariamente cambiar el texto del saludo en Fase 5B. Una mejora posterior podría derivar/validar conteos desde filas, sin reescribir la arquitectura.

### 7.4 `activo`

Node filtra `a.activo = 1`, alineado con Contactos y el guard de entrada/reentrada de cola. No vuelve a bloquear ni marca la fila durante el procesamiento. Si se inactiva después del `SELECT`, el envío ya seleccionado continúa.

PHP bloquea edición/reactivación/baja relevante según presencia en las colas, pero ese bloqueo consulta solo `id_invitados`; por la falta de fuente también puede bloquear un contacto `pre_*` debido a una fila de `invitados` con el mismo ID.

## 8. `tel_enviar` y colas compartidas

### 8.1 Selección y normalización del teléfono

- Node obtiene el número exclusivamente de `pre_invitados_tel.tel_enviar`, une la cabecera mediante `id_invitados` y selecciona la fila exacta mediante el par de IDs presente en `invitados_a_enviar`.
- **No usa `h.tel_enviar`** de la cola y no verifica que coincida. Esto evita confiar en un valor de cola viejo, pero no evita una colisión de IDs.
- PHP actual, en cambio, configura `pre_invitados` con `id_pre_invitado` y `telefono`; al encolar vuelve a consultar por PK + FK y guarda ese valor textual. El listado de colas vuelve a unir el teléfono por PK + FK.
- El helper PHP conserva `telefono` como texto. Solo rellena `tel_enviar` si el texto es numérico y cabe en BIGINT; entradas con `+`, espacios, guiones o longitud fuera de rango quedan con alias legado `NULL`.
- Node llama `inv.tel_enviar.toString()` **antes** del `try` por invitado. Un `NULL` provoca un rechazo no capturado de la IIFE, deja la conexión/progreso sin cierre y el front continúa consultando indefinidamente.
- No hay validación E.164, longitud, país, existencia del chat ni limpieza de caracteres.
- La expresión de prefijo siempre produce `549` + número sin un `549` inicial. Un número argentino local de 10 dígitos queda `549XXXXXXXXXX`, pero `54...` sin 9 queda `54954...`, `9...` queda `5499...`, números extranjeros reciben prefijo argentino y valores inválidos también se intentan.

### 8.2 Teléfonos compartidos

El contrato permite que distintos contactos (o integrantes) tengan el mismo valor de teléfono. Cada fila conserva su propio `pre_invitados_tel.id`, por lo que pueden encolarse y enviarse mensajes diferentes al mismo `chatId`. No hay deduplicación por valor, advertencia ni exclusión. Esto es compatible con “teléfonos compartidos” como dato, pero implica que una misma persona puede recibir varios envíos deliberados; el operador debe verlo como varias filas/contactos, no como un único destinatario.

### 8.3 Colisión real de IDs

**Sí, el riesgo es real y confirmado por estructura y joins.** Las colas no almacenan `source`. El unique tampoco incluye fuente.

Ejemplo concreto:

1. En modo CÓDIGO existe `invitados.id=25` con `invitados_tel.id=80`; PHP encola `(25,80,'111...')`.
2. Se cambia a FORM sin vaciar la cola.
3. Existe también `pre_invitados.id=25` y `pre_invitados_tel.id=80`, relacionado mediante `id_invitados=25`, con `tel_enviar='222...'`.
4. La consulta Node une la misma fila de cola `(25,80)` contra el contacto `pre_*` y envía el texto/imagen del precontacto al número `222...`, aunque la cola fue creada para el invitado definitivo y quizá contenía `111...`.
5. Después borra esa misma fila compartida y pretende registrarla como enviada en las tablas sin origen.

Si coincide solo el ID de cabecera pero no el de teléfono, el join doble de Node no toma la fila; si coinciden ambos, sí. En las comprobaciones PHP que bloquean CRUD basta la coincidencia del ID de cabecera, de modo que allí el falso bloqueo tiene un alcance aún mayor.

`invitados_enviados` presenta la misma ambigüedad y su unique puede impedir registrar un preenvío si ya existe el par del flujo `invitados`. La documentación de regresión existente ya impone como mitigación operativa vaciar colas antes de cambiar de fuente; no es una solución estructural ni verificable por Node.

## 9. RSVP FORM frente a código y URL RSVP

### 9.1 Rastreo de conceptos heredados

- La consulta carga `a.codigo`.
- `index.html` ofrece el botón `{{codigo}}` con etiqueta “Código de Invitación”.
- Ambos defaults de `web-logic.js` contienen `${base_url}?busqueda={{codigo}}#rsvp` y una línea visible “Código de Invitación: {{codigo}}”.
- `whatsapp.js` reemplaza todas las propiedades, incluido `codigo`, y además tiene una sustitución especial redundante para la URL completa.

La lógica está inequívocamente orientada al RSVP por código.

### 9.2 Flujo FORM actual

El modo público actual selecciona FORM mediante configuración efectiva y renderiza `includes/rsvp_form_public.php`: un formulario genérico con nombre, apellido, teléfono, confirmación e integrantes. No requiere que el visitante llegue con `?busqueda=<codigo>` ni que vea/escriba un código. El ancla útil sigue siendo conceptualmente la sección/modal RSVP, pero el query string de búsqueda y el copy de código dejan de corresponder.

Para ORO + FORM, el mensaje debería conducir a la URL pública del evento y a la entrada del formulario (por ejemplo, la URL base con el ancla efectiva que abre/ubica RSVP), **sin** depender de `codigo`. La URL exacta debe confirmarse contra la interacción pública final —el formulario se presenta mediante modal/botón—, pero nunca debe inventarse una búsqueda por código si FORM no la consume.

Variables aún útiles: `{{invitados}}`, `{{nombre}}`, `{{apellido}}`, cantidades y una URL pública. `{{codigo}}` y “Código de Invitación” son del modelo anterior. `ingreso` es leído por Node pero no se ofrece como botón. `telefono`, `telefono2` e `id_invitados_tel` se vuelven reemplazables accidentalmente porque se itera toda la fila, aunque no se documentan como variables.

## 10. Imagen de invitación

Node genera:

```text
<base_url><admin_folder>/invitaciones/<pre_invitados.id con 4 dígitos>.jpg
```

Ejemplo: ID `25` → `0025.jpg`. No inserta `/` entre `base_url` y `admin_folder`, por lo que depende de que `base_url` termine en `/` y `admin_folder` no empiece con `/`.

La relación es solo por el número de `pre_invitados.id`; no consulta una tabla/estado de imagen. Los generadores históricos de la referencia ORO FORM sí trabajaban con `pre_*`, pero los endpoints heredados activos `invitaciones/index.php` y `generador_masivo.php` están hoy deshabilitados con guard 410. No se encontró en el admin actual un generador activo integrado a Contactos que garantice `NNNN.jpg`. Por ello la existencia de imagen para altas/ediciones FORM **no está garantizada**.

Con “Incluir imagen” marcado (default):

- descarga con `fetch`, timeout de 12 segundos y exige HTTP exitoso, `Content-Type: image/*` y buffer no vacío;
- hace hasta 3 intentos, esperando 1 s y 3 s entre ellos (el valor final `5000` no llega a usarse porque no hay intento posterior);
- luego hace hasta 3 intentos de envío, recreando un temporal JPG por intento;
- escribe en `os.tmpdir()` como `inv_<id>_<timestamp>.jpg` y lo elimina en `finally`;
- si no existe la imagen (404), no envía siquiera el mensaje y deja la fila en cola.

La extensión temporal siempre es `.jpg` aunque el servidor entregue otro tipo de imagen. Si el proceso muere después de escribir y antes del `finally`, queda un temporal huérfano. Si “Incluir imagen” está desmarcado, envía solo texto y no consulta la URL.

## 11. Templates e interfaz

- Hay dos plantillas: plural y singular; se guardan junto al código como `template_plural.txt` y `template_singular.txt`.
- `/load-template/:type` trata cualquier tipo distinto de `plural` como singular y devuelve `mensaje:null` ante cualquier error de lectura.
- `/save-template` exige ambos textos truthy y escribe primero plural, después singular, sin operación atómica: un fallo intermedio puede dejar versiones distintas.
- Las plantillas se guardan solo cuando el polling observa `completed`. Un cierre/reinicio o error no capturado pierde los cambios del editor.
- Sustitución: por cada propiedad de la fila, crea una regex global sin escapar el nombre (los nombres actuales son seguros) y reemplaza por coerción JS. Valores `NULL` terminan como `null`; placeholders desconocidos permanecen literales.
- La separación singular/plural, edición previa, formato básico WhatsApp, vista de progreso e inclusión opcional de imagen siguen siendo útiles.
- Los nombres de novios “Maria y Jose”, la URL por código y su copy son hardcodes específicos/ejemplos antiguos.
- Todas las llamadas del browser fijan `http://localhost:3000`; no funcionan correctamente si el servidor se publica bajo otro host/puerto/HTTPS sin adaptar.

## 12. Manejo de errores, transacciones y reinicios

### 12.1 Atomicidad DB

Los tres cambios DB post-envío son atómicos entre sí: si uno falla, el rollback revierte los inserts/delete. El envío WhatsApp queda necesariamente fuera de la transacción y no puede deshacacerse.

### 12.2 Ventana WhatsApp–DB

- Si WhatsApp envía y luego falla DB, el destinatario ya recibió el mensaje, la fila permanece en `invitados_a_enviar` y un reintento duplica el envío.
- Para `pre_*`, el FK de `registro_mensajes_enviados` hace probable exactamente ese caso: el primer insert puede ocurrir pero se revierte junto con todo al fallar el segundo.
- Si el proceso muere después del envío y antes del commit, la cola queda pendiente y se reenvía al reiniciar.
- Si muere tras commit, DB refleja el envío correctamente.
- No hay reconciliación con IDs de mensajes WhatsApp, marca previa, outbox, lease, reanudación ni consulta de mensajes ya enviados.

### 12.3 Concurrencia y errores de proceso

- Dos llamadas a `/start-send/1` pueden iniciar dos bucles sobre la misma cola; no hay mutex. Ambos pueden enviar antes de que uno confirme DB; luego el otro choca con uniques/delete pero el duplicado WhatsApp ya ocurrió.
- `progress[usuarioId]` se sobrescribe en cada inicio.
- La IIFE no se espera ni tiene `catch/finally` externo. El `tel_enviar=NULL` señalado puede detener todo el lote sin `completed` ni `connection.end()`.
- Si falla abrir DB/SELECT antes de responder, se devuelve 500; no hay `finally` para una conexión abierta parcialmente.
- El error por invitado sí permite continuar cuando ocurre dentro del `try`.
- Los logs HTTP devuelven mensajes internos (`err.message`) y la UI los muestra; esto puede revelar detalles técnicos.

## 13. Sesión WhatsApp

- Estado en memoria por clave `usuarioId`; no se hidrata al arrancar aunque `LocalAuth` conserve credenciales.
- `LocalAuth.clientId = session_<usuarioId>` separa perfiles de navegador en `.wwebjs_auth`.
- QR en terminal con `qrcode-terminal`; no se expone a UI.
- `ready` pone `ready=true`, `lastState=CONNECTED`.
- `auth_failure` y `disconnected` ponen `ready=false` y conservan la entrada; `/start-session/:id` ya no permite recrearla porque solo comprueba que exista. Tras desconexión puede requerirse reinicio completo.
- Un error de `initialize()` también deja la entrada bloqueando un nuevo inicio.
- No se llama `client.destroy()`/logout ni se elimina una sesión.
- `USUARIO_ID='1'` está hardcodeado en el front y `server.js` abre sesión `1`; no hay multiusuario operativo pese al parámetro de ruta.
- Puerto `3000` fijo en `server.js`; `app.js` acepta `PORT`, pero no es el entry point.
- `/sessions` no funciona porque `sessions.js` no comparte el objeto real.

El patrón `Client` + `LocalAuth` + eventos `qr`/`ready`/fallos y el guard de `CONNECTED` debe conservarse; los defectos de lifecycle pueden corregirse sin rearquitectura.

## 14. Configuración, hardcodes y dependencias

### 14.1 Configuración

`config.js` contiene host, usuario, contraseña y database en texto claro, además de dominio y carpeta admin de un cliente. La contraseña no se reproduce aquí. Aunque `dotenv` es dependencia, ningún archivo carga `dotenv` ni usa variables de entorno para DB/web.

Hardcodes relevantes:

- instalación/credenciales concretas en `config.js`;
- `base_url` y `admin_folder` concretos;
- puerto 3000;
- usuario 1;
- URLs absolutas localhost en el browser;
- prefijo telefónico argentino `549`;
- nombres de novios en defaults;
- carpeta y formato de imagen;
- Puppeteer visible y flags de sandbox.

El Node tampoco consulta `site_settings`, ni valida `plan_servicio=oro`, `rsvp_modo=form`, `whatsapp_enabled=1` o `fuente_envios_whatsapp=pre_invitados`. Depende por completo de que el operador arranque la variante correcta y de que PHP haya poblado la cola correcta.

### 14.2 Dependencias

Declaradas: `cors`, `dotenv`, `express`, `mysql2`, `open`, `qrcode-terminal`, `whatsapp-web.js`.

- Todas salvo `dotenv` tienen uso directo; `dotenv` no se importa.
- `open` se importa correctamente como `.default` en `server.js` para la versión ESM declarada; `app.js` usa `require('open')` directamente, otra señal de que es residual y potencialmente incompatible.
- `fetch` y `AbortController` se presuponen globales; esto requiere una versión de Node que los proporcione (Node 18+ para el uso estable esperado). `package.json` no declara `engines`.
- No hay lockfile dentro del módulo: los rangos con `^` hacen que una instalación futura no sea reproducible.
- `whatsapp-web.js` y Chromium dependen de compatibilidad con el WhatsApp Web vigente y del entorno, pero esta auditoría no ejecutó la sesión; no se afirma una incompatibilidad de versión no demostrada.
- No se recomienda actualizar paquetes en Fase 5B salvo que una prueba controlada demuestre un bloqueo real. El README que sugiere `@latest` no debe tomarse como requisito de compatibilidad.

## 15. Comparación con PHP actual

### 15.1 Contrato correcto hoy

- La configuración efectiva separa `invitados` y `pre_invitados`; ORO + FORM + WhatsApp requiere fuente `pre_invitados`.
- El guard de Contactos exige exactamente ORO, FORM, WhatsApp activo y `pre_invitados`.
- “Contactos de envío” opera únicamente sobre las tres tablas `pre_*`.
- Alta y edición detectan perfil, priorizan moderno si coexiste, usan transacción y crean una fila de integrante para titular más acompañantes; guardan apodo/label y `es_menor`.
- Edición reconstruye teléfonos/integrantes y está bloqueada cuando el contacto aparece en colas.
- Baja es lógica (`activo=0`); reactivación/inactivación están bloqueadas en cola; pendientes y POST de encolado exigen `activo=1`.
- La cola selecciona el teléfono por el PK de teléfono y su FK al contacto, no meramente por valor; eso soporta teléfonos repetidos.
- El listado Enviar Invitaciones selecciona dinámicamente la fuente efectiva, pero para `pre_invitados` usa explícitamente las columnas modernas.

### 15.2 Desalineaciones Node/PHP

| Tema | PHP actual | Node auditado | Resultado |
|---|---|---|---|
| Fuente | Config efectiva + guards. | Siempre `pre_*`, sin validar configuración. | Riesgo operativo. |
| Perfil `pre_*` | Moderno prioritario. | Solo aliases legados. | Incompatibilidad real/futura. |
| Teléfono | `telefono`, FK `id_pre_invitado`. | `tel_enviar`, FK alias `id_invitados`. | Puede quedar nulo/desincronizado. |
| Integrantes | `nombre` moderno para listado de cola; helper completa labels adicionales. | `nombre_invitado` legado. | Compatible solo si normalizado. |
| Menores | Persiste y valida `es_menor`. | Ignora `es_menor`; confía cantidades. | Riesgo de texto/recuento incoherente. |
| Activo | Guard al encolar/reingresar. | Filtro al tomar snapshot. | Básicamente alineado, con carrera. |
| Cola | Compartida, sin fuente. | Interpreta cualquier par coincidente como `pre_*`. | Colisión crítica. |
| RSVP | FORM público sin código. | URL/copy por código. | Incompatible. |
| Registro | Colas históricas compartidas. | Inserta IDs `pre_*` en registro con FK `invitados*`. | Bloqueante post-envío. |

## 16. Clasificación de hallazgos

### A. Funcionalidad correcta que debe conservarse

- Entry point `server.js`, Express y router separado.
- `LocalAuth` por `clientId`, QR terminal y estados de conexión.
- Envío secuencial y progreso visible.
- Templates singular/plural editables y formato WhatsApp.
- Selección del teléfono por ID de fila encolada, no deduplicación por número.
- Filtro de activos.
- Imagen opcional, timeout, validación HTTP/tipo, reintentos y limpieza normal de temporal.
- Transacción para mantener consistentes las tres escrituras DB entre sí.
- Arquitectura pequeña y local: no hace falta reescribirla ni agregar un framework/servicio nuevo para recuperar funcionamiento.

### B. Incompatibilidades reales por cambios/contrato actual DQS

1. Node consume aliases legados mientras PHP prioriza columnas modernas.
2. Templates, `codigo`, `busqueda` y URL son RSVP CÓDIGO, no FORM.
3. `registro_mensajes_enviados` referencia por FK las tablas definitivas y no acepta correctamente identidades `pre_*`.
4. Las colas ahora compartidas por dos fuentes no tienen discriminador; cambio de fuente con filas presentes es ambiguo.
5. No hay garantía actual de imagen FORM `NNNN.jpg` para nuevos Contactos; generadores heredados activos fueron clausurados.
6. Node no verifica la combinación efectiva ORO + FORM + WhatsApp + `pre_invitados`.

### C. Bugs independientes/preexistentes

1. `INNER JOIN cliente` sin `ON`, con cero/múltiples filas destructivo/duplicador.
2. `/sessions` referencia variable inexistente.
3. `app.js` residual no funcional como servidor completo.
4. `tel_enviar.toString()` fuera del `try` puede abortar lote y filtrar conexión.
5. Doble inicio concurrente permite envíos duplicados.
6. No existe recuperación entre envío WhatsApp y commit DB.
7. Sustitución de `NULL` por texto `null` y placeholders desconocidos sin advertencia.
8. Agregación de nombres depende de cantidades, tiene caso `A y A` y posible incompatibilidad con `ONLY_FULL_GROUP_BY`.
9. Sesiones fallidas/desconectadas no se pueden reiniciar sin reiniciar proceso.
10. URLs localhost/puerto/usuario hardcodeados; footer PHP se sirve literalmente.
11. `save-template` y el procesamiento background no tienen atomicidad/lifecycle completo.

### D. Mejoras opcionales, no necesarias para Fase 5B

- UI de QR y administración multiusuario.
- Worker durable/outbox completo e idempotencia basada en message ID.
- Variables de template con esquema/preview y escape formal.
- Configuración íntegra por entorno y secret manager.
- Lockfile, `engines`, lint/tests automatizados y actualización de paquetes.
- UI responsive/de diseño, autenticación HTTP y limitación CORS.
- Telemetría estructurada y limpieza de temporales al arranque.

## 17. Riesgos priorizados

| Severidad | Riesgo | Consecuencia |
|---|---|---|
| **Crítico** | Colisión `(id contacto,id teléfono)` entre fuentes en cola sin `source`. | Envío al contacto/número equivocado y consumo de una cola ajena. |
| **Crítico** | WhatsApp enviado seguido de fallo de FK al registrar IDs `pre_*`. | Mensaje entregado, rollback y reenvío duplicado posterior. |
| **Crítico** | URL/copy por código en ORO + FORM. | Invitado recibe un mecanismo RSVP que no corresponde y puede no confirmar. |
| **Alto** | `JOIN cliente` cartesiano. | Cero envíos o múltiples envíos duplicados según cantidad de filas cliente. |
| **Alto** | Alias `tel_enviar` nulo/desactualizado frente a `telefono`. | Lote abortado o número incorrecto/no enviado. |
| **Alto** | Doble ejecución/reinicio en ventana WhatsApp–DB. | Duplicados sin posibilidad de rollback externo. |
| **Alto** | Imagen obligatoria por default sin generación garantizada. | Filas válidas no se envían por 404/archivo ausente. |
| **Medio** | `{{invitados}}` inconsistente con filas/cantidades/nulos. | Saludo duplicado, incompleto, `null` o singular/plural erróneo. |
| **Medio** | No valida config efectiva ni origen de cola. | Operación accidental en modalidad equivocada. |
| **Medio** | Estado background/sesión solo en memoria y lifecycle incompleto. | UI trabada, conexión abierta o necesidad de reinicio. |
| **Bajo** | `/sessions`, `app.js`, footer PHP, `dotenv` sin uso. | Diagnóstico/confusión/mantenimiento, no bloquean el camino nominal. |
| **Bajo** | Dependencias sin lock/engines. | Instalaciones futuras no reproducibles; no es fallo confirmado de la instalación presente. |

## 18. Propuesta de Fase 5B: cambios mínimos

### 18.1 Imprescindible

1. **Definir y hacer cumplir procedencia antes de enviar.** Sin cambiar schema inicialmente, Fase 5B debe fallar en cerrado si no puede asegurar que la cola activa pertenece a `pre_invitados`. Como mínimo: validar configuración efectiva y exigir/vigilar el precondition operacional de colas vacías al cambiar fuente. Si se admite cambio de schema en una fase posterior, el arreglo robusto es agregar un discriminador de fuente a ambas colas/registro y usarlo en unique/joins.
2. **Alinear la consulta al perfil moderno/canónico** usado por PHP (`id_pre_invitado`, `telefono`, `nombre`) o implementar el mismo detector mínimo de contrato, evitando depender de aliases no sincronizados. Mantener el PK del teléfono como identidad de cola.
3. **Resolver la escritura de historial `pre_*` antes de cualquier envío.** No enviar si `registro_mensajes_enviados` no puede representar la fuente sin violar FK. La decisión puede ser omitir ese registro para `pre_*` solo si el contrato funcional lo acepta, o introducir persistencia con fuente/FK apropiada en una fase autorizada; nunca atribuir IDs `pre_*` a `invitados*` por coincidencia.
4. **Cambiar defaults/URL de FORM**, retirando la dependencia de `{{codigo}}`, `?busqueda=` y “Código de Invitación”; apuntar a la entrada pública real del formulario RSVP.
5. **Hacer preflight sin efectos** antes de comenzar: configuración ORO + FORM + WhatsApp + `pre_invitados`, columnas/tablas esperadas, compatibilidad de historial, procedencia no ambigua, teléfono válido y política de imagen. Si algo falla, no llamar a WhatsApp.
6. **Eliminar el producto cartesiano de `cliente`** (no se usan sus columnas) o limitarlo correctamente a una sola fila si surge un uso real.
7. **Capturar `tel_enviar/telefono` nulo dentro del manejo por fila**, validar/normalizar explícitamente y garantizar `finally` para progreso/conexión.

### 18.2 Recomendable dentro de 5B si no amplía el alcance

- Comparar cantidad declarada con filas e `es_menor`; componer nombres desde labels normalizados con fallback seguro al titular.
- Evitar ejecución simultánea por sesión con un flag/mutex simple.
- Verificar imagen por fila antes de enviar y permitir política clara texto-only cuando el archivo falta; no asumir que toda alta FORM tiene imagen.
- Hacer que sesiones desconectadas/fallidas puedan recrearse y compartir correctamente el registro usado por `/sessions`, o retirar ese endpoint roto.
- Usar URLs HTTP relativas en el front y una única fuente de puerto/usuario.
- Validar templates y sustituir nulos por cadena vacía/fallback, informando placeholders no soportados.

### 18.3 Opcional/fuera de la recuperación mínima

- Rediseñar colas como outbox durable con estados, lease, intentos y message IDs.
- Migrar secretos a entorno, aunque es altamente recomendable por seguridad operacional.
- Multiusuario, QR web, autenticación, hardening CORS y despliegue remoto.
- Actualizar dependencias, incorporar lockfile/tests/lint y retirar archivos residuales.
- Crear un generador nuevo de imágenes; para 5B basta definir si el existente realmente produce el archivo o permitir texto-only de forma segura.

## 19. Veredicto final

### ¿Puede funcionar actualmente sin cambios?

**No de forma correcta y segura para ORO + FORM.** Puede leer algunos datos normalizados y llegar a enviar un mensaje en condiciones muy específicas, pero el contenido RSVP es incorrecto, el historial tiene FK incompatibles y la cola puede ser ambigua. Que un envío aislado “salga” no demuestra compatibilidad end-to-end.

### ¿Qué impide su funcionamiento correcto?

La combinación de: contrato moderno/legado divergente, URL/template por código, registro post-envío ligado a `invitados*`, falta de procedencia en colas, falta de garantía de imagen y bugs operativos (`JOIN cliente`, nulos y concurrencia).

### ¿Existe riesgo real con colas compartidas?

**Sí.** Cuando coinciden simultáneamente `invitados.id = pre_invitados.id` e `invitados_tel.id = pre_invitados_tel.id`, el Node `pre_*` no puede distinguir el origen y toma la fila. Las tablas y joins no contienen ninguna tercera dimensión que lo evite. Coincidir solo en el primer ID ya puede causar falsos bloqueos en el CRUD PHP.

### ¿Qué debe adaptarse específicamente para ORO + FORM?

Fuente/contrato moderno `pre_*`, preflight de configuración, URL y templates sin código, composición robusta de integrantes/apodos/menores, teléfono moderno validado, política de imagen FORM y persistencia de cola/historial con origen inequívoco.

### ¿Se puede conservar la arquitectura actual?

**Sí.** Express + router + `LocalAuth` + UI local + envío secuencial + transacción es suficiente. Fase 5B debe ser una corrección focalizada del contrato y de los guardrails, no una reescritura ni una modernización general.

## 20. Evidencia y límites de la auditoría

Archivos principales contrastados:

- Node: `dqs envios invitaciones a pre/{package.json,README.md,config.js,app.js,server.js,sessions.js,whatsapp.js,index.html,web-logic.js,ENVIOS WHATSAPP.bat}`.
- Schema: `docs/DQS_SCHEMA_SNAPSHOT_HOSTINGER_20260719_012551.sql` y `database/migrations/20260729_uni062_normalizar_pre_invitados.sql`.
- PHP actual: `admin7WZiwEM3XY/contactos_envio.php`, `nuevo_contacto_envio.php`, `editar_contacto_envio.php`, `gestionar_envios.php`, `admin7WZiwEM3XY/includes/guest_create_shared.php`, `includes/admin_feature_guard.php`, `includes/plan_config.php`, `includes/rsvp_mode.php` e `includes/rsvp_form_public.php`.
- Regresión/contrato previo: `docs/UNI_065_REGRESION_PLANES_CONTACTOS_ENVIOS.md` y documentación UNI de RSVP/pre_*.

Límites deliberados: no se verificaron datos reales, constraints desplegados distintos del schema versionado, existencia HTTP real de imágenes, versión efectiva de Node/Chromium ni compatibilidad viva de WhatsApp Web. Esas comprobaciones requieren una fase controlada posterior y no autorizan envíos reales como primera prueba.
