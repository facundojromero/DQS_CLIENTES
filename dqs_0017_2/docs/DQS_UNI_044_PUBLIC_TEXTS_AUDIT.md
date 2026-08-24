# UNI-044 — Auditoría de textos públicos editables y secciones ocultables

## 1. Alcance y método

Auditoría **read-only** del `index.php` público, sus includes directos (`header.php` y
`footer.php`) y el admin activo `admin7WZiwEM3XY/`. No se inspeccionó ni modificó
`admin_tmp`, ni se cambió código, esquema, configuración o datos. RSVP, WhatsApp,
tienda y el comportamiento funcional de regalos quedan expresamente fuera de una
implementación posterior derivada de este documento.

Se considera «texto hardcodeado detectado» cada **ocurrencia visible o potencialmente
visible para el invitado** que forma parte de la página principal y que podría necesitar
personalización. El inventario arroja **39 ocurrencias (34 textos distintos)**. Incluye
3 fallbacks de portada que solo aparecen si falta la fila de DB y 4 etiquetas bancarias
repetidas; no incluye mensajes internos, comentarios, atributos `alt`/ARIA, el bloque
RSVP ni textos de la tienda. Los rótulos de navegación y acciones se registran para que
la auditoría sea completa, aunque se recomienda mantenerlos como interfaz y no hacerlos
editables en la primera implementación.

Las líneas son las observadas al momento de esta auditoría y son aproximadas ante
ediciones futuras.

## 2. Resumen ejecutivo

- **Prioridad alta:** Historia tiene cabecera y bajada fijas, aunque sus hitos ya se
  administran; es el caso directo que motivó UNI-044.
- **Prioridad media:** Nosotros y Eventos ya permiten editar registros y ocultar la
  sección, pero conservan rótulos/cabeceras públicas fuera del admin.
- **Prioridad media-baja:** Más Info y Contacto tienen cabeceras fijas. Contacto se
  puede ocultar, pero no tiene una pantalla de edición activa (el enlace admin es `#`).
- **Ya resuelto en gran parte:** Portada y el contenido de los registros de Nosotros,
  Historia, Eventos y Más Info vienen de DB. Regalos ya tiene controles propios para
  sus títulos principales y visibilidad; no conviene tocarlo funcionalmente en UNI-044.
- **Visibilidad actual:** `about`, `story`, `gallery`, `events`, `wedding`, `contact`,
  `cronometro` y `logo` se gobiernan mediante `info_mostrar`. Portada se renderiza
  siempre. RSVP también se renderiza siempre y queda fuera de alcance.

## 3. Inventario de textos hardcodeados

### 3.1 Contenido editorial y fallbacks (convertir o evaluar como editable)

| # | Texto encontrado | Archivo/línea aprox. | Sección pública | ¿Editable hoy? | Admin recomendado | ¿Ocultable? / recomendación |
|---:|---|---|---|---|---|---|
| 1 | `#Fulano & #Mengano` | `index.php:19` | Portada (fallback) | No como fallback; el valor normal sí viene de DB | `?new=info_casamiento` | No hace falta ocultar el campo; reemplazar en una fase futura por fallback neutro/configurable |
| 2 | `Nos casamos` | `index.php:20` | Portada (fallback) | No como fallback; el valor normal sí viene de DB | `?new=info_casamiento` | Igual que el anterior |
| 3 | `8 de Diciembre 2040` | `index.php:21` | Portada (fallback) | No como fallback; el valor normal sí viene de DB | `?new=info_casamiento` | Igual que el anterior |
| 4 | `Acerca de ` | `index.php:414` | Nosotros, persona 1 | No; solo nombre y biografía son DB | `?new=nosotros` | La sección completa ya es ocultable; hacer configurable una sola plantilla/rótulo |
| 5 | `Acerca de ` | `index.php:431` | Nosotros, persona 2 | No; repetición del rótulo anterior | `?new=nosotros` | Mismo control que #4; no crear dos campos salvo necesidad demostrada |
| 6 | `Nuestra Historia` | `index.php:452` | Historia | No | `?new=historia` | Sí; la sección completa ya se puede ocultar |
| 7 | `Desde el primer encuentro hasta el compromiso, nuestra historia está llena de momentos inolvidables y amor verdadero.` | `index.php:453` | Historia | No | `?new=historia` | Sí; permitir ocultar solo la bajada además del toggle de sección |
| 8 | `Eventos` | `index.php:513` | Eventos | No | `?new=eventos` | Sí; sección ya ocultable |
| 9 | `Estamos muy felices y queremos compartir este día con vos!` + `Te dejamos toda la información de nuestro casamiento, para que nos acompañes en este gran día!` | `index.php:514` | Eventos | No (es una sola bajada HTML con salto) | `?new=eventos` | Permitir bajada vacía; sección ya ocultable |
| 10 | `Más Info` | `index.php:574` | Más Info | No | `?new=masinfo` | Sí; advertir que regalos puede forzar hoy el contenedor público |
| 11 | `Descubre más sobre nuestro evento.` | `index.php:575` | Más Info | No | `?new=masinfo` | Permitir ocultar la bajada independientemente |
| 12 | `Contactar con nosotros` | `index.php:681` | Contacto | No | Nueva pantalla `?new=contacto` (hoy el menú apunta a `#`) | Sí; sección ya ocultable |
| 13 | `Si quieres enviarnos un mensaje.` | `index.php:682` | Contacto | No | Nueva pantalla `?new=contacto` | Permitir vacío; sección ya ocultable |
| 14 | `Todos los derechos reservados. © [año] Desarrollado por: Dije que sí!` | `footer.php:6` | Pie | No | Configuración global/Portada, no Historia | No para la atribución contractual; definir primero política comercial/legal |
| 15 | `Tu fiesta. Dije que sí!` | `index.php:238` | Título del documento/navegador | No | Configuración global/Portada | No como sección; sí parametrizar por pareja/evento por accesibilidad y SEO |

**Conteo de esta tabla:** 15 ocurrencias, 14 textos/configuraciones distintos (el
rótulo «Acerca de» se repite).

### 3.2 Rótulos y microcopy de interfaz (auditar, no priorizar como campos libres)

| Ocurrencias | Texto(s) | Archivo/línea aprox. | Sección | Estado y recomendación |
|---:|---|---|---|---|
| 8 | `Nosotros`, `Historia`, `Fotos`, `Eventos`, `Más Info`, `Regalar`, `Contactar`, `RSVP` | `header.php:59-93` | Navegación | Hardcodeados. Su visibilidad acompaña los toggles, excepto RSVP; `Regalar` usa configuración funcional. Mantener como vocabulario de interfaz o centralizar para i18n, no crear campos por cliente en fase 1 |
| 2 | `RSVP`, `Regalar` | `index.php:382-384` | Acciones de portada/cronómetro | Hardcodeados; Regalar ya condiciona su aparición. No tocar RSVP ni regalos funcionalmente |
| 2 | `Ver ubicación`, `Ver link` | `index.php:552-554` | CTA de Eventos | Hardcodeados y elegidos según URL. Centralizar como microcopy/i18n; baja prioridad |
| 1 | `Link >` | `index.php:595` | CTA de Más Info | Hardcodeado. Centralizar como microcopy/i18n; baja prioridad |
| 9 | `Nombre`, `Email`, `Mensaje`, `Por favor, ingresar nombre`, `Por favor, ingresar email`, `Por favor, escribi tu mensaje para enviar`, `Enviar Mensaje` (más los dos textos editoriales #12 y #13) | `index.php:681-709` | Contacto | 7 ocurrencias de formulario más las 2 editoriales ya contadas. Editar solo cabecera/bajada; mantener placeholders, validaciones y botón como interfaz centralizada |
| 4 | `CBU/CVU:` (2), `Alias:` (2) | `index.php:621-656` | Más Info / transferencia de regalos | Hardcodeados. Son etiquetas de datos, no contenido editorial; no modificar funcionalmente regalos |

Esta tabla aporta **24 ocurrencias** al total (las 2 editoriales de Contacto se
muestran como contexto pero ya pertenecen a la tabla 3.1; por eso el subtotal material
es 8 + 2 + 2 + 1 + 7 + 4 = 24). Total auditado: **15 + 24 = 39**.

## 4. Textos que ya vienen de DB/config/admin

| Sección | Contenido público dinámico | Fuente observada | Admin activo existente | Visibilidad actual |
|---|---|---|---|---|
| Portada | título, frase y fecha | `info_casamiento` (`index.php:9-16`, salida `361-363`) | `?new=info_casamiento` edita los tres campos | Sin toggle de sección; siempre visible |
| Nosotros | nombre y texto de hasta dos registros activos | `info_nosotros` (`index.php:51-73`, salida `404-432`) | `?new=nosotros` edita nombre, texto, imagen y `activo` por registro | Toggle global `about` + `activo` por registro |
| Historia | fecha, título y texto de hitos activos | `info_historia` (`index.php:78-109`, salida `458-469`) | `?new=historia` edita fecha/título/texto y `activo` por hito | Toggle global `story` + `activo` por hito |
| Eventos | fecha, título, descripción, dirección, URL e imagen/icono | `info_eventos` (`index.php:148-180`, salida `519-554`) | `?new=eventos` edita dichos campos y `activo` | Toggle global `events` + `activo` por evento |
| Más Info | título, descripción, dirección, URL e icono | `info_otra` (`index.php:186-202`, salida `581-595`) | `?new=masinfo` edita dichos campos y `activo` | Toggle global `wedding` + `activo` por ítem; el contenedor también aparece si hay transferencia |
| Regalos | títulos de transferencia/cuentas y datos bancarios | helper/config de regalos + `cliente` (`index.php:207-228`, `604-659`) | `?new=regalos` permite títulos y controles de visualización; `?new=datos` mantiene datos bancarios | Controles específicos `mostrar_lista_regalos`, `mostrar_transferencia_regalos` y disponibilidad funcional |

Conclusión: **no hay que migrar los hitos/fichas existentes**. UNI-044 debe añadir
configuración solo para cabeceras editoriales de sección y, cuando corresponda, para
la visibilidad de esas cabeceras. Debe reutilizar el patrón global `info_mostrar` que
carga `header.php:3-11` sin cambiarlo en esta auditoría.

## 5. Matriz de secciones ocultables

| Sección | Guarda pública actual | Control admin actual | Brecha |
|---|---|---|---|
| Portada | Siempre se renderiza | No hay toggle en el menú | Evaluar toggle global solo después de definir qué reemplaza la portada; no es prioridad |
| Cronómetro | Clase/contador condicionado por `cronometro` | Sí | Los botones viven en el contenedor aun cuando el contador está apagado; revisar aparte, sin mezclar con textos |
| Nosotros | `in_array('about', $secciones)` | Sí | Falta editar el rótulo «Acerca de»; título principal se deriva de nombres |
| Historia | `in_array('story', $secciones)` | Sí | Falta editar/omitir título y bajada de cabecera |
| Fotos | `in_array('gallery', $secciones)` | Sí | No se detectó cabecera editorial visible; sin brecha de texto |
| Eventos | `in_array('events', $secciones)` | Sí | Falta editar/omitir título y bajada de cabecera |
| Más Info | `in_array('wedding', ...) || $mostrarTransferenciaRegalos` | Sí para `wedding`; regalos tiene control separado | El toggle `wedding` no garantiza ocultar el contenedor si transferencia está activa; documentar antes de cambiar |
| Contacto | `in_array('contact', $secciones)` | Sí, pero el enlace de edición es `#` | Crear pantalla de contenido, no otro toggle |
| Regalos | Condiciones específicas del helper/config | Sí | Sin brecha prioritaria; evitar cambios funcionales |
| RSVP | Siempre se renderiza | Configura modo, no visibilidad global | Fuera de alcance por requisito explícito |
| Pie | Siempre se renderiza | Ninguno | Mantener salvo decisión contractual |

El menú activo define los toggles en `admin7WZiwEM3XY/menu.php:110-122`; Portada es
solo enlace, mientras Nosotros, Historia, Eventos, Más Info y Contacto tienen toggle.

## 6. Lista priorizada de conversiones

1. **P0 — Historia:** `Nuestra Historia` y la frase exacta que motivó UNI-044.
   Añadir título y bajada a `?new=historia`; admitir bajada vacía; conservar el toggle
   global existente y los `activo` de cada hito.
2. **P1 — Nosotros:** configurar el rótulo común `Acerca de` (o una plantilla segura
   con nombre), conservando nombres/biografías y toggle existentes.
3. **P1 — Eventos:** configurar título y bajada introductoria; conservar fichas y
   toggle existentes.
4. **P2 — Más Info:** configurar título y bajada en `?new=masinfo`; especificar el
   comportamiento combinado con transferencia antes de implementar visibilidad.
5. **P2 — Contacto:** crear destino admin real para editar título/bajada. El microcopy
   del formulario debe centralizarse, no necesariamente exponerse como campos libres.
6. **P3 — Portada/global:** eliminar de forma segura los tres fallbacks de ejemplo y
   parametrizar el `<title>`; los campos visibles normales ya son editables.
7. **P4 — Interfaz:** centralizar navegación y CTAs para consistencia/i18n. No bloquear
   las fases editoriales por estos rótulos.
8. **P4 — Pie y regalos:** someter pie a decisión comercial/legal; mantener regalos
   sin cambios porque sus títulos y visibilidad principales ya están administrados.

## 7. Propuesta de fases de implementación

### Fase 1 — Historia (próxima fase recomendada)

- Definir contrato de configuración para `titulo`, `subtitulo/bajada` y visibilidad de
  la cabecera, con defaults compatibles con sitios existentes.
- Exponerlo en `admin/index.php?new=historia` (ruta real:
  `admin7WZiwEM3XY/index.php?new=historia`).
- Reutilizar el toggle `story` para toda la sección; un subtítulo vacío debe ocultar
  su nodo sin ocultar los hitos.
- Diseñar migración y rollback, pero ejecutarlos únicamente en un ticket posterior.

### Fase 2 — Nosotros

- Añadir un rótulo/plantilla común para «Acerca de» y decidir si el encabezado derivado
  de nombres necesita un título alternativo.
- Conservar `activo` por persona y toggle global `about`; no duplicar campos por persona
  sin un caso de uso confirmado.

### Fase 3 — Eventos

- Añadir título y bajada de sección al admin Eventos.
- Mantener intactos los campos de cada evento y el selector de CTA derivado de la URL.
- Permitir título/bajada vacíos con render condicional.

### Fase 4 — Portada, Contacto, Más Info y regalos

- Portada: revisar fallbacks y `<title>`; evaluar, no asumir, un toggle de portada.
- Contacto: crear pantalla admin para título/bajada y enlazarla desde el menú existente.
- Más Info: añadir cabecera editable y resolver explícitamente la interacción entre
  `wedding` y transferencia.
- Regalos: solo validar integración; no cambiar helper, tienda, datos bancarios ni
  comportamiento de visualización en este alcance.

### Fase 5 — Microcopy transversal

- Centralizar navegación, botones, CTAs y mensajes de formulario en un catálogo de
  interfaz/i18n si el producto requiere variantes de tono o idioma.
- Mantener estos textos separados del contenido editorial por sitio para evitar un
  admin sobrecargado y traducciones inconsistentes.

## 8. Riesgos y criterios sugeridos para el ticket de implementación

- **Compatibilidad:** sitios existentes deben conservar el texto actual mediante
  defaults; no convertir ausencia de configuración en secciones vacías.
- **Doble visibilidad:** distinguir «ocultar sección» de «ocultar título/bajada».
- **Datos vacíos:** definir si vacío significa ocultar, heredar default o error; se
  recomienda vacío = ocultar el nodo y ausencia de registro = default compatible.
- **Seguridad de salida:** escapar los nuevos campos según contexto HTML y decidir de
  forma explícita si se admiten saltos de línea, pero no HTML arbitrario.
- **Regalos/Más Info:** probar combinaciones `wedding` apagado/encendido y transferencia
  apagada/encendida antes de cambiar el contenedor.
- **No regresión:** verificar escritorio/móvil, navegación a anclas y sitios con una o
  dos personas; mantener RSVP, WhatsApp y tienda fuera del diff.

## 9. Recomendación final

La siguiente unidad debe ser **Historia únicamente**: convertir `Nuestra Historia` y
su bajada en campos administrables desde `?new=historia`, conservar el toggle `story`
y permitir omitir la bajada. Es el cambio de mayor valor, menor superficie y validación
más directa. Después deben seguir Nosotros y Eventos, reutilizando el mismo contrato y
patrón de render condicional ya probado.
