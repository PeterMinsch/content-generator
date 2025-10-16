<?php
/**
 * Manually create the reviews table
 * Run this via browser: /wp-content/plugins/content-generator-disabled/create-review-table.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== CREATING REVIEWS TABLE ===\n\n";

global $wpdb;

$table_name = $wpdb->prefix . 'seo_reviews';
$charset_collate = $wpdb->get_charset_collate();

echo "Table name: $table_name\n";
echo "Charset: $charset_collate\n\n";

// Create table SQL
$sql = "CREATE TABLE $table_name (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL DEFAULT 'google',
    external_id VARCHAR(255) NOT NULL,
    author_name VARCHAR(255),
    author_photo_url VARCHAR(500),
    rating DECIMAL(2,1),
    review_text TEXT,
    review_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (source, external_id),
    INDEX idx_source (source),
    INDEX idx_created_at (created_at),
    INDEX idx_rating (rating)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

echo "Creating table...\n";
dbDelta($sql);

// Check if table was created
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    echo "\nSUCCESS! Table created.\n\n";

    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "TABLE STRUCTURE:\n";
    echo "----------------\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }

    // Update database version
    update_option('seo_generator_review_db_version', '1.0');
    echo "\nDatabase version updated.\n";
} else {
    echo "\nERROR: Table was not created!\n";
    echo "MySQL Error: " . $wpdb->last_error . "\n";
}

echo "\n=== DONE ===\n";
