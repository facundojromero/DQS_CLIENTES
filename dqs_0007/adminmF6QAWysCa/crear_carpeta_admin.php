<?php
// Función para generar un nombre aleatorio
function generarNombreCarpeta($length = 10) {
    return 'admin' . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Generar el nombre de la carpeta
$nombreCarpeta = generarNombreCarpeta();

// Ruta base de tu directorio
$directorioBase = __DIR__ . "/../"; // Subir un nivel desde admin_tmp
$directorioAdmin = $directorioBase . $nombreCarpeta;

// Depuración: Mostrar las rutas
echo "Directorio base: " . $directorioBase . "<br>";
echo "Directorio admin: " . $directorioAdmin . "<br>";

// Crear la carpeta
if (!mkdir($directorioAdmin, 0755, true)) {
    die("Error al crear la carpeta de administración");
} else {
    echo "Carpeta de administración creada correctamente: " . $directorioAdmin . "<br>";
}

// Mover los archivos de administración a la nueva carpeta
$archivosAdmin = glob(__DIR__ . '/*'); // Obtener archivos en admin_tmp
foreach ($archivosAdmin as $archivo) {
    if (is_file($archivo)) { // Asegurarse de que solo se muevan archivos, no carpetas
        $nombreArchivo = basename($archivo);
        rename($archivo, $directorioAdmin . '/' . $nombreArchivo);
    }
}

// Determinar la ruta del archivo de conexión
$rutaConexion = file_exists($directorioBase . 'conexion.php') ? $directorioBase . 'conexion.php' : $directorioAdmin . '/../conexion.php';

// Incluir el archivo de conexión
include_once $rutaConexion;

// Depuración: Verificar si la conexión a la BD está cargada
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} else {
    echo "Conexión a la base de datos exitosa.<br>";
}

// Guardar el nombre de la carpeta en la base de datos
$sql = "INSERT INTO admin_config (nombre_carpeta) VALUES ('$nombreCarpeta')";
echo "Consulta SQL: " . $sql . "<br>"; // Mostrar la consulta SQL para depuración

if ($conn->query($sql) === TRUE) {
    echo "Nombre de carpeta guardado correctamente. La nueva ruta de administración es: " . $nombreCarpeta;
} else {
    echo "Error al guardar el nombre de la carpeta: " . $conn->error . "<br>";
}

// Verificar si la tabla existe
$result = $conn->query("SHOW TABLES LIKE 'admin_config'");
if ($result->num_rows == 0) {
    echo "La tabla 'admin_config' no existe.<br>";
} else {
    echo "La tabla 'admin_config' existe.<br>";
}

// Cerrar la conexión
$conn->close();
?>
