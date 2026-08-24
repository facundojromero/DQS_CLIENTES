# UNI-006 — `regalos_enabled`

## Propósito

UNI-006 aplica la configuración comercial `regalos_enabled` como feature flag reversible para regalos, tienda, carrito, checkout, gift card, transferencia bancaria asociada a regalos y pantallas admin de productos/regalos/compras.

Cuando `regalos_enabled=1`, el sistema conserva el comportamiento existente. Cuando `regalos_enabled=0`, el cliente/admin y los invitados no ven ni pueden usar regalos.

## `regalos_enabled` vs configuraciones visuales

- `regalos_enabled` es el interruptor comercial global. Si vale `0`, toda la funcionalidad de regalos queda bloqueada aunque existan productos, carritos o compras históricas.
- `mostrar_lista_regalos`, `mostrar_transferencia_regalos` y `show_giftcard` siguen siendo configuraciones visuales internas de regalos. Solo aplican cuando `regalos_enabled=1`.
- UNI-006 no cambia automáticamente `mostrar_lista_regalos`, `mostrar_transferencia_regalos` ni `show_giftcard`.

## Pantallas y endpoints controlados

### Front público

- `index.php`: oculta el link/botón “Regalar” y la card/sección `#regalar` de transferencia asociada a regalos cuando `regalos_enabled=0`.

### Tienda y carrito

- `tienda/index.php`
- `tienda/mostrar_productos.php`
- `tienda/carrito.php`
- `tienda/ver_carrito.php`
- `tienda/finalizar_compra.php`
- `tienda/procesar_compra.php`
- `tienda/compra_exitosa.php`
- `tienda/paginacion.php`
- `tienda/modificar_cantidad.php`
- `tienda/eliminar_producto.php`
- `tienda/vaciar_carrito.php`

### Admin activo

- `admin7WZiwEM3XY/menu.php`: oculta el menú “Regalos”.
- `admin7WZiwEM3XY/lista_regalos.php`: bloquea acceso directo y acciones AJAX de crear, editar, activar/desactivar o eliminar productos, y cambios de configuración visual de regalos.
- `admin7WZiwEM3XY/productos_vendidos.php`: bloquea acceso directo y acciones de confirmar/deshacer/cancelar regalos.

## Comportamiento con `regalos_enabled=1`

- Home y link “Regalar” siguen dependiendo de las configuraciones visuales existentes.
- `mostrar_lista_regalos`, `mostrar_transferencia_regalos` y `show_giftcard` siguen funcionando igual.
- `/tienda/`, carrito, checkout, procesamiento de compras y pantalla de compra exitosa funcionan igual.
- El admin muestra el menú “Regalos”.
- Lista de regalos, productos vendidos, confirmaciones y regalos recibidos funcionan igual.

## Comportamiento con `regalos_enabled=0`

- El front oculta “Regalar”.
- El front oculta la sección/card de transferencia bancaria asociada a regalos.
- `/tienda/` y endpoints de tienda/carrito/checkout quedan bloqueados.
- El admin oculta “Regalos”.
- Accesos directos a lista de regalos y productos vendidos quedan bloqueados.
- Las pantallas/endpoints bloqueados muestran el mensaje amigable: “La funcionalidad de regalos no está habilitada para este evento.”
- No se crean, editan, activan ni eliminan productos desde el admin bloqueado.
- No se confirman pagos/regalos desde el admin bloqueado.
- No se registran compras nuevas desde tienda bloqueada.
- No se borran históricos, carritos, productos, regalos ni compras por el bloqueo.

## Cómo probar con `tools/dqs_provider_config.php`

### Habilitado

```bash
php tools/dqs_provider_config.php --set regalos_enabled=1 --apply
```

Validar que:

- Home carga igual.
- “Regalar” aparece según `mostrar_lista_regalos` / `mostrar_transferencia_regalos`.
- `/tienda/` abre.
- Carrito y checkout funcionan.
- El admin muestra “Regalos”.
- Lista de regalos y productos vendidos abren.

### Deshabilitado en DEV

```bash
php tools/dqs_provider_config.php --set regalos_enabled=0 --apply
```

Validar que:

- Home carga sin romper.
- “Regalar” no aparece.
- La card `#regalar` no aparece.
- `/tienda/` muestra el mensaje de funcionalidad no habilitada.
- El menú admin oculta “Regalos”.
- `admin7WZiwEM3XY/lista_regalos.php` queda bloqueado.
- `admin7WZiwEM3XY/productos_vendidos.php` queda bloqueado.
- Entrar a pantallas bloqueadas no modifica productos, regalos, carritos ni compras.

## Restaurar configuración actual

La configuración actual informada para el evento activo es `regalos_enabled=1`. Para restaurarla:

```bash
php tools/dqs_provider_config.php --set regalos_enabled=1 --apply
```

## Confirmaciones de alcance

- UNI-006 no toca `admin_tmp`.
- UNI-006 no cambia RSVP.
- UNI-006 no cambia WhatsApp.
- UNI-006 no ejecuta Node.
- UNI-006 no crea, altera ni fusiona tablas.
- UNI-006 no usa `pre_invitados`.
- UNI-006 no borra datos históricos, carritos, productos, regalos ni compras.
