<?php
session_start();
include '../conexion.php';
include_once 'regalo_libre_helper.php';

dqs_require_gifts_enabled($conn, 'text');

if (!mostrarListaRegalosHabilitada($conn)) {
    responderListaRegalosNoDisponible('html');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$session_id = session_id();

$sql = 'DELETE FROM carrito WHERE producto_id = ? AND session_id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $id, $session_id);

if ($stmt->execute()) {
    echo 'Producto eliminado';
} else {
    echo 'Error: ' . $stmt->error;
}

$stmt->close();
$conn->close();
?>
