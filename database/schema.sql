-- ============================================================
-- FoodReviewPro — Database Schema
-- Engine: MySQL 8+ / MariaDB 10.6+
-- ============================================================

CREATE DATABASE IF NOT EXISTS foodreviewpro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE foodreviewpro;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  user_name     VARCHAR(80)     NOT NULL,
  email         VARCHAR(180)    NOT NULL UNIQUE,
  password_hash VARCHAR(255)    NOT NULL,
  role          ENUM('admin','user') NOT NULL DEFAULT 'user',
  status        ENUM('active','suspended','banned') NOT NULL DEFAULT 'active',
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_email  (email),
  INDEX idx_role   (role),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── Restaurants ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restaurants (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  owner_id      INT UNSIGNED    NOT NULL,
  name          VARCHAR(150)    NOT NULL,
  description   TEXT,
  category      VARCHAR(60)     NOT NULL,
  city          VARCHAR(100)    NOT NULL,
  address       VARCHAR(255),
  phone         VARCHAR(30),
  price_range   ENUM('€','€€','€€€') NOT NULL DEFAULT '€€',
  status        ENUM('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending',
  avg_rating    DECIMAL(3,2)    NOT NULL DEFAULT 0.00,
  review_count  INT UNSIGNED    NOT NULL DEFAULT 0,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status   (status),
  INDEX idx_city     (city),
  INDEX idx_category (category),
  INDEX idx_rating   (avg_rating)
) ENGINE=InnoDB;

-- ── Reviews ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  restaurant_id   INT UNSIGNED    NOT NULL,
  user_id         INT UNSIGNED    NOT NULL,
  rating          TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment         TEXT,
  status          ENUM('pending','approved','flagged','deleted') NOT NULL DEFAULT 'pending',
  flag_reason     VARCHAR(255),
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
  UNIQUE KEY uq_user_restaurant (user_id, restaurant_id),
  INDEX idx_status        (status),
  INDEX idx_restaurant_id (restaurant_id)
) ENGINE=InnoDB;

-- ── Activity logs ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  admin_id    INT UNSIGNED  NOT NULL,
  action      VARCHAR(30)   NOT NULL,   -- APPROVE, REJECT, DELETE, UPDATE, CREATE, EXPORT
  target_type VARCHAR(40)   NOT NULL,   -- restaurant, review, user
  target_id   INT UNSIGNED,
  details     VARCHAR(255),
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_admin_id (admin_id),
  INDEX idx_action   (action)
) ENGINE=InnoDB;

-- ── Trigger: update avg_rating after insert/update/delete ────
DELIMITER $$

CREATE TRIGGER trg_update_rating_after_insert
AFTER INSERT ON reviews FOR EACH ROW
BEGIN
  UPDATE restaurants
  SET avg_rating   = (SELECT COALESCE(AVG(rating), 0) FROM reviews
                      WHERE restaurant_id = NEW.restaurant_id AND status = 'approved'),
      review_count = (SELECT COUNT(*) FROM reviews
                      WHERE restaurant_id = NEW.restaurant_id AND status = 'approved')
  WHERE id = NEW.restaurant_id;
END$$

CREATE TRIGGER trg_update_rating_after_update
AFTER UPDATE ON reviews FOR EACH ROW
BEGIN
  UPDATE restaurants
  SET avg_rating   = (SELECT COALESCE(AVG(rating), 0) FROM reviews
                      WHERE restaurant_id = NEW.restaurant_id AND status = 'approved'),
      review_count = (SELECT COUNT(*) FROM reviews
                      WHERE restaurant_id = NEW.restaurant_id AND status = 'approved')
  WHERE id = NEW.restaurant_id;
END$$

CREATE TRIGGER trg_update_rating_after_delete
AFTER DELETE ON reviews FOR EACH ROW
BEGIN
  UPDATE restaurants
  SET avg_rating   = (SELECT COALESCE(AVG(rating), 0) FROM reviews
                      WHERE restaurant_id = OLD.restaurant_id AND status = 'approved'),
      review_count = (SELECT COUNT(*) FROM reviews
                      WHERE restaurant_id = OLD.restaurant_id AND status = 'approved')
  WHERE id = OLD.restaurant_id;
END$$

DELIMITER ;
