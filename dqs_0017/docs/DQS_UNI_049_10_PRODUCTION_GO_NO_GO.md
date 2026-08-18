# UNI-049.10 — Checklist de producción GO/NO-GO

**Regla:** todos los ítems obligatorios deben estar marcados. `WARN` exige causa,
impacto y aceptación del responsable. `BLOCKED`, `FAILED`, resultado ambiguo o una
casilla no verificable implica **NO-GO**. Este checklist no sustituye el manual
`DQS_UNI_049_10_INSTALLER_FINAL_OPERATIONS.md`.

## Acta mínima (sin secretos)

- Fecha/hora UTC: ____________________
- Release/commit: ____________________
- Host/entorno (alias no sensible): ____________________
- PHP web / CLI: ____________________ / ____________________
- Operador: ____________________  Aprobador: ____________________
- Resultado: ☐ GO  ☐ NO-GO
- Motivo/`WARN` aceptados (censurado): ________________________________

No anotar host DB, nombre DB, usuario, passwords, gate, cookies, paths absolutos,
comandos con secretos, stdout/stderr crudo ni HTML sin sanear.

## A. Antes de iniciar

### Release y hosting

- [ ] Es una instalación nueva, no actualización, reparación o migración.
- [ ] Release exacto e íntegro desplegado; no hay cambios fuera de scope.
- [ ] Dominio y certificado HTTPS válidos; HTTP no se usará para credenciales.
- [ ] PHP CLI y web compatibles, extensiones disponibles y misma aplicación.
- [ ] No hay despliegues, cron de mantenimiento ni otro operador concurrente.
- [ ] Existe acceso SSH/hPanel para revocar el gate si el navegador falla.

### DB, runtime y target

- [ ] DB creada manualmente en Hostinger, dedicada y vacía.
- [ ] Usuario DB dedicado y limitado a esa DB con privilegios de instalación.
- [ ] Credenciales guardadas solo en gestor/canal privado y nunca en evidencia.
- [ ] Runtime real fuera del repo y document root, sin symlink, privado y
      escribible por PHP web (`0700`/archivos `0600` cuando aplique).
- [ ] CLI y web resuelven el mismo runtime; `--status --debug-safe` da `valid: yes`.
- [ ] Target root real, escribible, externo al repo y resuelto por policy interna.
- [ ] Existe exactamente una plantilla admin válida; no se modificó `admin_tmp` ni
      el admin activo existente.
- [ ] En target nuevo no existen `install.lock` ni `.install.lock.pending`; nadie
      los borró de una instalación previa para satisfacer esta casilla.
- [ ] Existe plan de descarte/restauración y ventana suficiente sin apresurarse.

### Checks de entrada

- [ ] `php -l tools/dqs_install_web_audit.php` aprobado.
- [ ] `php tools/dqs_install_web_audit.php` termina con resultado satisfactorio.
- [ ] Diff de scope aprobado: solo documentación UNI-049.10 (y links README si
      correspondiera), sin cambios funcionales ni CLI UNI-048.
- [ ] Sin gate, HTTPS `/install/` responde bloqueado (`403`/pantalla de bloqueo).
- [ ] Se acordó registrar únicamente estados, códigos y conteos censurados.

**Decisión de entrada:** ☐ GO  ☐ NO-GO — Aprobador: __________ Hora: _______

## B. Gate one-time

- [ ] Gate creado con TTL corto desde la raíz del release.
- [ ] Secreto copiado una sola vez por canal privado; no está en URL/log/captura.
- [ ] Login realizado por formulario HTTPS POST.
- [ ] El secreto no aparece en URL ni HTML y no puede reutilizarse.
- [ ] Solo existe una sesión/operador activo.

**Abortar si:** runtime inválido, HTTP, gate reutilizable/expirado, más de cinco
intentos, exposición del secreto o comportamiento distinto al esperado.

## C. DB / Preflight

- [ ] Credenciales introducidas solo en el formulario; no se capturó la pantalla.
- [ ] Preflight devuelve `OK` o cada `WARN` fue comprendido y aceptado.
- [ ] DB confirmada vacía y accesible; paquete/runtime/permisos aceptados.
- [ ] Schema queda disponible únicamente después del resultado válido.

**Abortar si:** DB no vacía, conexión fallida, secreto expuesto, path/credencial en
salida, `BLOCKED`/`FAILED`, `WARN` inexplicado o estado ambiguo.

## D. Schema

- [ ] Dry-run `OK`/`WARN` revisado y sin cambios en DB.
- [ ] Apply habilitado solo por ese dry-run vigente.
- [ ] Checkbox marcado y frase exacta `INSTALAR SCHEMA` introducida.
- [ ] Apply ejecutado una sola vez y resultado `OK`/`WARN` aceptado.
- [ ] No se intentó repetir apply ni editar schema/seeds para continuar.

**Abortar si:** dry-run modifica DB; apply se habilita sin precondiciones; timeout,
desconexión o JSON inválido; schema parcial; rerun posible; cualquier estado no
explicado. Un schema parcial exige DB nueva vacía, no limpieza manual.

## E. Bootstrap

- [ ] Paso disponible solo tras Schema apply aceptado.
- [ ] Password inicial de al menos 6 caracteres; `123456` se acepta como valor temporal y se cambiará después.
- [ ] Datos/passwords se trataron como secretos y no quedaron en evidencia.
- [ ] Dry-run `OK`/`WARN`; antes de apply: `user=0`, `cliente=0`, `admin_config=0`.
- [ ] Checkbox y frase exacta `CREAR BOOTSTRAP`.
- [ ] Apply único `OK`/`WARN`; después: `user=1`, `cliente=1`, `admin_config=0`.
- [ ] Repetición bloqueada.

**Abortar si:** conteos distintos, password reflejado, etapa accesible antes de
Schema, repetición permitida, `BLOCKED`/`FAILED` o resultado ambiguo.

## F. Admin publish

- [ ] Paso disponible solo tras Bootstrap apply aceptado.
- [ ] Template, target y slug proceden de policy interna, no del navegador.
- [ ] Dry-run `OK`/`WARN`; no crea carpeta, `admin_config` ni lock.
- [ ] Checkbox y frase exacta `PUBLICAR ADMIN`.
- [ ] Apply único `OK`/`WARN`; existe una carpeta admin publicada.
- [ ] `admin_config=1`; `install.lock` aún ausente; repetición bloqueada.

**Abortar si:** target/template ambiguo, destino previo/symlink, dry-run muta,
carpeta o fila no única, aparece lock prematuro, rerun posible o salida ambigua.

## G. Finalize

- [ ] Paso disponible solo tras Admin publish apply aceptado.
- [ ] Dry-run `OK`/`WARN` y auditoría/conteos revisados.
- [ ] Antes del apply no existen `install.lock` ni `.install.lock.pending`.
- [ ] Checkbox y frase exacta `FINALIZAR INSTALACION`.
- [ ] Apply ejecutado una sola vez; pantalla final segura mostrada una vez.
- [ ] Apply devuelve `OK`/`WARN` aceptado y crea `install.lock`.

**Abortar si:** invariantes no coinciden, existe lock previo, queda resultado
desconocido o pending residual. No borrar ningún lock para reintentar.

## H. Verificación después de Finalize

- [ ] `user=1`.
- [ ] `cliente=1`.
- [ ] `admin_config=1`.
- [ ] `invitados=0`.
- [ ] `productos=0`.
- [ ] `regalos=0`.
- [ ] `install.lock` existe en el target correcto.
- [ ] `.install.lock.pending` está ausente.
- [ ] La carpeta admin publicada existe, es válida y coincide con `admin_config`.
- [ ] Una nueva petición HTTPS a `/install/` queda bloqueada.
- [ ] Gate revocado/inhabilitado aunque ya haya sido consumido.
- [ ] No se modificaron `conexion.php`, `admin_tmp`, admin activo,
      `database/install/*` ni CLI UNI-048 durante la operación.

## I. Seguridad y limpieza

- [ ] No se capturaron ni compartieron passwords, gate, cookies o credenciales.
- [ ] Evidencias conservadas contienen solo estados/conteos y están censuradas.
- [ ] Copias locales, portapapeles, HTML y evidencias sensibles fueron eliminados.
- [ ] Todo secreto potencialmente expuesto fue rotado.
- [ ] `install.lock` se conserva; no hay plan de borrarlo para “reactivar”.
- [ ] Runtime y locks de producción no se borran sin motivo claro, autorización y
      procedimiento específico.
- [ ] Si fue un ensayo: DB/usuario temporal, admin de prueba, runtime de prueba y
      evidencia sensible fueron destruidos.
- [ ] Si fue producción: no se destruyeron DB, admin ni runtime productivos.

## J. Criterios consolidados

### NO-GO / abortar

Marcar **NO-GO** ante cualquier casilla obligatoria pendiente, secreto expuesto,
DB no vacía, diferencia de conteos, runtime/target inseguro, mutación en dry-run,
etapa fuera de orden, rerun de apply, `BLOCKED`/`FAILED`, `WARN` no aceptado,
timeout/desconexión/respuesta ambigua, admin inconsistente, lock ausente o pending
residual. Revocar el gate, aislar el acceso, no repetir apply y escalar con
evidencia censurada. No reparar DB o locks manualmente.

### GO / aprobación

Solo hay **GO** cuando todas las etapas terminaron en orden, los `WARN` están
documentados y aceptados, los nueve invariantes finales coinciden, `/install/`
está bloqueado, el gate está revocado, no hubo exposición de secretos y el scope
permanece limpio.

**Decisión final:** ☐ GO  ☐ NO-GO

- Operador / fecha UTC: ______________________________
- Aprobador / fecha UTC: ______________________________
- Referencia de evidencia censurada: ______________________________

Con GO firmado, **UNI-049 queda cerrada**. No abrir UNI-049.11 salvo bug concreto,
reproducible y acotado. Toda mejora futura debe registrarse como una unidad nueva
y separada.
