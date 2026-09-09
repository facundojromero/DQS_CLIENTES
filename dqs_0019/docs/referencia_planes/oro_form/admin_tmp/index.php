<?php
ob_start();
session_start();



// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Incluir el archivo de conexión
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo

// Depuración: Verificar si la conexión a la BD está cargada
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del usuario logueado
$user_id = $_SESSION['user_id'];



// Consultar los datos de la tabla cliente para el usuario logueado
$sql = "SELECT * FROM cliente WHERE user_id = $user_id";
$result = $conn->query($sql);
$cliente = $result->fetch_assoc();
$nombre = $cliente['nombre']; // Tomo el nombre para el mensaje WhatsApp
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admnistra tu fiesta. Dijequesí!</title>
    <link rel="stylesheet" href="combined-styles.css"> <!-- Archivo CSS adicional -->
    <link rel="stylesheet" href="additional-styles.css">    
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- INDEXXXXXXXXXX Font Awesome -->
</head>
<body>
    <?php include 'menu.php'; ?>

        


<?php
// INICIO (GRAFITOCS)
if (!isset($_GET['new']) || $_GET['new'] == 'inicio'): ?>
    <div class="container">
        <?php include 'dashboard_casamiento.php'; ?>
    </div>  
<?php endif; ?>     
    




<?php
//INVITADOS
if (isset($_GET['new']) && $_GET['new'] == 'invitados'): ?>
    <div class="container">
        <?php include 'invitados.php'; ?>
    </div>  
<?php endif; ?>



<?php
//invitaciones invitados AUTOMATICO
if (isset($_GET['new']) && $_GET['new'] == 'invitaciones'): ?>
    <div class="container">
        <?php include 'invitados_invitaciones.php'; ?>
    </div>  
<?php endif; ?>



<?php
//envioinvitaciones POR USUAIO
if (isset($_GET['new']) && $_GET['new'] == 'envioinvitaciones'): ?>
    <div class="container">
        <?php include 'gestionar_envios.php'; ?>
    </div>  
<?php endif; ?>



 <?php
//PALETAS
if (isset($_GET['new']) && $_GET['new'] == 'paletas'): ?>
    <div class="container">
        <?php include 'paletas_colores.php'; ?>
    </div>  
<?php endif; ?>   



 <?php
//LOGO
if (isset($_GET['new']) && $_GET['new'] == 'logo'): ?>
    <div class="container">
        <?php include 'info_logo.php'; ?>
    </div>  
<?php endif; ?>     






<?php
//PORTADA
if (isset($_GET['new']) && $_GET['new'] == 'portada'): ?>
    <div class="container">
        <?php include 'modificar_portada.php'; ?>
    </div>  
<?php endif; ?>

<?php
//VENTAS
if (isset($_GET['new']) && $_GET['new'] == 'ventas'): ?>
    <div class="container">
        <?php include 'productos_vendidos.php'; ?>
    </div>  
<?php endif; ?>


<?php
//DATOS
if (isset($_GET['new']) && $_GET['new'] == 'datos'): ?>
    <div class="container">
        <?php include 'datos.php'; ?>
    </div>  
<?php endif; ?>



<?php
//modificar datos DATOS
if (isset($_GET['new']) && $_GET['new'] == 'modificardatos'): ?>
    <div class="container">
        <?php include 'datos_modificar.php'; ?>
    </div>  
<?php endif; ?>






<?php
//CAMBIAR CONTRASEÑA
if (isset($_GET['new']) && $_GET['new'] == 'pass'): ?>
    <div class="container">
        <?php include 'cambiar_password.php'; ?>
    </div>  
<?php endif; ?>



<?php
//info_casamiento
if (isset($_GET['new']) && $_GET['new'] == 'info_casamiento'): ?>
    <div class="container">
        <?php include 'info_casamiento.php'; ?>
    </div>  
    

<?php endif; ?>




<?php
//nosotros
if (isset($_GET['new']) && $_GET['new'] == 'nosotros'): ?>
    <div class="container">
        <?php include 'info_nosotros.php'; ?>
    </div>  
<?php endif; ?>



<?php
//historia
if (isset($_GET['new']) && $_GET['new'] == 'historia'): ?>
    <div class="container">
        <?php include 'info_historia.php'; ?>
    </div>  
<?php endif; ?>



<?php
//eventos
if (isset($_GET['new']) && $_GET['new'] == 'eventos'): ?>
    <div class="container">
        <?php include 'info_eventos.php'; ?>
    </div>  
<?php endif; ?>



<?php
//mas info
if (isset($_GET['new']) && $_GET['new'] == 'masinfo'): ?>
    <div class="container">
        <?php include 'info_otra.php'; ?>
    </div>  
<?php endif; ?>




<?php
//imagenes
if (isset($_GET['new']) && $_GET['new'] == 'imagenes'): ?>
    <div class="container">
        <?php include 'info_imagenes.php'; ?>
    </div>  
<?php endif; ?>



<?php
//fotos
if (isset($_GET['new']) && $_GET['new'] == 'fotos'): ?>
    <div class="container">
        <?php include 'info_fotos.php'; ?>
    </div>  
<?php endif; ?>



<?php
//LISTA DE REGALOS
if (isset($_GET['new']) && $_GET['new'] == 'regalos'): ?>
    <div class="container">
        <?php include 'lista_regalos.php'; ?>
    </div>  
<?php endif; ?>




<?php
//cronometro
if (isset($_GET['new']) && $_GET['new'] == 'cronometro'): ?>
    <div class="container">
        <?php include 'info_cronometro.php'; ?>
    </div>  
<?php endif; ?>


<?php
// Leer número soporte desde archivo
$archivo_telefono = '__TEL_SOPORTE.txt';
$telefono_soporte = trim(file_get_contents($archivo_telefono));
?>

<?php if (isset($nombre) && !empty($nombre)): ?>
    <?php 
    $mensaje = urlencode("Hola soy $nombre, necesito hacer una consulta.");
    ?>
<a 
    href="https://api.whatsapp.com/send?phone=<?= $telefono_soporte ?>&text=<?= $mensaje ?>" 
    target="_blank" 
    class="btn-whatsapp"
    title="Enviar mensaje al soporte por WhatsApp"
>
    <i class="fab fa-whatsapp"></i>
</a>

<?php endif; ?>

<footer>
    &copy; <?= date('Y') ?><a href="https://instagram.com/dijequesi.ar" target="_blank" class="footer-link"> Dije que Sí - Todos los derechos reservados.</a>
</footer>

</body>
</html>
<?php
// Cerrar la conexión
$conn->close();
ob_end_flush();
?>

