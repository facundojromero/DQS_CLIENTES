# UNI-069 — Auditoría de compatibilidad de DQS con PHP 8.4

> **Fecha:** 2026-08-04  
> **Alcance:** análisis estático y comandos de solo lectura. No se cambió código, configuración, dependencias, base de datos ni versión PHP.  
> **Objetivo:** determinar si la aplicación completa puede converger en PHP 8.4 y separar evidencia del repositorio de la configuración no observable de Hostinger.

## 1. Resumen ejecutivo

PHP 8.4 es **viable**, pero aún no debe habilitarse globalmente. El repositorio no contiene un handler que seleccione PHP 7.4: el `.htaccess` raíz sólo protege DEV y activa reescritura, `tools/.htaccess` deniega acceso, y `tienda/` no tiene `.htaccess`, `.user.ini` ni `php.ini`. Por tanto, el supuesto PHP 7.4 de la tienda proviene de la configuración externa del panel/vhost, o de una decisión operativa histórica repetida por el preflight y la documentación; no está demostrado como requisito exclusivo del código.

La conclusión exigida es: **la dependencia no surge de una configuración versionada propia de `/tienda/`; la versión web efectiva no puede determinarse sin evidencia del panel Hostinger o un endpoint diagnóstico temporal**. Tampoco puede afirmarse que sólo la tienda dependa de 7.4: las herramientas de instalación declaran 7.4 como mínimo, advierten para toda versión distinta de la rama 7.4 y el ejecutor conserva rutas explícitas a `php74`.

No se hallaron `mysql_*`, `each()`, `create_function()`, `money_format()`, `get_magic_quotes_gpc()`, offsets de string con llaves, clases con propiedades dinámicas ni firmas de interfaces heredadas en el código activo. Sí hay riesgos de ejecución en PHP 8.x/8.4: cambio de errores `mysqli` a excepciones, `strpos(false, ...)` en correo de tienda, división por cero, fechas inválidas, valores nulos en funciones internas, procesamiento de imágenes sin comprobar retornos y una inclusión local construida como URL. El endpoint de prueba PHPMailer referencia un `vendor/autoload.php` inexistente y no existe Composer en el proyecto.

### Dictamen y conteo

| Nivel | Cantidad | Identificadores |
|---|---:|---|
| BLOQUEANTE | 2 | UNI069-B01, UNI069-B02 |
| ALTO | 4 | UNI069-A01 a UNI069-A04 |
| MEDIO | 4 | UNI069-M01 a UNI069-M04 |
| BAJO | 3 | UNI069-L01 a UNI069-L03 |

Los bloqueantes son de **evidencia/certificación** antes del cambio global: no existe PHP 8.4 en este entorno y no se conoce el runtime web del dominio/tienda. Los defectos de código de mayor impacto están clasificados ALTO porque pueden producir errores fatales sólo al recorrer datos o fallos externos concretos.

## 2. Versiones actuales detectadas

| Capa | Versión detectada | Evidencia / límite |
|---|---|---|
| PHP CLI de Codex | `PHP 8.5.7-dev`, NTS, Xdebug 3.6.0-dev | `command -v php` resolvió `/root/.phpenv/shims/php`; `php -v`. No representa al dominio. |
| PHP 8.4 CLI | **No disponible** | No resolvieron `php8.4`, `php84` ni `/usr/bin/php8.4`. |
| Dominio web DQS DEV | **No verificable** | El repositorio no contiene configuración de vhost/panel ni salida de `phpinfo()`/`PHP_VERSION`. |
| `/tienda/` web | **No verificable; 7.4 histórico** | No hay override dentro de la carpeta. Preflight y schema runner dicen que la tienda “históricamente requiere PHP 7.4”. |
| Requisito del instalador | `>= 7.4.0`; warning fuera de `7.4.x` | `tools/dqs_install_preflight.php:40-44` y `tools/dqs_install_schema_runner.php:54-59`. No es una certificación de 8.4. |

Ninguna prueba de esta auditoría se ejecutó con PHP 8.4. El lint parcial se ejecutó con PHP 8.5.7-dev y, por ello, sólo aporta una señal adicional hacia adelante.

## 3. Versiones por carpeta y configuración

| Alcance | Configuración versionada | Herencia / resultado |
|---|---|---|
| Raíz y todo el árbol público | `.htaccess:1-8`: Basic Auth, `Options -Indexes`, `RewriteEngine On` | Apache hereda estas directivas a subdirectorios salvo override. No selecciona PHP. |
| `/tienda/` | Ninguna configuración PHP propia | Hereda la raíz y el handler externo del vhost/panel. No hay evidencia versionada de versión distinta. |
| `/admin7WZiwEM3XY/`, `/includes/`, `/install/`, endpoints raíz | Ninguna configuración PHP propia | Igual que tienda: runtime externo/heredado. |
| `/tools/` | `tools/.htaccess:1` → `Require all denied` | Añade denegación HTTP; no cambia PHP. |
| CLI del instalador | Resolución en `includes/install/install_cli_executor.php:98-115` | Prioriza política/env/binario actual; después enumera `php74`, `php80`, `php81`, `php82`, `php83`, pero no 8.4. Afecta procesos CLI, no al vhost. |

La búsqueda global de `application/x-lsphp74`, `lsphp74`, `PHP 7.4`, `php74`, `SetHandler`, `AddHandler` y `FilesMatch` no encontró handlers. Sólo encontró textos históricos y la ruta CLI `php74`. No se encontraron `.user.ini`, `php.ini`, scripts shell ni configuración de servidor versionada. Los `.bat` pertenecen a herramientas Node/WhatsApp y no seleccionan PHP.

## 4. Auditoría específica de `/tienda/`

### 4.1 Estructura, entrada y relación

La carpeta contiene 15 PHP activos, JavaScript/CSS e imágenes. `tienda/index.php` es el punto de entrada; carrito y regalos usan `carrito.php`, `ver_carrito.php`, `modificar_cantidad.php`, `eliminar_producto.php`, `vaciar_carrito.php`, `finalizar_compra.php`, `procesar_compra.php` y `compra_exitosa.php`. La paginación/productos se separa en `paginacion.php` y `mostrar_productos.php`.

Todos los flujos principales incluyen `../conexion.php`; el control de disponibilidad y el regalo libre pasan por `regalo_libre_helper.php`, que comparte `includes/gift_feature_guard.php`. También comparte `contador.php`, `header.php` y `footer.php` con el sitio raíz. El proceso de compra persiste con `mysqli`, sesiones y prepared statements; el correo se delega por HTTPS/JSON a `api.dijequesi.com`.

`test_mail.php` intenta cargar PHPMailer desde `tienda/vendor/autoload.php`, pero el directorio no existe. No se encontró `composer.json`/`composer.lock`; este archivo no prueba una dependencia instalada de PHPMailer y fallará si se publica tal como está.

### 4.2 Configuración y versión requerida

No existe `.htaccess` en `tienda/`; por tanto hereda el `.htaccess` raíz y la configuración PHP del servidor. No hay sintaxis que exija exactamente PHP 7.4. La evidencia del “requisito” es operacional/documental, no una restricción de Composer ni un handler. Al mismo tiempo, no se puede certificar 8.4 sin ejecutar el flujo contra PHP 8.4, MySQL y servicios reales.

### 4.3 Riesgos PHP 8.4 propios de tienda

* `enviar_correo.php:124-129` y `enviar_correo_vendedor.php:170-204`: `file_get_contents()` puede devolver `false`; pasarlo a `strpos()` causa `TypeError` en PHP 8.
* `ver_carrito.php:65-67`: una cotización cero puede provocar `DivisionByZeroError` al elegir dólares. `finalizar_compra.php` sí protege un caso equivalente, mostrando inconsistencia.
* `finalizar_compra.php:279`: `include 'ver_carrito.php?currency=…'` trata el query string como parte de un nombre de archivo local; genera warning y omite el carrito salvo que una configuración excepcional habilite wrappers/rutas distintas.
* `finalizar_compra.php:112`: `new DateTime()` puede lanzar excepción con un valor DB inválido.
* `conexion.php:8-12` y consultas sin `try/catch`: con el modo de errores moderno de `mysqli`, errores antes inspeccionados como `false` pueden lanzar `mysqli_sql_exception`.
* La sesión se inicia antes de HTML en endpoints directos. Sin embargo, includes previos pueden emitir warnings/fatales; se requiere prueba con `display_errors=0` y log para confirmar headers/cookies. No se declara SameSite en tienda.

**Resultado explícito:** no se comprobó que `/tienda/` necesite PHP 7.4. Su selección histórica parece externa y conservadora; tienda comparte los mismos riesgos y runtime heredado que el resto de DQS.

## 5. Inventario de `.htaccess` y handlers

| Archivo/líneas | Directiva | Alcance | PHP / carpetas afectadas |
|---|---|---|---|
| `.htaccess:1-5` | Basic Auth | Raíz y descendientes, incluido tienda | Ninguna versión; acceso DEV. |
| `.htaccess:7-8` | `Options -Indexes`, `RewriteEngine On` | Raíz y descendientes | Ninguna versión. |
| `tools/.htaccess:1` | `Require all denied` | `/tools/` y descendientes | Ninguna versión; override de autorización. |

No se copia un `.htaccess` desde el instalador: el publicador copia un template de admin y excluye ciertos directorios/secretos, pero no genera handlers. El release puede incluir el `.htaccess` raíz como cualquier archivo desplegado; éste no fuerza PHP. El panel Hostinger sigue siendo responsable de seleccionar el runtime.

## 6. Compatibilidad del código con PHP 8.4

### 6.1 Alcance revisado

Se inventariaron 387 PHP versionados: 118 fuera de `docs/` (sitio activo, admin, tienda, includes, instalador, CLI/AJAX) y 269 snapshots históricos bajo `docs/referencia_planes`. Los snapshots se buscaron para configuraciones/APIs antiguas, pero no se trataron como runtime activo. Se revisaron raíz, tienda, administrador, RSVP, regalos, invitados/contactos/envíos, APIs/correo, imágenes, instalador, herramientas CLI y módulos compartidos.

### 6.2 Clasificación general

* **Compatible estáticamente:** sintaxis moderna/7.4 común; JSON, sesiones, prepared statements y llamadas GD disponibles en PHP 8.4 con extensiones correctas.
* **Warning/deprecated:** null hacia funciones internas (`htmlspecialchars`, `trim`, `strpos`, etc.) y supuestos sobre índices DB/request; en PHP 8.x producen deprecation, warnings o `TypeError` según función/tipo.
* **Error potencial bloqueante de flujo:** `mysqli_sql_exception`, `DivisionByZeroError`, `TypeError` en correo y `DateMalformedStringException`/`Exception` por fecha inválida.
* **No encontrado:** APIs eliminadas listadas en el issue, offsets con llaves, constructores con nombre de clase, métodos mágicos incompatibles, clases activas con propiedades dinámicas o implementaciones antiguas de interfaces.
* **No verificable:** comportamiento end-to-end en 8.4, extensiones web, configuración `allow_url_fopen`, DB/schema/datos reales, servidor de correo y panel Hostinger.

### 6.3 Áreas transversales

**Base de datos.** Toda la aplicación usa `mysqli`; PDO sólo figura como extensión sugerida del preflight. La conexión pública no fija `mysqli_report()` ni charset. PHP moderno activa excepciones `mysqli` por defecto, de modo que muchos `if ($result)` dejan de ser manejo suficiente. Deben normalizarse política de error, charset, excepciones y retornos antes de certificar.

**Sesiones/cookies/headers.** El instalador configura `Secure`, `HttpOnly` y SameSite Strict. Tienda/admin usan defaults del hosting; su funcionamiento depende de no haber salida previa y del `session.*` de PHP web. La migración debe probar login/logout, carrito, RSVP y AJAX con cabeceras reales.

**Archivos/imágenes.** Raíz y admin usan GD y cargas. `getimagesize()`, `imagecreatefromjpeg()`, `move_uploaded_file()` y escrituras pueden devolver `false`; hay rutas que continúan sin validación completa. En 8.x GD retorna objetos `GdImage`, aunque no se encontró `is_resource()` aplicado a ellos. Deben probarse JPEG/PNG válidos, corruptos, grandes y permisos.

**Fechas.** `DateTime` se construye desde DB sin captura uniforme. PHP 8.3+ especializó algunas excepciones de fechas; la entrada inválida debe rechazarse o capturarse. Confirmar zona horaria de PHP/DB porque no se encontró una política global.

**JSON/APIs.** Se usa JSON ampliamente sin `JSON_THROW_ON_ERROR` ni chequeo uniforme de `json_last_error()`. Los endpoints deben verificar encoding UTF-8, cuerpo vacío/malformado y headers. El envío HTTP depende de `allow_url_fopen` y OpenSSL, no de curl.

## 7. Dependencias y librerías

| Dependencia | Versión / restricción PHP | Compatibilidad 8.4 | Estado y riesgo |
|---|---|---|---|
| Composer raíz/tienda | No hay `composer.json`, `composer.lock` ni `vendor/` versionado | No certificable | `tienda/test_mail.php` requiere PHPMailer inexistente. Bloquea sólo ese endpoint de prueba; alto riesgo de publicar credenciales/debug. En fase posterior, retirar el test de producción o declarar una versión PHPMailer soportada y probarla, sin actualización ciega. |
| PHPMailer | Versión no identificable | No inferible | No está instalado en repo; los correos funcionales llaman a API externa. La versión del servicio API está fuera del alcance y debe auditarse por separado. |
| GD | Extensión, no librería vendida | Disponible en CLI 8.5; web/8.4 desconocido | Obligatoria para invitaciones y edición/render de imágenes. Probar retornos `GdImage`. |
| SDK externos PHP | Ninguno encontrado | N/A | WhatsApp PHP usa HTTP/OpenSSL; las herramientas locales Node tienen sus propios `package.json`/`node_modules`, no fijan PHP. |
| Librerías manuales | No se detectó árbol PHP de terceros activo | N/A | Los 269 PHP de `docs/referencia_planes` son snapshots de referencia, no `vendor`. No desplegarlos como código activo. |

No se ejecutó `composer update`, install ni modificación del lock.

## 8. Extensiones PHP requeridas

| Extensión | Evidencia/módulo | ¿Obligatoria en Hostinger? | Prueba de confirmación PHP 8.4 |
|---|---|---|---|
| `mysqli` + `mysqlnd` | `conexion.php`, todo el front/admin/tienda; `get_result()` | **Sí**; mysqlnd necesario para `get_result()` | `extension_loaded`, conexión TLS/local, prepared statement y `get_result()`. |
| `PDO` / `pdo_mysql` | No hay uso funcional; sólo sugerida por preflight | No para runtime actual | Confirmar ausencia con análisis; eliminar de requisitos o agregar prueba sólo si se adopta. |
| `mbstring` | exportación, contratos RSVP, configuración | **Sí** para esos flujos | caracteres acentuados/emoji y `mb_*` en export/RSVP. |
| `curl` | No se encontraron `curl_*` | No actualmente | Búsqueda estática; no exigir salvo cambio del transporte HTTP. |
| `json` | APIs, AJAX, instalador, tienda/correo | **Sí** (core moderno) | round-trip UTF-8, JSON inválido y respuesta de cada endpoint. |
| `gd` | invitaciones, portada/logo, productos/galería | **Sí** para funcionalidades visuales | JPEG/PNG, archivo corrupto, resize/escritura y tipo `GdImage`. |
| `fileinfo` | Preflight la sugiere; no hay llamada activa detectada | Recomendable para validar uploads, no obligatoria hoy | `extension_loaded` y futura validación MIME real. |
| `openssl` | cifrado en invitaciones y HTTPS del correo | **Sí** | encrypt/decrypt y POST TLS a API en staging. |
| `session` | carrito, administrador, RSVP, instalador | **Sí** | cookies/SameSite, regeneración, login, carrito concurrente. |
| `zip` | exportación XLSX (`ZipArchive`) y preflight | **Sí** si se ofrece XLSX | exportar/abrir XLSX y `class_exists('ZipArchive')`. |
| `intl` | dashboard (formateo) | **Sí** para dashboard completo | locale `es_AR`, formatos y fallback controlado. |
| `iconv` | Sin uso directo detectado; puede respaldar mb/ZIP | No demostrado | `extension_loaded`; no convertirlo en requisito sin caso funcional. |
| `filter`, `hash` | preflight/validación/tokens | **Sí** (core) | preflight y probes del instalador. |

El CLI 8.5 local tiene todas las extensiones anteriores, pero eso no demuestra el conjunto cargado por PHP-FPM/LiteSpeed web de Hostinger.

## 9. Hallazgos por riesgo

Cada prueba propuesta debe ejecutarse en clon/staging PHP 8.4, nunca directamente sobre clientes.

### UNI069-B01 — BLOQUEANTE — Falta runtime PHP 8.4 de certificación

* **Archivo/línea/módulo:** entorno; toda la aplicación.
* **Descripción/evidencia:** sólo existe PHP CLI 8.5.7-dev; no hay binario 8.4.
* **7.4:** la documentación afirma operación histórica, no una prueba reproducida.
* **8.4 esperado:** desconocido hasta lint y pruebas funcionales reales.
* **Corrección sugerida:** crear CI/contenedor o staging idéntico a Hostinger con PHP 8.4 y extensiones fijadas.
* **Prueba requerida:** lint de los 118 PHP activos, suite/probes y matriz web completa.

### UNI069-B02 — BLOQUEANTE — Runtime web/handler no observable

* **Archivo/línea/módulo:** `.htaccess:1-8`; `tienda/` sin override; infraestructura.
* **Descripción/evidencia:** no hay handler PHP versionado ni export del panel/vhost.
* **7.4:** se informa como selección histórica de tienda, sin evidencia ejecutable.
* **8.4 esperado:** raíz y subcarpetas deberían compartir el handler 8.4 del dominio.
* **Corrección sugerida:** inventariar en Hostinger versión por dominio/subdominio/directorio y capturar SAPI/configuración sin exponer secretos.
* **Prueba requerida:** endpoint diagnóstico autenticado/temporal o panel + comparación `PHP_VERSION`, SAPI e INI en raíz/tienda/admin; retirarlo después.

### UNI069-A01 — ALTO — Excepciones `mysqli` no manejadas

* **Archivo/línea/módulo:** `conexion.php:8-12` y consultas de raíz/admin/tienda.
* **Descripción/evidencia:** se comprueba `connect_error` y muchos resultados `false`, pero no hay política global/captura de `mysqli_sql_exception`.
* **7.4:** el default histórico reportaba warnings y retornaba `false`.
* **8.4 esperado:** errores de conexión/consulta pueden lanzar excepción fatal antes de esos checks.
* **Corrección sugerida:** definir política única, charset y captura segura en frontera DB; no ocultar errores ni revelar credenciales.
* **Prueba requerida:** credencial inválida, tabla/columna ausente, constraint y pérdida de conexión en cada flujo crítico.

### UNI069-A02 — ALTO — `strpos()` recibe `false` en correo de tienda

* **Archivo/línea/módulo:** `tienda/enviar_correo.php:124-129`; `tienda/enviar_correo_vendedor.php:170-204`.
* **Descripción/evidencia:** fallo DNS/TLS/HTTP de `file_get_contents()` produce booleano.
* **7.4:** warning y comparación generalmente continuable.
* **8.4 esperado:** `TypeError`, interrumpe el procesamiento posterior al regalo.
* **Corrección sugerida:** comprobar `is_string`, inspeccionar HTTP/JSON y desacoplar persistencia de notificación con idempotencia.
* **Prueba requerida:** timeout, DNS, TLS, HTTP 500, cuerpo vacío y éxito.

### UNI069-A03 — ALTO — División por cero en moneda de tienda

* **Archivo/línea/módulo:** `tienda/ver_carrito.php:61-68`; carrito.
* **Descripción/evidencia:** para productos normales divide por `$cotizacion_dolar` sin validar `> 0`.
* **7.4:** warning/resultado no fiable según operación.
* **8.4 esperado:** `DivisionByZeroError` fatal.
* **Corrección sugerida:** validar cotización numérica positiva y bloquear USD con mensaje controlado.
* **Prueba requerida:** `NULL`, `''`, `0`, negativa, texto y tasa válida.

### UNI069-A04 — ALTO — Entradas de fecha/imagen sin validar retornos/excepciones

* **Archivo/línea/módulo:** `index.php:43,133-143`; `tienda/finalizar_compra.php:112`; portada/tienda/imágenes.
* **Descripción/evidencia:** `DateTime` y GD reciben DB/archivos sin una frontera uniforme de validación; `getimagesize`/create pueden devolver `false`.
* **7.4:** warnings o excepción genérica; rutas podían continuar accidentalmente.
* **8.4 esperado:** excepciones de fecha más específicas, TypeError/errores al operar con retornos falsos.
* **Corrección sugerida:** validar formato/zona/MIME/dimensiones y capturar excepciones esperadas.
* **Prueba requerida:** fechas inválidas/DST y JPEG/PNG corrupto, vacío, enorme o sin permisos.

### UNI069-M01 — MEDIO — Null/tipos e índices no normalizados

* **Archivo/línea/módulo:** ejemplo `tienda/ver_carrito.php:74-90`; patrón en front/admin/APIs.
* **Descripción/evidencia:** columnas y request values llegan directamente a `htmlspecialchars`, `trim`, aritmética o concatenación; no todos usan `??`/casts.
* **7.4:** coerción silenciosa o warning.
* **8.4 esperado:** deprecations por null a parámetros internos y posibles `TypeError`/warnings.
* **Corrección sugerida:** contratos de entrada y normalización explícita preservando semántica.
* **Prueba requerida:** columnas NULL, claves omitidas, arrays en request, números no numéricos y UTF-8 inválido.

### UNI069-M02 — MEDIO — Inclusión con query string local

* **Archivo/línea/módulo:** `tienda/finalizar_compra.php:279`; checkout.
* **Descripción/evidencia:** `include 'ver_carrito.php?currency=…'` no transmite GET; busca literalmente ese filename.
* **7.4/8.4:** warning y componente ausente; configuración de wrappers puede variar.
* **Corrección sugerida:** en fase posterior pasar dato por variable y requerir ruta filesystem estable.
* **Prueba requerida:** checkout con ambas monedas y `allow_url_include=0`.

### UNI069-M03 — MEDIO — Sesiones/cookies dependen de INI del hosting

* **Archivo/línea/módulo:** endpoints de tienda/admin; contraste `install/index.php:21`.
* **Descripción/evidencia:** tienda/admin no fijan SameSite/Secure/HttpOnly de forma uniforme; includes/warnings pueden impedir headers.
* **7.4:** dependía igualmente del hosting.
* **8.4 esperado:** funcional si INI es adecuado, pero puede perder sesión o emitir “headers already sent”.
* **Corrección sugerida:** política central antes de `session_start`, sin cambiarla hasta probar compatibilidad.
* **Prueba requerida:** HTTP→HTTPS, cookies del navegador, login/logout, carrito, AJAX y sesión expirada.

### UNI069-M04 — MEDIO — JSON y archivos remotos sin contrato de error

* **Archivo/línea/módulo:** tienda correo, APIs/AJAX, `php/form-process.php`.
* **Descripción/evidencia:** encode/decode y transporte no verifican consistentemente error JSON, status HTTP ni `allow_url_fopen`.
* **7.4:** fallos podían degradar a `false`/`null`.
* **8.4 esperado:** mayor rigor de tipos puede convertir degradación en error.
* **Corrección sugerida:** contrato JSON, status, timeout, logs con correlation ID y error handling tipado.
* **Prueba requerida:** UTF-8 inválido, JSON vacío/malformado, timeout y códigos 4xx/5xx.

### UNI069-L01 — BAJO — Preflight conserva contrato histórico 7.4

* **Archivo/línea/módulo:** `tools/dqs_install_preflight.php:40-56`; `tools/dqs_install_schema_runner.php:54-59`.
* **Descripción/evidencia:** acepta `>=7.4` pero advierte siempre que no sea 7.4.x; no certifica ni exige 8.4.
* **7.4:** OK sin warning.
* **8.4 esperado:** OK + warning engañoso.
* **Corrección sugerida:** tras certificar, fijar rango objetivo soportado y mensaje de 8.4.
* **Prueba requerida:** preflight CLI/web exactamente en 8.4.

### UNI069-L02 — BAJO — Resolver CLI no enumera PHP 8.4

* **Archivo/línea/módulo:** `includes/install/install_cli_executor.php:98-115`; instalador.
* **Descripción/evidencia:** rutas conocidas llegan a php83, aunque PHP actual/env/política pueden resolver 8.4.
* **7.4:** encuentra ruta alt explícita.
* **8.4 esperado:** funciona sólo si se resuelve antes; instalación Hostinger puede fallar al descubrir CLI.
* **Corrección sugerida:** después de certificar, agregar/priorizar 8.4 y eliminar preferencia heredada obsoleta.
* **Prueba requerida:** resolución con web 8.4, CLI 8.4 y mismatch controlado.

### UNI069-L03 — BAJO — Dependencia PHPMailer indeterminada en test publicado

* **Archivo/línea/módulo:** `tienda/test_mail.php:2-34`; diagnóstico de correo.
* **Descripción/evidencia:** falta autoloader/manifiesto/versión; además el archivo contiene configuración sensible y debug.
* **7.4/8.4:** fatal `require` si se invoca; compatibilidad de librería no evaluable.
* **Corrección sugerida:** retirar/aislar el test público y auditar el servicio de mail como componente independiente; no instalar una versión arbitraria.
* **Prueba requerida:** confirmar que la ruta no se despliega; smoke test de API con secretos externos.

## 10. Archivos afectados (para issues posteriores)

No fueron modificados. Los candidatos principales son:

* Configuración/instalación: `tools/dqs_install_preflight.php`, `tools/dqs_install_schema_runner.php`, `includes/install/install_cli_executor.php` y documentación/runbooks.
* Conexión y uso DB: `conexion.php` y consumidores `mysqli` en raíz, `admin7WZiwEM3XY/`, `tienda/`, RSVP y herramientas.
* Tienda: `enviar_correo.php`, `enviar_correo_vendedor.php`, `ver_carrito.php`, `finalizar_compra.php`, `procesar_compra.php`, `test_mail.php`.
* Fecha/imagen/upload: `index.php`, `invitacion.php`, `admin7WZiwEM3XY/info_logo.php`, `modificar_portada.php`, `info_fotos.php`, `info_imagenes.php`, `invitaciones/*` y `lista_regalos.php`.
* Sesiones/API: endpoints de tienda, admin, `confirmar_asistencia.php`, `procesar_confirmacion.php`, `rsvp_form_validate.php`, `php/form-process.php` e instalador.

## 11. Pruebas y comandos realizados

Todos fueron de lectura salvo la creación de este documento y archivos temporales en `/tmp`:

```text
find .. -name AGENTS.md -print
git status --short
command -v php; php -v; command -v php8.4/php84
find ... (.htaccess, .user.ini, php.ini, composer.*, shell/batch y PHP)
rg -n -i ... (handlers/versiones, APIs eliminadas, tipos, sesiones, DB, JSON, GD, fechas, extensiones e includes)
php -m
php -l <archivo> (lote de PHP activo, PHP 8.5.7-dev)
git ls-files '*.php'
nl -ba / sed -n (inspección de evidencia con líneas)
```

El lint con 8.5.7-dev alcanzó 92 archivos activos sin errores de sintaxis antes del límite de ejecución de la herramienta. No debe presentarse como lint completo ni como sustituto de 8.4. No se ejecutaron endpoints, instalador, probes con DB, SQL, Node, requests de correo ni pruebas que pudieran escribir.

## 12. Pruebas pendientes

1. Instalar/proveer PHP **8.4 exacto** y ejecutar `php -l` sobre los 118 PHP activos; ejecutar separadamente análisis/versionado de snapshots si alguna instalación aún los despliega.
2. Ejecutar analizadores PHPCompatibility/PHPStan sólo tras fijar configuración y baseline, sin auto-fix.
3. Obtener versión/SAPI/extensiones/INI reales de raíz, tienda y admin en Hostinger.
4. Clonar DB y archivos anonimizados; ejecutar probes/instalador en dry-run y luego sandbox desechable.
5. Matriz funcional: home, invitación/imagen, RSVP código/form, login/admin CRUD, invitados/contactos/exportaciones, WhatsApp sin envío real, tienda/carrito/monedas/checkout/correo, uploads y AJAX.
6. Fallos inyectados: DB, NULL/tipos, fecha, imágenes, permisos, sesión/cookies, TLS/API/JSON.
7. Verificar logs sin secretos/deprecations y comparar respuestas/status/body con baseline 7.4 controlado.
8. Auditar por separado la API externa de correo y su PHPMailer real.

## 13. Plan de migración por fases/issues

1. **UNI-069.1 — Harness PHP 8.4:** contenedor/CI exacto, extensiones, lint total y smoke read-only; capturar runtime Hostinger.
2. **UNI-069.2 — Política DB PHP 8.4:** conexión/charset/error mode y manejo de excepciones, con pruebas de fallo y sin cambiar SQL/schema.
3. **UNI-069.3 — Compatibilidad general de tipos:** null, arrays/strings, fechas, JSON y headers en raíz/admin/RSVP/APIs.
4. **UNI-069.4 — Compatibilidad tienda:** correo `false`, división cero, include, sesiones y checkout transaccional; pruebas de las dos monedas.
5. **UNI-069.5 — Imágenes/uploads:** GD/Fileinfo, MIME, límites, retornos y galería/logo/invitaciones/regalos.
6. **UNI-069.6 — Dependencias/correo:** decidir destino de `test_mail`, inventariar API/PHPMailer y actualizar sólo con matriz de regresión y lock revisado.
7. **UNI-069.7 — Certificación integral 8.4:** suite con DB clonada, extensiones web, logs limpios, performance y checklist de aceptación.
8. **UNI-069.8 — Configuración e instalador:** después de certificar, declarar 8.4, corregir preflight/resolver/runbooks y configurar una sola versión en staging. No depender de `.htaccess` por carpeta.
9. **UNI-069.9 — Piloto y despliegue gradual:** DQS DEV, cliente controlado, cohortes, observabilidad y rollback ensayado.

Cada fase es independiente y debe tener PR propio. El siguiente issue recomendado es **UNI-069.1: proveer el harness reproducible PHP 8.4 y capturar el runtime web real**, porque desbloquea evidencia y evita corregir contra PHP 8.5 por error.

## 14. Estrategia de prueba y reversión

1. Crear staging aislado desde backup de archivos y dump anonimizado; bloquear email/WhatsApp o dirigirlos a sinks.
2. Registrar versión/SAPI/INI/extensiones, release SHA, request ID, errores PHP (`E_ALL`) en archivo fuera de webroot y errores LiteSpeed/FPM, sin `display_errors` al usuario.
3. Tomar baseline en el runtime anterior sólo en el clon; cambiar staging a 8.4 y repetir exactamente la matriz crítica.
4. Respaldar antes del piloto: archivos/configuración externa, dump consistente DB, uploads, export de ajustes del panel, tareas cron y variables/secretos (custodiados, no en Git).
5. Criterios go: lint 100%, cero fatal/deprecation no aceptada, extensiones verificadas, flujos críticos completos, integridad DB, correo/WhatsApp controlados, cookies/headers y rollback ensayado.
6. Desplegar primero DQS DEV, después un cliente controlado y luego cohortes; observar errores, latencia, tasa de checkout/RSVP/login y colas externas.
7. Rollback temporal: restaurar en el **panel/vhost** el runtime anterior documentado y el release/DB compatibles desde backup si hubo escrituras incompatibles. No introducir un handler 7.4 permanente en `.htaccess`; fijar ventana y causa, y reintentar 8.4 después de corregir.

## 15. Instalaciones nuevas y recomendación final

Hoy el instalador no copia/genera un handler 7.4. La selección sigue siendo manual en Hostinger, el preflight sólo exige `>=7.4` y conserva el warning histórico, y el resolver CLI contempla rutas antiguas. Para que un cliente nuevo funcione directamente en PHP 8.4, una fase posterior debe:

* certificar un único stack PHP 8.4 para raíz, tienda, admin, instalador y cron/CLI;
* declarar 8.4 como runtime soportado en preflight, documentación y CI;
* verificar extensiones y coincidencia web/CLI, fallando con diagnóstico accionable;
* eliminar instrucciones/preferencias operativas que empujen a 7.4;
* configurar PHP a nivel dominio/panel (fuente única), no mediante overrides divergentes de carpeta;
* mantener un release instalable con dependencias declaradas y no desplegar diagnósticos inseguros.

Recomendación: **aprobar PHP 8.4 como objetivo**, pero mantener estado NO-GO hasta cerrar B01/B02, corregir A01–A04 y completar la certificación. No se recomienda 7.4, 8.0 ni 8.1 como solución permanente.

## 16. Confirmación de alcance no funcional

La única modificación de UNI-069 es este documento. No se modificó código funcional, `.htaccess`, `.user.ini`, `php.ini`, configuración Hostinger, versión PHP, dependencias, `composer.lock`, base de datos, schema, migrations, instalador ni seeds. No se ejecutó `composer update`, no se desplegó y no se realizó merge.
