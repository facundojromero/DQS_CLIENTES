<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php';
include_once 'icon_list.php';
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

$target_dir = "../images/";
$max_files = 3;
$recommended_width = 1920;
$recommended_height = 1000;
$mensaje_confirmacion = '';

// Subir imágenes
$imagenes_subidas = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $mensaje_confirmacion .= "La imagen del $file_key se ha subido correctamente.<br>";
            $imagenes_subidas = true;
        }
    }

    $_SESSION['mensaje'] = $mensaje_confirmacion;
    header("Location: " . $_SERVER['REQUEST_URI']); // Refrescar para evitar resubir
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Imágenes</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>


    <h1>Subir imagenes de Portada</h1>

    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?= $mensaje; ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div class="slider-container">
            <?php for ($i = 1; $i <= $max_files; $i++):
                $filename = sprintf("slider-0%d.jpg", $i);
                $filepath = $target_dir . $filename;
                $file_exists = file_exists($filepath);
            ?>
            <div class="slider-card">
                <div class="slider-preview">
                    <?php if ($file_exists): ?>
                        <img src="<?= $filepath . '?v=' . filemtime($filepath) ?>" alt="Imagen <?= $i ?>" class="slider-image">
                    <?php else: ?>
                        <div class="placeholder">Sin imagen</div>
                    <?php endif; ?>
                </div>
                <div class="slider-input">
                    <label for="slider-<?= $i ?>">Reemplazar Imagen <?= $i ?>:</label>
                    <input type="file" name="slider-<?= $i ?>" id="slider-<?= $i ?>" accept="image/*">
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <button type="submit" class="submit-button"><i class="fas fa-upload"></i> Subir Imágenes</button>
    </form>
</body>
</html>
<?php
mysqli_close($conn);
?>
