<?php
/**
 * Quick Generate - No Frills, Just Results
 */
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	die( 'No permission' );
}

$post_id = 2124;

echo "Starting...\n";

// Clean slate
$queue = get_option( 'seo_generation_queue', array() );
$new_queue = array();
foreach ( $queue as $item ) {
	if ( $item['post_id'] !== $post_id ) {
		$new_queue[] = $item;
	}
}
update_option( 'seo_generation_queue', $new_queue );
delete_post_meta( $post_id, '_queue_status' );

echo "Queue cleaned...\n";

// Generate NOW
try {
	$service = new \SEOGenerator\Services\GenerationService();
	$service->processQueuedPage( $post_id );
	echo "SUCCESS! Check your SEO pages list.\n";
} catch ( Exception $e ) {
	echo "ERROR: " . $e->getMessage() . "\n";
}
?>
