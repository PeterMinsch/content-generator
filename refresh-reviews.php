<?php
/**
 * Refresh reviews from Apify with corrected field mapping
 * Run this via browser: /wp-content/plugins/content-generator-disabled/refresh-reviews.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== REFRESHING REVIEWS WITH CORRECT FIELD MAPPING ===\n\n";

// Load services
$settings_service = new \SEOGenerator\Services\SettingsService();
$google_service = new \SEOGenerator\Services\GoogleBusinessService();
$repository = new \SEOGenerator\Repositories\ReviewRepository();

echo "Step 1: Clearing existing reviews from database...\n";
$deleted = $repository->deleteAll();
echo "✓ Deleted $deleted old reviews\n\n";

echo "Step 2: Fetching fresh reviews from Apify (this will take ~30-60 seconds)...\n";
echo "⚠️  WARNING: This will make an Apify API call!\n\n";

$review_fetch_service = new \SEOGenerator\Services\ReviewFetchService(
    $google_service,
    $repository
);

// Force refresh to get new data with corrected field mapping
$reviews = $review_fetch_service->getReviews(50, true);

if (empty($reviews)) {
    echo "❌ ERROR: No reviews fetched\n";
    exit;
}

echo "✅ SUCCESS: Fetched " . count($reviews) . " reviews\n\n";

echo "========================================\n";
echo "SAMPLE OF FETCHED REVIEWS:\n";
echo "========================================\n\n";

// Show first 5 reviews with full details
$sample_count = min(5, count($reviews));
for ($i = 0; $i < $sample_count; $i++) {
    $review = $reviews[$i];
    echo "Review " . ($i + 1) . ":\n";
    echo "  Author: " . ($review['reviewer_name'] ?? 'N/A') . "\n";
    echo "  Profile URL: " . (empty($review['reviewer_profile_url']) ? 'N/A' : $review['reviewer_profile_url']) . "\n";
    echo "  Avatar URL: " . (empty($review['reviewer_avatar_url']) ? 'N/A' : substr($review['reviewer_avatar_url'], 0, 60) . '...') . "\n";
    echo "  Rating: " . ($review['rating'] ?? 'N/A') . "/5\n";
    echo "  Date: " . ($review['review_date'] ?? 'N/A') . "\n";
    echo "  Text: " . substr($review['review_text'], 0, 100) . "...\n";
    echo "\n";
}

echo "========================================\n";
echo "VERIFICATION:\n";
echo "========================================\n\n";

// Count reviews with valid author names
$with_names = 0;
$with_avatars = 0;
$with_profile_urls = 0;

foreach ($reviews as $review) {
    if (!empty($review['reviewer_name']) && $review['reviewer_name'] !== 'Anonymous' && $review['reviewer_name'] !== 'Unknown') {
        $with_names++;
    }
    if (!empty($review['reviewer_avatar_url'])) {
        $with_avatars++;
    }
    if (!empty($review['reviewer_profile_url'])) {
        $with_profile_urls++;
    }
}

echo "Total reviews: " . count($reviews) . "\n";
echo "Reviews with author names: $with_names\n";
echo "Reviews with profile URLs: $with_profile_urls\n";
echo "Reviews with avatar URLs: $with_avatars\n\n";

if ($with_names === count($reviews)) {
    echo "✅ SUCCESS: All reviews have author names!\n";
} else {
    echo "⚠️  WARNING: " . (count($reviews) - $with_names) . " reviews are missing author names\n";
}

if ($with_profile_urls === count($reviews)) {
    echo "✅ SUCCESS: All reviews have profile URLs!\n";
} else {
    echo "⚠️  INFO: " . (count($reviews) - $with_profile_urls) . " reviews don't have profile URLs\n";
}

if ($with_avatars === count($reviews)) {
    echo "✅ SUCCESS: All reviews have avatar URLs!\n";
} else {
    echo "⚠️  INFO: " . (count($reviews) - $with_avatars) . " reviews don't have avatar URLs (this is normal for some users)\n";
}

echo "\n=== DONE ===\n";
echo "\nYou can now use these reviews in your content generation.\n";
