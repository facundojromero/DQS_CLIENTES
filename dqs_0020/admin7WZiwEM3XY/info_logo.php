<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

$target_file = "../images/logo/logo.jpg";
$logo_preview_url = $target_file;
if (is_file($target_file)) {
    $logo_modified_at = @filemtime($target_file);
    if ($logo_modified_at !== false) {
        $logo_preview_url .= '?v=' . $logo_modified_at;
    }
}
$recommended_width = 180;
$recommended_height = 61;
$mensaje_confirmacion = '';
$logo_subido = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $image_info = getimagesize($_FILES['logo']['tmp_name']);
        $width = $image_info[0];
        $height = $image_info[1];

        // Conservar el archivo original para no alterar su relación de aspecto.
        if ($width != $recommended_width || $height != $recommended_height) {
            $mensaje_confirmacion .= "La imagen no tiene el tamaño recomendado de 180x61. Se adaptará manteniendo su proporción.<br>";
        }
        move_uploaded_file($_FILES['logo']['tmp_name'], $target_file);

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
                    <img src="<?= htmlspecialchars($logo_preview_url, ENT_QUOTES, 'UTF-8') ?>" alt="Logo actual" class="logo-preview-image">
                <?php else: ?>
                    <div class="placeholder">Sin logo</div>
                <?php endif; ?>
            </div>
            <div class="logo-input">
                <label for="logo">Seleccionar nuevo logo (180x61 recomendado):</label>
                <small>Las imágenes con otras dimensiones se adaptarán manteniendo su proporción.</small>
                <input type="file" name="logo" id="logo" accept="image/*">
            </div>
        </div>
        <button type="submit"><i class="fas fa-upload"></i> Subir Logo</button>
    </form>

</body>
</html>
