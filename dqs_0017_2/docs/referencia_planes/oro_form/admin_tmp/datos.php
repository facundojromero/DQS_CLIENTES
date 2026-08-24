<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Incluir el archivo de conexión
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo

// Verificación de conexión
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del usuario logueado
$user_id = $_SESSION['user_id'];

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $telefono2 = $_POST['telefono2']; // ¡Nuevo campo!
    $direccion = $_POST['direccion'];
    $provincia = $_POST['provincia'];
    $ciudad = $_POST['ciudad'];
    $cbu = $_POST['cbu'];
    $cbu_titular = $_POST['cbu_titular'];    
    $alias = $_POST['alias'];
    $cbu_dolar = $_POST['cbu_dolar'];
    $alias_dolar = $_POST['alias_dolar'];
    $cotizacion_dolar = $_POST['cotizacion_dolar'];;

    $sql = "SELECT * FROM cliente a
            INNER JOIN `user` b
            ON a.user_id = b.id WHERE user_id = $user_id
            ";
            
    $result = $conn->query($sql);
if ($result->num_rows > 0) {
        $sql = "UPDATE cliente SET nombre='$nombre', apellido='$apellido', telefono='$telefono', telefono2='$telefono2', direccion='$direccion', provincia='$provincia', ciudad='$ciudad', cbu='$cbu', alias='$alias' , cbu_titular='$cbu_titular', cbu_dolar='$cbu_dolar', alias_dolar='$alias_dolar', cotizacion_dolar='$cotizacion_dolar' WHERE user_id = $user_id"; // ¡Campo telefono2 agregado!
    if ($conn->query($sql) === TRUE) {
        header("Location: index.php?new=datos");
        exit();
    } else {
        echo "Error al actualizar la información: " . $conn->error;
    }

    } else {
        $sql = "INSERT INTO cliente (user_id, nombre, apellido, telefono, telefono2, direccion, provincia, ciudad, cbu_titular, cbu, alias, cbu_dolar, alias_dolar, cotizacion_dolar) VALUES ($user_id, '$nombre', '$apellido', '$telefono', '$telefono2', '$direccion', '$provincia', '$ciudad', '$cbu_titular', '$cbu', '$alias', '$cbu_dolar', '$alias_dolar', '$cotizacion_dolar')";
        if ($conn->query($sql) === TRUE) {
            echo "Información guardada correctamente.";
        } else {
            echo "Error al guardar la información: " . $conn->error;
        }
    }
}

$sql = "SELECT * FROM cliente a
            INNER JOIN `user` b
            ON a.user_id = b.id WHERE user_id = $user_id";
$result = $conn->query($sql);
$cliente = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="cliente-info">
        <h1>Tu información</h1>

        <?php if ($cliente && !isset($_POST['modificar'])): ?>
            <div class="cliente-info">
                <p><strong>Nombre:</strong> <?php echo $cliente['nombre']; ?></p>
                <p><strong>Apellido:</strong> <?php echo $cliente['apellido']; ?></p>
                <p><strong>Mail:</strong> <?php echo $cliente['email']; ?></p>            
                <p><strong>Teléfono:</strong> <?php echo $cliente['telefono']; ?></p>
                <p><strong>Teléfono 2:</strong> <?php echo $cliente['telefono2']; ?></p> <p><strong>Dirección:</strong> <?php echo $cliente['direccion']; ?></p>
                <p><strong>Provincia:</strong> <?php echo $cliente['provincia']; ?></p>
                <p><strong>Ciudad:</strong> <?php echo $cliente['ciudad']; ?></p>
                <p><strong>CBU Titular:</strong> <?php echo $cliente['cbu_titular']; ?></p>            
                <p><strong>CBU:</strong> <?php echo $cliente['cbu']; ?></p>
                <p><strong>Alias:</strong> <?php echo $cliente['alias']; ?><p>
                <p><strong>CBU Dolar:</strong> <?php echo $cliente['cbu_dolar']; ?></p>
                <p><strong>Alias Dolar:</strong> <?php echo $cliente['alias_dolar']; ?><p>
                <p><strong>Cotización dolar:</strong> $<?php echo $cliente['cotizacion_dolar']; ?><p>                                               
                <form action="index.php?new=datos" method="post">
                <a href="?new=modificardatos" class="navbar-link">
                    <i class="fas fa-edit navbar-icon"></i> Modificar Info
                </a>
                </form>
               <p> 
                                <a href="?new=pass" class="navbar-link">
                    <i class="fas fa-key navbar-icon"></i> Cambiar contraseña
                </a>
                </form>
                
                <p>
            </div>


        <?php endif; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>