<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo
// Incluir la lista de iconos
include_once 'icon_list.php';


$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);
$target_dir = "../images/";
$max_files = 3;
$recommended_width = 1920;
$recommended_height = 1000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los valores actuales de la portada
    $query_portada = "SELECT portada_titulo, portada_frase, portada_fecha, portada_fecha_hora FROM info_casamiento";
    $result_portada = mysqli_query($conn, $query_portada);
    $row_portada = mysqli_fetch_assoc($result_portada);

    // Inicializar mensaje de confirmación
    $mensaje_confirmacion = '';

    // Actualizar los textos de la portada
    $portada_titulo = mysqli_real_escape_string($conn, $_POST['portada_titulo']);
    $portada_frase = mysqli_real_escape_string($conn, $_POST['portada_frase']);
    $portada_fecha = mysqli_real_escape_string($conn, $_POST['portada_fecha']);
    $portada_fecha_hora = mysqli_real_escape_string($conn, $_POST['portada_fecha_hora']);

    $textos_actualizados = false;

    if ($portada_titulo != $row_portada['portada_titulo'] || 
        $portada_frase != $row_portada['portada_frase'] || 
        $portada_fecha != $row_portada['portada_fecha'] || 
        $portada_fecha_hora != $row_portada['portada_fecha_hora']) {
        
        $update_query = "UPDATE info_casamiento SET 
            portada_titulo='$portada_titulo', 
            portada_frase='$portada_frase', 
            portada_fecha='$portada_fecha', 
            portada_fecha_hora='$portada_fecha_hora'";

        if (mysqli_query($conn, $update_query)) {
            $mensaje_confirmacion .= "Los textos de la portada se han actualizado correctamente.<br>";
            $textos_actualizados = true;
        } else {
            $mensaje_confirmacion .= "Error al actualizar los textos de la portada: " . mysqli_error($conn) . "<br>";
        }
    }
    
    // Actualizar la más info
    $info_actualizada = false;
    foreach ($_POST['mas_info'] as $id => $info) {
        $titulo = mysqli_real_escape_string($conn, $info['titulo']);
        $descripcion = mysqli_real_escape_string($conn, $info['descripcion']);
        $direccion = mysqli_real_escape_string($conn, $info['direccion']);
        $url = mysqli_real_escape_string($conn, $info['url']);
        $icono = mysqli_real_escape_string($conn, $info['icono']);
        $activo = isset($info['activo']) ? 1 : 0;
    
        // Obtener los valores actuales de la info
        $query_actual = "SELECT * FROM info_otra WHERE id='$id'";
        $result_actual = mysqli_query($conn, $query_actual);
        $row_actual = mysqli_fetch_assoc($result_actual);
    
        // Comparar los valores actuales con los valores enviados
        if ($titulo != $row_actual['titulo'] || 
            $descripcion != $row_actual['descripcion'] || 
            $direccion != $row_actual['direccion'] || 
            $url != $row_actual['url'] || 
            $icono != $row_actual['icono'] || 
            $activo != $row_actual['activo']) {
            
            $update_query_info = "UPDATE info_otra SET 
                titulo='$titulo', 
                descripcion='$descripcion', 
                direccion='$direccion', 
                url='$url', 
                icono='$icono', 
                activo='$activo' 
                WHERE id='$id'";
    
            if (mysqli_query($conn, $update_query_info)) {
                $info_actualizada = true;
            } else {
                $mensaje_confirmacion .= "Error al actualizar la info $id: " . mysqli_error($conn) . "<br>";
            }
        }
    }
    
    if ($info_actualizada) {
        $mensaje_confirmacion .= "La información adicional se ha actualizado correctamente.<br>";
    }
    
    
    
        // Actualizar los eventos
        $eventos_actualizados = false;
        foreach ($_POST['eventos'] as $id => $evento) {
            $titulo = mysqli_real_escape_string($conn, $evento['titulo']);
            $descripcion = mysqli_real_escape_string($conn, $evento['descripcion']);
            $direccion = mysqli_real_escape_string($conn, $evento['direccion']);
            $url = mysqli_real_escape_string($conn, $evento['url']);
            $icono = mysqli_real_escape_string($conn, $evento['icono']);
            $activo = isset($evento['activo']) ? 1 : 0;
        
            // Obtener los valores actuales del evento
            $query_actual = "SELECT * FROM info_eventos WHERE id='$id'";
            $result_actual = mysqli_query($conn, $query_actual);
            $row_actual = mysqli_fetch_assoc($result_actual);
        
            // Comparar los valores actuales con los valores enviados
            if ($titulo != $row_actual['titulo'] || 
                $descripcion != $row_actual['descripcion'] || 
                $direccion != $row_actual['direccion'] || 
                $url != $row_actual['url'] || 
                $icono != $row_actual['icono'] || 
                $activo != $row_actual['activo']) {
                
                $update_query_evento = "UPDATE info_eventos SET 
                    titulo='$titulo', 
                    descripcion='$descripcion', 
                    direccion='$direccion', 
                    url='$url', 
                    icono='$icono', 
                    activo='$activo' 
                    WHERE id='$id'";
        
                if (mysqli_query($conn, $update_query_evento)) {
                    $eventos_actualizados = true;
                } else {
                    $mensaje_confirmacion .= "Error al actualizar el evento $id: " . mysqli_error($conn) . "<br>";
                }
            }
        }
        
        if ($eventos_actualizados) {
            $mensaje_confirmacion .= "Los eventos se han actualizado correctamente.<br>";
        }
    


    // Subir imágenes
    $imagenes_subidas = false;
    for ($i = 1; $i <= $max_files; $i++) {
        $file_key = "slider-$i";
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $target_file = $target_dir . "slider-0$i.jpg";
            $image_info = getimagesize($_FILES[$file_key]['tmp_name']);
            $width = $image_info[0];
            $height = $image_info[1];

            if ($width != $recommended_width || $height != $recommended_height) {
                $mensaje_confirmacion .= "La imagen $file_key no tiene el tamaño recomendado de 1920x1000. Se ajustará automáticamente.<br>";
                $image = imagecreatefromjpeg($_FILES[$file_key]['tmp_name']);
                $resized_image = imagescale($image, $recommended_width, $recommended_height);
                imagejpeg($resized_image, $target_file);
                imagedestroy($image);
                imagedestroy($resized_image);
            } else {
                move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file);
            }
            $mensaje_confirmacion .= "La imagen $file_key se ha subido correctamente.<br>";
            $imagenes_subidas = true;
        }
    }

    // Guardar el mensaje de confirmación en la sesión
    $_SESSION['mensaje'] = $mensaje_confirmacion;

    // Redirigir a index.php?new=portada
    header("Location: index.php?new=portada");
    exit();
}




// consulta portada
$query = "SELECT portada_titulo, portada_frase, portada_fecha, portada_fecha_hora FROM info_casamiento";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $portada_titulo = $row['portada_titulo'];
    $portada_frase = $row['portada_frase'];
    $portada_fecha = $row['portada_fecha'];
    $portada_fecha_hora = $row['portada_fecha_hora'];
} else {
    $portada_titulo = "";
    $portada_frase = "";
    $portada_fecha = "";
    $portada_fecha_hora = "";
}


// consulta a más info
$query_mas_info = "SELECT * FROM info_otra";
$result_mas_info = mysqli_query($conn, $query_mas_info);
$mas_info = [];
if ($result_mas_info && mysqli_num_rows($result_mas_info) > 0) {
    while ($row_info = mysqli_fetch_assoc($result_mas_info)) {
        $mas_info[] = $row_info;
    }
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Portada</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    
        <div class="container">
            <?php include 'menu_info.php'; ?>
        </div>
    
    
    <h1>Modificar portada:</h1>
    
    
        <?php if ($mensaje): ?>
            <div class="alert">
                <p><?php echo $mensaje; ?></p>
            </div>
        <?php endif; ?>
        
        
        
           
        <form method="post" action="" enctype="multipart/form-data" class="formulario">
            <div class="form-group">
                <label for="portada_titulo">Título de la Portada:</label>
                <input type="text" name="portada_titulo" id="portada_titulo" value="<?php echo $portada_titulo; ?>" required>
            </div>
            <div class="form-group">
                <label for="portada_frase">Frase de la Portada:</label>
                <input type="text" name="portada_frase" id="portada_frase" value="<?php echo $portada_frase; ?>" required>
            </div>
            <div class="form-group">
                <label for="portada_fecha">Fecha de la Portada:</label>
                <input type="text" name="portada_fecha" id="portada_fecha" value="<?php echo $portada_fecha; ?>" required>
            </div>
            <div class="form-group">
                <label for="portada_fecha_hora">Fecha y Hora de la Portada:</label>
                <input type="datetime-local" name="portada_fecha_hora" id="portada_fecha_hora" value="<?php echo $portada_fecha_hora; ?>" required>
            </div>
            
            
            
            
            <div class="form-group">
                <h1>Subir imágenes de portada:</h1>
                <input type="file" name="slider-1" accept="image/*">
                <input type="file" name="slider-2" accept="image/*">
                <input type="file" name="slider-3" accept="image/*">
            </div>
            
            
            



            
        
            <?php include 'info_eventos.php'; ?>




<script>
function toggleDetailsInfo(id) {
    var details = document.getElementById('details_info_' + id);
    details.classList.toggle('active');
}
function selectIconInfo(iconClass, infoId) {
    var selected = document.querySelector('#details_info_' + infoId + ' .select-selected');
    var input = document.getElementById('icono_info_' + infoId);
    selected.innerHTML = '<i class="' + iconClass + '"></i>';
    input.value = iconClass;
    closeAllSelect();
}
function closeAllSelect() {
    var items = document.getElementsByClassName('select-items');
    for (var i = 0; i < items.length; i++) {
        items[i].classList.add('select-hide');
    }
}
document.addEventListener('click', function(e) {
    if (!e.target.matches('.select-selected, .select-selected *')) {
        closeAllSelect();
    }
});
document.querySelectorAll('.select-selected').forEach(function(selected) {
    selected.addEventListener('click', function() {
        closeAllSelect();
        this.nextElementSibling.classList.toggle('select-hide');
        this.classList.toggle('select-arrow-active');
    });
});
</script>




            <div class="confirmacion-body">
                <div>
                    <button type="submit" class="button">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
                <div>
                    <button type="button" class="button" onclick="window.history.back();">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
            
            
            
            
        </form>
    
    
    
        </div>
    

</body>
</html>


<?php
// Cerrar la conexión
mysqli_close($conn);
?>