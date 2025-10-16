<?php
/**
 * Temporary diagnostic script to check block configuration
 * Run this via browser: /wp-content/plugins/content-generator-disabled/check-blocks.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== BLOCK CONFIGURATION DIAGNOSTIC ===\n\n";

// Check recent posts with _seo_block_order
global $wpdb;
$recent_posts = $wpdb->get_results(
    "SELECT post_id, meta_value FROM {$wpdb->postmeta}
    WHERE meta_key = '_seo_block_order'
    ORDER BY meta_id DESC LIMIT 5",
    ARRAY_A
);

echo "RECENT POSTS WITH BLOCK ORDER:\n";
echo "-----------------------------\n";
foreach ($recent_posts as $row) {
    $post = get_post($row['post_id']);
    echo "Post ID: {$row['post_id']}\n";
    echo "Title: " . ($post ? $post->post_title : 'N/A') . "\n";
    echo "Block Order: {$row['meta_value']}\n";
    echo "Decoded: " . print_r(json_decode($row['meta_value'], true), true) . "\n";
    echo "---\n\n";
}

// Check generation queue
$queue = get_option('seo_generation_queue', array());
echo "\nGENERATION QUEUE:\n";
echo "----------------\n";
echo "Total items: " . count($queue) . "\n\n";

foreach (array_slice($queue, 0, 3) as $idx => $item) {
    echo "Item " . ($idx + 1) . ":\n";
    echo "Post ID: " . ($item['post_id'] ?? 'N/A') . "\n";
    echo "Status: " . ($item['status'] ?? 'N/A') . "\n";
    echo "Queued at: " . ($item['queued_at'] ?? 'N/A') . "\n";

    if (isset($item['blocks'])) {
        echo "Blocks configured: " . json_encode($item['blocks']) . "\n";
        echo "Block count: " . count($item['blocks']) . "\n";
    } else {
        echo "Blocks: Not set (will use all blocks)\n";
    }
    echo "---\n\n";
}

// Check max_tokens setting
$settings = get_option('seo_generator_settings', array());
echo "\nSETTINGS:\n";
echo "--------\n";
echo "max_tokens in database: " . ($settings['max_tokens'] ?? 'Not set (will use default)') . "\n";
echo "model: " . ($settings['model'] ?? 'Not set') . "\n";
echo "temperature: " . ($settings['temperature'] ?? 'Not set') . "\n";

echo "\n=== END DIAGNOSTIC ===\n";
