<?php
namespace Core\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class MailHelper
{
    public static function enviarCodigoVerificacion($email, $nombre, $codigo)
    {
        $mail = new PHPMailer(true);

        ob_start();

        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->SMTPDebug = SMTP::DEBUG_OFF;

            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug ($level): " . $str); 
            };

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
            ob_end_clean();
            return true;
        } catch (Exception $e) {
            
            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            
            error_log("Error detallado de PHPMailer: " . $mail->ErrorInfo); 
            return false;
        }
    }

    public static function enviarCorreoRecuperacion($email, $nombre, $codigo)
    {
        $mail = new PHPMailer(true);
        
        // 📢 CRÍTICO: Buffer interno para evitar corrupción JSON
        ob_start();

        try {
            // Configuración del servidor (Reutilizamos la lógica segura)
            $mail->isSMTP();
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Debugoutput = function($str, $level) { error_log("PHPMailer Debug ($level): " . $str); };
            
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
            $mail->Subject = "Código de verificación de recuperación";

            // Plantilla Personalizada
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;'>
                <h2 style='color: #00d2ff; font-size: 24px; margin-bottom: 20px;'>Código de verificación</h2>
                
                <p>Estimado usuario:</p>
                
                <p>Recibimos una solicitud para acceder a tu cuenta de <strong style='color: #00d2ff;'>Bytebox</strong> con tu dirección de correo electrónico. El código de verificación es:</p>
                
                <div style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #333; margin: 30px 0;'>
                    $codigo
                </div>
                
                <p>Si no solicitaste este código, es posible que otra persona esté intentando acceder a la cuenta de <strong style='color: #00d2ff;'>Bytebox</strong>. No reenvíes ni proporciones este código a otra persona.</p>
                
                <p style='margin-top: 40px;'>Atentamente.</p>
                <p><em>Bytebox</em></p>
            </div>";

            $mail->Body = $body;
            $mail->AltBody = "Tu código de recuperación es: $codigo";

            $mail->send();
            ob_end_clean();
            return true;

        } catch (Exception $e) {
            if (ob_get_length() > 0) ob_end_clean();
            error_log("Error PHPMailer Recuperación: " . $mail->ErrorInfo);
            return false;
        }
    }
}