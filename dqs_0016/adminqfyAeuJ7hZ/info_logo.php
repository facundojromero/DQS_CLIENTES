<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

$target_file = "../images/logo/logo.jpg";
$recommended_width = 180;
$recommended_height = 61;
$mensaje_confirmacion = '';
$logo_subido = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $image_info = getimagesize($_FILES['logo']['tmp_name']);
        $width = $image_info[0];
        $height = $image_info[1];

        // Si no es del tamaño recomendado, redimensionar
        if ($width != $recommended_width || $height != $recommended_height) {
            $mensaje_confirmacion .= "La imagen no tiene el tamaño recomendado de 180x61. Se ajustará automáticamente.<br>";
            $image = imagecreatefromjpeg($_FILES['logo']['tmp_name']);
            $resized_image = imagescale($image, $recommended_width, $recommended_height);
            imagejpeg($resized_image, $target_file);
            imagedestroy($image);
            imagedestroy($resized_image);
        } else {
            move_uploaded_file($_FILES['logo']['tmp_name'], $target_file);
        }

        $mensaje_confirmacion .= "La imagen se ha subido correctamente como logo.jpg.<br>";
        $logo_subido = true;
    }

    $_SESSION['mensaje'] = $mensaje_confirmacion;
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Logo</title>
    <link rel="stylesheet" href="combined-styles.css">
</head>
<body>

    <h1>Subir Logo</h1>

    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?= $mensaje; ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div class="logo-container">
            <div class="logo-preview">
                <?php if (file_exists($target_file)): ?>
                    <img src="<?= $target_file . '?v=' . filemtime($target_file) ?>" alt="Logo actual" width="180" height="61">
                <?php else: ?>
                    <div class="placeholder">Sin logo</div>
                <?php endif; ?>
            </div>
            <div class="logo-input">
                <label for="logo">Seleccionar nuevo logo (180x61 recomendado):</label>
                <input type="file" name="logo" id="logo" accept="image/*">
            </div>
        </div>
        <button type="submit"><i class="fas fa-upload"></i> Subir Logo</button>
    </form>

</body>
</html>
