# UNI-050 — instalación por cliente con publicación automática

## Decisión operativa

El instalador web publica el admin directamente en la raíz pública del proyecto (`public_html` en producción). El destino no se recibe por `POST`: se deriva de la raíz del checkout validada por el servidor. El slug continúa siendo aleatorio y generado en servidor; el publisher rechaza destinos existentes y symlinks, copia mediante un staging vecino y publica con `rename`. Todas las carpetas copiadas quedan en `0755` y los archivos en `0644`. La plantilla activa se usa únicamente como origen de lectura y `admin_tmp` sigue expresamente prohibido.

Finalize usa el archivo privado de conexión creado y validado por Preflight. No lo incluye ni lo ejecuta: extrae exclusivamente los cuatro literales admitidos y vuelve a validar la conexión. Solo puede reemplazar, byte por byte, este placeholder canónico:

```php
<?php
die("Instalación pendiente. Configurar conexion.php final después de crear la DB del cliente.");
```

Una configuración real, un contenido distinto o un symlink bloquean Finalize. El archivo final se prepara con nombre aleatorio, modo `0644`, y se renombra atómicamente sobre el placeholder. Conserva `$servername`, `$username`, `$password`, `$dbname`, `$conn` y `mysqli_set_charset($conn, "utf8mb4");`. Los valores no se incorporan a reportes, sesión, HTML ni `install.lock`.

Finalize prepara también `.install.lock.pending`; publica la conexión y, a continuación, renombra el lock. Si falla el último rename intenta restaurar atómicamente el placeholder y limpia temporales. Un `install.lock` existente, un pending existente, un admin existente o una instalación ya finalizada siguen bloqueando cualquier repetición.

## Runbook por cliente

1. Copiar el proyecto nuevo a la raíz pública. Antes de habilitar el gate, reemplazar el `conexion.php` de la copia por el placeholder canónico anterior. No usar una conexión real heredada.
2. Preparar/habilitar el gate privado como indica el runbook UNI-049 y abrir `/install/`.
3. Completar Preflight, Schema, Bootstrap, Admin publish y Finalize, respetando dry-run y confirmación de cada apply.
4. Registrar el slug que muestra el instalador. No crear ni mover carpetas admin manualmente.
5. Comprobar home, `/<slug>/login.php` y el bloqueo de `/install/`.
6. Verificar `install.lock` presente, `.install.lock.pending` ausente, permisos `0755/0644`, y conteos `user=1`, `cliente=1`, `admin_config=1`, `invitados=0`, `productos=0`, `regalos=0`.

No queda ningún paso manual posterior a Finalize. La preparación inicial del placeholder y del gate privado continúa siendo parte del despliegue previo, no una reparación posterior.
