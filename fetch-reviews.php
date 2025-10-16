<?php
/**
 * Manually fetch and populate reviews from Apify/Google Business
 * Run this via browser: /wp-content/plugins/content-generator-disabled/fetch-reviews.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== FETCHING REVIEWS ===\n\n";

// Check if required settings are configured
$settings = get_option('seo_generator_settings', array());

echo "CONFIGURATION CHECK:\n";
echo "--------------------\n";
echo "Apify API Token: " . (!empty($settings['apify_api_token']) ? 'SET ✓' : 'NOT SET ✗') . "\n";
echo "Place URL: " . (!empty($settings['place_url']) ? $settings['place_url'] : 'NOT SET ✗') . "\n";
echo "Max Reviews: " . ($settings['max_reviews'] ?? 50) . "\n\n";

if (empty($settings['apify_api_token']) || empty($settings['place_url'])) {
    echo "ERROR: Missing required configuration!\n\n";
    echo "To configure:\n";
    echo "1. Go to WordPress Admin > SEO Generator > Settings\n";
    echo "2. Scroll to 'Review Integration' section\n";
    echo "3. Enter your Apify API Token\n";
    echo "4. Enter your Google Business Place URL\n";
    echo "5. Save settings\n\n";
    echo "Then run this script again.\n\n";
    echo "=== END ===\n";
    exit;
}

echo "Configuration looks good! Attempting to fetch reviews...\n\n";

try {
    // Get review service
    $review_service = \SEOGenerator\Plugin::getReviewFetchService();

    echo "Fetching reviews (this may take 30-60 seconds)...\n";
    echo "Watch for Apify API call messages in output below...\n\n";

    // Capture error log output
    ob_start();

    // Force refresh to bypass cache and fetch fresh from API
    $reviews = $review_service->getReviews(50, true);

    $log_output = ob_get_clean();

    echo "\n";
    echo "SUCCESS!\n";
    echo "--------\n";
    echo "Reviews fetched: " . count($reviews) . "\n\n";

    if (count($reviews) > 0) {
        echo "SAMPLE REVIEWS:\n";
        echo "---------------\n";
        foreach (array_slice($reviews, 0, 5) as $idx => $review) {
            echo "\nReview " . ($idx + 1) . ":\n";
            echo "  Author: " . ($review['author_name'] ?? 'Unknown') . "\n";
            echo "  Rating: " . ($review['rating'] ?? 'N/A') . "/5\n";
            echo "  Date: " . ($review['review_date'] ?? 'N/A') . "\n";
            echo "  Text: " . substr($review['review_text'] ?? '', 0, 100) . "...\n";
        }

        // Check database
        global $wpdb;
        $table_name = $wpdb->prefix . 'seo_reviews';
        $db_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        echo "\n\nDATABASE STATUS:\n";
        echo "----------------\n";
        echo "Total reviews in database: $db_count\n";
    } else {
        echo "\nWARNING: No reviews were fetched!\n";
        echo "This could mean:\n";
        echo "- The API credentials are incorrect\n";
        echo "- The Place URL is invalid\n";
        echo "- The business has no reviews\n";
        echo "- There was an API error\n\n";
        echo "Check the debug log for more details.\n";
    }

} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===\n";
