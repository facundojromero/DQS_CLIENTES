<?php 
error_reporting(E_ERROR);
session_start();

include "conexion.php";

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (!empty($_POST)) {
    $alert = '';
    // Si cancela el invitado. Automáticamente agrega 0 a los invitados mayores y menores. Sin obligación de completar.
    if ($_POST['entrada'] == 'No') {
        if (empty($_POST['entrada'])) {
            $alert = '<p class="msg_error">Error: Hay que confirmar la cantidad y la hora de ingreso</p>';
        } else {
            $idCliente = $_POST['codigo'];
            $mayores = 0;
            $menores = 0;
            $entrada = $_POST['entrada'];
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $contenido = $_POST['contenido'];
            $alimento = $_POST['alimento'];
            $usuario_id = $_SESSION['idUser'];
            $result = 1;


            $sql_update = mysqli_query($conn, "UPDATE invitados
                                                SET confirmacion='$entrada',
                                                    confirmacion_fecha=NOW(),
                                                    confirmacion_comentario='$contenido',
                                                    confirmacion_mayores='$mayores',
                                                    confirmacion_menores='$menores',
                                                    alimento = '$alimento'
                                                WHERE codigo='$idCliente';");

            if ($sql_update) {
                $alert = '<br>
                          <center><h1>' . $nombre . '</h1> <br>
                          <p class="msg_save">Lastima que no vas a poder asistir
                          (Igual, podes cambiar de opinion)</center>
                          </p>';
            } else {
                echo "Error en la consulta: " . mysqli_error($conn);
                $alert = '<p class="msg_error">Error al actualizar la confirmacion.</p>';
            }
        }
    }

    // Si el invitado asiste va a tener que completar los otros campos.
    if ($_POST['entrada'] != 'No') {
        if (empty($_POST['mayores']) || empty($_POST['entrada'])) {
            $alert = '<p class="msg_error">Error: Hay que confirmar la cantidad y la hora de ingreso</p>';
        } else {
            $idCliente = $_POST['codigo'];
            $mayores = $_POST['mayores'];
            if (empty($_POST['menores'])) {
                $menores = 0;
            } else {
                $menores = $_POST['menores'];
            }
            $entrada = $_POST['entrada'];
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $contenido = $_POST['contenido'];
            $alimento = $_POST['alimento'];
            $usuario_id = $_SESSION['idUser'];
            $result = 1;


            $sql_update = mysqli_query($conn, "UPDATE invitados
                                                SET confirmacion='$entrada',
                                                    confirmacion_fecha=NOW(),
                                                    confirmacion_comentario='$contenido',
                                                    confirmacion_mayores='$mayores',
                                                    confirmacion_menores='$menores',
                                                    alimento = '$alimento'
                                                WHERE codigo='$idCliente';");

            if ($sql_update) {
                $alert = '<br>
                          <center><h1>' . $nombre . '</h1>
                          <p class="msg_save">Muchas gracias por tu confirmación</center>
                          <p class="msg_save"> Cantidad de Mayores: ' . $mayores . ' <br>
                          Cantidad de Menores: ' . $menores . '<br>
                          </p>';
            } else {
                echo "Error en la consulta: " . mysqli_error($conn);
                $alert = '<p class="msg_error">Error al actualizar la confirmacion.</p>';
            }
        }
    }
}


if (!empty($alert)) {
    echo "<div class='alert alert-info'>$alert</div>";
}

// Mostrar Datos
$idcliente = $_REQUEST['id'];
if (empty($idcliente)) {
    echo "Error: ID de cliente no proporcionado.";
    exit;
}

$sql = mysqli_query($conn, "							SELECT 
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
                            AND a.codigo  = '$idcliente'
                            AND activo = 1
                            GROUP BY a.id;");
mysqli_close($conn);
$result_sql = mysqli_num_rows($sql);
if ($result_sql == 0) {
    // No se encontraron resultados
} else {
    while ($data = mysqli_fetch_array($sql)) {
        $idcliente = $data['id_invitados'];
        $codigo = $data['codigo'];
        $nombre = $data['nombre'];
        $apellido = $data['apellido'];
        $telefono = $data['cel'];
        $acompanado = $data['acompanado'];
        $mayores = $data['cantidad_mayores'];
        $entrada = $data['ingreso'];
        $menores = $data['cantidad_menores'];
        $ingreso = $data['ingreso'];
        $titulo_invitados = $data['titulo_invitados'];
        $tel_enviar_concatenado = $data['tel_enviar_concatenado'];
        $invitados = $data['invitados'];
        $hora_entrada = $data['hora_entrada'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include "../gestion/includes/scripts.php"; ?>
    <title>Invitar</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Pogo Slider CSS -->
    <link rel="stylesheet" href="css/pogo-slider.min.css">
    <!-- Site CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="css/responsive.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
    <header></header>
    <div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>
    <?php if ($result == 0) {?> 
    <div id="contact" class="contact-box">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="title-box">
                        <h1><?php echo $nombre; ?></h1>
                        <h3>Hora de Misa: 17:00<br>Hora de Fiesta: <?php echo $hora_entrada; ?></h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 col-sm-12 col-xs-12">
                    <div class="contact-block">
                        
<form id="formConfirmacion" method="POST">

                            <input type="hidden" name="codigo" value="<?php echo $codigo; ?>">
                            <input type="hidden" name="nombre" value="<?php echo $nombre; ?>">
                            <input type="hidden" name="apellido" value="<?php echo $apellido; ?>">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="entrada">Confirmo asistencia</label>
                                        <select class="custom-select d-block form-control notItemOne" name="entrada" id="entrada" data-error="Please select an item in the list." required>
                                            <option disabled selected></option>
                                            <option value="Si">Si</option>
                                            <option value="No">No</option>
                                        </select>
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <div class="col-md-12" id="mayores-container">
                                    <div class="form-group">
                                        <label for="mayores">Cantidad de Mayores</label>
                                        <input type="number" name="mayores" class="form-control" id="mayores" max="<?php echo $mayores; ?>" min="1" placeholder="Máximo de mayores <?php echo $mayores; ?>">
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <?php if ($menores > 0) { ?>
                                <div class="col-md-12" id="menores-container">
                                    <div class="form-group">
                                        <label for="menores">Cantidad de Menores</label>
                                        <input type="number" name="menores" class="form-control" id="menores" max="<?php echo $menores; ?>" min="0" placeholder="Máximos de menores <?php echo $menores; ?>">
                                        <div class="help-block with-errors"></div>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="col-md-12">
                                    <div class="form-group" id="alimento-id">
                                        <label for="alimento">Algún Criterio alimenticio</label>
                                        <select class="custom-select d-block form-control notItemOne" name="alimento" id="alimento" required data-error="Please select an item in the list.">
                                            <option value="No">No</option>
                                            <option value="Vegetariano">Vegetariano</option>
                                            <option value="Vegano">Vegano</option>
                                            <option value="Celiaco">Celiaco</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                        <div class="help-block with-errors"></div>
                                    </div>
                                    <div class="form-group" id="contenido-group" style="display: none;">
                                        <textarea class="form-control" id="contenido" name="contenido" placeholder="Aclaración" rows="8"></textarea>
                                        <div class="help-block with-errors"></div>
                                    </div>
                                    <div class="submit-button text-center">
                                        <button class="btn btn-common" id="submit" type="submit">Confirmar</button>
                                        <div id="msgSubmit" class="h3 text-center hidden"></div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var entradaSelect = document.getElementById("entrada");
            var alimentoIdDiv = document.getElementById("alimento-id");
            entradaSelect.addEventListener("change", function () {
                if (entradaSelect.value === "No") {
                    alimentoIdDiv.style.display = "none";
                } else {
                    alimentoIdDiv.style.display = "block";
                }
            });
            entradaSelect.dispatchEvent(new Event("change"));
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var entradaSelect = document.getElementById("entrada");
            var mayoresContainer = document.getElementById("mayores-container");
            var menoresContainer = document.getElementById("menores-container");
            var mayoresInput = document.getElementById("mayores");
            entradaSelect.addEventListener("change", function () {
                if (entradaSelect.value === "No") {
                    mayoresContainer.style.display = "none";
                    menoresContainer.style.display = "none";
                    mayoresInput.removeAttribute("required");
                } else {
                    mayoresContainer.style.display = "block";
                    menoresContainer.style.display = "block";
                    mayoresInput.setAttribute("required", "required");
                }
            });
            entradaSelect.dispatchEvent(new Event("change"));
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var alimentoSelect = document.getElementById("alimento");
            var contenidoGroup = document.getElementById("contenido-group");
            alimentoSelect.addEventListener("change", function () {
                if (alimentoSelect.value !== "No") {
                    contenidoGroup.style.display = "block";
                } else {
                    contenidoGroup.style.display = "none";
                }
            });
            alimentoSelect.dispatchEvent(new Event("change"));
        });
    </script>
    <?php } ?>     
    <?php include "includes/footer.php"; ?>
    <!-- ALL JS FILES -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- ALL PLUGINS -->
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/jquery.pogo-slider.min.js"></script>
</body>
</html>