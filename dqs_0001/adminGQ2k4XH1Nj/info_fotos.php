<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$target_dir = "../images/gallery/";
$mensaje = "";

// Subir nuevas imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nueva_imagen'])) {
    $subio_algo = false;

    foreach ($_FILES['nueva_imagen']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['nueva_imagen']['error'][$index] === UPLOAD_ERR_OK) {
            $nombre_original = basename($_FILES['nueva_imagen']['name'][$index]);
            $nombre_final = uniqid() . '_' . $nombre_original;
            move_uploaded_file($tmp_name, $target_dir . $nombre_final);
            $subio_algo = true;
        }
    }

    if ($subio_algo) {
        echo "Subiendo imagenes... Por finalizar...";
        header("refresh:2;url=index.php?new=fotos");
        exit();
    }
}


// Eliminar una imagen
if (isset($_GET['eliminar'])) {
    $archivo = basename($_GET['eliminar']);
    $archivo_path = $target_dir . $archivo;
    if (file_exists($archivo_path)) {
        unlink($archivo_path);
        $mensaje .= "Imagen <b>$archivo</b> eliminada correctamente.<br>";
    }
}

$imagenes = array_diff(scandir($target_dir), array('.', '..'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Galería de Imágenes</title>
    <style>
        .galeria { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { border: 1px solid #ccc; padding: 10px; width: 220px; text-align: center; }
        .card img { max-width: 100%; max-height: 150px; }
        .card button { margin-top: 10px; }
    </style>
</head>
<body>
    
    <h1>Galería de Imágenes</h1>

    <?php if ($mensaje): ?>
        <div style="background-color: #e0ffe0; padding: 10px; margin-bottom: 20px;">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="new" value="fotos">
    <label>Subir nuevas imágenes:</label><br>
    <input type="file" name="nueva_imagen[]" multiple accept="image/*">
    <button type="submit">Subir</button>
</form>

    <hr>

    <div class="galeria">
        <?php foreach ($imagenes as $img): ?>
            <div class="card">
                <img src="<?= $target_dir . $img ?>?v=<?= filemtime($target_dir . $img) ?>" alt="<?= $img ?>">

                <form method="get">
                    <input type="hidden" name="new" value="fotos">
                    <input type="hidden" name="eliminar" value="<?= $img ?>">
                    <button type="submit">Eliminar</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
