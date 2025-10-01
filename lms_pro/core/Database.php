<?php

namespace LmsPro\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    /**
     * The single instance of the class.
     *
     * @var Database|null
     */
    private static $instance = null;

    /**
     * The PDO connection object.
     *
     * @var PDO
     */
    public $pdo;

    /**
     * The constructor is private to prevent direct creation of object.
     */
    private function __construct()
    {
        $config = config('database');
        $default = $config['default'];
        $connection = $config['connections'][$default];

        $dsn = "{$connection['driver']}:host={$connection['host']};port={$connection['port']};dbname={$connection['database']};charset={$connection['charset']}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $connection['username'], $connection['password'], $options);
        } catch (PDOException $e) {
            // In a real app, you would log this error, not display it to the user.
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the single instance of the Database class.
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection object.
     *
     * @return PDO
     */
    public static function connection()
    {
        return self::getInstance()->pdo;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance.
     */
    public function __wakeup() {}
}