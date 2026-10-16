-- Схема базы данных «Отель» (MySQL 8 / MariaDB 10.4+).
-- Связи: users 1—N bookings, rooms 1—N bookings, bookings 1—1 payments.

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    number      VARCHAR(10)  NOT NULL,
    class       ENUM('economy', 'standard', 'comfort', 'suite') NOT NULL,
    price       DECIMAL(10, 2) NOT NULL,
    capacity    TINYINT UNSIGNED NOT NULL,
    description TEXT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_number (number),
    KEY idx_rooms_class (class),
    CONSTRAINT chk_rooms_price CHECK (price > 0),
    CONSTRAINT chk_rooms_capacity CHECK (capacity BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    room_id    INT UNSIGNED NOT NULL,
    date_from  DATE NOT NULL,
    date_to    DATE NOT NULL,
    guests     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status     ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_room_dates (room_id, date_from, date_to),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE RESTRICT,
    CONSTRAINT chk_bookings_dates CHECK (date_to > date_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id INT UNSIGNED NOT NULL,
    amount     DECIMAL(10, 2) NOT NULL,
    paid_at    DATE NOT NULL,
    method     ENUM('card', 'cash', 'online') NOT NULL,
    status     ENUM('paid', 'refunded') NOT NULL DEFAULT 'paid',
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_booking (booking_id),
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
