<?php
/**
 * Test Script: Create Duplicate Posts for Testing Duplicate Detection
 *
 * Run this script once to create test posts, then test the duplicate detection.
 * After testing, you can delete these posts from the WordPress admin.
 */

// Load WordPress
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Check if user is logged in and is admin
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	die( 'You must be logged in as an administrator to run this script.' );
}

echo '<h1>Creating Test Posts for Duplicate Detection</h1>';
echo '<p>This will create 5 test posts that match geographic title patterns...</p>';

// Test posts to create (these will match generated titles)
$test_posts = array(
	array(
		'title' => 'Diamond Rings In Carlsbad',
		'slug'  => 'diamond-rings-in-carlsbad',
	),
	array(
		'title' => 'Diamond Rings Near La Jolla',
		'slug'  => 'diamond-rings-near-la-jolla',
	),
	array(
		'title' => 'Wedding Bands In San Diego',
		'slug'  => 'wedding-bands-in-san-diego',
	),
	array(
		'title' => 'Engagement Rings Within Encinitas',
		'slug'  => 'engagement-rings-within-encinitas',
	),
	array(
		'title' => 'Gold Rings In Del Mar',
		'slug'  => 'gold-rings-in-del-mar',
	),
);

$created_count = 0;
$skipped_count = 0;

echo '<ul>';

foreach ( $test_posts as $test_post ) {
	// Check if post already exists
	$existing = get_page_by_path( $test_post['slug'], OBJECT, 'seo-page' );

	if ( $existing ) {
		echo '<li>❌ Skipped: <strong>' . esc_html( $test_post['title'] ) . '</strong> (already exists)</li>';
		$skipped_count++;
		continue;
	}

	// Create the post
	$post_id = wp_insert_post(
		array(
			'post_title'   => $test_post['title'],
			'post_name'    => $test_post['slug'],
			'post_type'    => 'seo-page',
			'post_status'  => 'draft', // Create as draft so they're easy to identify
			'post_content' => '<!-- wp:paragraph --><p>This is a test post for duplicate detection. You can safely delete this.</p><!-- /wp:paragraph -->',
		)
	);

	if ( is_wp_error( $post_id ) ) {
		echo '<li>❌ Error creating: <strong>' . esc_html( $test_post['title'] ) . '</strong> - ' . esc_html( $post_id->get_error_message() ) . '</li>';
	} else {
		echo '<li>✅ Created: <strong>' . esc_html( $test_post['title'] ) . '</strong> (ID: ' . $post_id . ', Status: draft)</li>';
		$created_count++;
	}
}

echo '</ul>';

echo '<hr>';
echo '<h2>Summary</h2>';
echo '<p>✅ Created: <strong>' . $created_count . '</strong> test posts</p>';
echo '<p>⏭️ Skipped: <strong>' . $skipped_count . '</strong> (already exist)</p>';

echo '<hr>';
echo '<h2>Next Steps:</h2>';
echo '<ol>';
echo '<li>Go to the <strong>Geographic Title Generator</strong> page</li>';
echo '<li>Upload a CSV with keywords like: <code>Diamond Rings</code>, <code>Wedding Bands</code>, <code>Engagement Rings</code>, <code>Gold Rings</code></li>';
echo '<li>Click <strong>Generate Title Variations</strong></li>';
echo '<li>You should see <strong>⚠️ Warning: X titles already exist</strong></li>';
echo '<li>The duplicate titles should be <strong>highlighted in yellow</strong> with a red <strong>⚠️ EXISTS</strong> badge</li>';
echo '<li>A checkbox should appear: <strong>"Hide duplicate titles (X)"</strong></li>';
echo '</ol>';

echo '<hr>';
echo '<h2>Cleanup:</h2>';
echo '<p>After testing, go to <strong>SEO Pages → All SEO Pages</strong> and delete the test posts (they are in Draft status).</p>';
echo '<p><a href="' . admin_url( 'edit.php?post_type=seo-page&post_status=draft' ) . '" class="button button-primary">View Draft SEO Pages</a></p>';
