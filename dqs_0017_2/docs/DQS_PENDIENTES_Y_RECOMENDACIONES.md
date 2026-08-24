# DQS - Pendientes, riesgos y recomendaciones

## Limitaciones de la auditoría

- No se consultó una base de datos real ni se tocaron datos reales.
- No existe schema completo versionado en el repo; solo dos migraciones parciales de invitados.
- La estructura de tablas fue inferida desde consultas en código, por lo que tipos, claves e índices deben validarse contra MySQL.
- No se ejecutó la aplicación web ni flujos con datos reales.
- No se expusieron credenciales ni secretos en esta documentación.

## Riesgos de seguridad

1. **Secretos en archivos del repo.** La conexión MySQL está definida en código PHP. Recomendación: mover a variables de entorno o archivo no versionado y rotar credenciales si el repo estuvo compartido.
2. **SQL injection potencial.** Hay mezcla de prepared statements y SQL interpolado. Recomendación: issue para reemplazar interpolaciones por prepared statements en endpoints públicos/admin.
3. **Autorización inconsistente.** Verificar que todos los archivos en admin y endpoints AJAX validen sesión/permisos antes de ejecutar acciones.
4. **Exposición de carpeta admin.** La carpeta tiene nombre aleatorio, pero eso no reemplaza autenticación/autorización.
5. **Subidas de archivos.** Revisar validación de tipo MIME, extensión, tamaño, nombres y ubicación de uploads.
6. **Emails/WhatsApp.** Asegurar que tokens SMTP/API/WhatsApp no queden versionados ni impresos en errores.
7. **Passwords.** Confirmar uso de `password_hash`/`password_verify` y política de cambio/recuperación.

## Riesgos de datos

1. **Ausencia de migraciones completas.** Dificulta reproducir ambientes y validar cambios.
2. **Modelo multi-cliente incompleto.** Muchas tablas parecen singleton; escalar a múltiples eventos puede mezclar datos.
3. **Confirmación duplicada.** Los campos de cabecera y detalle de invitados pueden divergir.
4. **Regalos duplicados en texto y detalle.** `regalos.productos` y `regalos_detalles` pueden desincronizarse.
5. **Carrito por sesión sin expiración visible.** Puede acumular filas huérfanas.
6. **Estados de WhatsApp duplicados.** Varias tablas registran envíos/estados con semántica parecida.
7. **Faltan constraints.** No se detectaron FKs/unique/indexes desde repo.

## Duplicación de lógica

- Front público y tienda repiten consultas de `info_casamiento` e `info_eventos`.
- Emails están repartidos en varios scripts con plantillas HTML similares.
- WhatsApp existe en PHP y Node con responsabilidades superpuestas.
- Datos de cliente/cotización se consultan desde múltiples archivos con `user_id=1`.
- Confirmación de invitados aparece en RSVP, dashboard, exportación y gestión admin sin capa común.

## Partes difíciles de mantener

- Archivos PHP mezclan SQL, validación, lógica de negocio y HTML.
- No hay router/controladores ni capa de repositorio.
- No hay tests automatizados detectados.
- Nombres de tablas/campos con errores históricos (`intivados_acompanante`) dificultan comprensión.
- Assets generados/subidos conviven con código y respaldos `.bkp`.
- Dos herramientas Node con `node_modules` versionado aumentan tamaño y complejidad.

## Funcionalidades parcialmente implementadas

- **Pagos/comprobantes:** confirmación manual existe, pero no comprobantes ni gateway.
- **Multi-cliente:** hay `user`, `cliente`, `admin_config`, pero no aislamiento consistente.
- **WhatsApp:** hay envío/reenvío y estado API, pero se requiere consolidar operación, configuración y bitácoras.
- **Analytics:** dashboard consulta visitas/sesiones, pero la escritura/esquema no está completamente documentada.
- **Regalo libre:** helper crea/configura ajustes fuera del sistema de migraciones.
- **Fotos:** carga/gestión existe, pero falta política clara de almacenamiento/limpieza.

## Recomendaciones para próximos issues

### Prioridad alta

1. **Crear dump/schema base versionado.** Generar `database/schema.sql` desde ambiente controlado, sin datos reales, con PKs, FKs e índices.
2. **Mover secretos fuera del repo.** Variables de entorno + `.env.example` sin valores reales + rotación de credenciales.
3. **Auditar SQL injection.** Convertir consultas interpoladas a prepared statements, empezando por endpoints públicos y acciones admin destructivas.
4. **Asegurar endpoints admin.** Middleware/include común de sesión y autorización.
5. **Definir alcance multi-cliente.** Decidir si DQS será single-event por deploy o multi-tenant; ajustar modelo y consultas.

### Prioridad media

6. **Unificar modelo de confirmación.** Definir fuente de verdad entre cabecera `invitados` y detalle `invitados_listado_mesa`.
7. **Normalizar regalos.** Usar `regalos_detalles` como fuente y dejar `regalos.productos` como snapshot controlado o eliminar en futura migración.
8. **Consolidar WhatsApp.** Elegir PHP o Node como flujo oficial; documentar colas, estados y reintentos.
9. **Centralizar emails.** Crear servicio/plantillas reutilizables y configuración SMTP segura.
10. **Agregar limpieza de carritos.** Job o tarea para eliminar carritos viejos por sesión.

### Prioridad baja / mantenimiento

11. **Separar vistas y lógica.** Refactor incremental por módulo cuando se pidan cambios funcionales.
12. **Agregar tests de regresión.** Empezar por RSVP y checkout.
13. **Limpiar backups y dependencias versionadas.** Revisar `.bkp`, `node_modules` y zips, sin borrar hasta confirmar política.
14. **Documentar operación.** Guías para publicar evento, cargar invitados, enviar WhatsApp, revisar pagos y exportar datos.
15. **Inventario de assets subidos.** Definir tamaño, formatos permitidos y ciclo de vida.

## Issues sugeridos concretos

- `SEC-001: Externalizar configuración y secretos de conexión`.
- `SEC-002: Reemplazar SQL interpolado por prepared statements`.
- `DB-001: Generar schema.sql sin datos reales y documentar migraciones`.
- `DB-002: Agregar índices y constraints para invitados, regalos y carrito`.
- `RSVP-001: Definir fuente de verdad de confirmaciones por invitado/persona`.
- `GIFTS-001: Documentar y normalizar estados de pago/regalo`.
- `WA-001: Consolidar flujo oficial de WhatsApp y estados de envío`.
- `ADMIN-001: Include común para autenticación/autorización admin`.
- `OPS-001: Guía operativa para alta de nuevo evento/cliente`.
- `TEST-001: Tests mínimos para RSVP y checkout`.
