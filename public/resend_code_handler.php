<?php
// =================================================================
// 📢 CRÍTICO: HANDLER AISLADO PARA REENVÍO DE CÓDIGO DE VERIFICACIÓN
// Este archivo NO DEBE CONTENER NINGÚN ESPACIO NI SALTO DE LÍNEA ANTES DE <?php
// =================================================================

// 1. Cargar el entorno y el autoloader esencial manualmente
require_once __DIR__ . '/../Core/LoadEnv.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Helpers\MailHelper;
use Core\Database;

// 2. Definir cabecera JSON y Output Buffering local
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error de inicialización.'];

try {
    // Cargar variables de entorno (LoadEnv debe estar listo en este punto)
    \Core\LoadEnv::load(); 

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido.';
        goto send_response;
    }

    $email = $_POST['email'] ?? '';
    if (empty($email)) {
        $response['message'] = 'Email no proporcionado.';
        goto send_response;
    }
    
    // 3. Buscar el registro pendiente
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT nombre, email FROM registros_pendientes WHERE email = ? AND expira_en > NOW()");
    $stmt->execute([$email]);
    $pendiente = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$pendiente) {
        $response['message'] = 'El tiempo de espera ha expirado o el email no es válido. Regístrate de nuevo.';
        goto send_response;
    }

    // 4. Generar y Guardar nuevo código
    $nuevoCodigo = rand(100000, 999999);
    $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $sql = "UPDATE registros_pendientes SET codigo = ?, expira_en = ? WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$nuevoCodigo, $expira, $email]);

    // 5. Enviar Email
    if (MailHelper::enviarCodigoVerificacion($email, $pendiente['nombre'], $nuevoCodigo)) {
        $response = ['success' => true, 'message' => 'Nuevo código enviado.'];
    } else {
        $response['message'] = 'Error al enviar el correo. Verifique la configuración SMTP.';
    }

} catch (\Exception $e) {
    error_log("Error CRÍTICO en resend_handler: " . $e->getMessage());
    $response['message'] = 'Error crítico en el proceso de reenvío.';
    http_response_code(500);
}

send_response:
    // Limpieza y envío de respuesta
    if (ob_get_length() > 0) {
        ob_end_clean();
    }
    echo json_encode($response);
    exit;
?>