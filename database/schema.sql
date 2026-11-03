-- =====================================================================
-- SmartFood Rescue - database schema (MySQL 5.7+ / MariaDB 10.4+, InnoDB)
-- Import with phpMyAdmin (Import tab) or: mysql -u root < database/schema.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS smartfood_rescue
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartfood_rescue;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_resets, reviews, notifications, deliveries, pickup_schedules, food_requests,
                     donation_images, food_donations, food_categories, volunteers,
                     organizations, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- users: one row per login, whatever the role
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name           VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    phone          VARCHAR(30)  NULL,
    role           ENUM('admin','donor','ngo','volunteer') NOT NULL,
    status         ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at  DATETIME NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- organizations: profile of a donor business (restaurant, bakery...) or an NGO/charity
-- ---------------------------------------------------------------------
CREATE TABLE organizations (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    org_name        VARCHAR(150) NOT NULL,
    org_type        ENUM('restaurant','bakery','hotel','grocery','ngo','charity') NOT NULL,
    registration_no VARCHAR(60)  NULL,
    address         VARCHAR(255) NOT NULL,
    city            VARCHAR(80)  NULL,
    latitude        DECIMAL(10,7) NULL,
    longitude       DECIMAL(10,7) NULL,
    verified        TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_org_user (user_id),
    KEY idx_org_type (org_type),
    KEY idx_org_verified (verified),
    CONSTRAINT fk_org_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- volunteers: extra profile for users with role = volunteer
-- ---------------------------------------------------------------------
CREATE TABLE volunteers (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    vehicle       ENUM('none','bike','car','van') NOT NULL DEFAULT 'none',
    service_area  VARCHAR(150) NULL,
    availability  ENUM('available','busy','offline') NOT NULL DEFAULT 'available',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vol_user (user_id),
    CONSTRAINT fk_vol_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- food_categories
-- ---------------------------------------------------------------------
CREATE TABLE food_categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)  NOT NULL,
    description VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_category_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- food_donations: a surplus-food listing posted by a donor organization
-- status flow: available -> requested -> approved -> scheduled -> collected -> delivered
--              (any open state can also end as expired or cancelled)
-- ---------------------------------------------------------------------
CREATE TABLE food_donations (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    org_id         INT UNSIGNED NOT NULL,
    category_id    INT UNSIGNED NOT NULL,
    title          VARCHAR(150) NOT NULL,
    description    TEXT NULL,
    quantity       DECIMAL(10,2) NOT NULL,
    unit           ENUM('kg','portions','packs','liters','items') NOT NULL DEFAULT 'portions',
    expiry_at      DATETIME NOT NULL,
    pickup_address VARCHAR(255) NOT NULL,
    latitude       DECIMAL(10,7) NOT NULL,
    longitude      DECIMAL(10,7) NOT NULL,
    status         ENUM('available','requested','approved','scheduled','collected','delivered','expired','cancelled')
                   NOT NULL DEFAULT 'available',
    expiry_alerted TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_don_status_expiry (status, expiry_at),
    KEY idx_don_org (org_id),
    KEY idx_don_category (category_id),
    CONSTRAINT chk_don_qty CHECK (quantity > 0),
    CONSTRAINT fk_don_org      FOREIGN KEY (org_id)      REFERENCES organizations(id)  ON DELETE CASCADE,
    CONSTRAINT fk_don_category FOREIGN KEY (category_id) REFERENCES food_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE donation_images (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    donation_id  INT UNSIGNED NOT NULL,
    image_path   VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_img_donation (donation_id),
    CONSTRAINT fk_img_donation FOREIGN KEY (donation_id) REFERENCES food_donations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- food_requests: an NGO asks for a donation; the donor approves or rejects
-- ---------------------------------------------------------------------
CREATE TABLE food_requests (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    donation_id      INT UNSIGNED NOT NULL,
    ngo_id           INT UNSIGNED NOT NULL,           -- organizations.id of the NGO
    quantity_needed  DECIMAL(10,2) NOT NULL,
    message          VARCHAR(500) NULL,
    status           ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    responded_at     DATETIME NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_request_once (donation_id, ngo_id),
    KEY idx_req_ngo (ngo_id),
    KEY idx_req_status (status),
    CONSTRAINT chk_req_qty CHECK (quantity_needed > 0),
    CONSTRAINT fk_req_donation FOREIGN KEY (donation_id) REFERENCES food_donations(id) ON DELETE CASCADE,
    CONSTRAINT fk_req_ngo      FOREIGN KEY (ngo_id)      REFERENCES organizations(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- pickup_schedules: the agreed collection slot for an approved request (1:1)
-- ---------------------------------------------------------------------
CREATE TABLE pickup_schedules (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id  INT UNSIGNED NOT NULL,
    pickup_at   DATETIME NOT NULL,
    notes       VARCHAR(500) NULL,
    created_by  INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_schedule_request (request_id),
    KEY idx_schedule_time (pickup_at),
    CONSTRAINT fk_sched_request FOREIGN KEY (request_id) REFERENCES food_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_sched_user    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- deliveries: the physical hand-over + distribution record (1:1 with a schedule)
-- qr_token is shown as a QR code to the collector and scanned by the donor at pickup
-- ---------------------------------------------------------------------
CREATE TABLE deliveries (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    schedule_id         INT UNSIGNED NOT NULL,
    volunteer_id        INT UNSIGNED NULL,
    qr_token            CHAR(32) NOT NULL,
    status              ENUM('scheduled','picked_up','delivered','cancelled') NOT NULL DEFAULT 'scheduled',
    picked_up_at        DATETIME NULL,
    delivered_at        DATETIME NULL,
    people_served       INT UNSIGNED NULL,
    distribution_notes  VARCHAR(500) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_delivery_schedule (schedule_id),
    UNIQUE KEY uq_delivery_token (qr_token),
    KEY idx_delivery_volunteer (volunteer_id),
    KEY idx_delivery_status (status),
    CONSTRAINT fk_del_schedule  FOREIGN KEY (schedule_id)  REFERENCES pickup_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_del_volunteer FOREIGN KEY (volunteer_id) REFERENCES volunteers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- notifications: in-app alerts (requests, approvals, expiry warnings...)
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    type        ENUM('info','request','approval','pickup','delivery','expiry') NOT NULL DEFAULT 'info',
    title       VARCHAR(150) NOT NULL,
    message     VARCHAR(500) NOT NULL,
    link        VARCHAR(255) NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user_read (user_id, is_read, created_at),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- reviews: donor <-> NGO feedback after a completed delivery
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    delivery_id  INT UNSIGNED NOT NULL,
    reviewer_id  INT UNSIGNED NOT NULL,
    reviewee_id  INT UNSIGNED NOT NULL,
    rating       TINYINT UNSIGNED NOT NULL,
    comment      VARCHAR(500) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_review_once (delivery_id, reviewer_id),
    KEY idx_review_reviewee (reviewee_id),
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_rev_delivery FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- password_resets: one-time, expiring tokens (only the SHA-256 hash is stored)
-- ---------------------------------------------------------------------
CREATE TABLE password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reset_token (token_hash),
    KEY idx_reset_user (user_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Seed data
-- Demo password for every account: Password@123   (admin: Admin@123)
-- ---------------------------------------------------------------------
INSERT INTO food_categories (name, description) VALUES
 ('Cooked meals',        'Ready-to-eat hot or chilled meals'),
 ('Bakery & bread',      'Bread, buns, pastries, cakes'),
 ('Fruits & vegetables', 'Fresh produce'),
 ('Dairy & eggs',        'Milk, yoghurt, cheese, eggs'),
 ('Packaged food',       'Sealed and packaged goods'),
 ('Beverages',           'Juices, soft drinks, water'),
 ('Catering leftovers',  'Surplus from events and buffets');

INSERT INTO users (name, email, password_hash, phone, role) VALUES
 ('Site Admin',      'admin@smartfood.test',     '$2y$10$IKOlv48y/r02h661IW2d0u3h98QUiao26RPXmpFsHWHNQnXS8vuTa', '0300-0000000', 'admin'),
 ('Hamza Bakery',    'donor@smartfood.test',     '$2y$10$7BzOvkmab8qZQ/9ZyY353ObFkebK6MoqeKrjaBOnzKTJM6mgno6vG',  '0300-1111111', 'donor'),
 ('Green Crescent',  'ngo@smartfood.test',       '$2y$10$7BzOvkmab8qZQ/9ZyY353ObFkebK6MoqeKrjaBOnzKTJM6mgno6vG',  '0300-2222222', 'ngo'),
 ('Ali Raza',        'volunteer@smartfood.test', '$2y$10$7BzOvkmab8qZQ/9ZyY353ObFkebK6MoqeKrjaBOnzKTJM6mgno6vG',  '0300-3333333', 'volunteer');

INSERT INTO organizations (user_id, org_name, org_type, registration_no, address, city, latitude, longitude, verified) VALUES
 (2, 'Hamza Bakery & Sweets',   'bakery', 'NTN-100200', 'Shop 4, Main Boulevard',  'Karachi', 24.8138000, 67.0299000, 1),
 (3, 'Green Crescent Welfare',  'ngo',    'REG-77881',  'Block 7, Community Hall', 'Karachi', 24.9056000, 67.0822000, 1);

INSERT INTO volunteers (user_id, vehicle, service_area) VALUES (4, 'bike', 'Central district');

INSERT INTO food_donations (org_id, category_id, title, description, quantity, unit, expiry_at, pickup_address, latitude, longitude) VALUES
 (1, 2, 'Fresh bread loaves (end of day)', 'Unsold white and brown loaves, baked this morning.', 40, 'items',
     DATE_ADD(NOW(), INTERVAL 20 HOUR), 'Shop 4, Main Boulevard', 24.8138000, 67.0299000),
 (1, 1, 'Chicken biryani trays', 'Two catering trays left over from an event. Keep refrigerated.', 60, 'portions',
     DATE_ADD(NOW(), INTERVAL 5 HOUR), 'Shop 4, Main Boulevard', 24.8138000, 67.0299000);
