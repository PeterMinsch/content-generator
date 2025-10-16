<?php
/**
 * Check what block order is saved in post meta
 * Run this via browser: /wp-content/plugins/content-generator-disabled/check-block-order.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== CHECKING BLOCK ORDER IN POST META ===\n\n";

// Get the 5 most recent seo-page posts
$args = array(
    'post_type' => 'seo-page',
    'posts_per_page' => 5,
    'orderby' => 'ID',
    'order' => 'DESC',
    'post_status' => 'any',
);

$posts = get_posts($args);

if (empty($posts)) {
    echo "No seo-page posts found.\n";
    exit;
}

echo "Found " . count($posts) . " recent seo-page posts:\n\n";

foreach ($posts as $post) {
    echo "========================================\n";
    echo "Post ID: $post->ID\n";
    echo "Title: $post->post_title\n";
    echo "Status: $post->post_status\n";
    echo "Created: $post->post_date\n\n";

    // Get block order from post meta
    $block_order_json = get_post_meta($post->ID, '_seo_block_order', true);

    if (empty($block_order_json)) {
        echo "❌ NO BLOCK ORDER SAVED\n";
        echo "(Will use default: all 13 blocks)\n\n";
    } else {
        echo "✅ BLOCK ORDER FOUND:\n";
        echo "Raw JSON: $block_order_json\n\n";

        $block_order = json_decode($block_order_json, true);

        if (is_array($block_order)) {
            echo "Decoded blocks (" . count($block_order) . " total):\n";
            foreach ($block_order as $i => $block) {
                echo "  " . ($i + 1) . ". $block\n";
            }
        } else {
            echo "❌ INVALID JSON - could not decode\n";
        }
    }

    echo "\n";
}

echo "=== DONE ===\n\n";
echo "NOTE: If posts show 'NO BLOCK ORDER SAVED', they will generate all 13 blocks.\n";
echo "If posts show '2-3 blocks' (seo_metadata + hero + maybe one more), that's correct.\n";
