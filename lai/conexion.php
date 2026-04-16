<?php
$host = '127.0.0.1';
$user = 'u385461681_lai_prueba_us';
$password = 'Curuzu.1810';
$dbname = 'u385461681_lai_prueba';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>