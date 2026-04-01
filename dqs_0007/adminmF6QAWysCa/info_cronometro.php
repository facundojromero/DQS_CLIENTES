<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php';
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portada_fecha_hora = mysqli_real_escape_string($conn, $_POST['portada_fecha_hora']);

    $update_query = "UPDATE info_casamiento SET 
        portada_fecha_hora='$portada_fecha_hora'";
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['mensaje'] = "El cronometro se ha actualizado correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar la información: " . mysqli_error($conn);
    }
    header("Location: ?new=cronometro");
    exit();
}

$query = "SELECT portada_fecha_hora FROM info_casamiento";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Info Casamiento</title>
    <link rel="stylesheet" href="combined-styles.css">
</head>
<body>
    

    <h1>Configurar cronometro</h1>
    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="">

<div class="form-group">
    <label for="portada_fecha_hora">Fecha y Hora del cronometro</label>
    <input type="datetime-local" name="portada_fecha_hora" id="portada_fecha_hora" value="<?php echo $row['portada_fecha_hora']; ?>" required>
</div>


        <button type="submit">Guardar</button>
    </form>
</body>
</html>
<?php
mysqli_close($conn);
?>
