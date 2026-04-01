<?php
$servername = "127.0.0.1";
$dbname = "u385461681_dqs_0007";
$username = "u385461681_dqs_0007_user";
$password = "qeDn1|IU1>";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>