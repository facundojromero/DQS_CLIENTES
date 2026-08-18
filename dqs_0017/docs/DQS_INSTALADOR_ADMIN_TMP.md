> Auditoría documental generada sin ejecutar PHP, Node, instaladores ni SQL. Fuente principal: `docs/referencia_planes/`. No se modificaron archivos productivos ni referencias.

# DQS - Instalador y `admin_tmp`

## Cómo funciona el instalador detectado

El archivo común `admin_tmp/crear_carpeta_admin.php` actúa como instalador/renombrador de administración:

1. Genera un nombre aleatorio con prefijo `admin` y 10 caracteres.
2. Calcula el directorio base como `__DIR__ . '/../'`.
3. Crea el directorio admin final.
4. Toma archivos de `admin_tmp` con `glob(__DIR__ . '/*')`.
5. Mueve solo archivos sueltos con `rename(...)`; no mueve subcarpetas.
6. Incluye `conexion.php` desde raíz o ruta relativa.
7. Inserta `nombre_carpeta` en `admin_config`.
8. Muestra mensajes de depuración y consulta si existe `admin_config`.

## Qué hace `admin_tmp`

`admin_tmp` es una carpeta temporal de administración que se transforma en una carpeta admin con nombre no predecible. La intención es ocultar/variar la ruta del admin por seguridad operacional.

## Cómo se renombra o mueve el admin

No renombra la carpeta completa: crea una carpeta nueva y mueve archivos individuales desde `admin_tmp` al nuevo directorio. Esto deja subdirectorios dentro de `admin_tmp` sin mover si solo se usa `is_file`.

## Qué archivos crea/copia/mueve

- Crea carpeta `adminXXXXXXXXXX`.
- Mueve archivos sueltos desde `admin_tmp`.
- No copia recursivamente carpetas como `whatsapp/` o `invitaciones/` según la lógica observada.
- Registra ruta en `admin_config`.

## Qué base de datos inicializa

El instalador PHP no crea schema; asume conexión existente y tabla `admin_config`. Los SQL de inicialización están en carpetas separadas:

- `basico/base_datos/*.sql`.
- `basico_form/admin_tmp/base_datos.sql` y `_BD/nuevo_cliente_v2.sql`.
- `oro_codigo/__base_datos/*.sql`.
- `oro_form/BASE DE DATOS.sql`.

## Riesgos actuales

1. Ejecutarlo mueve archivos de referencia/productivos y puede dejar `admin_tmp` incompleto.
2. No es idempotente: una segunda ejecución puede fallar o registrar múltiples admins.
3. No mueve subcarpetas, por lo que módulos admin anidados pueden quedar inaccesibles.
4. Imprime rutas y SQL en pantalla.
5. Inserta SQL con interpolación directa de `$nombreCarpeta`.
6. Requiere `admin_config` ya creada.
7. No valida colisiones de carpeta.

## Recomendación para futuro instalador unificado

- Separar instalación en modo dry-run y modo apply.
- Crear migraciones versionadas, no scripts SQL sueltos.
- Generar admin seguro, pero registrar metadata con prepared statements.
- Copiar/mover recursivamente con lista blanca y manifest.
- Nunca ejecutar instalador desde documentación o referencia.
- Añadir configuración inicial: `plan_servicio`, `rsvp_modo`, `fuente_envios_whatsapp`, `whatsapp_enabled`, `regalos_enabled`.
- No migrar datos ni fusionar tablas en la primera etapa.
