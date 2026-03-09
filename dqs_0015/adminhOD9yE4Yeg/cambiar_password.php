<?php
// Asegúrate de iniciar la sesión en index.php
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>Debes iniciar sesión para cambiar tu contraseña.</p>";
    exit();
}

// Incluir el archivo de conexión a la base de datos
include_once '../conexion.php'; // Asegúrate de que la ruta sea correcta

// Inicializar la variable de sesión para el estado de la operación
if (!isset($_SESSION['password_status'])) {
    $_SESSION['password_status'] = null;
}

// Procesar el formulario si se envió una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_SESSION['user_id'];
    $password_actual = $_POST['current_password'];
    $password_nueva = $_POST['new_password'];
    $password_confirmar = $_POST['confirm_password'];

    // Validar los datos del formulario
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $_SESSION['password_status'] = 'empty_fields';
    } elseif ($password_nueva !== $password_confirmar) {
        $_SESSION['password_status'] = 'mismatch';
    } else {
        // 1. Obtener la contraseña actual desde la base de datos
        $sql = "SELECT password FROM user WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            $password_encriptada_db = $fila['password'];

            // 2. Verificar la contraseña actual
            if (password_verify($password_actual, $password_encriptada_db)) {
                // 3. Encriptar y actualizar la nueva contraseña
                $nueva_password_encriptada = password_hash($password_nueva, PASSWORD_BCRYPT);
                $sql_update = "UPDATE user SET password = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $nueva_password_encriptada, $id_usuario);

                if ($stmt_update->execute()) {
                    $_SESSION['password_status'] = 'success';
                } else {
                    $_SESSION['password_status'] = 'update_error';
                }
                $stmt_update->close();
            } else {
                $_SESSION['password_status'] = 'incorrect_password';
            }
        } else {
            $_SESSION['password_status'] = 'user_not_found';
        }
        $stmt->close();
    }
    
    $conn->close();

    // Redireccionar siempre a la página de índice con el parámetro correcto
    header('Location: ?new=pass');
    exit();
}

$conn->close();

// Lógica para mostrar el mensaje basado en el estado de la sesión
$mensaje = "";
$clase_mensaje = "";
if (isset($_SESSION['password_status'])) {
    switch ($_SESSION['password_status']) {
        case 'success':
            $mensaje = "Contraseña actualizada correctamente.";
            $clase_mensaje = "green";
            break;
        case 'empty_fields':
            $mensaje = "Todos los campos son obligatorios.";
            $clase_mensaje = "red";
            break;
        case 'mismatch':
            $mensaje = "La nueva contraseña y la confirmación no coinciden.";
            $clase_mensaje = "red";
            break;
        case 'incorrect_password':
            $mensaje = "La contraseña actual es incorrecta.";
            $clase_mensaje = "red";
            break;
        case 'update_error':
            $mensaje = "Error al actualizar la contraseña.";
            $clase_mensaje = "red";
            break;
        case 'user_not_found':
            $mensaje = "Usuario no encontrado.";
            $clase_mensaje = "red";
            break;
    }
    // Eliminar la variable de sesión después de mostrar el mensaje
    unset($_SESSION['password_status']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <style>
        .red { color: red; }
        .green { color: green; }
    </style>
</head>
<body>
    <h2>Cambiar Contraseña</h2>
    <?php if ($mensaje): ?>
        <p class="<?php echo $clase_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>
    <form action="?new=pass" method="post">
        <label for="current_password">Contraseña actual:</label><br>
        <input type="password" id="current_password" name="current_password" required><br><br>

        <label for="new_password">Nueva contraseña:</label><br>
        <input type="password" id="new_password" name="new_password" required><br><br>

        <label for="confirm_password">Confirmar nueva contraseña:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <button type="submit">Cambiar Contraseña</button>
    </form>
    <p></p>
     <a href="?new=datos">
        <button>Volver</button>
    </a>
</body>
</html>