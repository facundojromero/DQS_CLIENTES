<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php';
include_once 'icon_list.php'; // Asegúrate de que 'icon_list.php' define $iconos_disponibles

$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['info_otra'] as $id => $evento) {
        $titulo = mysqli_real_escape_string($conn, $evento['titulo']);
        $descripcion = mysqli_real_escape_string($conn, $evento['descripcion']);
        $direccion = mysqli_real_escape_string($conn, $evento['direccion']);
        $url = mysqli_real_escape_string($conn, $evento['url']);
        $icono = mysqli_real_escape_string($conn, $evento['icono']);
        $activo = isset($evento['activo']) ? 1 : 0;
        // Se elimina la captación de $orden
        
        // Actualizar el registro en la base de datos (sin 'orden')
        $update_query = "UPDATE info_otra SET
            titulo='$titulo',
            descripcion='$descripcion',
            direccion='$direccion',
            url='$url',
            icono='$icono',
            activo='$activo'
            WHERE id='$id'";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['mensaje'] = "La información se ha actualizado correctamente.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la información: " . mysqli_error($conn);
        }
    }
    header("Location: ?new=masinfo");
    exit();
}

// La consulta SELECT ya no necesita 'orden' si no la usas, pero dejarla no causaría error
$query = "SELECT * FROM info_otra";
$result = mysqli_query($conn, $query);
$eventos = []; // Renombrado de $eventos a $info_otra para mayor claridad, pero se mantiene $eventos en HTML para compatibilidad.
while ($row = mysqli_fetch_assoc($result)) {
    $eventos[] = $row; // Se mantiene $eventos por simplicidad con el HTML existente
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar más info</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    
    <h1>Más Info</h1>
    <?php if ($mensaje): ?>
    <div class="alert">
        <p><?php echo $mensaje; ?></p>
    </div>
    <?php endif; ?>
    <form method="post" action="">
        <?php foreach ($eventos as $evento): ?>
        <div class="event-header">
            <h3><?php echo $evento['titulo']; ?></h3>
            <div>
                <label for="activo_<?php echo $evento['id']; ?>">Activo:</label>
                <input type="checkbox" name="info_otra[<?php echo $evento['id']; ?>][activo]" id="activo_<?php echo $evento['id']; ?>" <?php echo $evento['activo'] ? 'checked' : ''; ?>>
                <button type="button" class="toggle-details" onclick="toggleDetails(<?php echo $evento['id']; ?>)">+</button>
            </div>
        </div>
        <div class="event-details" id="details_<?php echo $evento['id']; ?>">
            <div class="form-group">
                <label for="titulo_<?php echo $evento['id']; ?>">Título:</label>
                <input type="text" name="info_otra[<?php echo $evento['id']; ?>][titulo]" id="titulo_<?php echo $evento['id']; ?>" value="<?php echo $evento['titulo']; ?>" required>
            </div>
            <div class="form-group">
                <label for="descripcion_<?php echo $evento['id']; ?>">Descripción:</label>
                <?php
                // Calcular un número de filas aproximado basado en la longitud del texto
                $num_rows_desc = max(4, ceil(strlen($evento['descripcion']) / 70));
                ?>
                <textarea
                    name="info_otra[<?php echo $evento['id']; ?>][descripcion]"
                    id="descripcion_<?php echo $evento['id']; ?>"
                    rows="<?php echo $num_rows_desc; ?>"
                    required
                ><?php echo $evento['descripcion']; ?></textarea>
            </div>
            <div class="form-group">
                <label for="direccion_<?php echo $evento['id']; ?>">Dirección:</label>
                <input type="text" name="info_otra[<?php echo $evento['id']; ?>][direccion]" id="direccion_<?php echo $evento['id']; ?>" value="<?php echo $evento['direccion']; ?>">
            </div>
            <div class="form-group">
                <label for="url_<?php echo $evento['id']; ?>">URL:</label>
                <input type="text" name="info_otra[<?php echo $evento['id']; ?>][url]" id="url_<?php echo $evento['id']; ?>" value="<?php echo $evento['url']; ?>">
            </div>
            <div class="form-group">
                <label for="icono_<?php echo $evento['id']; ?>">Icono:</label>
                <div class="custom-select">
                    <div class="select-selected">
                        <i class="<?php echo $evento['icono']; ?> fa-2x"></i>
                    </div>
                    <div class="select-items select-hide">
                        <?php foreach ($iconos_disponibles as $icono_class => $nombre): ?>
                        <div class="icon-option" onclick="selectIcon('<?php echo $icono_class; ?>', <?php echo $evento['id']; ?>)">
                            <i class="<?php echo $icono_class; ?> fa-2x"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <input type="hidden" name="info_otra[<?php echo $evento['id']; ?>][icono]" id="icono_<?php echo $evento['id']; ?>" value="<?php echo $evento['icono']; ?>">
            </div>
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