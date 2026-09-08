<?php
   require 'vendor/autoload.php';
   use PHPMailer\PHPMailer\PHPMailer;
   use PHPMailer\PHPMailer\Exception;

   $mail = new PHPMailer(true);

   try {
       $mail->isSMTP();
       $mail->SMTPDebug = 3; // Nivel de depuración
       $mail->Host = 'smtp.hostinger.com';
       $mail->Port = 465; // Probá con 465 si falla
       $mail->SMTPSecure = 'ssl'; // Si usás 465, cambiá a 'ssl'
       $mail->SMTPAuth = true;
       $mail->Username = 'info@rdpcompraventa.com';
       $mail->Password = 'Abc123456@';

       $mail->setFrom('info@rdpcompraventa.com', 'Your Name');
       $mail->addReplyTo('info@rdpcompraventa.com', 'Your Name');
       $mail->addAddress('facundoj.romero@gmail.com', 'Receiver Name');
       $mail->Subject = 'Checking if PHPMailer works';
       
       // Mensaje HTML
       $mail->isHTML(true);
       $mail->Body = '<h1>Este es un correo de prueba</h1><p>Si lo recibís, significa que PHPMailer funciona correctamente.</p>';
       
       // Alternativa en texto plano
       $mail->AltBody = 'Este es un correo de prueba en texto plano.';
       
       // Enviar correo
       $mail->send();
       echo 'El correo se envió correctamente.';
   } catch (Exception $e) {
       echo "Error al enviar el correo: {$mail->ErrorInfo}";
   }
?>
