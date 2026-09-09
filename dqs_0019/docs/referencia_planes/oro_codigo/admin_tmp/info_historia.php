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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['historias'] as $id => $historia) {
        $titulo = mysqli_real_escape_string($conn, $historia['titulo']);
        $texto = mysqli_real_escape_string($conn, $historia['texto']);
        $activo = isset($historia['activo']) ? 1 : 0;
        // Tomar la fecha del POST si es proporcionada, de lo contrario, asignar la fecha actual
        $fecha = isset($historia['fecha']) && !empty($historia['fecha']) ? $historia['fecha'] : date('Y-m-d'); 

        $update_query = "UPDATE info_historia SET 
            titulo='$titulo', 
            texto='$texto', 
            activo='$activo', 
            fecha='$fecha' 
            WHERE id='$id'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['mensaje'] = "La historia se ha actualizado correctamente.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la información: " . mysqli_error($conn);
        }
    }
    header("Location: ?new=historia");
    exit();
}

$query = "SELECT * FROM info_historia";
$result = mysqli_query($conn, $query);
$historias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $historias[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Info Historia</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <h1>Nuestra historia</h1>
    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="">
        <?php foreach ($historias as $historia): ?>
            <div class="event-header">
                <h3><?php echo $historia['titulo']; ?></h3>
                <div>
                    <label for="activo_<?php echo $historia['id']; ?>">Activo:</label>
                    <input type="checkbox" name="historias[<?php echo $historia['id']; ?>][activo]" id="activo_<?php echo $historia['id']; ?>" <?php echo $historia['activo'] ? 'checked' : ''; ?>>
                    <button type="button" class="toggle-details" onclick="toggleDetails(<?php echo $historia['id']; ?>)">+</button>
                </div>
            </div>
            <div class="event-details" id="details_<?php echo $historia['id']; ?>">
                <div class="form-group">
                    <label for="titulo_<?php echo $historia['id']; ?>">Título:</label>
                    <input type="text" name="historias[<?php echo $historia['id']; ?>][titulo]" id="titulo_<?php echo $historia['id']; ?>" value="<?php echo $historia['titulo']; ?>" required>
                </div>
                                <div class="form-group">
                    <label for="fecha_<?php echo $historia['id']; ?>">Fecha:</label>
                    <input type="date" name="historias[<?php echo $historia['id']; ?>][fecha]" id="fecha_<?php echo $historia['id']; ?>" value="<?php echo $historia['fecha']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="texto_<?php echo $historia['id']; ?>">Texto:</label>
                    <textarea name="historias[<?php echo $historia['id']; ?>][texto]" id="texto_<?php echo $historia['id']; ?>" required><?php echo $historia['texto']; ?></textarea>
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
