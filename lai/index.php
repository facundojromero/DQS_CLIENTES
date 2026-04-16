<?php
include 'conexion.php';


session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];


//ESTE CODIGO ESTÁ ACÁ PARA QUE NO SE NOTE CUANDO ACTUALIZA (PERO PODRÍA IR SIN PROBLEMAS EN ventas_rapidas.php
// Registrar venta si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['producto'])) {
    $producto = $_POST['producto'];
    $precio = $_POST['precio'];
    $forma_pago = $_POST['forma_pago'];
    $fecha_hora = date('Y-m-d H:i:s', strtotime('-5 hours'));

    // Inicializar array de tickets
    $tickets = array();

    // Verificar si el producto es una combinación
    $sql_combo = "SELECT * FROM combinaciones WHERE nombre = '$producto'";
    $result_combo = $conn->query($sql_combo);

    if ($result_combo->num_rows > 0) {
        $combo = $result_combo->fetch_assoc();
        $combo_id = $combo['id'];
        $precio_combo = $combo['precio'];

        // Ajustar el precio según forma de pago
        if ($forma_pago == "Mercado Pago") {
            $precio_combo *= 1.10;
        }

        // Obtener los productos que componen la combinación
        $sql_detalles = "SELECT p.nombre, p.precio 
							FROM combinaciones_detalles AS cd
							JOIN productos AS p ON cd.id_producto = p.id 
							WHERE cd.id_combinacion = $combo_id";
        $result_detalles = $conn->query($sql_detalles);

        $productos = array();
        $suma_precios = 0;

        while ($row = $result_detalles->fetch_assoc()) {
            $productos[] = $row;
            $suma_precios += $row['precio'];
        }

        // Registrar la venta en tabla ventas
        $sql = "INSERT INTO ventas (fecha_hora, producto, precio, forma, id_usuario) VALUES ('$fecha_hora', '$producto', '$precio_combo', '$forma_pago', $usuario_id)";
        if ($conn->query($sql) === TRUE) {
            // Calcular y registrar cada ticket proporcional
            foreach ($productos as $p) {
                $proporcion = $p['precio'] / $suma_precios;
                $precio_ajustado = round($precio_combo * $proporcion);
                $tickets[] = array('producto' => $p['nombre'], 'precio' => $precio_ajustado);
            }
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    } else {
        // Producto individual
        if ($forma_pago == "Mercado Pago") {
            $precio *= 1.10;
        }

        $sql = "INSERT INTO ventas (fecha_hora, producto, precio, forma, id_usuario) VALUES ('$fecha_hora', '$producto', '$precio', '$forma_pago', $usuario_id)";

        if ($conn->query($sql) === TRUE) {
            $tickets[] = array('producto' => $producto, 'precio' => round($precio));
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    // Imprimir tickets (PoC: agente local + fallback a impresión actual)
    echo '<script>
        (function () {
            const tickets = ' . json_encode($tickets) . ';

            if (!window.__laiPrintAgentPoC) {
                window.__laiPrintAgentPoC = {
                    agentBaseUrl: "http://127.0.0.1:3000",
                    agentReachable: null,
                    formatCurrency: function (value) {
                        return new Intl.NumberFormat("es-AR", {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(Number(value || 0));
                    },
                    buildTicketContent: function (ticket) {
                        const now = new Date();
                        const fecha = now.toLocaleDateString("es-AR");
                        const hora = now.toLocaleTimeString("es-AR");
                        return [
                            "LAI",
                            "Fecha: " + fecha + " " + hora,
                            "Producto: " + ticket.producto,
                            "Precio: $" + this.formatCurrency(ticket.precio),
                            "------------------------------"
                        ].join("\\n");
                    },
                    checkAgent: async function () {
                        if (this.agentReachable !== null) {
                            return this.agentReachable;
                        }

                        try {
                            const response = await fetch(this.agentBaseUrl + "/health", {
                                method: "GET",
                                mode: "cors"
                            });
                            this.agentReachable = response.ok;
                        } catch (error) {
                            this.agentReachable = false;
                        }

                        return this.agentReachable;
                    },
                    printWithAgent: async function (ticket) {
                        const available = await this.checkAgent();
                        if (!available) {
                            throw new Error("Agente local no disponible.");
                        }

                        const payload = {
                            type: "ticket",
                            content: this.buildTicketContent(ticket),
                            copies: 1
                        };

                        const response = await fetch(this.agentBaseUrl + "/print", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify(payload)
                        });

                        if (!response.ok) {
                            const errorText = await response.text();
                            throw new Error(errorText || "Error al imprimir con agente local.");
                        }
                    },
                    printWithFallback: function (ticket) {
                        return new Promise(function (resolve) {
                            const ventana = window.open(
                                "factura_tkt.php?producto=" + encodeURIComponent(ticket.producto) + "&precio=" + ticket.precio,
                                "_blank",
                                "width=200,height=100,top=1000,left=2000"
                            );

                            if (!ventana) {
                                resolve();
                                return;
                            }

                            const checkClosed = setInterval(function () {
                                if (ventana.closed) {
                                    clearInterval(checkClosed);
                                    resolve();
                                }
                            }, 300);
                        });
                    },
                    delay: function (ms) {
                        return new Promise(function (resolve) { setTimeout(resolve, ms); });
                    },
                    printTickets: async function (tickets) {
                        for (let i = 0; i < tickets.length; i++) {
                            const ticket = tickets[i];
                            try {
                                await this.printWithAgent(ticket);
                            } catch (error) {
                                console.warn("Fallo agente local, uso fallback de navegador:", error);
                                await this.printWithFallback(ticket);
                            }
                            await this.delay(500);
                        }
                    }
                };
            }

            window.__laiPrintAgentPoC.printTickets(tickets);
        })();
    </script>';
}





//ESTE CODIGO ESTÁ ACÁ PARA QUE NO SE NOTE CUANDO ACTUALIZA (PERO PODRÍA IR SIN PROBLEMAS EN carrito.php)
// Registrar venta si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_venta'])) {
    $forma_pago = $_POST['forma_pago2'];
    $fecha_hora = date('Y-m-d H:i:s', strtotime('-5 hours'));

    $sql_venta = "INSERT INTO ventas (fecha_hora, producto, precio, forma, id_usuario) VALUES ('$fecha_hora', 'Carrito', 0, '$forma_pago', $usuario_id)";

    if ($conn->query($sql_venta) === TRUE) {
        $venta_id = $conn->insert_id;
        $total_precio = 0;
        $tickets = array();

        $sql_carrito = "SELECT * FROM carrito WHERE id_usuario = $usuario_id";
        $result_carrito = $conn->query($sql_carrito);

        while ($row = $result_carrito->fetch_assoc()) {
            $producto_id = $row['producto_id'];
            $cantidad = $row['cantidad'];
            $tipo = $row['tipo'];

            if ($tipo == 'combinacion') {
                $sql_combo = "SELECT * FROM combinaciones WHERE id = '$producto_id'";
                $result_combo = $conn->query($sql_combo);

                if ($result_combo && $result_combo->num_rows > 0) {
                    $combo = $result_combo->fetch_assoc();
                    $combo_id = $combo['id'];
                    $precio_combo = $combo['precio'] * $cantidad;

                    if ($forma_pago == "Mercado Pago") {
                        $precio_combo *= 1.10;
                    }

                    $productos = array();
                    $sql_detalles = "SELECT id_producto FROM combinaciones_detalles WHERE id_combinacion = $combo_id";
                    $result_detalles = $conn->query($sql_detalles);

                    while ($row_detalle = $result_detalles->fetch_assoc()) {
                        $id_prod = $row_detalle['id_producto'];
                        $sql_producto = "SELECT nombre, precio FROM productos WHERE id = $id_prod";
                        $res_producto = $conn->query($sql_producto);
                        if ($res_producto && $res_producto->num_rows > 0) {
                            $prod = $res_producto->fetch_assoc();
                            $prod['id'] = $id_prod;
                            $productos[] = $prod;
                        }
                    }

                    $suma_precios = 0;
                    foreach ($productos as $p) {
                        $suma_precios += $p['precio'];
                    }

                    foreach ($productos as $p) {
                        $porcentaje = $p['precio'] / $suma_precios;
                        $precio_ajustado = round($precio_combo * $porcentaje, 2);

                        // Insertar en venta_detalles
                        $sql_detalle = "INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio, tipo) 
                                        VALUES ($venta_id, {$p['id']}, $cantidad, $precio_ajustado, 'combinacion')";
                        $conn->query($sql_detalle);

                        // Armar ticket
                        $tickets[] = array('producto' => $p['nombre'], 'precio' => $precio_ajustado);
                    }

                    $total_precio += $precio_combo;
                }

            } else {
                $sql_producto = "SELECT nombre, precio FROM productos WHERE id = $producto_id";
                $result_producto = $conn->query($sql_producto);
                $producto = $result_producto->fetch_assoc();

                $precio_unitario = $producto['precio'];
                if ($forma_pago == "Mercado Pago") {
                    $precio_unitario *= 1.10;
                }

                $precio_total = round($precio_unitario * $cantidad, 2);

                // Insertar en venta_detalles
                $sql_detalle = "INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio, tipo) 
                                VALUES ($venta_id, $producto_id, $cantidad, $precio_total, 'producto')";
                $conn->query($sql_detalle);

                $tickets[] = array('producto' => $producto['nombre'], 'precio' => $precio_total);

                $total_precio += $precio_total;
            }
        }

        // Actualizar el precio total de la venta
        $sql_update_venta = "UPDATE ventas SET precio = $total_precio WHERE id = $venta_id";
        $conn->query($sql_update_venta);

        // Limpiar el carrito
        $conn->query("DELETE FROM carrito");

        // Imprimir tickets (PoC: agente local + fallback a impresión actual)
        echo '<script>
            (function () {
                const tickets = ' . json_encode($tickets) . ';

                if (!window.__laiPrintAgentPoC) {
                    window.__laiPrintAgentPoC = {
                        agentBaseUrl: "http://127.0.0.1:3000",
                        agentReachable: null,
                        formatCurrency: function (value) {
                            return new Intl.NumberFormat("es-AR", {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(Number(value || 0));
                        },
                        buildTicketContent: function (ticket) {
                            const now = new Date();
                            const fecha = now.toLocaleDateString("es-AR");
                            const hora = now.toLocaleTimeString("es-AR");
                            return [
                                "LAI",
                                "Fecha: " + fecha + " " + hora,
                                "Producto: " + ticket.producto,
                                "Precio: $" + this.formatCurrency(ticket.precio),
                                "------------------------------"
                            ].join("\\n");
                        },
                        checkAgent: async function () {
                            if (this.agentReachable !== null) {
                                return this.agentReachable;
                            }

                            try {
                                const response = await fetch(this.agentBaseUrl + "/health", {
                                    method: "GET",
                                    mode: "cors"
                                });
                                this.agentReachable = response.ok;
                            } catch (error) {
                                this.agentReachable = false;
                            }

                            return this.agentReachable;
                        },
                        printWithAgent: async function (ticket) {
                            const available = await this.checkAgent();
                            if (!available) {
                                throw new Error("Agente local no disponible.");
                            }

                            const payload = {
                                type: "ticket",
                                content: this.buildTicketContent(ticket),
                                copies: 1
                            };

                            const response = await fetch(this.agentBaseUrl + "/print", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify(payload)
                            });

                            if (!response.ok) {
                                const errorText = await response.text();
                                throw new Error(errorText || "Error al imprimir con agente local.");
                            }
                        },
                        printWithFallback: function (ticket) {
                            return new Promise(function (resolve) {
                                const ventana = window.open(
                                    "factura_tkt.php?producto=" + encodeURIComponent(ticket.producto) + "&precio=" + ticket.precio,
                                    "_blank",
                                    "width=200,height=100,top=1000,left=2000"
                                );

                                if (!ventana) {
                                    resolve();
                                    return;
                                }

                                const checkClosed = setInterval(function () {
                                    if (ventana.closed) {
                                        clearInterval(checkClosed);
                                        resolve();
                                    }
                                }, 300);
                            });
                        },
                        delay: function (ms) {
                            return new Promise(function (resolve) { setTimeout(resolve, ms); });
                        },
                        printTickets: async function (tickets) {
                            for (let i = 0; i < tickets.length; i++) {
                                const ticket = tickets[i];
                                try {
                                    await this.printWithAgent(ticket);
                                } catch (error) {
                                    console.warn("Fallo agente local, uso fallback de navegador:", error);
                                    await this.printWithFallback(ticket);
                                }
                                await this.delay(500);
                            }
                        }
                    };
                }

                window.__laiPrintAgentPoC.printTickets(tickets);
            })();
        </script>';

    } else {
        echo "Error: " . $sql_venta . "<br>" . $conn->error;
    }
}






// Consulta SQL para obtener los totales de ventas por forma de pago
$sql_totales = "
SELECT * FROM (
    SELECT 'TOTAL' AS agrupacion, SUM(precio) AS total_precio 
    FROM ventas 
    WHERE activado = 1 
    AND forma NOT IN ('Entrada', 'Gratis')

    UNION ALL

    SELECT forma, SUM(precio) AS total_precio 
    FROM ventas 
    WHERE activado = 1 
    GROUP BY forma 
) AS subconsulta
ORDER BY 
    CASE agrupacion
        WHEN 'TOTAL' THEN 0
        WHEN 'Efectivo' THEN 1
        WHEN 'Mercado Pago' THEN 2
        WHEN 'Entrada' THEN 3
        WHEN 'Gratis' THEN 4
        ELSE 5
    END;
";

// Ejecutar la consulta y obtener los resultados
$result_totales = $conn->query($sql_totales);
$totales = array();
if ($result_totales->num_rows > 0) {
    while($row = $result_totales->fetch_assoc()) {
        $totales[] = $row;
    }
}





// Consulta para obtener los productos
$sql_productos = "SELECT * FROM productos ORDER BY orden ASC";
$result_productos = $conn->query($sql_productos);

// Array para almacenar los productos
$productos = array();
if ($result_productos->num_rows > 0) {
    while($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Consulta para obtener las combinaciones
$sql_combinaciones = "SELECT * FROM combinaciones ORDER BY id ASC";
$result_combinaciones = $conn->query($sql_combinaciones);

// Array para almacenar las combinaciones
$combinaciones = array();
if ($result_combinaciones->num_rows > 0) {
    while($row = $result_combinaciones->fetch_assoc()) {
        $combinaciones[] = $row;
    }
}


$sql_usuario = "SELECT nombre FROM usuarios WHERE id = $usuario_id";
$result_usuario = $conn->query($sql_usuario);
$usuario_nombre = "Usuario";

if ($result_usuario && $result_usuario->num_rows > 0) {
    $fila = $result_usuario->fetch_assoc();
    $usuario_nombre = $fila['nombre'];
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Venta - LAI</title>
  
      <link rel="stylesheet" href="formato.css">
	


</head>
<body>

<div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin: 10px 20px;">
    <span style="font-weight:bold;">
        Bienvenido: <?php echo htmlspecialchars($usuario_nombre); ?>
    </span>
    <a href="logout.php" class="action-button cancel-button" style="font-size: 0.85em;">Cerrar sesión</a>
</div>
    <div class="container">

		
		<div class="left-panel">
		
			<?php include 'ventas_rapidas.php'; ?>
		
					
	<?php include 'ventas_registradas.php'; ?>
			
        </div>



        <div class="right-panel">

				
			<?php include 'carrito.php'; ?>

		
        </div>
		
		
    </div>
</body>
</html>

<?php $conn->close(); ?>
