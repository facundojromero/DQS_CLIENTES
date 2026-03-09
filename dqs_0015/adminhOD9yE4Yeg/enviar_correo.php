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
 * @param string $mensaje Mensaje adicional del remitente.
 * @param string $cbu El CBU/CVU de la cuenta (ya sea pesos o dólares).
 * @param string $cbu_titular El titular de la cuenta.
 * @param string $alias El alias de la cuenta (ya sea pesos o dólares).
 * @param string $simbolo_moneda Símbolo de la moneda ($, u$s).
 * @param string $tipo_cuenta Descripción de la cuenta (Pesos/Dólares).
 * @param string $portada_titulo El título de la portada (ej: Boda de Juan y María). <-- NUEVO
 */
function enviarCorreoConfirmacion($email, $nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $cbu, $cbu_titular, $alias, $simbolo_moneda, $tipo_cuenta, $portada_titulo) {

    $recipientEmail = $email;
    $recipientName = "$nombre $apellido";
    // Título del correo actualizado para incluir $portada_titulo
    $subject = "=?UTF-8?B?" . base64_encode("[DQS] Tu regalo para " . $portada_titulo . " fue confirmado") . "?=";
    
    
$detalle_pago = '';
if (strtolower(trim($forma_pago)) === 'transferencia') {
    $detalle_pago = '
    <div style="margin-top:30px;padding:15px;border:1px solid #ddd;background:#f1f7ff;border-radius:5px;">
      <p style="margin:0 0 10px;"><strong>Ha pagado por transferencia bancaria.</strong></p>
      <p style="margin:5px 0;">💰 <strong>Importe:</strong> ' . $simbolo_moneda . ' ' . number_format($monto_total, 0, ',', '.') . '</p>
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

    // Cuerpo HTML del correo
$body = '
  <div style="max-width:600px;margin:0 auto;background:#fff;padding:20px;font-family:\'Mulish\',Arial,sans-serif;color:#353943;border:1px solid #eee;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.05);">
    <h2 style="color:#25B9D7;text-align:center;">✅ ¡Regalo Confirmado para ' . $portada_titulo . '!</h2>
    <p style="font-size:16px;">Gracias, <strong>' . $nombre . ' ' . $apellido . '</strong>. Tu regalo fue entregado exitosamente.</p>



            ' . $detalle_compartido . '


            ' . $detalle_pago . '
    

    <h3 style="margin-top:30px;border-bottom:1px solid #eee;padding-bottom:5px;">🛒 Productos Regalados:</h3>
    <p style="background-color:#f9f9f9;padding:10px;border-radius:5px;border:1px solid #ddd;font-size:14px;">' . nl2br($productos) . '</p>
    


    <p style="margin-top:30px;font-size:14px;">📦 ¡Tu regalo ha sido entregado!</p>
    
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
        'is_html' => true
    ];

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

    // Realiza la solicitud POST a tu script de envío de correo PHPMailer
    $response = file_get_contents('https://api.dijequesi.com/enviar_correo.php', false, $context);

    if (strpos($response, 'Correo enviado exitosamente.') !== false) {
        return true;
    } else {
        error_log("Error al enviar correo de confirmación a $email: " . $response);
        return false;
    }
}
?>