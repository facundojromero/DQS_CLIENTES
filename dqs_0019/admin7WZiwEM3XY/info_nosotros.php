<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php';
include_once 'icon_list.php'; // Incluir la lista de iconos
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

$target_dir = "../images/about/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['nosotros'] as $id => $nosotros) {
        $nombre = mysqli_real_escape_string($conn, $nosotros['nombre']);
        $texto = mysqli_real_escape_string($conn, $nosotros['texto']);
        $activo = isset($nosotros['activo']) ? 1 : 0;
        $images = $nosotros['images'];

        // Verificar si se subió una nueva imagen
        if (isset($_FILES['nosotros']['name'][$id]['images']) && $_FILES['nosotros']['error'][$id]['images'] === UPLOAD_ERR_OK) {
            $imageFileType = strtolower(pathinfo($_FILES['nosotros']['name'][$id]['images'], PATHINFO_EXTENSION));
            $new_image_name = $id == 1 ? "img_01.jpg" : "img_02.jpg";
            $target_file = $target_dir . $new_image_name;

            // Mover la imagen subida al directorio de destino
            if (move_uploaded_file($_FILES['nosotros']['tmp_name'][$id]['images'], $target_file)) {
                $images = $new_image_name;
            } else {
                $_SESSION['mensaje'] = "Error al subir la imagen.";
                header("Location: ?new=nosotros");
                exit();
            }
        }

        // Actualizar el registro en la base de datos
        $update_query = "UPDATE info_nosotros SET 
            nombre='$nombre', 
            texto='$texto', 
            activo='$activo' 
            WHERE id='$id'";
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['mensaje'] = "La información de Nostros se ha actualizado correctamente.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la información: " . mysqli_error($conn);
        }
    }
    header("Location: ?new=nosotros");
    exit();
}

$query = "SELECT * FROM info_nosotros";
$result = mysqli_query($conn, $query);
$nosotros = [];
while ($row = mysqli_fetch_assoc($result)) {
    $nosotros[] = $row;
}



?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Info Nosotros</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    

    <h1>Nosotros</h1>
    
    
    
    
    
    
    
    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php foreach ($nosotros as $info): ?>
            <div class="event-header">
                <h3><?php echo $info['nombre']; ?></h3>
                <div>
                    <label for="activo_<?php echo $info['id']; ?>">Activo:</label>
                    <input type="checkbox" name="nosotros[<?php echo $info['id']; ?>][activo]" id="activo_<?php echo $info['id']; ?>" <?php echo $info['activo'] ? 'checked' : ''; ?>>
                    <button type="button" class="toggle-details" onclick="toggleDetails('<?php echo $info['id']; ?>')">+</button>
                </div>
            </div>
            <div class="event-details" id="details_<?php echo $info['id']; ?>">
                <div class="form-group">
                    <label for="nombre_<?php echo $info['id']; ?>">Nombre:</label>
                    <input type="text" name="nosotros[<?php echo $info['id']; ?>][nombre]" id="nombre_<?php echo $info['id']; ?>" value="<?php echo $info['nombre']; ?>" required>
                </div>
<div class="form-group">
    <label for="texto_<?php echo $info['id']; ?>">Texto:</label>
    <?php
    // Calcular un número de filas aproximado basado en la longitud del texto
    // Asumimos un promedio de 50 caracteres por fila para estimar.
    // Añadimos un mínimo de 4 filas para que no sea demasiado pequeño.
    $num_rows = max(4, ceil(strlen($info['texto']) / 100));
    ?>
    <textarea
        name="nosotros[<?php echo $info['id']; ?>][texto]"
        id="texto_<?php echo $info['id']; ?>"
        rows="<?php echo $num_rows; ?>"
        cols="80" required
    ><?php echo $info['texto']; ?></textarea>
</div>
                <div class="form-group">
                    <label for="images_<?php echo $info['id']; ?>">Imagen:</label>
                    <input type="file" name="nosotros[<?php echo $info['id']; ?>][images]" id="images_<?php echo $info['id']; ?>" accept="image/*">
                    <input type="hidden" name="nosotros[<?php echo $info['id']; ?>][images]" value="<?php echo $info['images']; ?>">
                </div>


                <?php if ($info['id'] == 1): ?>
    <div class="form-group">
        <label>Imagen Actual:</label>
        <img src="../images/about/img_01.jpg?<?php echo time(); ?>" alt="Imagen Actual" style="max-width: 100px; max-height: 100px;">
    </div>
<?php elseif ($info['id'] == 2): ?>
    <div class="form-group">
        <label>Imagen Actual:</label>
        <img src="../images/about/img_02.jpg?<?php echo time(); ?>" alt="Imagen Actual" style="max-width: 100px; max-height: 100px;">
    </div>
<?php endif; ?>



                
            </div>
        <?php endforeach; ?>
        <button type="submit">Guardar</button>
    </form>
    <script>
        function toggleDetails(id) {
            var details = document.getElementById('details_' + id);
            details.classList.toggle('active');
        }

        function selectIcon(iconClass, eventId) {
            var selected = document.querySelector('#details_' + eventId + ' .select-selected');
            var input = document.getElementById('icono_' + eventId);
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
</body>
</html>
<?php
mysqli_close($conn);
?>
