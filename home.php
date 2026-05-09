<?php
/**
 * home.php - Main Backend Entry Point for Photography Club Website
 * 
 * This file serves as the backend initialization for the Photography & Media Club website.
 * It checks for the existence of API configuration files and initializes the data structures
 * required for the application to function properly.
 * 
 * Functions:
 * - Validates API configuration file existence
 * - Loads database configuration from config.php
 * - Initializes default data and JSON files via init.php
 * - Prepares the application for handling event management, registrations, and comments
 */

// Initialize data the first time
if (!file_exists(__DIR__ . '/api/config.php')) {
    // API files not yet created - display warning or create defaults
} else {
    // Include configuration and initialization files
    require_once __DIR__ . '/api/config.php';
    require_once __DIR__ . '/api/init.php';
}
?>