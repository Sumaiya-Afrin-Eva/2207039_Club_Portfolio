-- ====================================================
-- Club Portfolio Database Setup Script
-- This script creates all necessary tables for the application
-- ====================================================

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `club_portfolio`;
USE `club_portfolio`;
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
-- 5. REGISTRATIONS TABLE - Store event registrations
-- Consolidated table for all event registrations (both users and guests)
-- ====================================================
CREATE TABLE IF NOT EXISTS `registrations` (
  `registration_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT,
  `institute` VARCHAR(255),
  `academic_year` VARCHAR(50),
  `gender` VARCHAR(20),
  `experience` VARCHAR(255),
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_event_registration` (`event_id`, `email`),
  INDEX `idx_event` (`event_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_registration_date` (`registration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 6. COMMENTS TABLE - Store event comments and reviews
-- ====================================================
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255),
  `comment_text` TEXT,
  `rating` INT CHECK (rating >= 1 AND rating <= 5),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 7. REMINDERS TABLE - Store event reminders
-- ====================================================
CREATE TABLE IF NOT EXISTS `reminders` (
  `reminder_id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `reminder_time` DATETIME NOT NULL,
  `is_sent` BOOLEAN DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_at` TIMESTAMP NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  INDEX `idx_event` (`event_id`),
  INDEX `idx_email` (`email`),
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
-- 9. TEAM_MEMBERS TABLE - Store team member information
-- ====================================================
CREATE TABLE IF NOT EXISTS `team_members` (
  `team_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `position` VARCHAR(255) NOT NULL,
  `bio` TEXT,
  `image_url` VARCHAR(500),
  `email` VARCHAR(255),
  `phone` VARCHAR(20),
  `is_active` BOOLEAN DEFAULT 1,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_display_order` (`display_order`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- 10. CONTACT_SUBMISSIONS TABLE - Store contact form submissions
-- ====================================================
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `contact_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'read', 'archived') DEFAULT 'new',
  `response_notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
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

-- ====================================================
-- INSERT DEFAULT TEAM MEMBERS
-- ====================================================
INSERT INTO `team_members` (`full_name`, `position`, `bio`, `display_order`, `is_active`)
VALUES 
  ('Sanjida Afrin Shikha', 'President', 'Leading the Photography & Media Club with vision and creativity', 1, 1),
  ('Sumaiya Afrin Eva', 'Creative Lead', 'Driving innovative creative projects and member engagement', 2, 1)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);