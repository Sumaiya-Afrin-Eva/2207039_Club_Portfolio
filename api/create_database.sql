-- ====================================================
-- Club Portfolio Database Setup Script
-- This script creates all necessary tables for the application
-- ====================================================

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `club_portfolio`;
USE `club_portfolio`;
DROP TABLE IF EXISTS `users`;
-- ====================================================
-- 1. USERS TABLE - Store admin and member information
-- ====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'member') DEFAULT 'member',
  `phone` VARCHAR(20),
  `is_active` BOOLEAN DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 2. EVENTS TABLE - Store event details
-- ====================================================
CREATE TABLE IF NOT EXISTS `events` (
  `event_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100),
  `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
  `event_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME,
  `location` VARCHAR(255),
  `organizer_id` INT,
  `price` DECIMAL(10, 2) DEFAULT 0,
  `capacity` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_organizer` (`organizer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 3. EVENT_AGENDA TABLE - Store event schedule/agenda
-- ====================================================
CREATE TABLE IF NOT EXISTS `event_agenda` (
  `agenda_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `agenda_time` TIME NOT NULL,
  `activity` VARCHAR(500),
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 4. EVENT_EQUIPMENT TABLE - Store equipment requirements
-- ====================================================
CREATE TABLE IF NOT EXISTS `event_equipment` (
  `equipment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `equipment_name` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 5. REGISTRATIONS TABLE - Store user event registrations
-- ====================================================
CREATE TABLE IF NOT EXISTS `registrations` (
  `registration_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_registration` (`event_id`, `user_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 6. COMMENTS TABLE - Store event comments and reviews
-- ====================================================
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment_text` TEXT,
  `rating` INT CHECK (rating >= 1 AND rating <= 5),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 7. REMINDERS TABLE - Store event reminders
-- ====================================================
CREATE TABLE IF NOT EXISTS `reminders` (
  `reminder_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `reminder_time` DATETIME NOT NULL,
  `is_sent` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` TIMESTAMP NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_reminder_time` (`reminder_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 8. AUDIT_LOG TABLE - Track admin activities
-- ====================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100),
  `table_name` VARCHAR(100),
  `record_id` INT,
  `old_values` JSON,
  `new_values` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_admin` (`admin_id`),
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- INSERT DEFAULT ADMIN USER
-- Default credentials: email: admin@clubportfolio.com, password: Admin@123
-- IMPORTANT: Change password after first login!
-- ====================================================
INSERT INTO `users` (`email`, `password`, `full_name`, `role`, `is_active`)
VALUES (
  'admin@clubportfolio.com',
  '$2y$10$GYsT5/hSy7mZUMSGrKdVOemzw4p4H.GGOvN9fQwwwN5RRv9u5M7zm', -- Hash of 'Admin@123'
  'Admin User',
  'admin',
  1
) ON DUPLICATE KEY UPDATE `email` = `email`;
