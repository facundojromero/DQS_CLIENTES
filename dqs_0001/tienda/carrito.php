<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $producto_id = $_POST['producto_id'];
    $session_id = session_id();

    // Incluir el archivo de conexión
    include '../conexion.php';

    // Verificar si el producto ya está en el carrito
    $sql = "SELECT * FROM carrito WHERE session_id = '$session_id' AND producto_id = $producto_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Si el producto ya está en el carrito, incrementar la cantidad
        $sql = "UPDATE carrito SET cantidad = cantidad + 1 WHERE session_id = '$session_id' AND producto_id = $producto_id";
    } else {
        // Si el producto no está en el carrito, agregarlo
        $sql = "INSERT INTO carrito (session_id, producto_id) VALUES ('$session_id', $producto_id)";
    }

    if ($conn->query($sql) === TRUE) {
        echo json_encode(array("status" => "success", "message" => "Producto agregado al carrito"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Error al agregar el producto al carrito: " . $conn->error));
    }

    $conn->close();
} else {
    echo json_encode(array("status" => "error", "message" => "Método no permitido"));
}
?>