<?php
if (!isset($_SESSION)) {
    session_start();
}
include '../conexion.php';

// Obtener la moneda seleccionada (1 para Pesos, 2 para Dólares) desde la URL
$currency = isset($_GET['currency']) ? (int)$_GET['currency'] : 1;

// Obtener la cotización del dólar de la tabla cliente
$query_dolar = "SELECT cotizacion_dolar FROM cliente WHERE user_id=1";
$result_dolar = $conn->query($query_dolar);
$cotizacion_dolar = 1;
if ($result_dolar && $result_dolar->num_rows > 0) {
    $row_dolar = $result_dolar->fetch_assoc();
    $cotizacion_dolar = $row_dolar['cotizacion_dolar'];
}

$session_id = session_id();
$sql = "SELECT productos.id, productos.titulo, productos.descripcion, productos.precio, imagenes.url imagen, carrito.cantidad 
        FROM carrito 
        JOIN productos ON carrito.producto_id = productos.id
        LEFT JOIN ( 
            SELECT 
                producto_id, 
                id,
                url,
                @rank := IF(@prev_producto_id = producto_id, @rank + 1, 1) AS RANK,
                @prev_producto_id := producto_id
            FROM 
                imagenes, 
                (SELECT @rank := 0, @prev_producto_id := NULL) AS init
            ORDER BY 
                producto_id, id
        ) imagenes
        ON carrito.producto_id = imagenes.producto_id
        WHERE RANK=1 
        AND carrito.session_id = '$session_id'";
$result = $conn->query($sql);
$total = 0;
if ($result->num_rows > 0) {
    echo "<button id='emptyCartButton' class='button'>Vaciar Carrito</button>";
    echo "<div class='cart-grid'>";
    
    $simbolo_moneda = ($currency == 2) ? "u\$s" : "$";

    while($row = $result->fetch_assoc()) {
        $precio_producto_a_mostrar = $row["precio"];
        if ($currency == 2) {
            $precio_producto_a_mostrar = $row["precio"] / $cotizacion_dolar;
        }

        $subtotal = $precio_producto_a_mostrar * $row["cantidad"];
        $total += $subtotal;
        
        echo "<div class='cart-item' data-id='" . htmlspecialchars($row["id"]) . "'>";
        echo "<img src='imagenes/" . htmlspecialchars($row["imagen"]) . "' alt='" . htmlspecialchars($row["titulo"]) . "' class='cart-item-image'>";
        echo "<div class='cart-item-details'>";
        echo "<h2>" . htmlspecialchars($row["titulo"]) . "</h2>";
        echo "<p>" . htmlspecialchars($row["descripcion"]) . "</p>";
        
        echo "<p class='precio'>Precio: " . htmlspecialchars($simbolo_moneda) . " " . number_format($precio_producto_a_mostrar, 0, '', '.') . "</p>";
        echo "<p class='precio'>Cantidad: <button class='decrease-quantity'>-</button> <span class='quantity'>" . htmlspecialchars($row["cantidad"]) . "</span> <button class='increase-quantity'>+</button></p>";
        echo "<p class='precio'>Subtotal: " . htmlspecialchars($simbolo_moneda) . " " . number_format($subtotal, 0, '', '.') . "</p>";
        echo "<button class='remove-item button'><i class='fas fa-trash-alt'></i></button>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    echo "<div class='cart-total'>";
    echo "<h3>Total: " . htmlspecialchars($simbolo_moneda) . " " . number_format($total, 0, '', '.') . "</h3>";
    echo "</div>";
    echo "<div class='cart-actions'>";
    echo "<button id='continueShoppingButton' class='button'><i class='fas fa-shopping-cart'></i> Seguir Regalando</button>";
    echo "<a href='finalizar_compra.php?currency=" . htmlspecialchars($currency) . "' class='button'><i class='fas fa-check'></i> Finalizar Regalo</a>";
    echo "</div>";
} else {
    echo "<p>Tu carrito está vacío.</p>";
    echo "<div class='cart-actions'>";
    echo "<button id='continueShoppingButton' class='button'>Seguir Regalando</button>";
    echo "</div>";
}
$conn->close();
?>