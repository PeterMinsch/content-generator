<?php
/**
 * Fix column name mismatches in reviews table
 * Run this via browser: /wp-content/plugins/content-generator-disabled/fix-column-names.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== FIXING COLUMN NAMES ===\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'seo_reviews';

// Check current columns
$columns = $wpdb->get_results("DESCRIBE $table_name");

echo "CURRENT COLUMNS:\n";
echo "----------------\n";
$column_names = array();
foreach ($columns as $column) {
    echo "  - {$column->Field}\n";
    $column_names[] = $column->Field;
}
echo "\n";

// Map of code expectations => database actuals
$renames = array(
    'external_id' => 'external_review_id',
    'author_name' => 'reviewer_name',
    'author_photo_url' => 'reviewer_avatar_url',
);

echo "APPLYING FIXES:\n";
echo "---------------\n";

foreach ($renames as $old_name => $new_name) {
    if (in_array($old_name, $column_names) && !in_array($new_name, $column_names)) {
        echo "Renaming '$old_name' to '$new_name'...\n";

        $sql = "ALTER TABLE $table_name CHANGE COLUMN `$old_name` `$new_name` VARCHAR(255) NOT NULL";
        $result = $wpdb->query($sql);

        if ($result !== false) {
            echo "  ✓ SUCCESS\n";
        } else {
            echo "  ✗ ERROR: " . $wpdb->last_error . "\n";
        }
    } elseif (in_array($new_name, $column_names)) {
        echo "Column '$new_name' already exists - skipping\n";
    }
}

// Show final schema
echo "\nFINAL SCHEMA:\n";
echo "-------------\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

echo "\n=== DONE ===\n";
echo "\nNow re-run the fetch-reviews.php script to populate the database!\n";
