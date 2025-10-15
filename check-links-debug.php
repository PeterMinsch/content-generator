<?php
/**
 * Quick debug script to check related links
 * Access via: yoursite.com/wp-content/plugins/content-generator-disabled/check-links-debug.php
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/../../../../wp-load.php',
    dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php',
];

$loaded = false;
foreach ( $wp_load_paths as $path ) {
    if ( file_exists( $path ) ) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if ( ! $loaded ) {
    die( 'Error: Could not find wp-load.php' );
}

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied' );
}

header( 'Content-Type: text/plain' );

echo "=== INTERNAL LINKING DEBUG ===\n\n";

// Get the pearl-necklaces page
$page = get_page_by_path( 'pearl-necklaces', OBJECT, 'seo-page' );

if ( ! $page ) {
    echo "ERROR: Could not find pearl-necklaces page\n";
    exit;
}

$post_id = $page->ID;

echo "Page ID: {$post_id}\n";
echo "Page Title: " . get_the_title( $post_id ) . "\n";
echo "Page Status: {$page->post_status}\n\n";

// Check for related links meta
echo "=== CHECKING POST META ===\n";
$related_links_raw = get_post_meta( $post_id, '_related_links', true );
echo "Has _related_links meta: " . ( $related_links_raw ? 'YES' : 'NO' ) . "\n";

if ( $related_links_raw ) {
    echo "\nRaw Meta Data:\n";
    print_r( $related_links_raw );
}

$timestamp = get_post_meta( $post_id, '_related_links_timestamp', true );
if ( $timestamp ) {
    echo "\nTimestamp: " . date( 'Y-m-d H:i:s', $timestamp ) . "\n";
    $age_days = ( time() - $timestamp ) / DAY_IN_SECONDS;
    echo "Age: " . round( $age_days, 2 ) . " days\n";
    echo "Stale? " . ( $age_days > 7 ? 'YES' : 'NO' ) . "\n";
}

// Test the getRelatedLinks method
echo "\n=== TESTING getRelatedLinks() ===\n";
$linking_service = new \SEOGenerator\Services\InternalLinkingService();
$related_links = $linking_service->getRelatedLinks( $post_id );

if ( $related_links ) {
    echo "✓ getRelatedLinks() returned " . count( $related_links ) . " links\n\n";
    foreach ( $related_links as $link ) {
        echo "Link ID: {$link['id']}\n";
        echo "  Title: " . get_the_title( $link['id'] ) . "\n";
        $linked_post = get_post( $link['id'] );
        echo "  Status: {$linked_post->post_status}\n";
        echo "  Score: {$link['score']}\n";
        echo "  Permalink: " . get_permalink( $link['id'] ) . "\n\n";
    }
} else {
    echo "✗ getRelatedLinks() returned NULL\n";
    echo "This means either:\n";
    echo "  1. No related links are stored\n";
    echo "  2. Links are stale (>7 days old)\n";
    echo "  3. All linked pages are not published\n";
}

// Check template path
echo "\n=== CHECKING TEMPLATE PATH ===\n";
$template_path = WP_PLUGIN_DIR . '/content-generator-disabled/templates/frontend/blocks/related-links.php';
echo "Template path: {$template_path}\n";
echo "File exists: " . ( file_exists( $template_path ) ? 'YES' : 'NO' ) . "\n";

// Check what template is being used
echo "\n=== CHECKING ACTIVE TEMPLATE ===\n";
$current_theme = get_stylesheet_directory();
$theme_template = $current_theme . '/single-seo-page.php';
echo "Theme template: {$theme_template}\n";
echo "File exists: " . ( file_exists( $theme_template ) ? 'YES' : 'NO' ) . "\n";

echo "\n=== DEBUG COMPLETE ===\n";
