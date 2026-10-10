CREATE DATABASE IF NOT EXISTS property_marketplace
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE property_marketplace;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dealers (
    dealer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    whatsapp VARCHAR(20) NULL,
    full_address TEXT NOT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    verification_status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dealers_province (province),
    INDEX idx_dealers_city (city)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS properties (
    property_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dealer_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    area_size DECIMAL(12,2) NOT NULL,
    area_unit VARCHAR(20) NOT NULL,
    property_type VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    google_map_url TEXT NOT NULL,
    property_status ENUM('unsold', 'sold')
        NOT NULL DEFAULT 'unsold',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_properties_dealer
        FOREIGN KEY (dealer_id)
        REFERENCES dealers(dealer_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_properties_dealer_id (dealer_id),
    INDEX idx_properties_province (province),
    INDEX idx_properties_city (city),
    INDEX idx_properties_status (property_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_images (
    image_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_property_images_property
        FOREIGN KEY (property_id)
        REFERENCES properties(property_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_property_images_property_id (property_id)
) ENGINE=InnoDB;