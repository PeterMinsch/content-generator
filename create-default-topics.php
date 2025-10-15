<?php
/**
 * Temporary script to create default SEO topics.
 * Run this once from WordPress admin or via browser.
 * Delete this file after running.
 */

// Load WordPress.
require_once __DIR__ . '/../../../wp-load.php';

// Check if user is logged in and has permission.
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('You must be logged in as an administrator to run this script.');
}

// Default topics to create.
$default_topics = array(
    'Wedding Rings',
    'Engagement Rings',
    'Necklaces',
    'Bracelets',
    'Earrings',
    'Pendants',
    'Gemstones',
    'Diamond Education',
    'Jewelry Care',
    'Gift Guides',
);

echo '<h1>Creating Default SEO Topics</h1>';
echo '<ul>';

foreach ($default_topics as $topic) {
    // Check if term already exists.
    $existing = term_exists($topic, 'seo-topic');

    if ($existing) {
        echo '<li>✓ Topic already exists: ' . esc_html($topic) . '</li>';
    } else {
        // Create the term.
        $result = wp_insert_term($topic, 'seo-topic');

        if (is_wp_error($result)) {
            echo '<li>✗ Failed to create: ' . esc_html($topic) . ' - ' . esc_html($result->get_error_message()) . '</li>';
        } else {
            echo '<li>✓ Created: ' . esc_html($topic) . '</li>';
        }
    }
}

echo '</ul>';
echo '<p><strong>Done!</strong> You can now delete this file: <code>create-default-topics.php</code></p>';
echo '<p><a href="' . admin_url('edit-tags.php?taxonomy=seo-topic&post_type=seo-page') . '">View SEO Topics</a></p>';
