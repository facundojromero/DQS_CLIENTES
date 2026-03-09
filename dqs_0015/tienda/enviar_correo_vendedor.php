<?php
// Incluir el archivo de conexión a la base de datos (si la conexión $conn es necesaria)
include '../conexion.php';

/**
 * Envía un correo de notificación al vendedor sobre un nuevo regalo.
 * Utiliza un servicio externo de envío de correos (PHPMailer).
 *
 * @param string $nombre Nombre de quien hizo el regalo.
 * @param string $apellido Apellido de quien hizo el regalo.
 * @param float $monto_total Monto total del regalo.
 * @param string $productos Lista de productos del regalo.
 * @param string $forma_pago Forma de pago utilizada.
 * @param string $compartido Información de con quién se compartió.
 * @param string $mensaje Mensaje adicional del remitente del regalo.
 * @param string $telefono Número de teléfono del remitente del regalo.
 * @param string $email Email del remitente del regalo.
 * @param string $regalo_id ID del regalo.
 * @param string $cbu_a_mostrar El CBU/CVU de la cuenta con el que se debe pagar. <-- NUEVO
 * @param string $cbu_titular El titular de la cuenta. <-- NUEVO
 * @param string $alias_a_mostrar El alias de la cuenta. <-- NUEVO
 * @param string $simbolo_moneda Símbolo de la moneda ($, u$s). <-- NUEVO
 * @param string $tipo_cuenta Descripción de la cuenta (Pesos/Dólares). <-- NUEVO
 * @return bool Retorna true si ambos correos (al vendedor y BCC) se enviaron correctamente, false en caso contrario.
 */
function enviarCorreoVendedor($nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $telefono, $email, $regalo_id, $cbu_a_mostrar, $cbu_titular, $alias_a_mostrar, $simbolo_moneda, $tipo_cuenta) {
    global $conn;

    $to_vendedor = '';
    $bcc_email = "info@rdpcompraventa.com"; // Dirección de correo para la copia oculta (BCC)

    // Obtener el correo electrónico del vendedor desde la tabla 'user'
    $sql = "SELECT 
            nombre
            , apellido
            , email
            , nombre_carpeta
            FROM cliente a
            INNER JOIN `user` b
            ON a.user_id = b.id 
            INNER JOIN (SELECT nombre_carpeta FROM admin_config WHERE fecha_creacion IN (SELECT MAX(fecha_creacion) FROM admin_config)) c
            LIMIT 1
";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $to_vendedor = $row['email'];
        $nombre_cliente = $row['nombre'];
        $apellido_cliente = $row['apellido'];
        $nombre_carpeta = $row['nombre_carpeta'];        
    } else {
        error_log("No se encontró el correo electrónico del vendedor en la base de datos.");
    }

    if (empty($to_vendedor) && empty($bcc_email)) {
        return false;
    }

    $subject = "[DQS] Nuevo regalo de: $nombre $apellido N° $regalo_id";
    
    
    
    
    
$detalle_compartido = '';
if (!empty(trim($compartido))) {
    $detalle_compartido = '
    <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:20px;font-size:14px;">    
      <tr>
        <td style="padding:10px 0;"><span class="label">👥 Compartido con:</span><strong> ' . $compartido . '</strong></td>
      </tr>
    </table>';
}


// Bloque mensaje
$detalle_mensaje = '';
if (!empty(trim($mensaje))) {
    $detalle_mensaje = '
      <tr>
        <td style="padding:10px 0;"><span class="label">📝 Mensaje:</span><br>
          <div style="background:#f9f9f9;padding:10px;border:1px solid #ddd;border-radius:5px;margin-top:5px;">' . nl2br($mensaje) . '</div>
        </td>
      </tr>';
}

$detalle_pago = '';
if (strtolower(trim($forma_pago)) === 'transferencia') {
    $detalle_pago = '
    <div style="margin-top:30px;padding:15px;border:1px solid #ddd;background:#f1f7ff;border-radius:5px;">
      <p style="margin:0 0 10px;"><strong>El cliente ha elegido pagar por transferencia bancaria (' . $tipo_cuenta . ').</strong></p>
      <p style="margin:5px 0;">💰 <strong>Importe:</strong> ' . $simbolo_moneda . ' ' . number_format($monto_total, 0, ',', '.') . '</p>
      <p style="margin:5px 0;">👤 <strong>Titular de la cuenta:</strong> ' . $cbu_titular . '</p>
      <p style="margin:5px 0;">🏦 <strong>Alias:</strong> ' . $alias_a_mostrar . '</p>
      <p style="margin:5px 0;">📍 <strong>CBU:</strong> ' . $cbu_a_mostrar . '</p>
    </div>';
}


$base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['PHP_SELF']));
$base_url = rtrim($base_url, '/');

$body = '
  <div style="max-width:600px;margin:0 auto;background:#fff;padding:20px;font-family:\'Mulish\',Arial,sans-serif;color:#353943;border:1px solid #eee;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.05);">
    <h2 style="color:#25B9D7;text-align:center;">🎁 Nuevo Regalo</h2>
    <h4 style="padding:10px 0;">Regalo N°: ' . $regalo_id . ' </h4>
    <p style="font-size:16px;">Quien regaló: <strong>' . $nombre . ' ' . $apellido . '</strong></p>

    ' . $detalle_compartido . '

    <table cellpadding="0" cellspacing="0" width="100%" style="margin-top:20px;font-size:14px;">
    ' . $detalle_mensaje . '
      <tr>
        <td style="padding:10px 0;"><span class="label">📱 </span> <a href="https://api.whatsapp.com/send?phone=54' . $telefono . '" target="_blank" style="color:#25B9D7;text-decoration:none;">Responder por whatsapp</a></td>
      </tr>
      <tr>
        <td style="padding:10px 0;"><span class="label">✉️ Enviar mail:</span> <a href="mailto:' . $email . '" style="color:#25B9D7;text-decoration:none;">' . $email . '</a></td>
      </tr>
      <tr>
        <td style="padding:10px 0;"><span class="label">💳 Forma de Pago:</span> ' . $forma_pago . '</td>
      </tr>
      <tr>
        <td style="padding:10px 0;"><span class="label">💵 Monto Total:</span> <strong>' . $simbolo_moneda . ' ' . number_format($monto_total, 0, ',', '.') . '</strong></td>
      </tr>
    </table>
    
    ' . $detalle_pago . '


    <h3 style="margin-top:30px;border-bottom:1px solid #eee;padding-bottom:5px;">🎁 Productos Regalados:</h3>
    <p style="background-color:#f9f9f9;padding:10px;border-radius:5px;border:1px solid #ddd;font-size:14px;">' . nl2br($productos) . '</p>

    <p style="margin-top:30px;font-size:14px;"><a href="' . $base_url . '/' . $nombre_carpeta . '/index.php?new=ventas&view=confirmarPago" style="color:#25B9D7;font-weight:600;text-decoration:none;">✅ Confirmá si has recibido el regalo</a></p>
    


    <p style="margin-top:40px;font-size:12px;color:#999;text-align:center;">Este es un mensaje automático. No respondas a este correo.</p>

    <p style="margin-top:20px;text-align:center;font-size:14px;">
      <a href="https://instagram.com/dijequesi.ar" target="_blank" style="color:#25B9D7;text-decoration:none;font-weight:600;">Desarrollado por dijequesi.ar</a>
    </p>
  </div>';
    


    $mailServiceUrl = 'https://api.dijequesi.com/enviar_correo.php';
    $sendSuccess = true;

    // --- ENVIAR CORREO AL VENDEDOR ---
    if (!empty($to_vendedor)) {
        $data_vendedor = [
            'recipient_email' => $to_vendedor,
            'recipient_name' => $nombre_cliente,
            'subject' => $subject,
            'body' => $body,
            'is_html' => true
        ];

        $options_vendedor = [
            'http' => [
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($data_vendedor),
                'timeout' => 10
            ],
        ];

        $context_vendedor = stream_context_create($options_vendedor);
        $response_vendedor = @file_get_contents($mailServiceUrl, false, $context_vendedor); // Usamos @ para suprimir errores si el servicio falla

        if (strpos($response_vendedor, 'Correo enviado exitosamente.') === false) {
            error_log("Error al enviar correo al vendedor ($to_vendedor): " . $response_vendedor);
            $sendSuccess = false;
        }
    } else {
        error_log("No se intentó enviar correo al vendedor porque no se encontró su email.");
    }

    // --- ENVIAR CORREO BCC ---
    if (!empty($bcc_email)) {
        $data_bcc = [
            'recipient_email' => $bcc_email,
            'recipient_name' => 'Administrador',
            'subject' => "(BCC) " . $subject,
            'body' => $body,
            'is_html' => true
        ];

        $options_bcc = [
            'http' => [
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($data_bcc),
                'timeout' => 10
            ],
        ];

        $context_bcc = stream_context_create($options_bcc);
        $response_bcc = @file_get_contents($mailServiceUrl, false, $context_bcc);

        if (strpos($response_bcc, 'Correo enviado exitosamente.') === false) {
            error_log("Error al enviar correo BCC ($bcc_email): " . $response_bcc);
            $sendSuccess = false;
        }
    } else {
        error_log("No se intentó enviar correo BCC porque no se configuró una dirección.");
    }

    return $sendSuccess;
}
?>