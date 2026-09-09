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
    foreach ($_POST['eventos'] as $id => $evento) {
        $fecha = mysqli_real_escape_string($conn, $evento['fecha']);
        $titulo = mysqli_real_escape_string($conn, $evento['titulo']);
        $descripcion = mysqli_real_escape_string($conn, $evento['descripcion']);
        $direccion = mysqli_real_escape_string($conn, $evento['direccion']);
        $url = mysqli_real_escape_string($conn, $evento['url']);
        $tipo_visual = mysqli_real_escape_string($conn, $evento['tipo_visual']);
        $icono = mysqli_real_escape_string($conn, $evento['icono']);
        // Se elimina la captación de $orden
        $activo = isset($evento['activo']) ? 1 : 0;
        
        // Manejo de la imagen: Obtener la imagen actual y procesar nueva subida
        $imagen_actual_db = ''; 
        $stmt = $conn->prepare("SELECT imagen FROM info_eventos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($imagen_actual_db);
        $stmt->fetch();
        $stmt->close();
        
        $imagen = $imagen_actual_db; 

        $input_file_name = 'imagen_' . $id; 
        if (isset($_FILES[$input_file_name]) && $_FILES[$input_file_name]['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES[$input_file_name]['tmp_name'];
            $nombre_archivo_original = basename($_FILES[$input_file_name]['name']);
            $ruta_destino_absoluta = '../images/events/' . time() . '_' . $nombre_archivo_original; 

            if (move_uploaded_file($tmp_name, $ruta_destino_absoluta)) {
                $imagen = mysqli_real_escape_string($conn, basename($ruta_destino_absoluta));
            } else {
                $_SESSION['mensaje'] = "Error al subir la imagen para el evento " . $id;
            }
        }
        
        // Actualizar el registro en la base de datos (sin 'orden')
        $update_query = "UPDATE info_eventos SET 
            fecha='$fecha',
            titulo='$titulo', 
            descripcion='$descripcion', 
            direccion='$direccion', 
            url='$url', 
            tipo_visual='$tipo_visual', 
            imagen='$imagen', 
            icono='$icono', 
            activo='$activo' 
            WHERE id='$id'";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['mensaje'] = "La información de Eventos se ha actualizado correctamente.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la información: " . mysqli_error($conn);
        }
    }
    header("Location: ?new=eventos");
    exit();
}

// La consulta SELECT ya no necesita 'orden' si no la usas, pero dejarla no causaría error
$query = "SELECT * FROM info_eventos"; 
$result = mysqli_query($conn, $query);
$eventos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $eventos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Info Eventos</title>
    <link rel="stylesheet" href="combined-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>
<body>
    
    <h1>Información de evento</h1>
    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="" enctype="multipart/form-data">

        <?php foreach ($eventos as $evento): ?>
            <div class="event-header">
                <h3><?php echo $evento['titulo']; ?></h3>
                <div>
                    <label for="activo_<?php echo $evento['id']; ?>">Activo:</label>
                    <input type="checkbox" name="eventos[<?php echo $evento['id']; ?>][activo]" id="activo_<?php echo $evento['id']; ?>" <?php echo $evento['activo'] ? 'checked' : ''; ?>>
                    <button type="button" class="toggle-details" onclick="toggleDetails(<?php echo $evento['id']; ?>)">+</button>
                </div>
            </div>
            <div class="event-details" id="details_<?php echo $evento['id']; ?>">
                <div class="form-group">
                    <label for="titulo_<?php echo $evento['id']; ?>">Título:</label>
                    <input type="text" name="eventos[<?php echo $evento['id']; ?>][titulo]" id="titulo_<?php echo $evento['id']; ?>" value="<?php echo $evento['titulo']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_<?php echo $evento['id']; ?>">Fecha:</label>
                    <input type="date" name="eventos[<?php echo $evento['id']; ?>][fecha]" id="fecha_<?php echo $evento['id']; ?>" value="<?php echo $evento['fecha']; ?>">
                </div>

                <div class="form-group">
                    <label for="descripcion_<?php echo $evento['id']; ?>">Descripción:</label>
                    <?php
                    // Calcular un número de filas aproximado basado en la longitud del texto
                    // Añadimos un mínimo de 4 filas
                    $num_rows_desc = max(4, ceil(strlen($evento['descripcion']) / 70)); 
                    ?>
                    <textarea
                        name="eventos[<?php echo $evento['id']; ?>][descripcion]"
                        id="descripcion_<?php echo $evento['id']; ?>"
                        rows="<?php echo $num_rows_desc; ?>"
                        required
                    ><?php echo $evento['descripcion']; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="direccion_<?php echo $evento['id']; ?>">Dirección:</label>
                    <input type="text" name="eventos[<?php echo $evento['id']; ?>][direccion]" id="direccion_<?php echo $evento['id']; ?>" value="<?php echo $evento['direccion']; ?>">
                </div>
                <div class="form-group">
                    <label for="url_<?php echo $evento['id']; ?>">URL:</label>
                    <input type="text" name="eventos[<?php echo $evento['id']; ?>][url]" id="url_<?php echo $evento['id']; ?>" value="<?php echo $evento['url']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="tipo_visual_<?php echo $evento['id']; ?>">Tipo de visual:</label>
                    <select name="eventos[<?php echo $evento['id']; ?>][tipo_visual]" id="tipo_visual_<?php echo $evento['id']; ?>" onchange="toggleVisualFields(<?php echo $evento['id']; ?>)">
                        <option value="imagen" <?php echo $evento['tipo_visual'] === 'imagen' ? 'selected' : ''; ?>>Imagen</option>
                        <option value="icono" <?php echo $evento['tipo_visual'] === 'icono' ? 'selected' : ''; ?>>Ícono</option>
                    </select>
                </div>

                <div class="form-group visual-imagen visual-<?php echo $evento['id']; ?>" style="<?php echo $evento['tipo_visual'] === 'imagen' ? '' : 'display:none;'; ?>">
                    <label for="imagen_<?php echo $evento['id']; ?>">Subir Imagen:</label>
                    <input type="file" name="imagen_<?php echo $evento['id']; ?>" id="imagen_<?php echo $evento['id']; ?>" accept="image/*">
                    
                    <?php if (!empty($evento['imagen'])): ?>
                        <div class="imagen-actual">
                            <label>Imagen actual:</label><br>
                            <a href="../images/events/<?php echo $evento['imagen']; ?>" target="_blank">
                                <img src="../images/events/<?php echo $evento['imagen']; ?>" alt="Imagen actual" style="max-width: 150px; max-height: 100px; border: 1px solid #ccc; padding: 2px;">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group visual-icono visual-<?php echo $evento['id']; ?>" style="<?php echo $evento['tipo_visual'] === 'icono' ? '' : 'display:none;'; ?>">
                    <label for="icono_<?php echo $evento['id']; ?>">Ícono:</label>
                    <div class="custom-select">
                        <div class="select-selected">
                            <i class="<?php echo $evento['icono']; ?> fa-2x"></i>
                        </div>
                        <div class="select-items select-hide">
                            <?php foreach ($iconos_disponibles as $icono_class => $nombre_icono): ?>
                            <div class="icon-option" onclick="selectIcon('<?php echo $icono_class; ?>', <?php echo $evento['id']; ?>)">
                                <i class="<?php echo $icono_class; ?> fa-2x"></i>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <input type="hidden" name="eventos[<?php echo $evento['id']; ?>][icono]" id="icono_<?php echo $evento['id']; ?>" value="<?php echo $evento['icono']; ?>">
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
        
        function toggleVisualFields(id) {
            var tipo = document.getElementById('tipo_visual_' + id).value;
            var imagenDiv = document.querySelector('.visual-imagen.visual-' + id);
            var iconoDiv = document.querySelector('.visual-icono.visual-' + id);
            if (tipo === 'imagen') {
                imagenDiv.style.display = '';
                iconoDiv.style.display = 'none';
            } else {
                imagenDiv.style.display = 'none';
                iconoDiv.style.display = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[id^="tipo_visual_"]').forEach(function(selectElement) {
                toggleVisualFields(selectElement.id.split('_')[2]);
            });
        });

    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>