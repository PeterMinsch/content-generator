<?php
/**
 * Test Generation with Full Error Display
 */

// Enable ALL error reporting
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Insufficient permissions.' );
}

header('Content-Type: text/plain');

echo "=== TESTING GENERATION WITH VERBOSE ERRORS ===\n\n";

// Get first pending post
$queue = get_option( 'seo_generation_queue', array() );
$test_post_id = null;

foreach ( $queue as $item ) {
	if ( $item['status'] === 'pending' ) {
		$test_post_id = $item['post_id'];
		break;
	}
}

if ( ! $test_post_id ) {
	echo "No pending posts found in queue.\n";
	exit;
}

echo "Testing generation for post ID: $test_post_id\n\n";

try {
	echo "1. Creating GenerationService...\n";
	$service = new \SEOGenerator\Services\GenerationService();
	echo "   ✅ Service created\n\n";

	echo "2. Calling processQueuedPage($test_post_id)...\n";
	$service->processQueuedPage( $test_post_id );
	echo "   ✅ Process completed!\n\n";

	echo "SUCCESS! Check the post to see if content was generated.\n";

} catch ( \Throwable $e ) {
	echo "\n❌ ERROR CAUGHT:\n";
	echo "Message: " . $e->getMessage() . "\n";
	echo "File: " . $e->getFile() . "\n";
	echo "Line: " . $e->getLine() . "\n\n";
	echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END ===\n";
