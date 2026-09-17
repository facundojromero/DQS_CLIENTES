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
 * @return bool Retorna true si ambos correos (al vendedor y BCC) se enviaron correctamente, false en caso contrario.
 */
function enviarCorreoVendedor($nombre, $apellido, $monto_total, $productos, $forma_pago, $compartido, $mensaje, $telefono, $email, $regalo_id) {
    global $conn; // Asegúrate de que $conn esté disponible globalmente si la usas

    $to_vendedor = ''; // Inicializar la variable del email del vendedor
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
        // Opcional: Podrías retornar aquí si el correo del vendedor es indispensable
        // return false;
    }

    // Si no se encontró un email de vendedor, al menos intentaremos enviar el BCC si es posible.
    if (empty($to_vendedor) && empty($bcc_email)) {
        echo "No hay destinatarios válidos para el correo.";
        return false;
    }

    $subject = "Nuevo regalo de: $nombre $apellido N° $regalo_id";
    
    
    
    
    
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
      <p style="margin:0 0 10px;"><strong>Ha elegido pagar por transferencia bancaria.</strong></p>
      <p style="margin:5px 0;">💰 <strong>Importe:</strong> $' . number_format($monto_total, 2, ',', '.') . '</p>
      <p style="margin:5px 0;">👤 <strong>Titular de la cuenta:</strong> Facundo Joaquin Romero</p>
      <p style="margin:5px 0;">🏦 <strong>Alias:</strong> rdp.mp</p>
      <p style="margin:5px 0;">📍 <strong>CBU:</strong> 012236654785696558745233669</p>
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
        <td style="padding:10px 0;"><span class="label">💵 Monto Total:</span> <strong>$' . number_format($monto_total, 2, ',', '.') . '</strong></td>
      </tr>
    </table>


    <h3 style="margin-top:30px;border-bottom:1px solid #eee;padding-bottom:5px;">🎁 Productos Regalados:</h3>
    <p style="background-color:#f9f9f9;padding:10px;border-radius:5px;border:1px solid #ddd;font-size:14px;">' . nl2br($productos) . '</p>

    <p style="margin-top:30px;font-size:14px;"><a href="' . $base_url . '/' . $nombre_carpeta . '/index.php?new=ventas&view=confirmarPago" style="color:#25B9D7;font-weight:600;text-decoration:none;">✅ Confirmá si has recibido el regalo</a></p>
    


    <p style="margin-top:40px;font-size:12px;color:#999;text-align:center;">Este es un mensaje automático. No respondas a este correo.</p>

    <p style="margin-top:20px;text-align:center;font-size:14px;">
      <a href="https://instagram.com/dijequesi.ar" target="_blank" style="color:#25B9D7;text-decoration:none;font-weight:600;">Desarrollado por dijequesi.ar</a>
    </p>
  </div>';
    


    // URL de tu servicio PHPMailer
    $mailServiceUrl = 'https://api.dijequesi.com/enviar_correo.php';
    $sendSuccess = true; // Para rastrear si ambos correos se enviaron

    // --- ENVIAR CORREO AL VENDEDOR ---
    if (!empty($to_vendedor)) {
        $data_vendedor = [
            'recipient_email' => $to_vendedor,
            'recipient_name' => $nombre_cliente, // Puedes poner un nombre genérico o buscar el nombre del vendedor si lo tienes
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
        $response_vendedor = file_get_contents($mailServiceUrl, false, $context_vendedor);

        if (strpos($response_vendedor, 'Correo enviado exitosamente.') === false) {
            error_log("Error al enviar correo al vendedor ($to_vendedor): " . $response_vendedor);
            // echo 'El mensaje al vendedor no pudo ser enviado: ' . $response_vendedor;
            $sendSuccess = false;
        } else {
            // echo 'El mensaje al vendedor ha sido enviado.';
        }
    } else {
        error_log("No se intentó enviar correo al vendedor porque no se encontró su email.");
    }

    // --- ENVIAR CORREO BCC ---
    // Si quieres que el BCC sea una copia exacta, envías el mismo contenido.
    // Si quieres que sea un BCC "silencioso", podrías enviar un cuerpo diferente o más corto.
    if (!empty($bcc_email)) {
        $data_bcc = [
            'recipient_email' => $bcc_email,
            'recipient_name' => 'Administrador', // Nombre para el BCC
            'subject' => "(BCC) " . $subject, // Puedes modificar el asunto para indicar que es un BCC
            'body' => $body, // Mismo cuerpo que el original
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
        $response_bcc = file_get_contents($mailServiceUrl, false, $context_bcc);

        if (strpos($response_bcc, 'Correo enviado exitosamente.') === false) {
            error_log("Error al enviar correo BCC ($bcc_email): " . $response_bcc);
            // echo 'El mensaje BCC no pudo ser enviado: ' . $response_bcc;
            $sendSuccess = false; // Si falla el BCC, el envío total también falla
        } else {
            // echo 'El mensaje BCC ha sido enviado.';
        }
    } else {
        error_log("No se intentó enviar correo BCC porque no se configuró una dirección.");
    }

    return $sendSuccess; // Retorna true si ambos se enviaron, false si alguno falló
}

// --- Ejemplo de uso de la función (quitar o comentar en producción) ---
/*
// Datos de prueba
$test_nombre = "Carlos";
$test_apellido = "Gómez";
$test_monto_total = 150.75;
$test_productos = "Libro 'PHP Avanzado', Taza de café";
$test_forma_pago = "PayPal";
$test_compartido = "Con Sofía";
$test_mensaje = "Espero que te guste mucho este regalo.";
$test_telefono = "1123456789"; // Asegúrate de que sea solo números
$test_email = "carlos.gomez@example.com";

// Llama a la función
if (enviarCorreoVendedor($test_nombre, $test_apellido, $test_monto_total, $test_productos, $test_forma_pago, $test_compartido, $test_mensaje, $test_telefono, $test_email)) {
    echo "¡Proceso de envío de correos (vendedor y BCC) completado con éxito!";
} else {
    echo "Hubo un error en el proceso de envío de correos.";
}
*/
?>