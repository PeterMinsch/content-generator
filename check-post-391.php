<?php
/**
 * Diagnostic script to check post 391 meta data
 */

require_once __DIR__ . '/../../../wp-load.php';

$post_id = 391;

echo "=== POST 391 META DATA ===\n\n";

// Get all post meta
$all_meta = get_post_meta( $post_id );

echo "All post meta for post {$post_id}:\n";
foreach ( $all_meta as $key => $value ) {
	if ( strpos( $key, 'hero' ) !== false || strpos( $key, 'image' ) !== false ) {
		echo "\n{$key}:\n";
		print_r( $value );
	}
}

echo "\n\n=== ACF FIELD CHECK ===\n\n";

// Check specific ACF fields
if ( function_exists( 'get_field' ) ) {
	$hero_image = get_field( 'hero_image', $post_id );
	echo "get_field('hero_image', {$post_id}):\n";
	print_r( $hero_image );

	// Check field object
	if ( function_exists( 'acf_get_field' ) ) {
		$field_obj = acf_get_field( 'hero_image' );
		echo "\n\nacf_get_field('hero_image'):\n";
		print_r( $field_obj );

		// Also try with field key
		$field_obj_key = acf_get_field( 'field_hero_image' );
		echo "\n\nacf_get_field('field_hero_image'):\n";
		print_r( $field_obj_key );
	}
} else {
	echo "ACF functions not available!\n";
}

echo "\n\n=== RAW DATABASE CHECK ===\n\n";

global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare(
	"SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND (meta_key LIKE %s OR meta_key LIKE %s)",
	$post_id,
	'%hero%',
	'%image%'
) );

echo "Direct database query for hero/image meta:\n";
foreach ( $results as $row ) {
	echo "\n{$row->meta_key}: {$row->meta_value}\n";
}
