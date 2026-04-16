<?php
session_start();
include 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    $stmt = $conn->prepare("SELECT id, clave FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if (($clave) == $row['clave'])  {
            $_SESSION['usuario_id'] = $row['id'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="formato.css">
</head>
<body>
<div class="center-panel">
    <h2>Logueate</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Usuario:</label><br>
        <input type="text" name="usuario" required><br><br>
        <label>Pass:</label><br>
        <input type="password" name="clave" required><br><br>
        <button class="action-button" type="submit">Ingresar</button>
    </form>
</div>
</body>
</html>