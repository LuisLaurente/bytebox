<?php
// db-admin.php - Acceso directo y seguro
session_start();

// CONFIGURA ESTA CONTRASEÑA - ¡CÁMBIALA!
$access_password = 'ByteboxAccess2024!';

if ($_POST['password'] === $access_password) {
    $_SESSION['db_access'] = true;
    header('Location: /phpmyadmin');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Acceso Base de Datos</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial; margin: 50px; text-align: center; background: #f5f5f5; }
        .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto; }
        input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔐 Acceso Base de Datos</h2>
        <p>Base de datos: <strong>ylxfwfte_bytebox</strong></p>
        <form method="POST">
            <input type="password" name="password" placeholder="Contraseña de acceso" required>
            <button type="submit">Entrar a phpMyAdmin</button>
        </form>
    </div>
</body>
</html>