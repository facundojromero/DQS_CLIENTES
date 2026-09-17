<?php
if (!isset($_SESSION)) {
    session_start();
}
include '../conexion.php';
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
    while($row = $result->fetch_assoc()) {
        $subtotal = $row["precio"] * $row["cantidad"];
        $total += $subtotal;
        echo "<div class='cart-item' data-id='" . htmlspecialchars($row["id"]) . "'>";
        echo "<img src='imagenes/" . htmlspecialchars($row["imagen"]) . "' alt='" . htmlspecialchars($row["titulo"]) . "' class='cart-item-image'>";
        echo "<div class='cart-item-details'>";
        echo "<h2>" . htmlspecialchars($row["titulo"]) . "</h2>";
        echo "<p>" . htmlspecialchars($row["descripcion"]) . "</p>";
        echo "<p class='precio'>Precio: $" . htmlspecialchars($row["precio"]) . "</p>";
        echo "<p class='precio'>Cantidad: <button class='decrease-quantity'>-</button> <span class='quantity'>" . htmlspecialchars($row["cantidad"]) . "</span> <button class='increase-quantity'>+</button></p>";
        echo "<p class='precio'>Subtotal: $" . number_format($subtotal, 2) . "</p>";
        echo "<button class='remove-item button'><i class='fas fa-trash-alt'></i></button>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    echo "<div class='cart-total'>";
    echo "<h3>Total: $" . number_format($total, 2) . "</h3>";
    echo "</div>";
    echo "<div class='cart-actions'>";
//echo "<a href='finalizar_compra.php' class='button'>Seguir comprando</a>";	
echo "<button id='continueShoppingButton' class='button'><i class='fas fa-shopping-cart'></i> Seguir Regalando</button>";
echo "<a href='finalizar_compra.php' class='button'><i class='fas fa-check'></i> Finalizar Regalo</a>";
    echo "</div>";
} else {
    echo "<p>Tu carrito está vacío.</p>";
    echo "<div class='cart-actions'>";
    echo "<button id='continueShoppingButton' class='button'>Seguir Regalando</button>";
    echo "</div>";
}
$conn->close();
?>