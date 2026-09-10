CREATE DATABASE IF NOT EXISTS staylocal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staylocal;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS unit_images;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS apartments;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admin_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_admin_email (email)
) ENGINE=InnoDB;

CREATE TABLE apartments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    area VARCHAR(100) NOT NULL,
    address VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    pet_friendly TINYINT(1) NOT NULL DEFAULT 0,
    image_url VARCHAR(255) NOT NULL DEFAULT 'assets/feat-view.jpg',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_apartments_area (area),
    INDEX idx_apartments_pet_friendly (pet_friendly)
) ENGINE=InnoDB;

CREATE TABLE units (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    apartment_id INT UNSIGNED NOT NULL,
    floor_number TINYINT UNSIGNED NOT NULL,
    unit_number VARCHAR(20) NOT NULL,
    unit_type VARCHAR(60) NOT NULL,
    size_sqm DECIMAL(6,2) NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    features TEXT DEFAULT NULL,
    status ENUM('available', 'occupied', 'reserved') NOT NULL DEFAULT 'available',
    PRIMARY KEY (id),
    UNIQUE KEY unique_apartment_unit (apartment_id, unit_number),
    INDEX idx_units_status (status),
    CONSTRAINT fk_units_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE unit_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(100) DEFAULT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    CONSTRAINT fk_images_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(140) NOT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    move_in_date DATE NOT NULL,
    occupants TINYINT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    status ENUM('pending_payment', 'paid', 'cancelled') NOT NULL DEFAULT 'pending_payment',
    PRIMARY KEY (id),
    INDEX idx_reservations_unit (unit_id),
    INDEX idx_reservations_status (status),
    INDEX idx_reservations_unit_status (unit_id, status),
    CONSTRAINT fk_reservations_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id INT UNSIGNED NOT NULL,
    payment_method ENUM('card', 'gcash', 'bank_transfer') NOT NULL,
    transaction_reference VARCHAR(80) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('paid', 'failed', 'refunded') NOT NULL DEFAULT 'paid',
    paid_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_transaction_reference (transaction_reference),
    INDEX idx_payments_reservation (reservation_id),
    CONSTRAINT fk_payments_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE visits (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(140) NOT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    PRIMARY KEY (id),
    UNIQUE KEY unique_visit_slot (visit_date, visit_time),
    INDEX idx_visits_unit (unit_id),
    INDEX idx_visits_date_status (visit_date, status),
    CONSTRAINT fk_visits_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO admin_users (full_name, email, password_hash) VALUES
('StayLocal Administrator', 'admin@staylocal.local', '$2y$10$9DNAz2Wfck.CSBQ6LmxdnuIfmbAEFmsH9ShsvbEnnA7GheZ.E96z.');

INSERT INTO apartments (name, area, address, description, pet_friendly, image_url, featured) VALUES
('Casa Pilar', 'Sampaloc', 'P. Campa Street, Manila', 'A light-filled, lived-in building close to university routes, neighborhood groceries, and the small cafés that make Sampaloc feel like home.', 1, 'assets/feat-view.jpg', 1),
('Northline Residences', 'Quezon City', 'Tomas Morato Avenue, Quezon City', 'A practical residence for renters who want a central address, generous common corridors, and a quieter place to land after a busy day.', 0, 'assets/feat-bedroom.jpg', 1),
('The Linden House', 'Ermita', 'Del Pilar Street, Manila', 'An older Manila address with high ceilings, compact layouts, and easy access to transit, shops, and the city’s waterfront edge.', 1, 'assets/feat-kitchen.jpg', 0),
('Mabini Court', 'Malate', 'Mabini Street, Manila', 'A calm, low-rise apartment court with flexible one-bedroom homes and a tucked-away location near the everyday rhythm of Malate.', 0, 'assets/feat-view.jpg', 0),
('Rizal Park Lofts', 'Intramuros', 'General Luna Street, Manila', 'A characterful building for renters who enjoy walkable heritage streets, smaller floor plans, and a home base in the heart of old Manila.', 1, 'assets/feat-bedroom.jpg', 0);

INSERT INTO units (apartment_id, floor_number, unit_number, unit_type, size_sqm, monthly_rent, image_url, features, status) VALUES
(1, 1, '101', 'Studio', 24.00, 12500.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(1, 1, '102', '1 Bedroom', 31.00, 15800.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'occupied'),
(1, 2, '201', 'Studio', 25.00, 13000.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(1, 2, '202', '1 Bedroom', 34.00, 16900.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(1, 3, '301', '2 Bedroom', 51.00, 24500.00, 'assets/unit-2br.jpg', 'Fiber Ready, Master Suite, Second Bedroom, Full Kitchen, Large Balcony, Air Conditioning', 'available'),
(1, 3, '302', '1 Bedroom', 34.00, 17200.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'reserved'),
(2, 1, '1A', 'Studio', 22.00, 14000.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(2, 1, '1B', '1 Bedroom', 36.00, 18800.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(2, 2, '2A', '1 Bedroom', 38.00, 19500.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'occupied'),
(2, 2, '2B', '2 Bedroom', 55.00, 27500.00, 'assets/unit-2br.jpg', 'Fiber Ready, Master Suite, Second Bedroom, Full Kitchen, Large Balcony, Air Conditioning', 'available'),
(2, 3, '3A', '1 Bedroom', 38.00, 19800.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(2, 3, '3B', '2 Bedroom', 57.00, 28200.00, 'assets/unit-2br.jpg', 'Fiber Ready, Master Suite, Second Bedroom, Full Kitchen, Large Balcony, Air Conditioning', 'occupied'),
(3, 1, '101', 'Studio', 21.00, 11800.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(3, 1, '102', 'Studio', 22.00, 12100.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(3, 2, '201', '1 Bedroom', 33.00, 16500.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(3, 2, '202', '1 Bedroom', 35.00, 17100.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'reserved'),
(3, 3, '301', '2 Bedroom', 49.00, 23400.00, 'assets/unit-2br.jpg', 'Fiber Ready, Master Suite, Second Bedroom, Full Kitchen, Large Balcony, Air Conditioning', 'available'),
(4, 1, '101', 'Studio', 20.00, 11000.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(4, 1, '102', '1 Bedroom', 29.00, 14600.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'occupied'),
(4, 2, '201', '1 Bedroom', 31.00, 15200.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(4, 2, '202', '1 Bedroom', 31.00, 15200.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(4, 3, '301', '2 Bedroom', 47.00, 22000.00, 'assets/unit-2br.jpg', 'Fiber Ready, Master Suite, Second Bedroom, Full Kitchen, Large Balcony, Air Conditioning', 'available'),
(5, 1, '101', 'Studio', 23.00, 13500.00, 'assets/unit-studio.jpg', 'Fiber Ready, Kitchenette, Air Conditioning, Built-in Wardrobe', 'available'),
(5, 2, '201', '1 Bedroom', 32.00, 17500.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'occupied'),
(5, 2, '202', '1 Bedroom', 33.00, 17900.00, 'assets/unit-1br.jpg', 'Fiber Ready, Separate Bedroom, Full Kitchen, Balcony, Air Conditioning', 'available'),
(5, 3, '301', '2 Bedroom', 52.00, 24800.00, 'assets/unit-2br.jpg', 'Fiber Ready, Master Suite, Second Bedroom, Full Kitchen, Large Balcony, Air Conditioning', 'available');


INSERT INTO unit_images (unit_id, image_path, caption, sort_order) VALUES
(1, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(1, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(1, 'assets/feat-view.jpg', 'City View', 3),
(1, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(2, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(2, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(2, 'assets/feat-view.jpg', 'City View', 3),
(2, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(3, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(3, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(3, 'assets/feat-view.jpg', 'City View', 3),
(3, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(4, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(4, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(4, 'assets/feat-view.jpg', 'City View', 3),
(4, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(5, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(5, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(5, 'assets/feat-view.jpg', 'City View', 3),
(5, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(6, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(6, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(6, 'assets/feat-view.jpg', 'City View', 3),
(6, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(7, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(7, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(7, 'assets/feat-view.jpg', 'City View', 3),
(7, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(8, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(8, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(8, 'assets/feat-view.jpg', 'City View', 3),
(8, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(9, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(9, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(9, 'assets/feat-view.jpg', 'City View', 3),
(9, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(10, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(10, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(10, 'assets/feat-view.jpg', 'City View', 3),
(10, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(11, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(11, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(11, 'assets/feat-view.jpg', 'City View', 3),
(11, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(12, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(12, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(12, 'assets/feat-view.jpg', 'City View', 3),
(12, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(13, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(13, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(13, 'assets/feat-view.jpg', 'City View', 3),
(13, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(14, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(14, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(14, 'assets/feat-view.jpg', 'City View', 3),
(14, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(15, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(15, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(15, 'assets/feat-view.jpg', 'City View', 3),
(15, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(16, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(16, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(16, 'assets/feat-view.jpg', 'City View', 3),
(16, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(17, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(17, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(17, 'assets/feat-view.jpg', 'City View', 3),
(17, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(18, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(18, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(18, 'assets/feat-view.jpg', 'City View', 3),
(18, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(19, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(19, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(19, 'assets/feat-view.jpg', 'City View', 3),
(19, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(20, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(20, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(20, 'assets/feat-view.jpg', 'City View', 3),
(20, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(21, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(21, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(21, 'assets/feat-view.jpg', 'City View', 3),
(21, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(22, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(22, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(22, 'assets/feat-view.jpg', 'City View', 3),
(22, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(23, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(23, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(23, 'assets/feat-view.jpg', 'City View', 3),
(23, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(24, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(24, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(24, 'assets/feat-view.jpg', 'City View', 3),
(24, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(25, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(25, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(25, 'assets/feat-view.jpg', 'City View', 3),
(25, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4),
(26, 'assets/feat-kitchen.jpg', 'Modern Kitchen', 1),
(26, 'assets/feat-bathroom.jpg', 'Clean Bathroom', 2),
(26, 'assets/feat-view.jpg', 'City View', 3),
(26, 'assets/feat-bedroom.jpg', 'Cozy Bedroom', 4);