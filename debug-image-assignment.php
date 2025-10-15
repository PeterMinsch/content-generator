<?php
/**
 * Debug Image Assignment
 *
 * Temporary diagnostic script to check image auto-assignment configuration.
 *
 * Usage: Add ?debug_images=1 to any admin page URL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['debug_images'] ) || ! current_user_can( 'manage_options' ) ) {
	return;
}

// Check settings
$settings = get_option( 'seo_generator_settings', array() );
$image_settings = get_option( 'seo_generator_image_settings', array() );

echo '<div style="background: #fff; border: 1px solid #ccc; padding: 20px; margin: 20px; font-family: monospace;">';
echo '<h2>Image Auto-Assignment Debug Info</h2>';

// 1. Check settings
echo '<h3>1. Settings</h3>';
echo '<pre>';
echo "enable_auto_assignment: " . ( isset( $settings['enable_auto_assignment'] ) ? ( $settings['enable_auto_assignment'] ? 'TRUE' : 'FALSE' ) : 'NOT SET' ) . "\n";
echo "auto_assign_images (old key): " . ( isset( $settings['auto_assign_images'] ) ? ( $settings['auto_assign_images'] ? 'TRUE' : 'FALSE' ) : 'NOT SET' ) . "\n";
echo "\nFull seo_generator_settings:\n";
print_r( $settings );
echo "\nFull seo_generator_image_settings:\n";
print_r( $image_settings );
echo '</pre>';

// 2. Check for library images
echo '<h3>2. Image Library</h3>';
$library_images = new WP_Query( array(
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'posts_per_page' => 100,
	'fields'         => 'ids',
	'meta_query'     => array(
		array(
			'key'   => '_seo_library_image',
			'value' => '1',
		),
	),
) );

echo '<pre>';
echo "Total library images: " . count( $library_images->posts ) . "\n";

if ( ! empty( $library_images->posts ) ) {
	echo "\nFirst 10 library images:\n";
	foreach ( array_slice( $library_images->posts, 0, 10 ) as $image_id ) {
		$title = get_the_title( $image_id );
		$tags = wp_get_object_terms( $image_id, 'image_tag', array( 'fields' => 'names' ) );
		$folder = get_post_meta( $image_id, '_seo_image_folder', true );

		echo sprintf(
			"- Image #%d: %s | Tags: %s | Folder: %s\n",
			$image_id,
			$title,
			! empty( $tags ) ? implode( ', ', $tags ) : 'NONE',
			$folder ?: 'NONE'
		);
	}
}
echo '</pre>';

// 3. Check all images with image_tag taxonomy
echo '<h3>3. All Images with Tags (non-library)</h3>';
$tagged_images = new WP_Query( array(
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'posts_per_page' => 100,
	'fields'         => 'ids',
	'post_mime_type' => 'image',
	'tax_query'      => array(
		array(
			'taxonomy' => 'image_tag',
			'operator' => 'EXISTS',
		),
	),
) );

echo '<pre>';
echo "Total images with tags: " . count( $tagged_images->posts ) . "\n";

if ( ! empty( $tagged_images->posts ) ) {
	echo "\nFirst 10 tagged images:\n";
	foreach ( array_slice( $tagged_images->posts, 0, 10 ) as $image_id ) {
		$title = get_the_title( $image_id );
		$tags = wp_get_object_terms( $image_id, 'image_tag', array( 'fields' => 'names' ) );
		$is_library = get_post_meta( $image_id, '_seo_library_image', true );

		echo sprintf(
			"- Image #%d: %s | Tags: %s | Library: %s\n",
			$image_id,
			$title,
			! empty( $tags ) ? implode( ', ', $tags ) : 'NONE',
			$is_library ? 'YES' : 'NO'
		);
	}
}
echo '</pre>';

// 4. Check available image tags
echo '<h3>4. Available Image Tags</h3>';
$tags = get_terms( array(
	'taxonomy'   => 'image_tag',
	'hide_empty' => false,
) );

echo '<pre>';
echo "Total image tags: " . count( $tags ) . "\n";
if ( ! empty( $tags ) ) {
	echo "\nAll tags:\n";
	foreach ( $tags as $tag ) {
		echo sprintf( "- %s (slug: %s, count: %d)\n", $tag->name, $tag->slug, $tag->count );
	}
}
echo '</pre>';

// 5. Check recent generation logs
echo '<h3>5. Recent Generation Activity</h3>';
global $wpdb;
$table_name = $wpdb->prefix . 'seo_generation_log';

if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
	$logs = $wpdb->get_results(
		"SELECT * FROM $table_name WHERE block_type = 'hero' ORDER BY created_at DESC LIMIT 5",
		ARRAY_A
	);

	echo '<pre>';
	echo "Recent hero block generations: " . count( $logs ) . "\n";
	foreach ( $logs as $log ) {
		echo sprintf(
			"\n- Post #%d | Status: %s | Created: %s\n  Error: %s\n",
			$log['post_id'],
			$log['status'],
			$log['created_at'],
			$log['error_message'] ?: 'none'
		);
	}
	echo '</pre>';
} else {
	echo '<pre>Generation log table not found</pre>';
}

// 6. Test image matching with sample context
echo '<h3>6. Test Image Matching</h3>';
echo '<pre>';
echo "Testing image matching with sample contexts:\n\n";

$test_contexts = array(
	array(
		'focus_keyword' => 'platinum wedding band',
		'topic'         => 'Wedding Bands',
	),
	array(
		'focus_keyword' => 'mens tungsten ring',
		'topic'         => "Men's Wedding Bands",
	),
);

foreach ( $test_contexts as $i => $context ) {
	echo "Test " . ( $i + 1 ) . ": " . wp_json_encode( $context ) . "\n";

	try {
		$settings_service = new \SEOGenerator\Services\SettingsService();
		$openai_service = new \SEOGenerator\Services\OpenAIService( $settings_service );
		$image_matching = new \SEOGenerator\Services\ImageMatchingService( $openai_service );

		$image_id = $image_matching->findMatchingImage( $context );

		if ( $image_id ) {
			$title = get_the_title( $image_id );
			$tags = wp_get_object_terms( $image_id, 'image_tag', array( 'fields' => 'names' ) );
			echo "  MATCH FOUND: Image #$image_id ($title) | Tags: " . implode( ', ', $tags ) . "\n";
		} else {
			echo "  NO MATCH\n";
		}
	} catch ( Exception $e ) {
		echo "  ERROR: " . $e->getMessage() . "\n";
	}
	echo "\n";
}

echo '</pre>';

echo '</div>';
