<?php
/**
 * Fix existing posts to only generate hero block
 * Run this via browser: /wp-content/plugins/content-generator-disabled/fix-post-blocks.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== FIXING POST BLOCK CONFIGURATION ===\n\n";

// Get all seo-page posts
$args = array(
    'post_type' => 'seo-page',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
);

$posts = get_posts($args);

echo "Found " . count($posts) . " seo-page posts\n\n";

if (empty($posts)) {
    echo "No posts to fix!\n";
    echo "=== DONE ===\n";
    exit;
}

// Ask which blocks to keep
echo "Which blocks do you want to keep?\n";
echo "Enter comma-separated block names (e.g., hero,serp_answer)\n";
echo "Or press ENTER to use default: hero\n\n";

// Since this is browser-based, we'll use a default
$blocks_to_keep = array('hero'); // Default to just hero block

// Add seo_metadata (always generated)
$new_block_order = array_merge(array('seo_metadata'), $blocks_to_keep);

echo "Will update all posts to only generate these blocks:\n";
foreach ($new_block_order as $block) {
    echo "  - $block\n";
}
echo "\n";

// Update each post
$updated = 0;
$errors = 0;

foreach ($posts as $post_id) {
    $post_title = get_the_title($post_id);

    // Get current block order
    $current_order = get_post_meta($post_id, '_seo_block_order', true);

    // Update to new block order
    $result = update_post_meta($post_id, '_seo_block_order', wp_json_encode($new_block_order));

    if ($result !== false) {
        $updated++;
        echo "✓ Updated post $post_id: $post_title\n";
    } else {
        $errors++;
        echo "✗ Failed to update post $post_id: $post_title\n";
    }
}

echo "\n";
echo "RESULTS:\n";
echo "--------\n";
echo "Updated: $updated posts\n";
echo "Errors: $errors posts\n";

// Also clear the generation queue since those posts have wrong config
echo "\nClearing generation queue...\n";
$queue = get_option('seo_generation_queue', array());
$old_count = count($queue);

update_option('seo_generation_queue', array());

echo "Cleared $old_count items from queue\n";
echo "(These posts had the wrong block configuration)\n\n";

echo "=== DONE ===\n\n";
echo "NEXT STEPS:\n";
echo "-----------\n";
echo "1. The existing posts now have the correct block order\n";
echo "2. The queue has been cleared\n";
echo "3. For NEW imports, make sure to:\n";
echo "   a) Click 'Configure Block Order' BEFORE importing\n";
echo "   b) Remove unwanted blocks (click the X)\n";
echo "   c) Then click 'Proceed with Import'\n\n";
echo "4. If you want to regenerate the cleared posts, you can:\n";
echo "   - Manually trigger generation from the post edit page\n";
echo "   - Or re-import them with the correct block config\n";
