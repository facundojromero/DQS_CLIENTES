<?php

// Incluir el archivo de conexión a la base de datos
// Asegúrate de que '../conexion.php' sea la ruta correcta a tu archivo de conexión.
include '../conexion.php';

$errorMSG = "";
$name = "";
$email_remitente = ""; // Usaremos esta variable para el email del remitente del formulario
$message = "";
$guest = "";
$event = "";

// 1. Recopilación y validación de datos del formulario

// NAME
if (empty($_POST["name"])) {
    $errorMSG = "Nombre es requerido";
} else {
    $name = htmlspecialchars($_POST["name"]); // Sanitizar input
}

// EMAIL (del remitente del formulario)
if (empty($_POST["email"])) {
    $errorMSG .= "Email es requerido ";
} else {
    $email_remitente = htmlspecialchars($_POST["email"]); // Sanitizar input
}

// MESSAGE
if (empty($_POST["message"])) {
    $errorMSG .= "Mensaje es requerido ";
} else {
    $message = htmlspecialchars($_POST["message"]); // Sanitizar input
}

// guest y event (por si están en el formulario también)
$guest = isset($_POST["guest"]) ? htmlspecialchars($_POST["guest"]) : '';
$event = isset($_POST["event"]) ? htmlspecialchars($_POST["event"]) : '';

// Si hay errores de validación, mostrar y salir
if ($errorMSG != "") {
    echo $errorMSG;
    exit; // Terminar la ejecución si hay errores
}

// 2. Obtener el correo electrónico del destinatario (del vendedor/administrador) desde la base de datos

$recipientEmail = ''; // Variable para el email del destinatario final del correo
global $conn; // Asegúrate de que $conn esté disponible globalmente si la usas

$sql = "SELECT 
nombre
, apellido
, email
FROM cliente a
INNER JOIN `user` b
ON a.user_id = b.id LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
        $recipientEmail = $row['email'];
        $nombre_cliente = $row['nombre'];
        $apellido_cliente = $row['apellido'];        
        
} else {
    echo "No se encontró el correo electrónico del destinatario en la base de datos.";
    exit; // Salir si no hay un destinatario válido
}

// 3. Preparar los datos para el envío con el servicio PHPMailer

$Subject = "Nuevo mensaje enviado por: $name"; // Asunto más descriptivo

// Construir el cuerpo del mail en formato de texto plano para este tipo de mensaje
// No usamos HTML aquí porque el mensaje original no lo usaba, y así es más simple.
$Body = '
  <div style="max-width:600px;margin:0 auto;background:#fff;padding:20px;font-family:\'Mulish\',Arial,sans-serif;color:#353943;border:1px solid #eee;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.05);">
    <h2 style="color:#25B9D7;text-align:center;">📬 Nuevo Mensaje desde el Sitio</h2>
    <p style="font-size:16px;">Has recibido un nuevo mensaje de contacto.</p>

    <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:20px;font-size:14px;">
      <tr>
        <td style="padding:10px 0;"><span class="label">👤 Nombre:</span> <strong>' . $name . '</strong></td>
      </tr>
      <tr>
        <td style="padding:10px 0;"><span class="label">✉️ Mail:</span> <a href="mailto:' . $email_remitente . '" style="color:#25B9D7;text-decoration:none;">' . $email_remitente . '</a></td>
      </tr>';

if (!empty($guest)) {
    $Body .= '
      <tr>
        <td style="padding:10px 0;"><span class="label">👥 Invitado:</span> ' . $guest . '</td>
      </tr>';
}
if (!empty($event)) {
    $Body .= '
      <tr>
        <td style="padding:10px 0;"><span class="label">📅 Evento:</span> ' . $event . '</td>
      </tr>';
}

$Body .= '
      <tr>
        <td style="padding:10px 0;"><span class="label">📝 Mensaje:</span><br>
          <div style="background:#f9f9f9;padding:10px;border:1px solid #ddd;border-radius:5px;margin-top:5px;">' . nl2br($message) . '</div>
        </td>
      </tr>
    </table>';
    
// Agregamos info técnica del remitente
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible';
$fecha_envio = date("Y-m-d H:i:s");
$referer = $_SERVER['HTTP_REFERER'] ?? 'No disponible';
$idioma = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'No disponible';
$hostname = gethostbyaddr($ip);


//si quiero agregar otros datos
     // <li><strong>Navegador (User-Agent):</strong> ' . $user_agent . '</li>
     //  <li><strong>Idioma del navegador:</strong> ' . $idioma . '</li>
     //  <li><strong>Fecha y hora de envío:</strong> ' . $fecha_envio . '</li>
     //  <li><strong>Referer (página de origen):</strong> ' . $referer . '</li>

$Body .= '
    <hr style="margin:30px 0;">
    <h4 style="color:#888;font-size:16px;margin-bottom:10px;">📍 Información técnica del remitente</h4>
    <ul style="font-size:13px; line-height:1.6; color:#666; list-style:none; padding-left:0;">
      <li><strong>IP de origen:</strong> ' . $ip . '</li>
      <li><strong>Hostname:</strong> ' . $hostname . '</li>

    </ul>';


// Prepara los datos que se enviarán a tu servidor de correo PHPMailer
$data = [
    'recipient_email' => $recipientEmail, // El email del vendedor/administrador
    'recipient_name' => $nombre_cliente, // Nombre genérico para el destinatario
    'subject' => $Subject,
    'body' => $Body,
    'is_html' => true // Este correo será texto plano, no HTML
];

// URL de tu servicio PHPMailer
$mailServiceUrl = 'https://api.dijequesi.com/enviar_correo.php';

// Configura las opciones para la solicitud POST
$options = [
    'http' => [
        'header'  => "Content-type: application/json",
        'method'  => 'POST',
        'content' => json_encode($data),
        'timeout' => 10
    ],
];

// Crea el contexto de la solicitud HTTP
$context = stream_context_create($options);

// Realiza la solicitud POST al script de envío de correo PHPMailer
$response = file_get_contents($mailServiceUrl, false, $context);

// 4. Mostrar respuesta al usuario
if (strpos($response, 'Correo enviado exitosamente.') !== false) {
    echo "success";
} else {
    // Si hay un error, mostrar el error devuelto por el servicio PHPMailer o un mensaje genérico
    error_log("Error al enviar mensaje de contacto: " . $response); // Registrar el error para depuración
    echo "Algo salió mal al enviar el mensaje. Por favor, inténtalo de nuevo más tarde.";
    // Puedes comentar la línea de arriba y descomentar la siguiente para ver el error completo del servidor
    // echo "Error detallado: " . $response;
}

?>