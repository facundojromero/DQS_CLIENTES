<?php
session_start();
include '../conexion.php';
include_once 'regalo_libre_helper.php';

asegurarEstructuraRegaloLibre($conn);
$regaloLibreId = obtenerOCrearProductoRegaloLibre($conn);

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';
$session_id = session_id();

if ($id === $regaloLibreId) {
    echo 'Cantidad modificada';
    $conn->close();
    exit;
}

if ($action === 'increase') {
    $sql = 'UPDATE carrito SET cantidad = cantidad + 1 WHERE producto_id = ? AND session_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id, $session_id);
} elseif ($action === 'decrease') {
    $sql = 'UPDATE carrito SET cantidad = cantidad - 1 WHERE producto_id = ? AND session_id = ? AND cantidad > 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id, $session_id);
} else {
    echo 'Acción inválida';
    $conn->close();
    exit;
}

if ($stmt->execute()) {
    echo 'Cantidad modificada';
} else {
    echo 'Error: ' . $stmt->error;
}

$stmt->close();
$conn->close();
?>
