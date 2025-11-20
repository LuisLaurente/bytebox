<?php
namespace Core\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    public static function enviarCodigoVerificacion($email, $nombre, $codigo)
    {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USER'];
            $mail->Password = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
            $mail->CharSet = 'UTF-8';

            // Remitente y Destinatario
            $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_FROM_NAME'] ?? 'Bytebox');
            $mail->addAddress($email, $nombre);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = "Tu código de verificación Bytebox: $codigo";

            // Plantilla HTML simple y profesional
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                <h2 style='color: #00d2ff; text-align: center;'>Verifica tu cuenta</h2>
                <p>Hola <strong>$nombre</strong>,</p>
                <p>Gracias por registrarte en Bytebox. Usa el siguiente código para completar tu registro:</p>
                <div style='background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; font-weight: bold; color: #333; border-radius: 5px; margin: 20px 0;'>
                    $codigo
                </div>
                <p style='font-size: 12px; color: #666;'>Este código expirará en 10 minutos.</p>
                <p style='font-size: 12px; color: #999; text-align: center; margin-top: 30px;'>Si no solicitaste este código, ignora este mensaje.</p>
            </div>";

            $mail->Body = $body;
            $mail->AltBody = "Tu código de verificación es: $codigo";

            $mail->send();
            $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            return true;
        } catch (Exception $e) {
            error_log("Error detallado de PHPMailer: " . $mail->ErrorInfo); 
            return false;
        }
    }
}