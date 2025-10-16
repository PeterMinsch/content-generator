<?php
/**
 * Add reviewer_profile_url column to reviews table
 * Run this via browser: /wp-content/plugins/content-generator-disabled/add-reviewer-url-column.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== ADDING REVIEWER_PROFILE_URL COLUMN ===\n\n";

global $wpdb;
$table_name = $wpdb->prefix . 'seo_reviews';

// Check if column already exists
$column_exists = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'reviewer_profile_url'",
        DB_NAME,
        $table_name
    )
);

if (!empty($column_exists)) {
    echo "✓ Column 'reviewer_profile_url' already exists\n";
} else {
    echo "Adding 'reviewer_profile_url' column...\n";

    $sql = "ALTER TABLE $table_name ADD COLUMN reviewer_profile_url VARCHAR(500) AFTER reviewer_avatar_url";
    $result = $wpdb->query($sql);

    if ($result === false) {
        echo "❌ ERROR: Failed to add column\n";
        echo "Error: " . $wpdb->last_error . "\n";
        exit;
    }

    echo "✅ SUCCESS: Added 'reviewer_profile_url' column\n";
}

echo "\n=== TABLE STRUCTURE ===\n\n";

// Show current table structure
$columns = $wpdb->get_results("DESCRIBE $table_name");
foreach ($columns as $column) {
    echo sprintf(
        "%-25s %-15s %s\n",
        $column->Field,
        $column->Type,
        $column->Null === 'YES' ? 'NULL' : 'NOT NULL'
    );
}

echo "\n=== DONE ===\n";
echo "\nThe reviews table is now ready to store reviewer profile URLs.\n";
echo "Run refresh-reviews.php to fetch fresh data with profile URLs.\n";
