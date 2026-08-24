<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php';
include_once 'icon_list.php'; // Incluir la lista de iconos
require_once 'section_text_settings.php';
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_texto_historia'])) {
    $titulo_seccion = trim(strip_tags((string)($_POST['historia_section_title'] ?? '')));
    $bajada_seccion = trim(strip_tags((string)($_POST['historia_section_subtitle'] ?? '')));
    $bajada_seccion = str_replace(["\r\n", "\r"], "\n", $bajada_seccion);
    $cabecera_visible = ($_POST['historia_section_header_visible'] ?? '0') === '1' ? '1' : '0';

    try {
        dqs_save_site_settings($conn, [
            'historia_section_title' => $titulo_seccion,
            'historia_section_subtitle' => $bajada_seccion,
            'historia_section_header_visible' => $cabecera_visible,
        ]);
        $_SESSION['mensaje'] = 'Texto de la sección Historia actualizado correctamente.';
    } catch (Throwable $error) {
        $_SESSION['mensaje'] = 'No se pudo guardar el texto de Historia.';
    }

    header("Location: ?new=historia");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['historias']) && is_array($_POST['historias'])) {
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

$historia_config = dqs_get_plan_config($conn);
$historia_text_fields = [
    'historia_section_header_visible' => ['type' => 'visible', 'label' => 'Mostrar título y bajada de la sección'],
    'historia_section_title' => ['type' => 'text', 'label' => 'Título de sección'],
    'historia_section_subtitle' => ['type' => 'textarea', 'label' => 'Bajada / descripción'],
];

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
    <?php dqs_admin_render_section_text('historia', 'Texto de la sección Historia', $historia_text_fields, $historia_config); ?>
    <form method="post" action="">
        <?php foreach ($historias as $historia): ?>
            <div class="event-header">
                <h3><?php echo htmlspecialchars($historia['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <div>
                    <label for="activo_<?php echo $historia['id']; ?>">Activo:</label>
                    <input type="checkbox" name="historias[<?php echo $historia['id']; ?>][activo]" id="activo_<?php echo $historia['id']; ?>" <?php echo $historia['activo'] ? 'checked' : ''; ?>>
                    <button type="button" class="toggle-details" onclick="toggleDetails(<?php echo $historia['id']; ?>)">+</button>
                </div>
            </div>
            <div class="event-details" id="details_<?php echo $historia['id']; ?>">
                <div class="form-group">
                    <label for="titulo_<?php echo $historia['id']; ?>">Título:</label>
                    <input type="text" name="historias[<?php echo $historia['id']; ?>][titulo]" id="titulo_<?php echo $historia['id']; ?>" value="<?php echo htmlspecialchars($historia['titulo'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                                <div class="form-group">
                    <label for="fecha_<?php echo $historia['id']; ?>">Fecha:</label>
                    <input type="date" name="historias[<?php echo $historia['id']; ?>][fecha]" id="fecha_<?php echo $historia['id']; ?>" value="<?php echo $historia['fecha']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="texto_<?php echo $historia['id']; ?>">Texto:</label>
                    <textarea name="historias[<?php echo $historia['id']; ?>][texto]" id="texto_<?php echo $historia['id']; ?>" required><?php echo htmlspecialchars($historia['texto'], ENT_QUOTES, 'UTF-8'); ?></textarea>
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
