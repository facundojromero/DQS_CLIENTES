# UNI-049.10 — Operación final del instalador web en producción

## 1. Propósito y autoridad operativa

Este manual cierra UNI-049 y describe cómo ejecutar **una instalación nueva** de
DQS desde `/install/` en el hosting productivo. Debe usarse junto con el checklist
GO/NO-GO de UNI-049.10. No es un procedimiento de actualización, reparación,
migración ni recuperación de una instalación existente.

El instalador web es un orquestador controlado de los CLI ya validados en UNI-048:

1. protege el acceso con un gate de un solo uso;
2. comprueba runtime, paquete, conexión y que la DB esté vacía;
3. ejecuta dry-run y apply del schema y sus seeds canónicos;
4. crea el bootstrap inicial (`user=1` y `cliente=1`);
5. publica una única carpeta admin y crea `admin_config=1`;
6. audita el resultado y finaliza creando atómicamente `install.lock`.

La web no escribe SQL directamente, no acepta comandos o paths libres y no
reimplementa los CLI. Cada mutación requiere un dry-run vigente, checkbox y frase
exacta. Un estado `BLOCKED` o `FAILED`, una respuesta ambigua o un `WARN` no
explicado detienen el avance.

## 2. Qué no hace

El flujo no:

- crea la cuenta de hosting, dominio, certificado TLS, DB o usuario MySQL;
- sube/descomprime el release, configura PHP o corrige permisos del hosting;
- migra datos ni instala sobre una DB con tablas o datos;
- elimina o repara una instalación previa;
- borra `install.lock` ni `.install.lock.pending`;
- cambia `conexion.php`, el admin fuente/activo ni los SQL canónicos por decisión
  del operador;
- activa, rediseña o modifica tienda, RSVP, WhatsApp, regalos o lógica pública;
- conserva ni devuelve passwords para recuperarlos después;
- garantiza rollback completo: en particular, DDL puede quedar parcial si Schema
  falla. En ese caso se descarta la DB y se comienza con otra vacía.

## 3. Trabajo manual previo en Hostinger

Estas acciones se realizan en hPanel/SSH y quedan fuera del instalador:

1. Apuntar el dominio al hosting y habilitar HTTPS válido.
2. Seleccionar una versión de PHP CLI/web compatible y las extensiones requeridas
   por Preflight; confirmar que CLI y web ven el mismo release.
3. Subir el release íntegro al document root previsto, sin mezclarlo con una
   instalación anterior, y conservar la estructura y permisos.
4. Crear en **Bases de datos MySQL** una DB nueva y vacía, un usuario dedicado y
   asignarlo únicamente a esa DB con los privilegios requeridos para instalar.
   Guardar host, nombre, usuario y password en un gestor de secretos, no en notas,
   tickets, capturas ni comandos compartidos.
5. Preparar un runtime privado real, escribible por PHP, fuera del repositorio y
   de `public_html`, sin symlink y con permisos restrictivos. En la disposición
   estándar de Hostinger el helper puede crear su fallback privado junto a
   `public_html`; alternativamente, configurar el mismo
   `DQS_INSTALLER_RUNTIME_DIR` para PHP CLI y PHP web.
6. Preparar el **target root del admin**, también real, escribible y fuera del
   repositorio. Definir `DQS_INSTALLER_ADMIN_TARGET_ROOT` de forma visible para el
   proceso web o crear `admin_target_root` dentro del runtime privado. No enviar el
   target desde el navegador ni usar un symlink.
7. Confirmar que existe exactamente una plantilla admin válida seleccionable por
   la policy interna y que el destino no contiene la carpeta que se publicará.
8. Confirmar backups/plan de descarte, ventana operativa sin concurrencia y acceso
   SSH/hPanel para revocar el gate. El backup no convierte una DB usada en apta:
   la entrada debe ser una DB vacía.

No crear manualmente `conexion.php`, filas iniciales, schema, seeds, carpeta admin
publicada o locks para “ayudar” al flujo. Si Preflight no valida el ambiente, se
corrige el hosting antes de continuar.

## 4. Prerrequisitos y línea base

Antes del GO deben cumplirse todos estos puntos:

- release exacto desplegado, sin cambios locales inesperados;
- HTTPS obligatorio y `/install/` inicialmente bloqueado;
- PHP web/CLI y extensiones aceptados por Preflight;
- DB dedicada, alcanzable y realmente vacía (cero tablas de aplicación);
- credenciales verificadas, disponibles solo para el operador autorizado;
- runtime privado fuera de repo/document root, no symlink, legible/escribible por
  PHP y con permisos privados (directorio `0700`, archivos privados `0600` cuando
  el sistema lo permita);
- target root inequívoco, externo al repo, real y escribible;
- ausencia de `install.lock` y `.install.lock.pending` únicamente porque se trata
  de un target nuevo, nunca porque se hayan borrado de una instalación existente;
- una sola ventana/sesión de instalación y ningún despliegue simultáneo;
- auditor estático y checklist GO/NO-GO aprobados.

Registrar solo fecha UTC, identificador no sensible del release/host, versión PHP,
estados y conteos. No registrar paths absolutos, comandos con argumentos secretos,
HTML sin revisar ni stdout/stderr crudo.

## 5. Gate de un solo uso

Desde la raíz del release, validar de forma censurada el runtime y crear un gate
corto, por ejemplo de 30 minutos:

```bash
php tools/dqs_install_web_gate_prepare.php --status --debug-safe
php tools/dqs_install_web_gate_prepare.php --create --ttl-minutes=30
```

Si se usa un runtime explícito, el CLI y el proceso web deben resolver exactamente
el mismo directorio. El secreto se muestra una sola vez: copiarlo directamente a
un canal privado, no a la línea de comandos, URL, captura, ticket o evidencia.
Acceder a `/install/` por HTTPS e introducirlo mediante POST en el formulario. El
login lo consume; no puede reutilizarse. Hay un máximo de cinco intentos, 10
minutos de inactividad y 30 minutos de sesión absoluta.

Para cancelar o ante cualquier duda, revocar desde la raíz del release:

```bash
php tools/dqs_install_web_gate_prepare.php --revoke
```

Crear/revocar el gate limpia inputs privados de sesiones interrumpidas. Revocar no
revierte mutaciones ya aplicadas y no autoriza a borrar locks.

## 6. Flujo completo en `/install/`

### Paso 1 — DB / Preflight

Introducir host, DB, usuario y password exclusivamente en el formulario HTTPS.
Exigir `OK` o revisar y aceptar conscientemente cada `WARN`. La DB debe resultar
vacía. Si está usada, inaccesible o el runtime no es seguro: **abortar**, no forzar.

### Paso 2 — Schema

1. Ejecutar dry-run y revisar alcance/checks.
2. Confirmar que no hubo cambios.
3. Solo con resultado vigente, marcar el checkbox y escribir `INSTALAR SCHEMA`.
4. Ejecutar apply una sola vez y exigir `OK` o `WARN` comprendido.

No recargar ni repetir apply. Ante timeout, desconexión, JSON inválido o estado
parcial, tratar el resultado como desconocido. No intentar limpiar tablas a mano:
revocar, conservar evidencia censurada y reemplazar la DB por una nueva vacía.

### Paso 3 — Bootstrap

Introducir los datos iniciales y passwords sin reutilizarlos en evidencia. El
password inicial admite desde 6 caracteres; `123456` es un valor operativo temporal
válido que el cliente o administrador debe cambiar después. Ejecutar dry-run; antes de apply los conteos deben seguir `user=0`, `cliente=0` y
`admin_config=0`. Aplicar una sola vez con checkbox y `CREAR BOOTSTRAP`. Verificar
`user=1`, `cliente=1` y `admin_config=0`.

### Paso 4 — Admin publish

El template, target y slug se resuelven por policy del servidor, no por el POST.
Revisar el dry-run: no debe crear carpeta, fila ni lock. Aplicar una sola vez con
checkbox y `PUBLICAR ADMIN`. Verificar una única carpeta admin publicada,
`admin_config=1` y todavía ningún `install.lock`.

### Paso 5 — Finalize

Ejecutar dry-run y revisar todos los checks y conteos finales. Debe confirmar el
schema esperado, bootstrap único, admin coherente y tablas operativas inicialmente
vacías. Aplicar una sola vez con checkbox y `FINALIZAR INSTALACION`.

El éxito crea `install.lock` mediante `.install.lock.pending` y rename atómico.
La pantalla de cierre se muestra una vez; no contiene passwords. No refrescar con
la expectativa de continuar: la siguiente petición debe bloquear `/install/`.

## 7. Verificación final obligatoria

Verificar mediante una consulta segura/read-only o las evidencias censuradas del
Finalize, sin copiar credenciales a la evidencia:

| Invariante | Esperado |
|---|---:|
| filas en `user` | `1` |
| filas en `cliente` | `1` |
| filas en `admin_config` | `1` |
| filas en `invitados` | `0` |
| filas en `productos` | `0` |
| filas en `regalos` | `0` |
| `install.lock` en el target | existe |
| `.install.lock.pending` | ausente |
| carpeta admin publicada indicada por `admin_config` | existe, es única y válida |
| nueva petición HTTPS a `/install/` | bloqueada (`403`/pantalla de bloqueo) |

Una diferencia es **NO-GO**. No maquillar conteos ni locks manualmente. Aislar el
sitio y aplicar el procedimiento de incidente/recuperación autorizado.

## 8. Cierre seguro y custodia

1. Revocar el gate aunque figure consumido y confirmar estado no habilitado.
2. Mantener `install.lock` de forma permanente. No borrarlo para reabrir el
   instalador ni como prueba rutinaria.
3. Tratar `.install.lock.pending` como señal de estado indeterminado: no borrarlo
   sin diagnóstico, autorización, backup y motivo operativo documentado.
4. Eliminar del equipo/canal del operador secretos copiados, capturas, HTML,
   exports, comandos y evidencias sensibles. Conservar solo el acta censurada.
5. Rotar cualquier secreto que haya sido expuesto. No compartir passwords de DB,
   admin o gate, ni siquiera después del cierre.
6. En un ensayo E2E, destruir la DB temporal, usuario temporal, carpeta admin de
   prueba, runtime de prueba y evidencia sensible. **No destruir la DB, runtime ni
   admin de producción** después de una instalación aprobada.
7. No borrar el runtime/lock de producción sin una causa operativa clara,
   autorización y runbook específico. El runtime debe permanecer fuera del área
   pública y con permisos mínimos.

## 9. Declaración de cierre

Con el checklist aprobado y todos los invariantes finales satisfechos, **UNI-049
queda cerrada operativamente**. No abrir UNI-049.11 salvo que exista un bug
concreto, reproducible y acotado del instalador. Refactors, mejoras de UX, nuevas
capacidades, migraciones o automatizaciones futuras deben planificarse como
unidades nuevas y separadas, sin reabrir este cierre.
