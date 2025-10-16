<?php
/**
 * Temporary script to clear failed queue items
 * Run this via browser: /wp-content/plugins/content-generator-disabled/clear-queue.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== CLEARING GENERATION QUEUE ===\n\n";

// Get current queue
$queue = get_option('seo_generation_queue', array());

echo "BEFORE:\n";
echo "-------\n";
echo "Total items: " . count($queue) . "\n\n";

foreach ($queue as $idx => $item) {
    echo "Item " . ($idx + 1) . ":\n";
    echo "  Post ID: " . ($item['post_id'] ?? 'N/A') . "\n";
    echo "  Status: " . ($item['status'] ?? 'N/A') . "\n";
    echo "  Queued at: " . ($item['queued_at'] ?? 'N/A') . "\n";
    if (isset($item['blocks'])) {
        echo "  Blocks: " . json_encode($item['blocks']) . "\n";
    } else {
        echo "  Blocks: Not set\n";
    }
    echo "\n";
}

// Clear the queue
$updated = update_option('seo_generation_queue', array());

if ($updated || empty($queue)) {
    echo "\nSUCCESS! Queue cleared.\n\n";

    // Verify
    $queue = get_option('seo_generation_queue', array());
    echo "AFTER:\n";
    echo "------\n";
    echo "Total items: " . count($queue) . "\n";
} else {
    echo "\nERROR: Failed to clear queue\n";
}

echo "\n=== DONE ===\n";
echo "\nNow you can re-import your keywords with the correct block configuration.\n";
