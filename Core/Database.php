<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // Carga la configuración desde el archivo externo
            $config = require __DIR__ . '/../config/database.php';

            // Construcción del DSN (Data Source Name)
            $dsn = "mysql:host={$config['host']};port=3306;dbname={$config['dbname']};charset=utf8mb4"; //port 3307 Cambiar de ser necesario


            // Conexión PDO usando los parámetros de configuración
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Si ocurre un error, se detiene la ejecución y muestra el mensaje
            die('❌ Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    // Singleton: asegura que solo haya una instancia
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    // Obtiene la conexión activa
    public function getConnection()
    {
        return $this->connection;
    }
        public static function getConexion()
    {
        return self::getInstance()->getConnection();
    }
    

    
}

