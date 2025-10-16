<?php
/**
 * Temporary script to enable WP_DEBUG for testing
 * Run this via browser: /wp-content/plugins/content-generator-disabled/enable-debug.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== ENABLING WP_DEBUG TEMPORARILY ===\n\n";

$wp_config_path = 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-config.php';

// Read wp-config.php
$config_content = file_get_contents($wp_config_path);

// Enable WP_DEBUG
$config_content = preg_replace(
    "/define\\(\s*'WP_DEBUG',\s*false\s*\\);/",
    "define( 'WP_DEBUG', true );",
    $config_content
);

$config_content = preg_replace(
    "/define\\('WP_DEBUG_LOG',\s*false\s*\\);/",
    "define('WP_DEBUG_LOG', true);",
    $config_content
);

// Write back
if (file_put_contents($wp_config_path, $config_content)) {
    echo "SUCCESS! WP_DEBUG enabled.\n\n";
    echo "Debug log location: C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-content/debug.log\n\n";
    echo "IMPORTANT: After testing, disable it again to prevent slow page loads!\n";
} else {
    echo "ERROR: Failed to update wp-config.php\n";
}

echo "\n=== DONE ===\n";
