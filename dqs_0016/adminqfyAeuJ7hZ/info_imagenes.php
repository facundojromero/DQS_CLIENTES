<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ruta de la carpeta de imágenes (respetando tu estructura)
$target_dir = "../images/";
$mensaje = "";

// --- 1. LÓGICA DE SUBIDA (Nombres correlativos slider-01, 02...) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nueva_imagen'])) {
    $subio_algo = false;

    foreach ($_FILES['nueva_imagen']['tmp_name'] as $index => $tmp_name) {
        if ($_FILES['nueva_imagen']['error'][$index] === UPLOAD_ERR_OK) {
            
            // Buscamos el primer número disponible para no sobrescribir
            $i = 1;
            while (file_exists($target_dir . "slider-" . str_pad($i, 2, "0", STR_PAD_LEFT) . ".jpg")) {
                $i++;
            }
            $nombre_final = "slider-" . str_pad($i, 2, "0", STR_PAD_LEFT) . ".jpg";
            
            if (move_uploaded_file($tmp_name, $target_dir . $nombre_final)) {
                $subio_algo = true;
            }
        }
    }

    if ($subio_algo) {
        echo "Subiendo imagenes... Por finalizar...";
        header("refresh:2;url=index.php?new=imagenes");
        exit();
    }
}

// --- 2. LÓGICA DE ELIMINACIÓN (Mismo sistema que info_fotos.php) ---
if (isset($_GET['eliminar'])) {
    $archivo = basename($_GET['eliminar']);
    $archivo_path = $target_dir . $archivo;
    
    if (file_exists($archivo_path)) {
        unlink($archivo_path);
        $mensaje = "Imagen <b>$archivo</b> eliminada.";
        // Permanecemos en el mismo link
        header("refresh:1;url=index.php?new=imagenes");
    }
}

// Obtener las imágenes actuales para mostrar
$imagenes = glob($target_dir . "slider-*.{jpg,jpeg,png,gif}", GLOB_BRACE);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Slider</title>
    <style>
        .galeria { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { border: 1px solid #ccc; padding: 10px; width: 220px; text-align: center; }
        .card img { max-width: 100%; max-height: 150px; }
        .card button { margin-top: 10px; }
    </style>
</head>
<body>
    
    <h1>Administrar Slider Principal</h1>

    <?php if ($mensaje): ?>
        <div style="background-color: #e0ffe0; padding: 10px; margin-bottom: 20px;">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="index.php?new=imagenes">
        <input type="hidden" name="new" value="imagenes">
        <label>Subir nuevas imágenes:</label><br>
        <input type="file" name="nueva_imagen[]" multiple accept="image/*">
            <button type="submit">Subir</button>
    </form>

    <hr>

<div class="galeria">
        <?php foreach ($imagenes as $img_path): 
            $nombre_solo = basename($img_path);
        ?>
            <div class="card">
                <img src="<?= $img_path ?>?v=<?= time() ?>" alt="Slider">
                <p style="font-size: 11px;"><?= $nombre_solo ?></p>
                
                <form method="get">
                    <input type="hidden" name="new" value="imagenes">
                    <input type="hidden" name="eliminar" value="<?= $nombre_solo ?>">
                    <button type="submit" onclick="return confirm('¿Eliminar?')">Eliminar</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>



</body>
</html>