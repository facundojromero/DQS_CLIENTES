<?php
session_start();
include '../conexion.php';
$id = $_POST['id'];
$action = $_POST['action'];
$session_id = session_id();

if ($action == 'increase') {
    $sql = "UPDATE carrito SET cantidad = cantidad + 1 WHERE producto_id = '$id' AND session_id = '$session_id'";
} else if ($action == 'decrease') {
    $sql = "UPDATE carrito SET cantidad = cantidad - 1 WHERE producto_id = '$id' AND session_id = '$session_id' AND cantidad > 1";
}

if ($conn->query($sql) === TRUE) {
    echo "Cantidad modificada";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>