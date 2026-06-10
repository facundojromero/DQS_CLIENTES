<?php
$servername = "127.0.0.1";
$dbname = "u385461681_dqs_0016";
$username = "u385461681_dqs_0016_user";
$password = "Mk?3Kx61Me";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>