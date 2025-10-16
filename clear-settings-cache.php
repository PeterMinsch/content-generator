<?php
/**
 * Clear settings cache to apply new max_tokens value
 * Run this via browser: /wp-content/plugins/content-generator-disabled/clear-settings-cache.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== CLEARING SETTINGS CACHE ===\n\n";

// Clear WordPress object cache
wp_cache_delete('seo_gen_settings');
echo "✓ Cleared WordPress object cache for 'seo_gen_settings'\n\n";

// Get current settings
$settings = get_option('seo_generator_settings', array());
echo "CURRENT SETTINGS:\n";
echo "----------------\n";
echo "Model: " . ($settings['model'] ?? 'not set') . "\n";
echo "Temperature: " . ($settings['temperature'] ?? 'not set') . "\n";
echo "Max Tokens: " . ($settings['max_tokens'] ?? 'not set (will use default 4096)') . "\n";
echo "Monthly Budget: $" . ($settings['monthly_budget'] ?? 'not set') . "\n\n";

// If max_tokens is not set or is 3000, update it to 4096
if (empty($settings['max_tokens']) || $settings['max_tokens'] == 3000) {
    echo "UPDATING max_tokens from " . ($settings['max_tokens'] ?? 'not set') . " to 4096...\n";
    $settings['max_tokens'] = 4096;
    update_option('seo_generator_settings', $settings);
    echo "✓ Updated max_tokens to 4096\n\n";
} else {
    echo "max_tokens is already set to: " . $settings['max_tokens'] . "\n\n";
}

echo "=== DONE ===\n\n";
echo "The new max_tokens value (4096) will now be used for all content generation.\n";
echo "This should prevent JSON truncation issues with longer blocks like FAQs, comparison, etc.\n";
