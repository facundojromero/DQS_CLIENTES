<?php
// contador.php

// Inicia o reanuda la sesión del usuario
session_start();


// --- Función para obtener la IP del usuario real ---
function obtenerIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_lista = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_lista[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}

// Obtener la IP y la página actual
$ip_usuario = obtenerIp();
$pagina_visitada = $_SERVER['REQUEST_URI'];

// Lógica para verificar si la IP ya ha visitado ESTA PÁGINA en los últimos 30 minutos
$intervalo_segundos = 1800; // 30 minutos
$tiempo_limite = date('Y-m-d H:i:s', time() - $intervalo_segundos);

// Preparamos la nueva consulta de verificación
$sql_verificacion = "SELECT COUNT(*) FROM visitas WHERE ip_usuario = ? AND pagina_visitada = ? AND fecha_visita >= ?";
$stmt_verificacion = $conn->prepare($sql_verificacion);
$stmt_verificacion->bind_param("sss", $ip_usuario, $pagina_visitada, $tiempo_limite);
$stmt_verificacion->execute();
$stmt_verificacion->bind_result($visitas_recientes);
$stmt_verificacion->fetch();
$stmt_verificacion->close();

// Si no hay visitas recientes para esta IP y página (el conteo es 0), insertamos una nueva
if ($visitas_recientes == 0) {
    // Prepara la consulta SQL para insertar la visita
    $sql_insercion = "INSERT INTO visitas (ip_usuario, pagina_visitada) VALUES (?, ?)";
    $stmt_insercion = $conn->prepare($sql_insercion);
    $stmt_insercion->bind_param("ss", $ip_usuario, $pagina_visitada);

    // Ejecuta la consulta de inserción
    if (!$stmt_insercion->execute()) {
        error_log("Error al insertar la visita: " . $stmt_insercion->error);
    }
    $stmt_insercion->close();
}


?>