# DQS checklist de regresión del sistema activo

Fecha de creación: 2026-06-24.

Este checklist valida el comportamiento actual después de cada PR. Está pensado para pruebas manuales en un entorno seguro/staging con datos de prueba. No ejecutar contra producción sin autorización.

## Reglas de ejecución

- No usar credenciales reales en reportes ni capturas.
- No ejecutar scripts WhatsApp reales durante regresión normal.
- No ejecutar SQL manual salvo que el PR lo requiera explícitamente y exista respaldo.
- Usar invitados, teléfonos y correos de prueba.
- Registrar navegador, dispositivo/ancho de pantalla y URL probada.

## 1. Home público

- [ ] Cargar `/index.php` sin parámetros.
- [ ] Verificar que portada, título, frase y fecha cargan.
- [ ] Verificar que no aparecen errores PHP visibles.
- [ ] Verificar que las imágenes principales cargan o degradan sin romper layout.
- [ ] Verificar que el contador/fecha no rompe la página.

## 2. Menú público

- [ ] Click en cada item visible del menú.
- [ ] Confirmar que navega/scroll a la sección correcta.
- [ ] Confirmar que no quedan anchors rotos visibles.
- [ ] Confirmar que el botón “Regalar” respeta la configuración actual:
  - [ ] Si lista de regalos está activa, apunta a `tienda/`.
  - [ ] Si solo transferencia está activa, apunta a `#regalar`.
  - [ ] Si ambas están inactivas, no debería mostrarse o no debería quedar acción rota.

## 3. RSVP búsqueda por código

- [ ] Abrir `/index.php#rsvp`.
- [ ] Buscar un código válido de invitado activo.
- [ ] Confirmar que se muestra el grupo/personas esperadas.
- [ ] Buscar un código inexistente.
- [ ] Confirmar que el sistema muestra mensaje de no encontrado sin romper.
- [ ] Abrir directamente `/index.php?busqueda=CODIGO#rsvp`.
- [ ] Confirmar que precarga la búsqueda y muestra el invitado.

## 4. RSVP confirmación modal

- [ ] Desde un código válido, click en “Confirmar Asistencia”.
- [ ] Confirmar que abre modal cargado desde `confirmacion_modal.php`.
- [ ] Confirmar que muestra el código y nombres correctos.
- [ ] Seleccionar “Sí”.
- [ ] Confirmar que aparece checklist de personas.
- [ ] En grupo de una persona, confirmar comportamiento automático/oculto según corresponda.
- [ ] En grupo de varias personas, seleccionar al menos una persona.
- [ ] Completar restricciones alimentarias por persona.
- [ ] Enviar.
- [ ] Confirmar mensaje de éxito.
- [ ] Reabrir modal y verificar que la asistencia queda precargada.

## 5. RSVP rechazo/no asistencia

- [ ] Abrir modal para un código válido.
- [ ] Seleccionar “No”.
- [ ] Confirmar que se oculta/deshabilita checklist de personas.
- [ ] Enviar.
- [ ] Confirmar mensaje de no asistencia.
- [ ] Reabrir modal y confirmar que las personas figuran como no asistentes.

## 6. Confirmación legacy

- [ ] Si existe una URL legacy usada por clientes, abrir `confirmar_asistencia.php?id=CODIGO` con código de prueba.
- [ ] Confirmar que la página carga sin error fatal.
- [ ] No usar este flujo para validar el comportamiento principal salvo que haya usuarios reales dependientes.
- [ ] Documentar cualquier diferencia frente al modal moderno.

## 7. Regalos en home / transferencia bancaria

- [ ] Con transferencia habilitada, navegar a `#regalar`.
- [ ] Confirmar que se muestra el título configurado.
- [ ] Confirmar que datos de cuenta en pesos aparecen si están configurados.
- [ ] Confirmar que datos de cuenta en dólares aparecen si están configurados.
- [ ] Confirmar que no se exponen campos vacíos como bloques rotos.
- [ ] Probar copiar/seleccionar alias/CBU si el diseño lo permite.

## 8. Tienda/lista de regalos

- [ ] Abrir `/tienda/`.
- [ ] Confirmar que carga sin errores visibles.
- [ ] Confirmar que muestra productos activos.
- [ ] Confirmar que cada producto muestra título, descripción/precio e imagen o fallback aceptable.
- [ ] Cambiar moneda si el selector está disponible.
- [ ] Confirmar que la cotización dólar se aplica de manera visual coherente.
- [ ] Confirmar que Gift Card aparece solo si está habilitada.

## 9. Link “Regalar” en productos

- [ ] Click en “Regalar” de un producto normal.
- [ ] Confirmar mensaje de agregado al carrito.
- [ ] Abrir carrito.
- [ ] Confirmar producto, cantidad, subtotal y total.
- [ ] Cerrar carrito y seguir comprando.

## 10. Gift Card / regalo libre

- [ ] Click en Gift Card / “Elegir monto”.
- [ ] Ingresar monto válido.
- [ ] Confirmar que se agrega al carrito.
- [ ] Intentar monto inválido (cero, negativo o no numérico).
- [ ] Confirmar que se bloquea o muestra error sin agregar.
- [ ] Confirmar que el carrito muestra “Gift Card” con el monto ingresado.

## 11. Carrito

- [ ] Abrir carrito desde ícono.
- [ ] Incrementar cantidad de producto normal.
- [ ] Disminuir cantidad de producto normal.
- [ ] Eliminar un producto.
- [ ] Vaciar carrito completo.
- [ ] Confirmar que al vaciar se muestra carrito vacío.
- [ ] Confirmar que Gift Card mantiene cantidad 1 y monto libre esperado.

## 12. Checkout

- [ ] Agregar uno o más productos al carrito.
- [ ] Ir a finalizar compra.
- [ ] Confirmar resumen de productos y total.
- [ ] Completar nombre, apellido, email, teléfono, mensaje y compartido si aplica.
- [ ] Seleccionar transferencia bancaria.
- [ ] Enviar compra.
- [ ] Confirmar que no aparecen errores PHP visibles.

## 13. Compra exitosa

- [ ] Confirmar que la pantalla de compra exitosa muestra datos del comprador.
- [ ] Confirmar que muestra productos/regalos comprados.
- [ ] Confirmar que indica pago pendiente por transferencia.
- [ ] Confirmar que muestra cuenta correcta según moneda.
- [ ] Confirmar que “seguir regalando” vuelve a tienda o destino configurado.
- [ ] Confirmar que el carrito queda vacío tras la compra.

## 14. Admin login y menú

- [ ] Ingresar a `admin7WZiwEM3XY/` sin sesión.
- [ ] Confirmar redirección a login.
- [ ] Iniciar sesión con usuario de prueba.
- [ ] Confirmar carga de dashboard.
- [ ] Confirmar que el menú muestra Inicio, Regalos, Invitados, Web, Datos y Cerrar Sesión.
- [ ] Abrir cada opción visible del menú y confirmar que no rompe.

## 15. Admin configuración de regalos

- [ ] Abrir Regalos > Lista de regalos.
- [ ] Cambiar “Mostrar lista de regalos/productos”.
- [ ] Cambiar “Mostrar transferencia”.
- [ ] Cambiar títulos de transferencia/cuentas.
- [ ] Cambiar show Gift Card.
- [ ] Guardar.
- [ ] Volver al home y confirmar que el link “Regalar” y secciones reflejan la configuración.
- [ ] Restaurar configuración inicial al finalizar la prueba.

## 16. Admin productos/regalos

- [ ] Crear producto de prueba con imagen de prueba.
- [ ] Editar título/descripción/precio.
- [ ] Confirmar que aparece en tienda.
- [ ] Desactivar/eliminar producto de prueba según UI.
- [ ] Confirmar que ya no aparece en tienda.
- [ ] No modificar productos reales durante prueba.

## 17. Admin invitados

- [ ] Abrir Invitados > Lista de invitados.
- [ ] Confirmar que lista carga.
- [ ] Filtrar/buscar invitado.
- [ ] Abrir edición de invitado de prueba.
- [ ] Confirmar que campos principales se muestran.
- [ ] Crear invitado de prueba si el entorno lo permite.
- [ ] Confirmar que el invitado de prueba puede buscarse en RSVP.
- [ ] Restaurar o marcar como inactivo al finalizar.

## 18. Admin confirmaciones/dashboard

- [ ] Confirmar que dashboard muestra totales sin errores.
- [ ] Confirmar que cambios RSVP de prueba impactan en confirmados/pendientes.
- [ ] Confirmar que restricciones alimentarias aparecen en métricas si corresponde.
- [ ] Confirmar que no hay doble conteo evidente entre grupo y personas.

## 19. Admin regalos vendidos

- [ ] Abrir Regalos > Confirmar.
- [ ] Confirmar que regalos pendientes cargan.
- [ ] Abrir Regalos > Recibidos.
- [ ] Confirmar que regalos confirmados cargan.
- [ ] Con compra de prueba, confirmar pago si el entorno lo permite.
- [ ] Confirmar que pasa de pendiente a confirmado.
- [ ] Probar deshacer solo con compra de prueba.

## 20. Admin envíos

- [ ] Abrir Invitados > Enviar Invitaciones.
- [ ] Confirmar que listas/columnas cargan.
- [ ] Mover un teléfono de prueba a “a enviar”.
- [ ] Quitar el teléfono de prueba de “a enviar”.
- [ ] Confirmar que contadores se actualizan.
- [ ] No ejecutar envío real WhatsApp.
- [ ] No iniciar Node.

## 21. WhatsApp sin envío real

- [ ] Revisar que los links generados para prueba mantengan formato `?busqueda=CODIGO#rsvp`.
- [ ] Confirmar que los teléfonos de prueba aparecen en la cola esperada.
- [ ] No enviar mensajes reales.
- [ ] No ejecutar `ENVIOS WHATSAPP.bat`.
- [ ] No ejecutar `node`, `npm`, `server.js`, `app.js` ni `whatsapp.js` en regresión común.

## 22. Mobile básico

- [ ] Probar home en ancho móvil.
- [ ] Confirmar que menú/hamburger abre y cierra.
- [ ] Confirmar que RSVP se puede usar con teclado móvil.
- [ ] Confirmar que modal RSVP entra en pantalla y permite scroll.
- [ ] Confirmar que tienda lista productos en columna usable.
- [ ] Confirmar que carrito y checkout son operables.
- [ ] Confirmar que admin menú no bloquea tareas básicas en móvil si se usa desde celular.

## 23. Cierre de regresión

- [ ] Restaurar datos de prueba si se modificaron configuraciones.
- [ ] Eliminar/desactivar productos/invitados de prueba si corresponde.
- [ ] Confirmar que no quedaron teléfonos en cola de envío real.
- [ ] Adjuntar capturas solo sin secretos.
- [ ] Registrar diferencias contra baseline en el PR.
