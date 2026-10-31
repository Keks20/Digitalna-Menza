<?php

/**
 * Konekcija sa bazom – Singleton: u celoj aplikaciji postoji samo jedna mysqli konekcija.
 */
class Database
{
    private static $instance = null;
    private $conn;

    private function __construct(array $config)
    {
        // Ukljucujemo izuzetke za mysqli greske (mysqli_sql_exception)
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = new mysqli(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );
        $this->conn->set_charset('utf8mb4');
    }

    private function __clone()
    {
    }

    public static function getInstance(): mysqli
    {
        if (self::$instance === null) {
            $config = include __DIR__ . '/../config.php';
            self::$instance = new Database($config);
        }
        return self::$instance->conn;
    }
}
