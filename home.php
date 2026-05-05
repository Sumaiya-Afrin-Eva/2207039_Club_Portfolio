<?php
// Initialize data the first time
if (!file_exists(__DIR__ . '/api/config.php')) {
    // API files not yet created
} else {
    require_once __DIR__ . '/api/config.php';
    require_once __DIR__ . '/api/init.php';
}
?>