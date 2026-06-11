<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/db_functions.php';

$events = get_all_events();
echo "Total events from database: " . count($events) . "\n\n";

if (!empty($events)) {
    echo "First event data:\n";
    var_dump($events[0]);
    
    echo "\n\nAll events:\n";
    foreach ($events as $event) {
        echo "- Event ID: {$event['event_id']}, Title: {$event['title']}, Status: {$event['status']}\n";
    }
}
?>