<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * Centralizes database connection setup using PDO (PHP Data Objects).
 * 
 * PDO is a database abstraction layer that allows secure database queries
 * with prepared statements (prevents SQL injection attacks).
 * 
 * Responsibilities:
 * - Create a single PDO connection instance
 * - Configure error mode and fetch behavior
 * - Handle connection errors gracefully
 */
class Database
{
    // Hard-coded connection details (in production, use environment variables)
    private $host = 'db';
    private $db_name = 'monster_maker';
    private $username = 'root';
    private $password = 'root';
    private $conn;

    /**
     * Establish and return a PDO database connection.
     * 
     * Connection Configuration:
     * - charset=utf8mb4: Support for full Unicode (emojis, symbols, etc.)
     * - ATTR_ERRMODE => ERRMODE_EXCEPTION: Errors thrown as exceptions (easier to catch/handle)
     * - ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC: Results returned as associative arrays
     * - ATTR_EMULATE_PREPARES => false: Use native prepared statements (more secure)
     * 
     * @return PDO The active database connection object
     * @throws PDOException if connection fails
     */
    public function getConnection(): PDO
    {
        // Initialize connection variable to null
        $this->conn = null;

        try {
            // Attempt to create a new PDO connection using the Data Source Name (DSN)
            // DSN format: "driver:host=value;dbname=value;charset=value"
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    // ATTR_ERRMODE => ERRMODE_EXCEPTION: If a query fails, throw an exception
                    // instead of silently failing. This makes errors visible and debuggable.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    
                    // ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC: Every query result will be
                    // returned as an associative array (key => value) by default.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    
                    // ATTR_EMULATE_PREPARES => false: Use the database server's native
                    // prepared statement functionality instead of PHP emulation.
                    // This is more secure against SQL injection.
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Log connection error without exposing details to end users
            error_log('Database connection error: ' . $e->getMessage());
            throw $e;
        }

        return $this->conn; // Return the established PDO connection
    }
}
