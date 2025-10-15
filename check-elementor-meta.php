<?php
/**
 * Check Elementor Meta Fields
 *
 * Quick diagnostic to check if Elementor meta is set correctly.
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

$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 442;

echo "=== ELEMENTOR META CHECK ===\n\n";
echo "Post ID: {$post_id}\n";
echo "Post Title: " . get_the_title( $post_id ) . "\n";
echo "Post Status: " . get_post_status( $post_id ) . "\n\n";

echo "=== META FIELDS ===\n";
$edit_mode = get_post_meta( $post_id, '_elementor_edit_mode', true );
$template = get_post_meta( $post_id, '_wp_page_template', true );

echo "_elementor_edit_mode: " . ( $edit_mode ? "'{$edit_mode}'" : 'NOT SET' ) . "\n";
echo "_wp_page_template: " . ( $template ? "'{$template}'" : 'NOT SET' ) . "\n\n";

echo "=== EXPECTED VALUES ===\n";
echo "_elementor_edit_mode: 'builder'\n";
echo "_wp_page_template: 'elementor_canvas'\n\n";

echo "=== STATUS ===\n";
if ( $edit_mode === 'builder' && $template === 'elementor_canvas' ) {
    echo "✓ Elementor meta fields are CORRECT\n";
} else {
    echo "✗ Elementor meta fields are INCORRECT or MISSING\n\n";
    echo "FIX: Run this command to set them manually:\n";
    echo "update_post_meta( {$post_id}, '_elementor_edit_mode', 'builder' );\n";
    echo "update_post_meta( {$post_id}, '_wp_page_template', 'elementor_canvas' );\n";
}

echo "\n=== ELEMENTOR PLUGIN CHECK ===\n";
if ( is_plugin_active( 'elementor/elementor.php' ) ) {
    echo "✓ Elementor plugin is ACTIVE\n";
} else {
    echo "✗ Elementor plugin is NOT active\n";
}

echo "\n=== ALL POST META ===\n";
$all_meta = get_post_meta( $post_id );
foreach ( $all_meta as $key => $values ) {
    if ( strpos( $key, 'elementor' ) !== false || strpos( $key, 'template' ) !== false ) {
        echo "{$key}: " . print_r( $values, true );
    }
}
