<?php
include 'conexion.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}


// Anular venta si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['anular'])) {
    $venta_id = $_POST['venta_id'];

    $sql_anular = "UPDATE ventas SET activado = 0 WHERE id = $venta_id";
    if ($conn->query($sql_anular) === TRUE) {
        echo "<script>alert('Venta anulada correctamente');</script>";
    } else {
        echo "Error al anular la venta: " . $conn->error;
    }
}


function obtenerPrecioVentaPorForma($conn, $venta_id, $forma_pago) {
    $sql_detalles = "SELECT vd.tipo, vd.producto_id, vd.cantidad, p.precio, p.precio_mercadopago, c.precio AS combo_precio, c.precio_mercadopago AS combo_precio_mercadopago
                    FROM venta_detalles vd
                    LEFT JOIN productos p ON vd.tipo = 'producto' AND vd.producto_id = p.id
                    LEFT JOIN combinaciones c ON vd.tipo = 'combinacion' AND vd.producto_id = c.id
                    WHERE vd.venta_id = $venta_id";
    $result_detalles = $conn->query($sql_detalles);

    if ($result_detalles && $result_detalles->num_rows > 0) {
        $total = 0;
        while ($detalle = $result_detalles->fetch_assoc()) {
            if ($detalle['tipo'] === 'producto') {
                $precio_unitario = ($forma_pago === 'Mercado Pago') ? $detalle['precio_mercadopago'] : $detalle['precio'];
            } else {
                $precio_unitario = ($forma_pago === 'Mercado Pago') ? $detalle['combo_precio_mercadopago'] : $detalle['combo_precio'];
            }
            $total += ((float)$precio_unitario * (int)$detalle['cantidad']);
        }
        return $total;
    }

    $sql_venta = "SELECT producto, precio FROM ventas WHERE id = $venta_id LIMIT 1";
    $result_venta = $conn->query($sql_venta);
    if (!$result_venta || $result_venta->num_rows === 0) {
        return 0;
    }

    $venta = $result_venta->fetch_assoc();
    if ($venta['producto'] === 'Carrito') {
        return $venta['precio'];
    }

    $producto = $conn->real_escape_string($venta['producto']);

    $sql_producto = "SELECT precio, precio_mercadopago FROM productos WHERE nombre = '$producto' LIMIT 1";
    $result_producto = $conn->query($sql_producto);
    if ($result_producto && $result_producto->num_rows > 0) {
        $fila_producto = $result_producto->fetch_assoc();
        return ($forma_pago === 'Mercado Pago') ? $fila_producto['precio_mercadopago'] : $fila_producto['precio'];
    }

    $sql_combo = "SELECT precio, precio_mercadopago FROM combinaciones WHERE nombre = '$producto' LIMIT 1";
    $result_combo = $conn->query($sql_combo);
    if ($result_combo && $result_combo->num_rows > 0) {
        $fila_combo = $result_combo->fetch_assoc();
        return ($forma_pago === 'Mercado Pago') ? $fila_combo['precio_mercadopago'] : $fila_combo['precio'];
    }

    return $venta['precio'];
}

// Actualizar forma de pago si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_pago'])) {
    $venta_id = $_POST['venta_id'];
    $forma_pago = $_POST['forma_pago'];
    $precio_actualizado = obtenerPrecioVentaPorForma($conn, (int)$venta_id, $forma_pago);

    $sql_update_pago_precio = "UPDATE ventas SET forma = '$forma_pago', precio = '$precio_actualizado' WHERE id = $venta_id";

    if ($conn->query($sql_update_pago_precio) === TRUE) {
        echo "<script>alert('Forma de pago y precio actualizados correctamente');</script>";
    } else {
        echo "Error al actualizar la forma de pago y el precio: " . $conn->error;
    }
}


// Paginación
$limit = 5; // Cantidad de ventas por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$usuario_id = $_SESSION['usuario_id'];
$sql_ventas = "SELECT * FROM ventas WHERE activado = 1 AND id_usuario = $usuario_id ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result_ventas = $conn->query($sql_ventas);

// Consulta total de ventas para la paginación
$sql_count = "SELECT COUNT(*) as total FROM ventas WHERE activado = 1 AND id_usuario = $usuario_id";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_ventas = $row_count['total'];
$total_paginas = ceil($total_ventas / $limit);
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas Registradas - LAI</title>
    <link rel="stylesheet" href="formato.css">
	
	
</head>
<body>
            <h1>Ventas Registradas</h1> <?php include 'menu_botones.php'; ?>


            <!-- Tabla de ventas registradas -->
<table>
    <tr>
        <th>ID</th>
        <th>Fecha y Hora</th>
        <th>Producto</th>
        <th>Precio</th>
        <th>Forma de Pago</th> <!-- Columna para mostrar la forma de pago -->
        <th>Acciones</th>
    </tr>
    <?php
    // Mostrar las ventas en la tabla
    if ($result_ventas->num_rows > 0) {
        while ($row = $result_ventas->fetch_assoc()) {
            $id = $row['id'];
            $producto = $row['producto'];
            $precio = $row['precio'];
            $forma_pago = $row['forma']; // Nueva columna para la forma de pago
            echo "<tr>
                    <td>{$id}</td>
                    <td>{$row['fecha_hora']}</td>";
echo "<td>";
if ($producto == "Carrito") {
    echo "<a href='#' onclick=\"window.open('detalle_ticket.php?id={$id}', 'DetalleTicket', 'width=600,height=400'); return false;\">{$producto}</a>";
} else {
    echo $producto;
}
echo "</td>";



                           echo"<td>\${$precio}</td>
                    <td>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='venta_id' value='{$id}'>
                            <select name='forma_pago' required>
                                <option value='Efectivo' ".($forma_pago == 'Efectivo' ? 'selected' : '').">Efectivo</option>
                                <option value='Mercado Pago' ".($forma_pago == 'Mercado Pago' ? 'selected' : '').">Mercado Pago</option>
                                <option value='Gratis' ".($forma_pago == 'Gratis' ? 'selected' : '').">Gratis</option>
						
                            </select>
                            <button type='submit' name='actualizar_pago' class='action-button'>Actualizar</button>
                        </form>
                    </td>
                    <td>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='venta_id' value='{$id}'>
                            <button type='submit' name='anular' class='action-button cancel-button' onclick=\"return confirm('¿Estás seguro que deseas anular esta venta?');\">Anular</button>
                        </form>
                        <button class='action-button' onclick=\"window.open('factura_tkt.php?producto={$producto}&precio={$precio}', '_blank');\">Imprimir</button>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No se han registrado ventas.</td></tr>";
    }
    ?>
</table>


			<div class="pagination">
				<?php
				// Limitar la cantidad de páginas a mostrar
				$pages_to_show = 3; // Por ejemplo, mostrar 3 páginas antes y 3 después de la página actual

				// Botón "Anterior"
				if ($page > 1) {
					echo "<a href='?page=" . ($page - 1) . "'>Anterior</a>";
				}

				// Mostrar el primer número de página si está fuera del rango
				if ($page > $pages_to_show + 1) {
					echo "<a href='?page=1'>1</a>";
					if ($page > $pages_to_show + 2) {
						echo "<span>...</span>"; // Puntos suspensivos si hay un gran salto entre las páginas
					}
				}

				// Mostrar páginas dentro del rango ($page - $pages_to_show) a ($page + $pages_to_show)
				for ($i = max(1, $page - $pages_to_show); $i <= min($total_paginas, $page + $pages_to_show); $i++) {
					if ($i == $page) {
						// Resaltar la página actual
						echo "<a href='?page=$i' style='font-weight: bold; background-color: #ddd;'>$i</a>";
					} else {
						echo "<a href='?page=$i'>$i</a>";
					}
				}

				// Mostrar el último número de página si está fuera del rango
				if ($page < $total_paginas - $pages_to_show) {
					if ($page < $total_paginas - $pages_to_show - 1) {
						echo "<span>...</span>"; // Puntos suspensivos si hay un gran salto
					}
					echo "<a href='?page=$total_paginas'>$total_paginas</a>";
				}

				// Botón "Siguiente"
				if ($page < $total_paginas) {
					echo "<a href='?page=" . ($page + 1) . "'>Siguiente</a>";
				}
				?>
			</div>
</body>			
</html>