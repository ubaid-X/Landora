<?php

class Property
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new property for a dealer.
     * Returns the new property_id.
     */
    public function create(int $dealerId, array $data): int
    {
        $sql = "
            INSERT INTO properties (
                dealer_id,
                title,
                description,
                price,
                area_size,
                area_unit,
                property_type,
                address,
                province,
                city,
                google_map_url,
                property_status
            )
            VALUES (
                :dealer_id,
                :title,
                :description,
                :price,
                :area_size,
                :area_unit,
                :property_type,
                :address,
                :province,
                :city,
                :google_map_url,
                'unsold'
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':dealer_id'      => $dealerId,
            ':title'          => $data['title'],
            ':description'    => $data['description'],
            ':price'          => $data['price'],
            ':area_size'      => $data['area_size'],
            ':area_unit'      => $data['area_unit'],
            ':property_type'  => $data['property_type'],
            ':address'        => $data['address'],
            ':province'      => $data['province'],
            ':city'      => $data['city'],
            ':google_map_url' => $data['google_map_url'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a property by ID.
     */
    public function findById(int $propertyId): ?array
    {
        $sql = "SELECT * FROM properties WHERE property_id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $propertyId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Get all properties belonging to a dealer.
     */
    public function findByDealer(int $dealerId): array
    {
        $sql = "
            SELECT *
            FROM properties
            WHERE dealer_id = :dealer_id
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dealer_id' => $dealerId]);

        return $stmt->fetchAll();
    }

    /**
     * Update a property (must belong to the given dealer).
     * Returns true if a row was updated.
     */
    public function update(int $propertyId, int $dealerId, array $data): bool
    {
        $sql = "
            UPDATE properties
            SET
                title          = :title,
                description    = :description,
                price          = :price,
                area_size      = :area_size,
                area_unit      = :area_unit,
                property_type  = :property_type,
                address        = :address,
                province       = :province,
                city           = :city,
                google_map_url = :google_map_url
            WHERE property_id  = :id
              AND dealer_id    = :dealer_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':title'          => $data['title'],
            ':description'    => $data['description'],
            ':price'          => $data['price'],
            ':area_size'      => $data['area_size'],
            ':area_unit'      => $data['area_unit'],
            ':property_type'  => $data['property_type'],
            ':address'        => $data['address'],
            ':province'      => $data['province'],
            ':city'      => $data['city'],
            ':google_map_url' => $data['google_map_url'],
            ':id'             => $propertyId,
            ':dealer_id'      => $dealerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Toggle sold/unsold for a property (must belong to dealer).
     * Returns the new status, or null if nothing changed / not found.
     */
    public function toggleStatus(int $propertyId, int $dealerId, string $newStatus): ?string
    {
        if (!in_array($newStatus, ['sold', 'unsold'], true)) {
            return null;
        }

        $sql = "
            UPDATE properties
            SET property_status = :status
            WHERE property_id = :id
              AND dealer_id   = :dealer_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status'    => $newStatus,
            ':id'        => $propertyId,
            ':dealer_id' => $dealerId,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return $newStatus;
    }

    /**
     * Admin-only: delete a property by ID.
     */
    public function deleteById(int $propertyId): bool
    {
        $sql = "DELETE FROM properties WHERE property_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $propertyId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Admin-only: get all properties with dealer info.
     */
    public function findAllWithDealer(): array
    {
        $sql = "
            SELECT
                p.property_id,
                p.dealer_id,
                p.title,
                p.description,
                p.price,
                p.area_size,
                p.area_unit,
                p.property_type,
                p.address,
                p.province,
                p.city,
                p.google_map_url,
                p.property_status,
                p.views,
                p.created_at,
                p.updated_at,
                d.username       AS dealer_username,
                d.business_name  AS dealer_business_name,
                d.phone          AS dealer_phone,
                d.whatsapp       AS dealer_whatsapp
            FROM properties p
            INNER JOIN dealers d ON d.dealer_id = p.dealer_id
            ORDER BY p.created_at DESC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }
}