<?php

/**
 * Envía un correo de confirmación de compra usando el servicio de envío de correos.
 *
 * @param string $email El email del destinatario.
 * @param string $nombre El nombre del destinatario.
 * @param string $apellido El apellido del destinatario.
 * @param float $monto_total El monto total de la compra.
 * @param string $productos Los productos comprados.
 * @param string $forma_pago La forma de pago utilizada.
 * @param string $compartido Con quién se compartió la compra.
 */
function enviarCorreoConfirmacion($email, $nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $cbu, $cbu_titular, $alias) {

    $recipientEmail = $email;
    $recipientName = "$nombre $apellido";
    $subject = "=?UTF-8?B?" . base64_encode("Confirmación de Regalo - Dije que Sí! $$monto_total") . "?="; // Asunto específico para este tipo de correo
    
    
$detalle_pago = '';
if (strtolower(trim($forma_pago)) === 'transferencia') {
    $detalle_pago = '
    <div style="margin-top:30px;padding:15px;border:1px solid #ddd;background:#f1f7ff;border-radius:5px;">
      <p style="margin:0 0 10px;"><strong>Ha elegido pagar por transferencia bancaria.</strong></p>
      <p style="margin:5px 0;">💰 <strong>Importe:</strong> $' . number_format($monto_total, 2, ',', '.') . '</p>
      <p style="margin:5px 0;">👤 <strong>Titular de la cuenta:</strong> ' . $cbu_titular . '</p>
      <p style="margin:5px 0;">🏦 <strong>Alias:</strong> ' . $alias . '</p>
      <p style="margin:5px 0;">📍 <strong>CBU:</strong> ' . $cbu . '</p>
    </div>';
}




// Bloque compartido
$detalle_compartido = '';
if (!empty(trim($compartido))) {
    $detalle_compartido = '
        <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:20px;font-size:14px;">    
    <tr>
        <td style="padding:10px 0;"><span class="label">👥 Compartido con:</span> <strong>' . $compartido . '</strong></td>
    </tr>
            </table>';
}

// Bloque mensaje
$detalle_mensaje = '';
if (!empty(trim($mensaje))) {
    $detalle_mensaje = '
          <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:20px;font-size:14px;">      
      <tr>
        <td style="padding:10px 0;"><span class="label">📝 Mensaje:</span><br>
          <div style="background:#f9f9f9;padding:10px;border:1px solid #ddd;border-radius:5px;margin-top:5px;">' . nl2br($mensaje) . '</div>
        </td>
      </tr>
            </table>';
}

    // Construye el cuerpo HTML del correo aquí, como lo hacías antes
$body = '
  <div style="max-width:600px;margin:0 auto;background:#fff;padding:20px;font-family:\'Mulish\',Arial,sans-serif;color:#353943;border:1px solid #eee;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.05);">
    <h2 style="color:#25B9D7;text-align:center;">✅ ¡Regalo Confirmado!</h2>
    <p style="font-size:16px;">Gracias, <strong>' . $nombre . ' ' . $apellido . '</strong>. Tu regalo fue procesada exitosamente.</p>



            ' . $detalle_compartido . '


            ' . $detalle_pago . '
    
    
            ' . $detalle_mensaje . '

    <h3 style="margin-top:30px;border-bottom:1px solid #eee;padding-bottom:5px;">🛒 Productos Regalados:</h3>
    <p style="background-color:#f9f9f9;padding:10px;border-radius:5px;border:1px solid #ddd;font-size:14px;">' . nl2br($productos) . '</p>
    


    <p style="margin-top:30px;font-size:14px;">📦 Tu regalo está siendo preparado. ¡Pronto será entregado!</p>
    
    <p style="margin-top:40px;font-size:12px;color:#999;text-align:center;">Este es un mensaje automático. No respondas a este correo.</p>
    
    <p style="margin-top:20px;text-align:center;font-size:14px;">
      <a href="https://instagram.com/dijequesi.ar" target="_blank" style="color:#25B9D7;text-decoration:none;font-weight:600;">Desarrollado por dijequesi.ar</a>
    </p>
    </div>
';


    // Prepara los datos que se enviarán a tu servidor de correo PHPMailer
    $data = [
        'recipient_email' => $recipientEmail,
        'recipient_name' => $recipientName,
        'subject' => $subject,
        'body' => $body,
        'is_html' => true // Indica que el cuerpo es HTML
    ];

    // Configura las opciones para la solicitud POST
    $options = [
        'http' => [
            'header'  => "Content-type: application/json", // Especifica que el contenido es JSON
            'method'  => 'POST',
            'content' => json_encode($data), // Codifica los datos a JSON
            'timeout' => 10 // Tiempo de espera en segundos
        ],
    ];

    // Crea el contexto de la solicitud HTTP
    $context = stream_context_create($options);

    // Realiza la solicitud POST a tu script de envío de correo PHPMailer
    // Asegúrate de que esta URL sea la correcta para tu enviar_correo.php "servidor"
    $response = file_get_contents('https://api.dijequesi.com/enviar_correo.php', false, $context);

    // Opcional: Procesar la respuesta del servidor
    // Puedes loggear la respuesta o mostrarla para depuración
    if (strpos($response, 'Correo enviado exitosamente.') !== false) {
        // echo 'El mensaje de confirmación ha sido enviado correctamente.';
        return true; // Éxito
    } else {
        error_log("Error al enviar correo de confirmación a $email: " . $response);
        // echo 'El mensaje de confirmación no pudo ser enviado: ' . $response;
        return false; // Error
    }
}

// --- Ejemplo de cómo llamar a esta función ---
/*
// Datos de ejemplo para la confirmación de compra
$email_cliente = 'cliente@example.com';
$nombre_cliente = 'Juan';
$apellido_cliente = 'Pérez';
$monto_compra = 12500;
$productos_comprados = 'Camisa (x1), Pantalón (x2)';
$forma_pago_cliente = 'Mercado Pago';
$compartido_compra = 'No';

if (enviarCorreoConfirmacion($email_cliente, $nombre_cliente, $apellido_cliente, $monto_compra, $productos_comprados, $forma_pago_cliente, $compartido_compra)) {
    echo "Correo de confirmación de compra enviado exitosamente a $email_cliente.";
} else {
    echo "Falló el envío del correo de confirmación de compra a $email_cliente. Revisa los logs.";
}
*/
?>