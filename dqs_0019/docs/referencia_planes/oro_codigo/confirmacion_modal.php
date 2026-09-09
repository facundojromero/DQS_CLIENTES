<?php
error_reporting(E_ERROR); // Ajusta el nivel de reporte de errores según tu entorno
include_once 'conexion.php'; // Asegúrate de que esta ruta es correcta

$codigo_invitado = isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo']) : '';

$nombre_invitado = '';
$apellido_invitado = '';
$max_mayores = 0;
$max_menores = 0;
$permite_menores = false;
$ya_confirmo = false;
$confirmacion_actual = '';
$cant_mayores_actual = 0;
$cant_menores_actual = 0;
$alimento_actual = 'No';
$contenido_actual = '';


if (!empty($codigo_invitado)) {
    // Obtener los datos del invitado
    $query_invitado = mysqli_query($conn, "SELECT 
                            CASE WHEN cantidad_mayores>1 THEN e.titulo_invitados ELSE CONCAT(titulo_invitados,' ',apellido) END nombre, 
                            nombre nombre_revision, 
                            apellido apellido_revision,
                            CASE WHEN LENGTH(apellido) > 3 THEN CONCAT(SUBSTRING(apellido, 1, 3), '.') ELSE CONCAT(SUBSTRING(apellido, 1, 2), '.') END AS apellido,
                            -- CONCAT('xxxxxx', SUBSTRING(tel, 7, 5)) AS cel,
                            -- tel,
                            a.id id_invitados,
                            a.codigo,
                            cantidad_mayores,
                            cantidad_menores,
                            ingreso, 
                            categoria_acompanante acompanado,
                            CASE WHEN ingreso='Inicio' THEN '19:30' 
                                 WHEN ingreso='Tarde' THEN '22:45' END hora_entrada,
                            e.*
                            -- , REPLACE(tel_enviar_concatenado, ',', ' ó ') AS tel_enviar_concat 
                            FROM invitados a
                            LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
                            LEFT JOIN invitados_prioridad c ON a.id_prioridad = c.id
                            LEFT JOIN (
                                SELECT 
                                aa.id_invitados,
                                bb.invitados,
                                bb.titulo_invitados,
                                ROW_NUMBER() OVER (PARTITION BY aa.id_invitados ORDER BY aa.id_invitados ASC) AS numero_fila
                                -- , GROUP_CONCAT(CONCAT('xxxxxx', SUBSTRING(tel_enviar, 7, 5))) AS tel_enviar_concatenado
                                FROM invitados_listado_mesa aa
                                INNER JOIN (
                                    SELECT 
                                    a.id_invitados,
                                    SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ' y '), ' y ', 2) AS titulo_invitados,
                                    CASE WHEN cantidad_mayores<2 THEN nombre_invitado ELSE 
                                    CONCAT(
                                        IF(COUNT(*) > 1,
                                        SUBSTRING_INDEX(
                                            GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '),
                                            ', ',
                                            COUNT(*) - 1
                                        ),
                                        GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
                                        ),
                                        ' y ',
                                        SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
                                    ) END AS invitados
                                    -- , GROUP_CONCAT(CONCAT('xxxxxx', SUBSTRING(tel_enviar, 7, 5))) AS tel_enviar_concatenado
                                    FROM invitados_listado_mesa a
                                    INNER JOIN invitados b
                                    ON a.id_invitados=b.id
                                    GROUP BY a.id_invitados
                                ) bb ON aa.id_invitados = bb.id_invitados
                                WHERE 1=1
                                GROUP BY aa.id_invitados
                            ) e ON a.id = e.id_invitados
                            WHERE 1=1
                            AND a.codigo  = '$codigo_invitado'
                            AND activo = 1
                            GROUP BY a.id;");
    $result_invitado = mysqli_num_rows($query_invitado);

    if ($result_invitado > 0) {
        $data_invitado = mysqli_fetch_array($query_invitado);
        $nombre_invitado = $data_invitado['nombre'];
        $invitados = $data_invitado['invitados'];
        $apellido_invitado = $data_invitado['apellido'];
        $max_mayores = $data_invitado['cantidad_mayores'];
        $max_menores = $data_invitado['cantidad_menores'];
        $permite_menores = (bool)$data_invitado['permite_menores'];
        $confirmacion_actual = $data_invitado['confirmacion'];
        $cant_mayores_actual = $data_invitado['confirmacion_mayores'];
        $cant_menores_actual = $data_invitado['confirmacion_menores'];
        $alimento_actual = $data_invitado['alimento'];
        $contenido_actual = $data_invitado['confirmacion_comentario'];

        if ($confirmacion_actual == 'Si' || $confirmacion_actual == 'No') {
            $ya_confirmo = true;
        }

    } else {
        // Código de invitado no encontrado, puedes manejarlo como un error o redirigir
        // Para el modal, simplemente mostramos un mensaje.
        echo '<div class="modal-body"><p class="alert alert-danger">Código de invitación no válido.</p></div>';
        echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>';
        exit; // Terminar la ejecución aquí
    }
} else {
    echo '<div class="modal-body"><p class="alert alert-danger">No se proporcionó un código de invitación.</p></div>';
    echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>';
    exit; // Terminar la ejecución aquí
}

?>

<div class="modal-header">
    <h5 class="modal-title" id="confirmacionModalLabel">Confirmar Asistencia</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>


<div class="modal-body">
    <div id="modalMessage" class="mb-3" style="display:none;"></div>

    <?php if ($ya_confirmo) { ?>
        <div class="alert alert-info">
            <p><strong><?php echo $nombre_invitado ; ?></strong>, ya has confirmado tu asistencia. Detalles:</p>
            <ul>
                <li>Asistencia: <strong><?php echo ($confirmacion_actual == 'Si' ? 'Sí' : 'No'); ?></strong></li>
                <?php if ($confirmacion_actual == 'Si') { ?>
                    <li>Mayores: <strong><?php echo $cant_mayores_actual; ?></strong></li>
                    <?php if ($max_menores) { ?>
                        <li>Menores: <strong><?php echo $cant_menores_actual; ?></strong></li>
                    <?php } ?>
                    <?php if (!empty($alimento_actual) && $alimento_actual != 'No') { ?>
                        <li>Criterio Alimenticio: <strong><?php echo $alimento_actual; ?></strong></li>
                    <?php } ?>
                    <?php if (!empty($contenido_actual)) { ?>
                        <li>Aclaración: <strong><?php echo nl2br(htmlspecialchars($contenido_actual)); ?></strong></li>
                    <?php } ?>
                <?php } ?>
            </ul>
            <p>Si necesitas modificarla, por favor contáctanos.</p>
        </div>
        
        
        
    <?php } else { ?>
    
    
    <div id="introTextConfirmacion">
    <?php
        // Usa la cantidad máxima de invitados permitida para el cálculo
        $total_personas = $max_mayores + $max_menores;

        // Determina la conjugación y la frase a mostrar
        if ($total_personas > 1) {
            $frase = "están a punto de confirmar la asistencia.";
        } else {
            $frase = "estás a punto de confirmar la asistencia.";
        }
    ?>
    <p>Hola <strong><?php echo $invitados; ?></strong>, <?php echo $frase; ?></p>

            <p>El código de la invitación es: <strong><?php echo $codigo_invitado; ?></strong></p>     
</div>
    



        <form id="formConfirmacion" action="procesar_confirmacion.php" method="POST">
            <input type="hidden" name="codigo_invitado" value="<?php echo $codigo_invitado; ?>">

            <div class="form-group">
                <label for="entrada">Confirmo asistencia:</label>
                <select class="form-control" id="entrada" name="confirmar_asistencia" required>
                    <option value="" disabled selected>Selecciona una opción</option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
            </div>

            <div class="form-group" id="mayores-container">
                <label for="cantidad_mayores">Cantidad de Mayores que asisten:</label>
                <input type="number" class="form-control" id="cantidad_mayores" name="cantidad_mayores" min="0" value="1" max="<?php echo $max_mayores; ?>" required>
                <small class="form-text text-muted">Máximo de mayores permitidos: <?php echo $max_mayores; ?></small>
            </div>

            <?php if ($max_menores) { ?>
                <div class="form-group" id="menores-container">
                    <label for="cantidad_menores">Cantidad de Menores que asisten:</label>
                    <input type="number" class="form-control" id="cantidad_menores" name="cantidad_menores" min="0" value="0" max="<?php echo $max_menores; ?>" required>
                    <small class="form-text text-muted">Máximo de menores permitidos: <?php echo $max_menores; ?></small>
                </div>
            <?php } else { ?>
                <input type="hidden" name="cantidad_menores" value="0">
            <?php } ?>

            <div class="form-group" id="alimento-container">
                <label for="alimento">Algún Criterio alimenticio:</label>
                <select class="form-control" name="alimento" id="alimento" required>
                    <option value="No">No</option>
                    <option value="Vegetariano">Vegetariano</option>
                    <option value="Vegano">Vegano</option>
                    <option value="Celiaco">Celiaco</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div class="form-group" id="contenido-group" style="display: none;">
                <label for="contenido">Aclaración (Ej: intolerancias, alergias o detalles del criterio "Otro"):</label>
                <textarea class="form-control" id="contenido" name="contenido" placeholder="Ingresa tu aclaración aquí" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Enviar Confirmación</button>
        </form>
    <?php } ?>
    
    
    
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>

<?php if (!$ya_confirmo) { // Solo añadir JS si el formulario está activo ?>


<script>
    // Se ejecuta inmediatamente cuando se carga dinámicamente el contenido
    (function () {
        var alimentoSelect = document.getElementById("alimento");
        var contenidoGroup = document.getElementById("contenido-group");

        if (alimentoSelect && contenidoGroup) {
            alimentoSelect.addEventListener("change", function () {
                if (alimentoSelect.value !== "No") {
                    contenidoGroup.style.display = "block";
                } else {
                    contenidoGroup.style.display = "none";
                }
            });

            // Llamamos inmediatamente al cambio para reflejar el valor actual
            alimentoSelect.dispatchEvent(new Event("change"));
        }
    })();
</script>


<script>
    // Se ejecuta inmediatamente cuando se carga dinámicamente el contenido
    (function () {
        var entradaSelect = document.getElementById("entrada");
        var contenidoMay = document.getElementById("mayores-container");
        var contenidoMen = document.getElementById("menores-container");       
        var contenidoAli = document.getElementById("alimento-container");           

        if (entradaSelect && contenidoMay && contenidoMen && contenidoAli) {
            entradaSelect.addEventListener("change", function () {
                if (entradaSelect.value !== "No") {
                    contenidoMay.style.display = "block";
                    contenidoMen.style.display = "block";                    
                    contenidoAli.style.display = "block";                        
                } else {
                    contenidoMay.style.display = "none";
                    contenidoMen.style.display = "none";     
                    contenidoAli.style.display = "none";                        
                }
            });

            // Llamamos inmediatamente al cambio para reflejar el valor actual
            entradaSelect.dispatchEvent(new Event("change"));
        }
    })();
</script>



<script>
    // Se ejecuta inmediatamente cuando se carga dinámicamente el contenido
    (function () {
        var alimentoSelect = document.getElementById("alimento");
        var contenidoGroup = document.getElementById("contenido-group");

        if (alimentoSelect && contenidoGroup) {
            alimentoSelect.addEventListener("change", function () {
                if (alimentoSelect.value !== "No") {
                    contenidoGroup.style.display = "block";
                } else {
                    contenidoGroup.style.display = "none";
                }
            });

            // Llamamos inmediatamente al cambio para reflejar el valor actual
            alimentoSelect.dispatchEvent(new Event("change"));
        }
    })();
</script>

<script>
    // Se ejecuta cuando el DOM está completamente cargado.
    document.addEventListener("DOMContentLoaded", function () {
        var entradaSelect = document.getElementById("entrada");
        var menoresContainer = document.getElementById("menores-container");
        var cantidadMayoresInput = document.getElementById("cantidad_mayores"); // Correct ID
        var alimentoSelect = document.getElementById("alimento");
        var contenidoGroup = document.getElementById("contenido-group");
        var alimentoContainer = document.getElementById("alimento-container"); // Nuevo: contenedor para el select de alimento



        function toggleContenido() {
            if (alimentoSelect.value !== "No") {
                contenidoGroup.style.display = "block";
            } else {
                contenidoGroup.style.display = "none";
            }
        }

        // Asignar los eventos
        entradaSelect.addEventListener("change", toggleVisibility);
        alimentoSelect.addEventListener("change", toggleContenido);

        // Disparar los eventos al cargar para establecer el estado inicial correcto
        toggleVisibility();
        // Si entradaSelect.value es 'Si', entonces toggleContenido ya se habrá llamado.
        // Si es 'No', se habrá ocultado todo.
        // Si no hay valor seleccionado inicialmente, entonces tampoco se muestra nada.
        if (entradaSelect.value === "Si" || entradaSelect.value === "") { // Si es 'Si' o no hay selección inicial, ajusta el contenido
             toggleContenido();
        }

        // Manejo del envío del formulario con AJAX (este script es el que ya tienes en index.php,
        // pero lo incluyo aquí como recordatorio de que la interacción se maneja así)
        $('#formConfirmacion').on('submit', function(e) {
            e.preventDefault(); // Evita el envío tradicional del formulario
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            var messageDiv = $('#modalMessage');

            submitButton.prop('disabled', true).text('Enviando...'); // Deshabilita el botón

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        messageDiv.removeClass('alert-danger msg_error').addClass('alert-success').text(response.message).show();
                        submitButton.hide(); // Oculta el botón de enviar
                        form.find('input, select, textarea').prop('disabled', true); // Deshabilita todos los campos
                        // Opcional: recargar el modal para mostrar el estado "ya confirmado"
                        // o recargar la página principal (manejado en index.php por 'hidden.bs.modal')
                    } else {
                        messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text(response.message).show();
                        submitButton.prop('disabled', false).text('Enviar Confirmación'); // Re-habilita
                    }
                },
                error: function(xhr, status, error) {
                    messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text('Hubo un error de conexión. Inténtalo de nuevo.').show();
                    submitButton.prop('disabled', false).text('Enviar Confirmación'); // Re-habilita
                }
            });
        });

    });
</script>
<?php } ?>