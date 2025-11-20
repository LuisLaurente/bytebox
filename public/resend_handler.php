<?php
// ====================================================
// PUNTO DE ENTRADA AISLADO PARA REENVÍO DE CÓDIGO
// CRÍTICO: Este script debe ser lo más limpio posible.
// ====================================================

// 1. Cargar variables de entorno y Helpers
require_once __DIR__ . '/../Core/LoadEnv.php';
try {
    Core\LoadEnv::load();
} catch (\Exception $e) {
    // Si falla la carga, registramos el error y devolvemos JSON
    error_log("LoadEnv Error in resend_handler: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de inicialización del servidor.']);
    exit;
}

// 2. Definir cabecera JSON y Output Buffering para este script
ob_start();
header('Content-Type: application/json');

// 3. Incluir helpers esenciales
require_once __DIR__ . '/../Core/Helpers/MailHelper.php';
require_once __DIR__ . '/../Core/Database.php'; // Para acceder a la tabla temporal

use Core\Helpers\MailHelper;

$response = ['success' => false, 'message' => 'Error interno del servidor.'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Método no permitido';
        goto send_response;
    }

    // 4. Recolección de datos (solo email y código)
    $email = $_POST['email'] ?? '';
    
    // 5. Buscar el registro pendiente (para obtener nombre, password_hash y generar nuevo código)
    $db = \Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM registros_pendientes WHERE email = ? AND expira_en > NOW()");
    $stmt->execute([$email]);
    $pendiente = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$pendiente) {
        $response['message'] = 'El registro no fue encontrado o ha expirado. Intente de nuevo.';
        goto send_response;
    }

    // 6. Generar nuevo código y tiempo de expiración
    $nuevoCodigo = rand(100000, 999999);
    $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // 7. Actualizar la tabla temporal con el nuevo código
    $sql = "UPDATE registros_pendientes SET codigo = ?, expira_en = ? WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$nuevoCodigo, $expira, $email]);

    // 8. Enviar Email con el nuevo código
    if (MailHelper::enviarCodigoVerificacion($email, $pendiente['nombre'], $nuevoCodigo)) {
        $response = ['success' => true, 'message' => 'Nuevo código enviado'];
    } else {
        $response['message'] = 'Error al enviar el correo. Verifique la configuración SMTP.';
    }

} catch (\Exception $e) {
    error_log("Error en resend_handler: " . $e->getMessage());
    $response['message'] = 'Error crítico en el proceso de reenvío.';
    http_response_code(500);
}

send_response:
    // Limpieza final de buffer interno
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($response);
    exit;
?>