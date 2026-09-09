<?php
session_start();
include '../conexion.php';
include_once 'regalo_libre_helper.php';

dqs_require_gifts_enabled($conn, 'text');

if (!mostrarListaRegalosHabilitada($conn)) {
    responderListaRegalosNoDisponible('html');
}

$session_id = session_id();
$sql = "DELETE FROM carrito WHERE session_id = '$session_id'";
if ($conn->query($sql) === TRUE) {
    echo "Carrito vaciado";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>