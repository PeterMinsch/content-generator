<?php
/**
 * Temporary script to update max_tokens in database
 * Run this via browser: /wp-content/plugins/content-generator-disabled/fix-max-tokens.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== FIXING MAX_TOKENS SETTING ===\n\n";

// Get current settings
$settings = get_option('seo_generator_settings', array());

echo "BEFORE:\n";
echo "-------\n";
echo "max_tokens: " . ($settings['max_tokens'] ?? 'Not set') . "\n\n";

// Update max_tokens to 3000
$settings['max_tokens'] = 3000;

$updated = update_option('seo_generator_settings', $settings);

if ($updated) {
    echo "SUCCESS! Updated max_tokens to 3000\n\n";

    // Verify
    $settings = get_option('seo_generator_settings', array());
    echo "AFTER:\n";
    echo "------\n";
    echo "max_tokens: " . ($settings['max_tokens'] ?? 'Not set') . "\n";

    // Clear settings cache
    wp_cache_delete('seo_gen_settings');
    echo "\nCache cleared.\n";
} else {
    echo "ERROR: Failed to update setting\n";
}

echo "\n=== DONE ===\n";
