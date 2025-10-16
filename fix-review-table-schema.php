<?php
/**
 * Fix reviews table schema - rename created_at to last_fetched_at
 * Run this via browser: /wp-content/plugins/content-generator-disabled/fix-review-table-schema.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== FIXING REVIEWS TABLE SCHEMA ===\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'seo_reviews';

// Check current columns
$columns = $wpdb->get_results("DESCRIBE $table_name");

echo "CURRENT COLUMNS:\n";
echo "----------------\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}
echo "\n";

// Check if we need to rename/add columns
$has_last_fetched_at = false;
$has_created_at = false;

foreach ($columns as $column) {
    if ($column->Field === 'last_fetched_at') {
        $has_last_fetched_at = true;
    }
    if ($column->Field === 'created_at') {
        $has_created_at = true;
    }
}

echo "FIXES NEEDED:\n";
echo "-------------\n";

if (!$has_last_fetched_at && $has_created_at) {
    echo "Need to rename 'created_at' to 'last_fetched_at'\n\n";

    $sql = "ALTER TABLE $table_name CHANGE COLUMN created_at last_fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP";
    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "SUCCESS! Renamed created_at to last_fetched_at\n";
    } else {
        echo "ERROR: " . $wpdb->last_error . "\n";
    }
} elseif (!$has_last_fetched_at) {
    echo "Need to add 'last_fetched_at' column\n\n";

    $sql = "ALTER TABLE $table_name ADD COLUMN last_fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP";
    $result = $wpdb->query($sql);

    if ($result !== false) {
        echo "SUCCESS! Added last_fetched_at column\n";
    } else {
        echo "ERROR: " . $wpdb->last_error . "\n";
    }
} else {
    echo "No fixes needed - last_fetched_at column exists\n";
}

// Show updated schema
echo "\nUPDATED SCHEMA:\n";
echo "---------------\n";
$columns = $wpdb->get_results("DESCRIBE $table_name");
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

echo "\n=== DONE ===\n";
