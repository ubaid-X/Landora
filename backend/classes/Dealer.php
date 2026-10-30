<?php

class Dealer
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function usernameExists(string $username): bool
    {
        $sql = "
            SELECT dealer_id
            FROM dealers
            WHERE username = :username
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':username' => $username
        ]);

        return $stmt->fetch() !== false;
    }

    public function emailExists(string $email): bool
    {
        $sql = "
            SELECT dealer_id
            FROM dealers
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch() !== false;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO dealers (
                username,
                email,
                password,
                business_name,
                owner_name,
                phone,
                whatsapp,
                full_address,
                province,
                city,
                verification_status
            )
            VALUES (
                :username,
                :email,
                :password,
                :business_name,
                :owner_name,
                :phone,
                :whatsapp,
                :full_address,
                :province,
                :city,
                'pending'
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':business_name' => $data['business_name'],
            ':owner_name' => $data['owner_name'],
            ':phone' => $data['phone'],
            ':whatsapp' => $data['whatsapp'],
            ':full_address' => $data['full_address'],
            ':province' => $data['province'],
            ':city' => $data['city'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}