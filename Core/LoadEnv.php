<?php
// Core/LoadEnv.php
namespace Core;

class LoadEnv
{
    public static function load($path = null)
    {
        if (!$path) {
            $path = __DIR__ . '/../.env';
        }

        if (!file_exists($path)) {
            // Si no existe el .env, no lanzamos error pero lo registramos
            error_log(".env file not found at: " . $path);
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Saltar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Separar nombre y valor
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue; // Línea inválida
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remover comillas si existen
            $value = trim($value, '"\'');
            
            // Solo establecer si no existe
            if ($name && !array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }
        
        error_log("✅ .env loaded successfully from: " . $path);
    }
}