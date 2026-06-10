<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once '../conexion.php';
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portada_titulo = mysqli_real_escape_string($conn, $_POST['portada_titulo']);
    $portada_frase = mysqli_real_escape_string($conn, $_POST['portada_frase']);
    $portada_fecha = mysqli_real_escape_string($conn, $_POST['portada_fecha']);

    $update_query = "UPDATE info_casamiento SET
        portada_titulo='$portada_titulo',
        portada_frase='$portada_frase',
        portada_fecha='$portada_fecha'"; // Aquí se guarda lo que el usuario escriba o seleccione
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['mensaje'] = "La información se ha actualizado correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar la información: " . mysqli_error($conn);
    }
    header("Location: ?new=info_casamiento");
    exit();
}

$query = "SELECT portada_titulo, portada_frase, portada_fecha, portada_fecha_hora FROM info_casamiento";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Info Casamiento</title>
    <link rel="stylesheet" href="combined-styles.css">
</head>
<body>
    
    <h1>Modificar Portada</h1>
    <?php if ($mensaje): ?>
        <div class="alert">
            <p><?php echo $mensaje; ?></p>
        </div>
    <?php endif; ?>
    <form method="post" action="">
        <div class="form-group">
            <label for="portada_titulo">Título de la Portada:</label>
            <input type="text" name="portada_titulo" id="portada_titulo" value="<?php echo $row['portada_titulo']; ?>" required>
        </div>
        <div class="form-group">
            <label for="portada_frase">Frase de la Portada:</label>
            <input type="text" name="portada_frase" id="portada_frase" value="<?php echo $row['portada_frase']; ?>" required>
        </div>

        <?php if (isset($row['portada_fecha_hora'])): ?>
            <input type="hidden" name="portada_fecha_hora" id="portada_fecha_hora" value="<?php echo $row['portada_fecha_hora']; ?>">
        <?php endif; ?>

        <div id="fecha-formatos">
            </div>

        <div class="form-group">
            <label for="portada_fecha">Fecha de la Portada:</label>
            <input type="text" name="portada_fecha" id="portada_fecha" value="<?php echo $row['portada_fecha']; ?>" required>
        </div>

<script>
    // Función para formatear la fecha y mostrar los textos
    function formatearFechaSugerencias() {
        // Decide qué campo usar como fuente para las sugerencias:
        // 1. Prioriza 'portada_fecha_hora' si existe (asumo que es la fecha 'base' del casamiento).
        // 2. Si no, usa lo que el usuario haya escrito en 'portada_fecha'.
        var fechaInput = document.getElementById('portada_fecha_hora') || document.getElementById('portada_fecha');
        var fechaStr = fechaInput.value;

        if (!fechaStr) {
            document.getElementById('fecha-formatos').innerHTML = ''; // Limpia si no hay fecha
            return;
        }

        // Intenta parsear la fecha. Es crucial que el formato sea algo que Date() pueda entender.
        // Ej: "YYYY-MM-DD", "MM/DD/YYYY", "YYYY-MM-DD HH:MM:SS"
        var fecha = new Date(fechaStr);

        // Si el parseo es inválido (ej. el usuario escribió "Hoy es el día"), muestra un mensaje.
        if (isNaN(fecha.getTime())) {
            document.getElementById('fecha-formatos').innerHTML = `<p style="color: gray; font-size: 0.9em;">Ingrese una fecha válida para ver sugerencias (ej. 2025-12-31).</p>`;
            return;
        }
        
        // Generar los formatos sugeridos
        var formato1 = `${fecha.getDate()} del ${fecha.toLocaleDateString('es-ES', {month: 'long'})} de ${fecha.getFullYear()}`;
        var formato2 = `${fecha.getDate()} ${fecha.toLocaleDateString('es-ES', {month: 'long'})} del ${fecha.getFullYear()}`;
        var formato3 = `El ${fecha.getDate()} del ${fecha.getMonth() + 1} de ${fecha.getFullYear()}`;
        var formato4 = `El ${fecha.getDate()} de ${fecha.toLocaleDateString('es-ES', {month: 'long'})} del ${fecha.getFullYear()}`;
        
        // Crear el HTML para mostrar los formatos
        var divFormatos = document.getElementById('fecha-formatos');
        divFormatos.innerHTML = `
            <p><strong>Formatos sugeridos:</strong></p>
            <ul>
                <li><a href="javascript:void(0);" onclick="selectFecha('${formato1}')">${formato1}</a></li>
                <li><a href="javascript:void(0);" onclick="selectFecha('${formato2}')">${formato2}</a></li>
                <li><a href="javascript:void(0);" onclick="selectFecha('${formato3}')">${formato3}</a></li>
                <li><a href="javascript:void(0);" onclick="selectFecha('${formato4}')">${formato4}</a></li>
            </ul>
        `;
    }

    // Función para colocar el formato elegido en el campo de fecha
    function selectFecha(formato) {
        document.getElementById('portada_fecha').value = formato;
        // Opcional: Si quieres que al seleccionar una sugerencia se actualicen
        // las propias sugerencias (por si la fuente fuera 'portada_fecha'
        // y no 'portada_fecha_hora'), puedes llamar de nuevo:
        // formatearFechaSugerencias();
    }

    // --- Ejecución y Event Listeners ---

    // 1. Ejecutar al cargar la página para mostrar sugerencias iniciales
    document.addEventListener('DOMContentLoaded', formatearFechaSugerencias);

    // 2. Ejecutar cuando el usuario escriba en 'portada_fecha'
    document.getElementById('portada_fecha').addEventListener('input', formatearFechaSugerencias);

    // 3. Ejecutar si 'portada_fecha_hora' cambia (útil si fuera un campo visible y editable)
    //    Como es hidden, esto solo se dispara en 'DOMContentLoaded'.
    var portadaFechaHoraInput = document.getElementById('portada_fecha_hora');
    if (portadaFechaHoraInput) {
        portadaFechaHoraInput.addEventListener('input', formatearFechaSugerencias);
    }

</script>

        <button type="submit">Guardar</button>
    </form>
</body>
</html>
<?php
mysqli_close($conn);
?>