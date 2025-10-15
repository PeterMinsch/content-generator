<?php
/**
 * Enable Elementor for seo-page post type
 *
 * Run this once to enable Elementor support for the seo-page post type.
 */

// Load WordPress
define( 'WP_USE_THEMES', false );
require_once __DIR__ . '/../../../../wp-load.php';

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied' );
}

header( 'Content-Type: text/plain' );

echo "=== ENABLING ELEMENTOR FOR SEO-PAGE POST TYPE ===\n\n";

// Get current Elementor settings
$elementor_cpt_support = get_option( 'elementor_cpt_support', array() );

echo "Current post types enabled for Elementor:\n";
if ( empty( $elementor_cpt_support ) ) {
    echo "  (none - using defaults)\n";
} else {
    foreach ( $elementor_cpt_support as $post_type ) {
        echo "  - {$post_type}\n";
    }
}

// Check if seo-page is already enabled
if ( in_array( 'seo-page', $elementor_cpt_support, true ) ) {
    echo "\n✓ seo-page is ALREADY enabled for Elementor\n";
} else {
    // Add seo-page to the list
    $elementor_cpt_support[] = 'seo-page';

    // If the array was empty, add the default post types too
    if ( count( $elementor_cpt_support ) === 1 ) {
        $elementor_cpt_support = array( 'page', 'post', 'seo-page' );
    }

    // Update the option
    update_option( 'elementor_cpt_support', $elementor_cpt_support );

    echo "\n✓ ADDED seo-page to Elementor post types\n";
}

echo "\nUpdated post types enabled for Elementor:\n";
$updated_support = get_option( 'elementor_cpt_support', array() );
foreach ( $updated_support as $post_type ) {
    echo "  - {$post_type}\n";
}

echo "\n=== DONE ===\n";
echo "Now refresh the edit page for post 442 and you should see 'Edit with Elementor' button!\n";
