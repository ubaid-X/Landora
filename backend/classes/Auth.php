<?php

class Auth
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findDealerByUsername(string $username): ?array
    {
        $sql = "
            SELECT *
            FROM dealers
            WHERE username = :username
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':username' => $username
        ]);

        $dealer = $stmt->fetch();

        return $dealer ?: null;
    }

    public function verifyPassword(
        string $password,
        string $hashedPassword
    ): bool {
        return password_verify($password, $hashedPassword);
    }
}