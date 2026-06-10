<?php
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

$sql = "UPDATE regalos SET activo = 1 WHERE id = $id";
if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>