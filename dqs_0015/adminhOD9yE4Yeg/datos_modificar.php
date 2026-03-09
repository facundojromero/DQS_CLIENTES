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


$sql = "SELECT * FROM cliente WHERE user_id = $user_id";
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
        <h1>Modificar tu información</h1>

        <div class="cliente-info">        
            <form action="index.php?new=datos" method="post" class="formulario">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo $cliente['nombre'] ?? ''; ?>" required><br>

                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" value="<?php echo $cliente['apellido'] ?? ''; ?>" required><br>

                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo $cliente['telefono'] ?? ''; ?>"><br>

                <label for="telefono2">Teléfono 2:</label> <input type="text" id="telefono2" name="telefono2" value="<?php echo $cliente['telefono2'] ?? ''; ?>"><br>

                <label for="direccion">Dirección:</label>
                <input type="text" id="direccion" name="direccion" value="<?php echo $cliente['direccion'] ?? ''; ?>"><br>

                <label for="provincia">Provincia:</label>
                <input type="text" id="provincia" name="provincia" value="<?php echo $cliente['provincia'] ?? ''; ?>"><br>

                <label for="ciudad">Ciudad:</label>
                <input type="text" id="ciudad" name="ciudad" value="<?php echo $cliente['ciudad'] ?? ''; ?>"><br>
                
                <label for="cbu_titular">CBU Titular:</label>
                <input type="text" id="cbu_titular" name="cbu_titular" value="<?php echo $cliente['cbu_titular'] ?? ''; ?>" required><br>        

                <label for="cbu">CBU:</label>
                <input type="text" id="cbu" name="cbu" value="<?php echo $cliente['cbu'] ?? ''; ?>" required><br>

                <label for="alias">Alias:</label>
                <input type="text" id="alias" name="alias" value="<?php echo $cliente['alias'] ?? ''; ?>" required><br>
                
                
                <label for="cbu">CBU Dolar:</label>
                <input type="text" id="cbu_dolar" name="cbu_dolar" value="<?php echo $cliente['cbu_dolar'] ?? ''; ?>"><br>

                <label for="alias">Alias Dolar:</label>
                <input type="text" id="alias_dolar" name="alias_dolar" value="<?php echo $cliente['alias_dolar'] ?? ''; ?>"><br>
                
                <label for="alias">Cotización Dolar:</label>
                <input type="text" id="cotizacion_dolar" name="cotizacion_dolar" value="<?php echo $cliente['cotizacion_dolar'] ?? ''; ?>"><br>
                
                

                <button type="submit" name="guardar" class="navbar-link">
                    <i class="fas fa-save navbar-icon"></i> Guardar
                </button>
            </form>
        </div>

    </div>
</body>
</html>
<?php
$conn->close();
?>