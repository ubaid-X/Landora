<?php

require_once __DIR__ . '/../config/config.php';

class Database
{
    private string $host = DB_HOST;
    private string $dbname = DB_NAME;
    private string $username = DB_USER;
    private string $password = DB_PASS;

    private ?PDO $connection = null;

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            try {
                $this->connection = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                    $this->username,
                    $this->password
                );

                $this->connection->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                
                $this->connection->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );
              
            } catch (PDOException $e) {
                throw new Exception("Database connection failed.");
            }
        }

        return $this->connection;
    }
}