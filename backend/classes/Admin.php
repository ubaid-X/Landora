<?php

class Admin
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByUsername(string $username): ?array
    {
        $sql = "
            SELECT admin_id, username, password, created_at
            FROM admins
            WHERE username = :username
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':username' => $username
        ]);

        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public function verifyPassword(
        string $password,
        string $hashedPassword
    ): bool {
        return password_verify($password, $hashedPassword);
    }
}