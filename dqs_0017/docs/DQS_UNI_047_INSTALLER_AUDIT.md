# UNI-047 — Auditoría del instalador web para alta de cliente

> **Alcance:** auditoría estática y de solo lectura realizada el 19 de julio de 2026. No se ejecutaron PHP, instaladores, SQL ni triggers; no se conectó a ninguna base; no se cambiaron permisos, `conexion.php`, la tienda pública, RSVP, WhatsApp, regalos, `admin7WZiwEM3XY` ni las copias históricas de `admin_tmp`.

## 1. Resumen ejecutivo

El repositorio no contiene un `admin_tmp` operativo en la raíz. Conserva cuatro variantes históricas bajo `docs/referencia_planes/{basico,basico_form,oro_codigo,oro_form}/admin_tmp`; el admin presente es `admin7WZiwEM3XY`. El alta histórica recibe solamente email y contraseña, crea el usuario mediante SQL interpolado, genera una ruta `admin` más 10 caracteres con `str_shuffle`, mueve el contenido de `admin_tmp`, inserta la ruta en `admin_config` y redirige después de haber emitido salida. No crea la base ni el esquema y no carga `cliente`.

Ese flujo no debe rehabilitarse. UNI-048 debería implementar un instalador separado, autenticado por un secreto de un solo uso, con estados persistentes, operaciones idempotentes y una **plantilla admin inmutable que se copia**. La instalación debe validar primero, aplicar un esquema canónico versionado y sin datos reales, crear configuración y usuario con sentencias preparadas, publicar el admin de forma atómica y bloquearse de manera irreversible al terminar.

La creación de la base, usuario y privilegios MySQL, la selección de PHP 7.4 y extensiones, la asociación de dominio/SSL y normalmente el despliegue inicial siguen siendo tareas del panel Hostinger. El wizard puede probarlas y explicar su corrección, pero una aplicación PHP no debe recibir credenciales del panel ni elevar privilegios.

## 2. Evidencia y archivos revisados

### 2.1 Instalador y administración

- Las cuatro variantes de `admin_tmp`, incluyendo `registro.html`, `registrar_usuario.php`, `crear_carpeta_admin.php`, `login.php`, `logout.php`, pantallas admin, recursos y subdirectorios `image/`, `invitaciones/` y `whatsapp/`.
- `registro.html` solo solicita `email` y `password`; no tiene confirmación, política visible de contraseña ni CSRF.
- `registrar_usuario.php` usa `password_hash(..., PASSWORD_BCRYPT)`, pero toma `$_POST` directamente, interpola ambos valores en el `INSERT`, imprime diagnóstico y comienza a mover archivos antes de registrar `admin_config`.
- `crear_carpeta_admin.php` es otra variante histórica: crea un destino y mueve archivos sueltos; su comportamiento difiere de `registrar_usuario.php`, que también mueve directorios.
- `admin7WZiwEM3XY/login.php`, `logout.php`, `index.php` y guardas de sesión se inspeccionaron **sin modificarlos**. El login sí consulta por email con prepared statement y verifica con `password_verify`; al autenticar guarda `user_id` y `email`. Logout destruye la sesión. Varias pantallas comprueban únicamente que exista `$_SESSION['user_id']` y redirigen a `login.php`.
- `admin7WZiwEM3XY/registrar_usuario.php` y `crear_carpeta_admin.php` todavía contienen lógica instaladora peligrosa dentro del admin activo. Esta auditoría no la ejecutó ni alteró.
- `.htaccess`, `.htpasswd` y `robots.txt` fueron inventariados. No se encontró un mecanismo de `install.lock` en el flujo histórico. Ocultar una URL o listarla en `robots.txt` no constituye control de acceso.

### 2.2 Conexión y configuración

- `conexion.php` contiene cuatro valores estáticos (`servername`, `dbname`, `username`, `password`), crea un `mysqli` y revela el error de conexión mediante `die`. No fija explícitamente `utf8mb4`, modo estricto ni opciones de timeout. Sus secretos no se reproducen en este documento.
- `includes/plan_config.php` define defaults y valores permitidos para plan, RSVP, WhatsApp, regalos y textos; lee `site_settings` de manera tolerante si existe.
- `includes/site_settings_writer.php` valida claves/valores y realiza upsert con prepared statement. Es un contrato útil para la futura carga inicial, no un instalador.
- Se revisaron referencias de tablas en PHP de raíz, admin y `tienda/`, además de documentación previa (`DQS_INSTALADOR_ADMIN_TMP.md`, mapa, baseline y documentos de base/planes).

### 2.3 SQL encontrado (no ejecutado)

Se hallaron 15 archivos `.sql`:

- Dumps históricos: `base_datos.sql`, `editado.sql`, `nuevo_cliente.sql`, `nuevo_cliente_v2.sql` en Básico y Oro Código; varias copias son byte a byte idénticas.
- `docs/referencia_planes/oro_form/BASE DE DATOS.sql`, única variante observada que incluye `pre_invitados`, sus tablas auxiliares y `site_settings`.
- Copias en `basico_form/_BD/` y `basico_form/admin_tmp/`.
- Migraciones actuales `database/migrations/2026-02-21_invitados_menores_asistencia.sql` y `2026-02-22_invitados_alimento_por_persona.sql`, que alteran `invitados_listado_mesa`.
- `alter_table_20250929.sql`, que altera `cliente`.

No existe hoy un artefacto inequívoco, versionado y completo que pueda declararse “base canónica de instalación”. Algunos dumps incluyen datos históricos (`INSERT`), productos y/o invitados, y las variantes discrepan en tablas y columnas. Por eso UNI-048 **no debe seleccionar automáticamente el primer `.sql` encontrado** ni importar `base_datos.sql`/`editado.sql` en producción.

## 3. Flujo actual detectado

1. Un operador crea DB, importa un dump, sube archivos y edita `conexion.php` manualmente.
2. Visita la referencia pública `admin_tmp/registro.html` e ingresa email/contraseña.
3. `registrar_usuario.php` incluye `../conexion.php`, calcula bcrypt e inserta `user` con SQL concatenado.
4. Genera `admin` + 10 caracteres mediante `str_shuffle` (sin CSPRNG ni control de colisión robusto).
5. Crea el directorio destino. La variante de registro recorre `admin_tmp`, excluye solo el script actual y mueve cada archivo/directorio con `rename`; si algo falla, termina sin rollback. Otra variante mueve únicamente archivos sueltos.
6. Inserta `nombre_carpeta` en `admin_config` mediante SQL concatenado.
7. Construye la URL desde `SCRIPT_NAME` y llama `header(Location)` tras numerosos `echo`; existe además un segundo redirect inalcanzable.
8. El login final usa prepared statement y `password_verify`, crea una sesión PHP y las pantallas protegidas verifican `user_id`.

No hay preflight, transacción integral, lock, CSRF, rate limit, confirmación de contraseña, validación del esquema/triggers, carga de `cliente`, unicidad garantizada, comprobación final ni reanudación segura.

## 4. Modelo mínimo de datos

La lista debe cerrarse contra un esquema canónico antes de programar UNI-048. Según SQL y consumidores actuales:

| Dominio | Tablas | Condición / observación |
|---|---|---|
| Núcleo | `user`, `admin_config`, `cliente`, `site_settings` | Obligatorias para admin/configuración unificada. Dumps antiguos pueden carecer de `site_settings`. Debe haber un único admin inicial y vínculo consistente `cliente.user_id`. |
| Contenido público | `info_mostrar`, `info_casamiento`, `info_eventos`, `info_historia`, `info_nosotros`, `info_otra`, `imagenes`, `visitas` | Crear estructura y solo filas semilla neutrales requeridas por el render. No copiar datos de otro cliente. |
| Tienda/regalos | `productos`, `carrito`, `regalos`, `regalos_confirmacion`, `regalos_detalles` | El esquema puede existir aunque la feature esté apagada; sembrar catálogos solo si son genéricos. |
| RSVP código | `invitados`, `intivados_acompanante` (error ortográfico heredado), `invitaciones_estado`, `invitados_listado_mesa`, `invitados_prioridad`, `invitados_tel` | Mantener el nombre heredado hasta una migración explícita. Aplicar las dos migraciones actuales de listado de mesa. |
| RSVP formulario | `pre_invitados`, `pre_invitados_listado_mesa`, `pre_invitados_tel` | Aparecen en Oro Form, no en todos los dumps. Necesarias si `rsvp_modo=form`/persistencia correspondiente las consume. |
| Envíos | `invitados_a_enviar`, `invitados_enviados`, `registro_mensajes_enviados` | Crear solo conforme al perfil soportado; no iniciar sesiones ni enviar mensajes durante instalación. |

Los nombres `sesiones` y `visitas_con_sesion` aparecen en consumidores pero no forman parte uniforme de los dumps inventariados: UNI-048 debe resolverlos mediante matriz “tabla ↔ consumidor ↔ perfil”, no asumirlos. Se recomienda que todas las tablas estructurales soportadas existan en ambos planes y que los flags controlen funcionalidad, reduciendo bifurcaciones de esquema.

## 5. Trigger requerido y validación

El único trigger de instalación explícito hallado es `generar_codigo_invitado`, `BEFORE INSERT ON invitados FOR EACH ROW`. Genera un código numérico aleatorio de seis dígitos, repite mientras ya exista y asigna `NEW.codigo`. Aparece en `nuevo_cliente*.sql` con bloques `DELIMITER`; no aparece uniformemente en los dumps completos.

Antes de adoptarlo hay que revisar concurrencia: el sondeo `COUNT(*)` no sustituye un índice `UNIQUE(codigo)` y dos inserciones simultáneas pueden elegir lo mismo. El esquema canónico debe definir el índice y decidir si el código se genera en DB o aplicación, pero no en ambos.

Validación propuesta, ejecutada por UNI-048 con la cuenta de aplicación y sin dispararlo:

```sql
SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE,
       ACTION_TIMING, ACTION_STATEMENT
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND TRIGGER_NAME = 'generar_codigo_invitado';
```

Debe comprobar exactamente: una fila, esquema actual, `INSERT`, `invitados`, `BEFORE`, y una huella normalizada/versionada del cuerpo. `SHOW TRIGGERS` es una verificación secundaria. “Existe un trigger con ese nombre” no basta. La instalación debe fallar con diagnóstico si el usuario DB carece de privilegio `TRIGGER`; jamás debe comprobarlo insertando un invitado real.

## 6. Riesgos del instalador viejo

| Riesgo | Evidencia / impacto | Tratamiento UNI-048 |
|---|---|---|
| SQL injection | Email y carpeta se interpolan en `INSERT`. | Prepared statements y allowlists; nunca interpolar identificadores/datos del wizard. |
| Instalador público | No hay token, lock ni comprobación de estado previa. | Secreto inicial fuera de URL, expiración/rate limit, estado `installed` y denegación antes de renderizar. |
| Sin CSRF | Form POST sin token. | Token por sesión, `SameSite=Strict`, validar origen y método. |
| Debug y secretos | Imprime conexión exitosa, rutas, SQL y errores DB. | Mensajes públicos genéricos; log privado con ID de correlación y secretos redactados. |
| Redirect roto | Hay `echo` antes de `header`; puede producir “headers already sent”. | Patrón POST/Redirect/GET sin salida previa y output buffering no usado como parche. |
| Entropía insuficiente | `str_shuffle` no es CSPRNG; 10 caracteres no garantizan colisión cero. | `bin2hex(random_bytes(16))`, prefijo fijo opcional, validación de regex y bucle de creación exclusiva. |
| Movimiento destructivo | Mueve la única plantilla; variantes no coinciden en recursividad. | Copiar desde plantilla inmutable privada, manifest/sha256, staging y rename atómico. |
| Estado parcial | Usuario puede quedar creado aunque falle carpeta/config, o carpeta a medias. | Máquina de estados, pasos idempotentes, transacciones DB y compensación explícita para filesystem. |
| Reintento destructivo | Puede borrar destino existente y duplicar filas. | ID de instalación, claves únicas, upsert limitado, nunca borrar un destino no poseído por esa instalación. |
| Admin por oscuridad | Ruta aleatoria es defensa secundaria; puede filtrarse. | Autenticación fuerte, sesiones endurecidas y autorización en cada endpoint. |
| Sesión débil | Tras login no se observa regeneración del ID; guardas comprueban solo presencia. | `session_regenerate_id(true)`, cookies Secure/HttpOnly/SameSite, timeout, lookup/estado de usuario. |
| Sin validación | No normaliza email, no confirma password ni limita longitud. | `filter_var`, normalización, límites, política de contraseña y confirmación; mensajes no enumerables. |
| Sin exclusión mutua | Dos solicitudes pueden instalar a la vez. | Lock atómico antes de mutar + fila de estado única; rechazar concurrencia. |
| Configuración expuesta | `conexion.php` guarda secretos junto al webroot y muestra error de driver. | Config privada/no servida, escritura atómica, permisos preconfigurados por hosting y errores neutros. |
| Dump no confiable | Dumps heterogéneos pueden contener datos reales y `DELIMITER`. | Manifest canónico, migraciones versionadas, parser probado o importador fuera de request. |

## 7. Propuesta de instalador moderno por pasos

### Principios transversales

- Endpoint separado de `admin_tmp`, sin reutilizar el admin activo, habilitado solo con un secreto de bootstrap almacenado fuera del webroot o variable de entorno.
- Estado explícito: `not_started → preflight_ok → db_ok → schema_ok → admin_ok → client_ok → files_ok → installed`, con `failed` más paso/diagnóstico seguro. Cada transición valida la anterior.
- Un solo escritor: lock de filesystem creado de forma exclusiva y/o advisory lock DB; cada POST lleva CSRF y clave idempotente.
- No mezclar DDL, datos de usuario y filesystem en una falsa “transacción global”. Aplicar transacciones donde MySQL las soporte y publicar archivos al final; registrar checkpoints verificables.
- Pantalla de resumen y confirmación antes de mutar; jamás mostrar passwords, hashes o secretos en HTML/logs.

### Paso 1 — Requisitos (sin mutaciones)

1. Exigir PHP `>=7.4.0 <8.0.0` si la compatibilidad contractual sigue siendo estricta; mostrar versión exacta y SAPI.
2. Requerir `mysqli`, `mysqlnd` (por `get_result`), `session`, `json`, `filter`, `hash`, `openssl`, `fileinfo`, `mbstring` y `gd` (invitaciones/imágenes). `ZipArchive`/`zip` es requerido para exportación XLSX; el código tiene fallback CSV, pero el wizard debe marcarlo claramente. Verificar también PDO solo si UNI-048 decide usarlo.
3. Comprobar de forma no destructiva directorios esperados, plantilla/manifest, espacio libre, posibilidad de crear **un archivo temporal propio** en el directorio de configuración y staging (y eliminarlo); no ejecutar `chmod`.
4. Detectar `conexion.php`/config privada existente, `install.lock`, fila/estado instalado, `admin_config` y directorios `admin*`. Ante evidencia contradictoria, detenerse y pedir intervención; nunca sobrescribir.
5. Verificar HTTPS, configuración de cookies y que el secreto de bootstrap sea válido.

### Paso 2 — Base de datos

Datos mínimos: host (y puerto/socket), nombre DB, usuario y contraseña. No pedir credenciales de Hostinger. Probar conexión con timeout corto, `utf8mb4`, modo estricto y TLS cuando el proveedor lo permita; comprobar versión MySQL/MariaDB, DB seleccionada y privilegios efectivos necesarios (`CREATE`, `ALTER`, `INDEX`, `INSERT`, `UPDATE`, `SELECT`, `DELETE`, `TRIGGER`). No imprimir el error crudo.

#### Generación segura de conexión

Preferencia: generar un archivo de configuración PHP fuera de `public_html`, leído por una ruta fija, o usar secretos/variables provistos por el hosting. Si la arquitectura obliga a `conexion.php`:

1. Generar contenido desde una plantilla fija usando `var_export` para valores, sin aceptar código ni ruta del usuario.
2. Incluir `mysqli_report`, timeout, conexión, `set_charset('utf8mb4')` y errores genéricos; no cerrar `?>`.
3. Escribir en archivo temporal del mismo filesystem con creación exclusiva, hacer `fflush`/validación sintáctica offline y `rename` atómico. Nunca sobrescribir uno existente.
4. Evitar que el servidor lo sirva como texto y definir permisos mínimos **en el despliegue/panel**, no mediante el wizard, conforme a las restricciones operativas.
5. No guardar la contraseña en sesión, DB, HTML, query string ni logs; descartarla tras escribir/probar.

### Paso 3 — Esquema y base inicial

UNI-048 necesita primero un nuevo paquete canónico, por ejemplo `database/install/manifest.json`, `schema.sql`, `seed.sql` y migraciones numeradas, con hash y versión. Debe derivarse/revisarse desde los dumps, no elegir uno de ellos en runtime. `seed.sql` contendrá únicamente filas neutrales y repetibles; quedan prohibidos invitados, productos personalizados, usuarios, hashes y datos bancarios históricos.

- Validar previamente encoding, tamaño, hash y versión; rechazar SQL subido desde navegador y rutas arbitrarias.
- Preferir un runner de migraciones que ejecute sentencias conocidas por versión. No pasar un dump completo a `mysqli::multi_query`: `DELIMITER` es sintaxis del cliente y los cuerpos de trigger requieren parser consciente de delimitadores.
- Para importaciones grandes, la alternativa operativa es CLI/Hostinger con artefacto firmado y timeout controlado; el web wizard luego valida y continúa.
- Crear una tabla de historial/estado de instalación con checksums. Cada migración debe ser reintentable o detectar con precisión que ya fue aplicada.
- Tras aplicar: comparar `information_schema.TABLES/COLUMNS/STATISTICS` con el manifest; validar engine, charset, índices, FKs y trigger. No insertar datos de prueba en producción.
- DDL de MySQL puede realizar commits implícitos: registrar cada unidad exitosa y detenerse ante divergencia; no prometer rollback total.

### Paso 4 — Usuario admin

Pedir email, password y confirmación. Normalizar/validar email, imponer longitud máxima acorde a columna e índice, un mínimo operativo de 6 caracteres para el password inicial temporal, permitir gestores, comparación con `hash_equals` donde corresponda y rate limit.

Dentro de una transacción: adquirir lock, verificar `COUNT(*) = 0`, exigir índice único case-insensitive/normalizado para email, generar `password_hash($password, PASSWORD_DEFAULT)` (en PHP 7.4 será bcrypt; columna `VARCHAR(255)`), insertar con prepared statement, comprobar resultado y borrar variables sensibles. No aceptar `user_id`, hash, rol o SQL del cliente. Tras la primera autenticación, regenerar ID de sesión; opcionalmente exigir cambio/2FA en una fase posterior.

### Paso 5 — Datos de cliente

**Mínimos para completar:** plan (`basico|oro`), modo RSVP permitido por plan, toggles WhatsApp/regalos, fuente de envíos, nombres visibles de la pareja/evento, fecha/hora con zona horaria y frase/título de portada. Vincular `cliente.user_id` al ID recién creado y guardar `site_settings` usando su allowlist.

**Opcionales y editables luego:** teléfono(s), ubicación, dirección/URLs del evento, cotización del dólar con decimal y fecha de vigencia, titular/CBU/alias y datos en dólares. Los datos bancarios deben marcarse como sensibles, no prellenarse desde dumps, validarse y omitirse si regalos/tienda están desactivados. Imágenes, invitados, productos, WhatsApp y envíos no deben formar parte del alta atómica inicial.

Validar fecha ISO y zona, longitudes/UTF-8, URLs `https`, decimales con punto y configuración comercial efectiva. Guardar `user`, `cliente` y settings relacionados en una transacción cuando las tablas ya existan; usar prepared statements y comprobar exactamente una fila núcleo.

### Paso 6 — Publicación y cierre de seguridad

1. Generar `admin-` + `bin2hex(random_bytes(16))` (128 bits), comprobar allowlist y crear un staging exclusivo. La ruta no reemplaza autenticación.
2. **Copiar**, no mover, una plantilla admin inmutable situada fuera del árbol público o dentro de un paquete no ejecutable. Copiar recursivamente solo el manifest esperado, rechazar symlinks/path traversal y verificar hash, cantidad y tamaño.
3. Hacer self-check del staging; publicar mediante `rename` atómico en el mismo filesystem. No copiar `registro.html`, instaladores, dumps, backups ni secretos al admin final.
4. Insertar/actualizar una única fila `admin_config` mediante prepared statement y restricción de unicidad, asociada al ID de instalación. Si DB falla antes de publicar, limpiar únicamente el staging propio; si falla después, conservar diagnóstico y reanudar sin generar otra ruta.
5. Crear `install.lock` atómicamente con versión, fecha e ID no secreto, y marcar DB `installed` **solo después** de todas las verificaciones. La presencia de cualquiera de ambos debe cerrar el endpoint; si discrepan, fail closed.
6. Deshabilitar el instalador por configuración del servidor/routing y retirar el secreto de bootstrap. No intentar “borrar el script que se está ejecutando”. Redirigir mediante PRG y URL relativa validada, sin salida previa.

## 8. Reintentos y recuperación

- Cada paso guarda checkpoint y checksum; al reingresar, recalcula evidencia y ofrece continuar desde el último estado consistente, nunca repetir ciegamente.
- Una operación se considera aplicada por su efecto verificable (tabla/versión/hash, usuario por ID de instalación, destino por manifest), no solo por un booleano en sesión.
- Las escrituras llevan claves únicas (`installation_id`, email, clave de setting, versión de migración). Un reenvío devuelve el resultado existente si coincide; si difiere, se detiene.
- Mantener staging con nombre ligado a `installation_id`; solo ese staging puede limpiarse automáticamente. Nunca borrar una carpeta admin existente.
- Antes del cierre puede existir “abortar”: revierte filas creadas por esa instalación cuando sea seguro y elimina su staging, pero no intenta deshacer DDL compartido. Después de `installed`, solo un procedimiento administrativo autenticado y respaldado puede intervenir.
- Reportar paso, código estable y acción de reparación, sin stack traces, SQL, rutas absolutas ni credenciales. Un log fuera del webroot registra eventos redactados.
- Casos a probar: doble click, refresh, dos navegadores, timeout en cada sentencia, pérdida de conexión, poco espacio, colisión de ruta, archivo corrupto, trigger sin privilegio, lock/DB discordantes y redirect fallido.

## 9. Qué se automatiza y qué queda manual

### Automatizable por el wizard

- Preflight de PHP/extensiones/HTTPS/configuración/espacio y diagnóstico de escritura sin cambiar permisos.
- Prueba de credenciales DB ya creadas y privilegios.
- Generación atómica de configuración, si la política de hosting permite escribirla.
- Aplicación de migraciones canónicas pequeñas, seed neutral y validación estructural/triggers.
- Creación segura del primer admin, `cliente` y `site_settings`.
- Copia verificada de plantilla, publicación de ruta admin, registro de `admin_config`, lock y redirección.
- Informe final sin secretos y exportación de checklist para el operador.

### Manual en Hostinger / infraestructura

- Crear base y usuario MySQL, asignar privilegios y conservar credenciales; eventualmente crear el trigger/importar un artefacto si el plan bloquea esos privilegios o el timeout web es insuficiente.
- Seleccionar PHP 7.4 en el panel e instalar/habilitar extensiones; el wizard solo puede detectar. Debe confirmarse que Hostinger aún ofrece una versión 7.4 con mantenimiento de seguridad o acordar una actualización, pues PHP 7.4 está fuera de soporte upstream.
- Subir/desplegar inicialmente la aplicación y la plantilla, asociar dominio/subdominio, DNS, SSL, document root y backups.
- Definir permisos/propietario mínimos desde panel/SSH y proteger configuración fuera del webroot; el wizard no ejecutará `chmod`.
- Configurar correo, cron, integraciones/proveedores y secretos externos si aplican. El instalador no debe enviar RSVP/WhatsApp ni activar compras como prueba.
- Verificación humana final de contenido, datos bancarios, plan contratado y restauración/rollback respaldado.

## 10. Compatibilidad PHP 7.4

- Declarar PHP 7.4 como plataforma de CI y evitar sintaxis 8.x: atributos, union types, named arguments, nullsafe operator, `match`, constructor property promotion y funciones exclusivas de 8.x.
- Usar APIs disponibles: `random_bytes`, `password_hash`, `hash_equals`, `filter_var`, `mysqli`, JSON y sesiones. No depender de `str_contains`/`array_is_list`.
- Mantener `PASSWORD_DEFAULT` y `VARCHAR(255)` para evolución del hash; probar bcrypt y límites de bytes.
- Probar MySQL y MariaDB ofrecidos por hosting, `mysqlnd`, `utf8mb4`, prepared statements y diferencias DDL/trigger.
- `ZipArchive` debe detectarse: si la instalación contractual exige XLSX, bloquear; si CSV es aceptable, advertir y continuar. `gd`, `mbstring`, `openssl` y `fileinfo` sí deben incluirse por funciones actuales.
- Añadir CI con `php -l` usando 7.4 y tests unitarios/integración en contenedor 7.4. No silenciar incompatibilidades con `@` ni basar seguridad en `display_errors=Off`.

## 11. Fases recomendadas para UNI-048

1. **Contrato de instalación:** decidir perfiles/tabla matriz, esquema canónico, seed neutral, versiones y manifest; reconciliar `site_settings`, `pre_invitados`, `sesiones`/`visitas_con_sesion` y migraciones actuales.
2. **Hardening previo:** retirar o bloquear por servidor toda ruta instaladora histórica del paquete desplegable; diseñar secreto bootstrap, estado/lock y logging redactado.
3. **Motor sin UI:** preflight read-only, conexión temporal, runner de migraciones con checksums y validadores de `information_schema`; tests de fallos e idempotencia.
4. **Servicios de escritura:** configuración atómica, admin/cliente/settings transaccionales y publicador de plantilla con staging/manifest; ninguna dependencia de `admin_tmp`.
5. **Wizard:** seis pasos, CSRF, PRG, confirmación, accesibilidad, no-cache y manejo seguro de sesiones/errores.
6. **Cierre y operación:** lock dual, deshabilitación de ruta, smoke test no destructivo, runbook Hostinger, backup/recuperación y auditoría de seguridad.
7. **Piloto:** entorno desechable equivalente a Hostinger, matriz Básico/Oro × código/form, instalación nueva y reintento inyectando fallos en cada transición. Producción solo tras revisión humana.

## 12. Checklist de seguridad

- [ ] Instalador separado de admin/plantilla y cerrado por defecto.
- [ ] HTTPS y secreto bootstrap de un uso, con expiración y rate limit.
- [ ] CSRF, cookies `Secure`, `HttpOnly`, `SameSite=Strict`, no-cache y PRG.
- [ ] Lock exclusivo y estado durable; concurrencia/reenvíos probados.
- [ ] Inputs con allowlist, límites y normalización; nada de rutas/SQL subidos.
- [ ] Prepared statements para todos los datos y restricciones únicas en DB.
- [ ] Password con confirmación/política, `PASSWORD_DEFAULT`, columna de 255 y borrado de variables.
- [ ] Config secreta fuera del webroot o generada atómicamente, nunca sobrescrita ni registrada.
- [ ] Esquema/seed canónicos, versionados, con checksums y sin datos reales.
- [ ] Tablas, columnas, índices y trigger validados por metadata; no por efectos destructivos.
- [ ] Plantilla inmutable, manifest, rechazo de symlinks y publicación atómica; copiar, no mover.
- [ ] No incluir registro, instalador, SQL, `.bkp`, logs o secretos en admin final.
- [ ] `admin_config` único y consistente con filesystem; ruta CSPRNG de 128 bits.
- [ ] `install.lock` + estado DB; discrepancia falla cerrada; endpoint deshabilitado al finalizar.
- [ ] Logs privados redactados, errores públicos genéricos y sin rutas absolutas.
- [ ] Sesión regenerada tras login, expiración y autorización por endpoint.
- [ ] Sin `chmod`, importación de datos reales, envío de mensajes, compras o invitados de prueba.
- [ ] Backups, runbook de recuperación y pruebas de fallo/reintento aprobadas.

## 13. Criterios de aceptación propuestos para UNI-048

1. Una instalación limpia completa los seis pasos sobre PHP 7.4 y el stack Hostinger soportado sin editar archivos manualmente después de contar con DB y despliegue.
2. Preflight no muta y bloquea requisitos, esquema previo o estado ambiguo con instrucciones accionables.
3. Existe un único esquema/seed versionado, sin PII, con manifest y checksums; todas las tablas/índices/triggers requeridos se verifican.
4. No existe SQL concatenado con input, secreto en respuesta/log o contraseña persistida fuera de la configuración final necesaria.
5. Admin, cliente, settings y `admin_config` quedan relacionados y únicos; login funciona con `password_verify` y sesión endurecida.
6. El admin se copia desde plantilla inmutable, se valida y se publica atómicamente con nombre CSPRNG; `admin_tmp` no se mueve ni se usa como origen operativo.
7. Cada transición es idempotente. Una suite inyecta fallo en DB/filesystem/redirect y demuestra reanudación sin duplicados, pérdida de plantilla ni borrado de admins.
8. Dos instalaciones concurrentes no pueden progresar; refresh y doble POST devuelven el mismo resultado seguro.
9. Al completar, lock de filesystem y estado DB impiden volver a abrir cualquier pantalla/POST del instalador; la discrepancia falla cerrada.
10. El paquete final no expone instaladores históricos, dumps, backups ni configuración; no toca tienda, RSVP, WhatsApp o regalos salvo guardar flags iniciales sin efectos externos.
11. CI ejecuta lint/tests en PHP 7.4 y pruebas de integración MySQL/MariaDB; hay runbook documentado para Hostinger, backup, importación alternativa y recuperación.
12. Una revisión de seguridad aprueba CSRF, autenticación bootstrap, sesiones, traversal/symlinks, inyección, salida de errores y manejo de secretos.

## 14. Respuestas directas a las preguntas de UNI-047

1. **Automatizable:** preflight, prueba DB, config atómica, esquema canónico, validación, admin/cliente/settings, copia/publicación admin y lock.
2. **Manual en Hostinger:** DB/usuario/privilegios, PHP/extensiones, despliegue inicial, dominio/SSL, permisos/propietario, backups y secretos de proveedores.
3. **`conexion.php`:** preferir config fuera del webroot; si no, plantilla fija, `var_export`, temporal + rename, no overwrite, `utf8mb4`, errores neutros y ningún secreto en sesión/log.
4. **Base inicial:** artefacto canónico versionado con schema y seed neutral separados; runner de migraciones/checksums, no dumps históricos arbitrarios ni `multi_query` ingenuo.
5. **Triggers:** consultar `information_schema.TRIGGERS` y comparar nombre, evento, tabla, timing y cuerpo/huella; comprobar índice único sin ejecutar el trigger.
6. **Admin inicial:** email normalizado/único, password confirmada, `PASSWORD_DEFAULT`, prepared statement, transacción y lock con verificación de cero admins.
7. **Carpeta final:** token de 128 bits con `random_bytes`, staging exclusivo, manifest y publicación atómica; la ruta es defensa secundaria.
8. **Mover o copiar:** copiar una plantilla inmutable; nunca mover `admin_tmp`.
9. **Bloqueo:** estado DB + `install.lock` atómico + deshabilitación de routing/servidor y retiro del bootstrap; discrepancias fallan cerradas.
10. **Reintento:** máquina de estados/checkpoints, claves únicas, efectos verificables y staging ligado al ID; nunca borrar destinos ajenos.
11. **Datos mínimos:** secreto bootstrap; DB host/puerto/nombre/usuario/password; email/password/confirmación; plan/modo/toggles; nombres, fecha/zona y título/frase.
12. **Wizard cliente:** núcleo anterior; contacto, evento, cotización y banco como opcionales condicionados, editables luego y tratados como sensibles.
13. **PHP 7.4:** plataforma fija de CI, sintaxis/API 7.4, `mysqli/mysqlnd`, extensiones detectadas y matriz MySQL/MariaDB; planificar actualización por fin de soporte.

---

**Conclusión:** UNI-048 no debería ser una refactorización de `registrar_usuario.php`. Debe ser un subsistema de bootstrap aislado y descartable, construido sobre un contrato de esquema nuevo, plantilla admin inmutable, checkpoints idempotentes y cierre fail-closed. Esa separación elimina el principal riesgo histórico: convertir una solicitud web anónima en una secuencia destructiva e irreversible sobre DB y filesystem.
