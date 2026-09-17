<?php
$servername = "127.0.0.1";
$dbname = "u385461681_dqs_0001";
$username = "u385461681_dqs_0001_user";
$password = "Ca7d]Ze6$|>4";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>