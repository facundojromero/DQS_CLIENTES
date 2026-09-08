<?php
// Función para generar un nombre aleatorio
function generarNombreCarpeta($length = 10) {
    return 'admin' . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Función para copiar recursivamente directorios y archivos
function copiarRecursivamente($origen, $destino) {
    $dir = opendir($origen);
    @mkdir($destino, 0755, true); // Asegura que el directorio destino exista con permisos recursivos
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($origen . '/' . $file) ) {
                copiarRecursivamente($origen . '/' . $file, $destino . '/' . $file);
            }
            else {
                // Si es un archivo, moverlo en lugar de copiarlo para que se elimine del origen
                if (!rename($origen . '/' . $file, $destino . '/' . $file)) {
                    error_log("Error al mover el archivo: " . $origen . '/' . $file);
                    return false; // Indicamos un error
                }
            }
        }
    }
    closedir($dir);
    return true; // Indicamos éxito
}

// Función para eliminar recursivamente directorios y archivos (necesario para limpiar el origen después de mover)
function eliminarRecursivamente($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!eliminarRecursivamente($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}


// Obtener los datos del formulario
$email = $_POST['email'];
$password = $_POST['password'];

// Encriptar la contraseña
$passwordEncriptada = password_hash($password, PASSWORD_BCRYPT);

// Incluir el archivo de conexión
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo

// Depuración: Verificar si la conexión a la BD está cargada
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} else {
    echo "Conexión a la base de datos exitosa.<br>";
}

// Insertar los datos en la tabla user
$sql = "INSERT INTO user (email, password) VALUES ('$email', '$passwordEncriptada')";
if ($conn->query($sql) === TRUE) {
    echo "Usuario registrado correctamente.<br>";
} else {
    die("Error al registrar el usuario: " . $conn->error . "<br>");
}

// Generar el nombre de la carpeta
$nombreCarpeta = generarNombreCarpeta();

// Rutas de tu directorio
// Asumiendo que 'registrar_usuario.php' está en 'admin_tmp/'
// y que los archivos y carpetas a mover están DENTRO de 'admin_tmp/'
$directorioOrigen = __DIR__; // Esto será el directorio donde está registrar_usuario.php (ej. /var/www/html/regalos_casamiento/admin_tmp)
$directorioBase = dirname(__DIR__); // Esto será un nivel arriba de admin_tmp (ej. /var/www/html/regalos_casamiento)
$directorioDestino = $directorioBase . '/' . $nombreCarpeta; // La nueva carpeta (ej. /var/www/html/regalos_casamiento/admin1a2b3c)

// Depuración: Mostrar las rutas
echo "Directorio origen: " . $directorioOrigen . "<br>";
echo "Directorio base: " . $directorioBase . "<br>";
echo "Directorio destino: " . $directorioDestino . "<br>";

// *** CAMBIO 1: Mover directorios y archivos recursivamente ***
// Primero, asegúrate de que el directorio destino no exista y, si lo hace, bórralo (opcional, para evitar conflictos en reintentos)
// Esto es si estás 'moviendo' el contenido de admin_tmp a la nueva carpeta
if (file_exists($directorioDestino)) {
    echo "El directorio destino ya existe. Intentando eliminarlo para evitar conflictos.<br>";
    if (!eliminarRecursivamente($directorioDestino)) {
        die("Error: No se pudo eliminar el directorio destino existente.");
    }
}


// Mover el contenido de admin_tmp al nuevo directorio.
// NOTA IMPORTANTE: Esta implementación MOVERÁ los archivos y carpetas de $directorioOrigen (admin_tmp)
// a la nueva carpeta, lo que significa que admin_tmp quedará VACÍO (excepto por registrar_usuario.php si no lo mueves).
// Si tu intención es dejar 'registrar_usuario.php' en 'admin_tmp' y solo mover el resto,
// necesitarás ajustar la lógica para excluir 'registrar_usuario.php' del proceso de movimiento.
// En este ejemplo, el contenido de '$directorioOrigen' (todo dentro de admin_tmp) será movido.

echo "Intentando mover contenido de " . $directorioOrigen . " a " . $directorioDestino . "<br>";

// Creamos la carpeta destino antes de mover el contenido.
if (!mkdir($directorioDestino, 0755, true)) {
    die("Error al crear la carpeta de administración: " . $directorioDestino);
} else {
    echo "Carpeta de administración creada correctamente: " . $directorioDestino . "<br>";
}

// Iterar sobre los elementos del directorio de origen y moverlos.
// Excluir el propio script registrar_usuario.php y los directorios '.' y '..'
$itemsAMover = scandir($directorioOrigen);
foreach ($itemsAMover as $item) {
    if ($item == '.' || $item == '..' || $item == basename(__FILE__)) { // Excluir el script actual
        continue;
    }

    $origenItem = $directorioOrigen . DIRECTORY_SEPARATOR . $item;
    $destinoItem = $directorioDestino . DIRECTORY_SEPARATOR . $item;

    if (is_dir($origenItem)) {
        if (!rename($origenItem, $destinoItem)) { // Mover directorios
            die("Error al mover el directorio: " . $origenItem);
        } else {
            echo "Directorio movido: " . $item . "<br>";
        }
    } elseif (is_file($origenItem)) {
        if (!rename($origenItem, $destinoItem)) { // Mover archivos
            die("Error al mover el archivo: " . $origenItem);
        } else {
            echo "Archivo movido: " . $item . "<br>";
        }
    }
}
echo "Todos los archivos y directorios movidos correctamente.<br>";

// Guardar el nombre de la carpeta en la base de datos
$sql = "INSERT INTO admin_config (nombre_carpeta) VALUES ('$nombreCarpeta')";
echo "Consulta SQL: " . $sql . "<br>"; // Mostrar la consulta SQL para depuración

if ($conn->query($sql) === TRUE) {
    echo "Nombre de carpeta guardado correctamente. La nueva ruta de administración es: " . $nombreCarpeta . "<br>";
} else {
    die("Error al guardar el nombre de la carpeta: " . $conn->error . "<br>");
}



// **PASO CLAVE PARA LA REDIRECCIÓN:**
// 1. Obtener la ruta del script actual en el contexto web (ej. /regalos_casamiento/admin_tmp/registrar_usuario.php)
$script_web_path = $_SERVER['SCRIPT_NAME'];

// 2. Subir un nivel para llegar al directorio de 'regalos_casamiento'
// Si $script_web_path es /regalos_casamiento/admin_tmp/registrar_usuario.php,
// dirname($script_web_path) es /regalos_casamiento/admin_tmp
// dirname(dirname($script_web_path)) es /regalos_casamiento
$base_del_proyecto_web = dirname(dirname($script_web_path));

// 3. Asegurarse de que no haya una doble barra al inicio si la base es la raíz del dominio
if ($base_del_proyecto_web === "/") {
    $base_del_proyecto_web = ""; // Esto evita //tu_carpeta/index.php
}

// 4. Construir la URL completa de redirección
$url_redireccion = $base_del_proyecto_web . '/' . $nombreCarpeta . '/index.php';

// Redirigir a la página de inicio en la nueva carpeta
header("Location: " . $url_redireccion);
exit();




header("Location: /$nombreCarpeta/index.php");
exit();

// Cerrar la conexión
$conn->close();
?>