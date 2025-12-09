<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * Handles PDO database connection using environment variables
 */
class Database
{
    private $host = 'db';
    private $db_name = 'monster_maker';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection(): PDO
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            echo "Connection error: " . $e->getMessage();
            die();
        }

        return $this->conn;
    }
}
