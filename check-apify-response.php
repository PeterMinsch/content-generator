<?php
/**
 * Check raw Apify API response to debug field names
 * Run this via browser: /wp-content/plugins/content-generator-disabled/check-apify-response.php
 */

define('WP_USE_THEMES', false);
require 'C:/Users/petem/Local Sites/contentgeneratorwpplugin/app/public/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

header('Content-Type: text/plain');

echo "=== CHECKING APIFY RAW RESPONSE ===\n\n";

// Get Apify settings
$settings = get_option('seo_generator_settings', array());

if (empty($settings['apify_api_token'])) {
    echo "❌ ERROR: Apify API token not configured\n";
    exit;
}

// Decrypt token
$api_token = seo_generator_decrypt_api_key($settings['apify_api_token']);
if (empty($api_token)) {
    echo "❌ ERROR: Failed to decrypt Apify API token\n";
    exit;
}

if (empty($settings['place_url'])) {
    echo "❌ ERROR: Place URL not configured\n";
    exit;
}

$place_url = $settings['place_url'];
echo "Place URL: $place_url\n\n";

// Get the most recent Apify run
echo "Fetching most recent Apify run...\n";

$url = sprintf(
    'https://api.apify.com/v2/acts/nwua9Gu5YrADL7ZDj/runs/last/dataset/items?token=%s',
    $api_token
);

$response = wp_remote_get($url, array('timeout' => 30));

if (is_wp_error($response)) {
    echo "❌ ERROR: " . $response->get_error_message() . "\n";
    exit;
}

$body = wp_remote_retrieve_body($response);
$data = json_decode($body, true);

if (empty($data) || !is_array($data)) {
    echo "❌ ERROR: No data returned from Apify\n";
    exit;
}

// Show first place result
if (isset($data[0])) {
    echo "✅ Found Apify data\n\n";
    echo "Place Name: " . ($data[0]['title'] ?? 'N/A') . "\n";
    echo "Total Reviews in Response: " . (isset($data[0]['reviews']) ? count($data[0]['reviews']) : 0) . "\n\n";

    // Show structure of first review
    if (isset($data[0]['reviews'][0])) {
        echo "========================================\n";
        echo "STRUCTURE OF FIRST REVIEW:\n";
        echo "========================================\n";

        $first_review = $data[0]['reviews'][0];

        echo "\nALL FIELDS AVAILABLE:\n";
        foreach ($first_review as $key => $value) {
            if (is_string($value)) {
                // Truncate long text
                $display_value = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
                echo "  - $key: $display_value\n";
            } elseif (is_numeric($value)) {
                echo "  - $key: $value\n";
            } elseif (is_bool($value)) {
                echo "  - $key: " . ($value ? 'true' : 'false') . "\n";
            } else {
                echo "  - $key: " . gettype($value) . "\n";
            }
        }

        echo "\n========================================\n";
        echo "WHAT WE'RE LOOKING FOR:\n";
        echo "========================================\n";

        // Check for reviewer name fields
        $name_fields = ['name', 'reviewerName', 'author', 'authorName', 'userName'];
        echo "\nREVIEWER NAME:\n";
        foreach ($name_fields as $field) {
            if (isset($first_review[$field])) {
                echo "  ✅ FOUND: '$field' = " . $first_review[$field] . "\n";
            }
        }

        // Check for profile photo fields
        $photo_fields = ['profilePhotoUrl', 'reviewerPhotoUrl', 'authorPhotoUrl', 'photoUrl', 'avatarUrl'];
        echo "\nPROFILE PHOTO URL:\n";
        foreach ($photo_fields as $field) {
            if (isset($first_review[$field])) {
                echo "  ✅ FOUND: '$field' = " . substr($first_review[$field], 0, 80) . "...\n";
            }
        }

        // Check for rating fields
        $rating_fields = ['stars', 'rating', 'score'];
        echo "\nRATING:\n";
        foreach ($rating_fields as $field) {
            if (isset($first_review[$field])) {
                echo "  ✅ FOUND: '$field' = " . $first_review[$field] . "\n";
            }
        }

        echo "\n========================================\n";
        echo "RAW JSON OF FIRST REVIEW:\n";
        echo "========================================\n";
        echo json_encode($first_review, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ ERROR: Empty response from Apify\n";
}

echo "\n=== DONE ===\n";
