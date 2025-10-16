<?php
/**
 * Temporary script to check review scraping and storage
 * Run this via browser: /wp-content/plugins/content-generator-disabled/check-reviews.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== REVIEW SCRAPING & STORAGE CHECK ===\n\n";

// Check if ReviewRepository exists
if (!class_exists('SEOGenerator\Repositories\ReviewRepository')) {
    echo "ERROR: ReviewRepository class not found!\n";
    echo "The review system may not be implemented yet.\n\n";
    echo "=== END ===\n";
    exit;
}

// Initialize repository
$review_repo = new \SEOGenerator\Repositories\ReviewRepository();

// Check database table
global $wpdb;
$table_name = $wpdb->prefix . 'seo_reviews';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "DATABASE TABLE:\n";
echo "---------------\n";
echo "Table name: $table_name\n";
echo "Table exists: " . ($table_exists ? 'YES' : 'NO') . "\n\n";

if (!$table_exists) {
    echo "ERROR: Reviews table does not exist!\n";
    echo "The table may need to be created during plugin activation.\n\n";
    echo "=== END ===\n";
    exit;
}

// Check table structure
$columns = $wpdb->get_results("DESCRIBE $table_name");
echo "TABLE STRUCTURE:\n";
echo "----------------\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}
echo "\n";

// Check for reviews in database
$review_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "STORED REVIEWS:\n";
echo "---------------\n";
echo "Total reviews in database: $review_count\n\n";

if ($review_count > 0) {
    // Get sample reviews
    $sample_reviews = $wpdb->get_results("SELECT * FROM $table_name ORDER BY rating DESC, created_at DESC LIMIT 5", ARRAY_A);

    echo "SAMPLE REVIEWS (Top 5 by rating):\n";
    echo "----------------------------------\n";
    foreach ($sample_reviews as $idx => $review) {
        echo "\nReview " . ($idx + 1) . ":\n";
        echo "  ID: {$review['id']}\n";
        echo "  External ID: {$review['external_id']}\n";
        echo "  Author: {$review['author_name']}\n";
        echo "  Rating: {$review['rating']}/5\n";
        echo "  Date: {$review['review_date']}\n";
        echo "  Text: " . substr($review['review_text'], 0, 100) . "...\n";
        echo "  Created: {$review['created_at']}\n";
    }
}

// Check Google Business API settings
$settings = get_option('seo_generator_settings', array());
echo "\n\nGOOGLE BUSINESS API SETTINGS:\n";
echo "-----------------------------\n";
echo "API Key set: " . (!empty($settings['google_business_api_key']) ? 'YES' : 'NO') . "\n";
echo "Place ID set: " . (!empty($settings['google_place_id']) ? 'YES' : 'NO') . "\n";

if (!empty($settings['google_place_id'])) {
    echo "Place ID: {$settings['google_place_id']}\n";
}

// Check review cache
$cached_reviews = get_transient('seo_google_reviews_cache');
echo "\nREVIEW CACHE:\n";
echo "-------------\n";
if ($cached_reviews) {
    $cached_reviews_array = json_decode($cached_reviews, true);
    echo "Cached reviews: " . count($cached_reviews_array) . "\n";
    echo "Cache expiration: " . get_option('_transient_timeout_seo_google_reviews_cache') . "\n";
    echo "Seconds until expiry: " . (get_option('_transient_timeout_seo_google_reviews_cache') - time()) . "\n";
} else {
    echo "No cached reviews found.\n";
}

// Check if ReviewFetchService can be instantiated
echo "\n\nREVIEW FETCH SERVICE:\n";
echo "--------------------\n";
try {
    $review_service = \SEOGenerator\Plugin::getReviewFetchService();
    echo "Service initialized: YES\n";

    // Try to fetch reviews
    echo "\nAttempting to fetch reviews...\n";
    $reviews = $review_service->getReviews(5);
    echo "Reviews fetched: " . count($reviews) . "\n";

    if (count($reviews) > 0) {
        echo "\nSample review data structure:\n";
        echo "-----------------------------\n";
        print_r($reviews[0]);
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n\n=== END DIAGNOSTIC ===\n";
