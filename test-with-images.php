<?php
/**
 * Test Complete Workflow with Background Images
 *
 * Tests the new two-stage approach:
 * 1. Generate page quickly (without images)
 * 2. Trigger background job to add images
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	die( 'No permission' );
}

$post_id = 2124;

?>
<!DOCTYPE html>
<html>
<head>
	<title>Test with Images</title>
	<style>
		body { font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; }
		.step { margin: 10px 0; }
		.success { color: #10b981; }
		.error { color: #ef4444; }
		.info { color: #3b82f6; }
	</style>
</head>
<body>
	<h1>Testing Complete Workflow</h1>

	<?php
	try {
		// Step 1: Clean queue
		echo '<div class="step info">[1/5] Cleaning queue...</div>';
		flush();

		$queue = get_option( 'seo_generation_queue', array() );
		$new_queue = array();
		foreach ( $queue as $item ) {
			if ( $item['post_id'] !== $post_id ) {
				$new_queue[] = $item;
			}
		}
		update_option( 'seo_generation_queue', $new_queue );
		delete_post_meta( $post_id, '_queue_status' );
		echo '<div class="step success">✓ Queue cleaned</div>';
		flush();

		// Step 2: Generate page content (fast, no images)
		echo '<div class="step info">[2/5] Generating page content...</div>';
		flush();

		$service = new \SEOGenerator\Services\GenerationService();
		$service->processQueuedPage( $post_id );
		echo '<div class="step success">✓ Page generated (without images)</div>';
		flush();

		// Step 3: Check if image job was scheduled
		echo '<div class="step info">[3/5] Checking if image generation was scheduled...</div>';
		flush();

		$next_image_job = wp_next_scheduled( 'seo_generate_related_link_images', array( $post_id ) );
		if ( $next_image_job ) {
			$time_until = $next_image_job - time();
			echo '<div class="step success">✓ Image generation scheduled in ' . $time_until . ' seconds</div>';
		} else {
			echo '<div class="step error">✗ No image generation job scheduled</div>';
		}
		flush();

		// Step 4: Manually trigger image generation NOW (don't wait)
		echo '<div class="step info">[4/5] Triggering image generation NOW...</div>';
		echo '<div class="step info">This may take 2-5 minutes (generating 4 images with AI)...</div>';
		flush();

		$image_service = new \SEOGenerator\Services\RelatedLinksImageService();
		$result = $image_service->generateImagesForPost( $post_id );

		if ( $result['success'] ) {
			echo '<div class="step success">✓ Images generated: ' . $result['generated'] . ' successful, ' . $result['failed'] . ' failed</div>';
			echo '<div class="step info">Duration: ' . $result['duration'] . ' seconds</div>';

			if ( ! empty( $result['errors'] ) ) {
				echo '<div class="step error">Errors:</div>';
				foreach ( $result['errors'] as $error ) {
					echo '<div class="step error">  - ' . esc_html( $error ) . '</div>';
				}
			}
		} else {
			echo '<div class="step error">✗ Image generation failed: ' . esc_html( $result['error'] ?? 'Unknown error' ) . '</div>';
		}
		flush();

		// Step 5: Verify results
		echo '<div class="step info">[5/5] Verifying results...</div>';
		flush();

		// Get links from ACF field
		$links = get_field( 'links', $post_id );

		if ( empty( $links ) ) {
			echo '<div class="step error">No links found!</div>';
		} elseif ( is_array( $links ) ) {
			$with_images = 0;
			$without_images = 0;

			foreach ( $links as $link ) {
				if ( ! empty( $link['link_image'] ) ) {
					$with_images++;
					echo '<div class="step success">  ✓ ' . esc_html( $link['link_title'] ) . ' (has image ID: ' . $link['link_image'] . ')</div>';
				} else {
					$without_images++;
					echo '<div class="step error">  ✗ ' . esc_html( $link['link_title'] ) . ' (no image)</div>';
				}
			}

			echo '<div class="step info">Summary: ' . $with_images . ' with images, ' . $without_images . ' without</div>';
		} else {
			echo '<div class="step error">Links data is not in expected format</div>';
		}

		echo '<div class="step success">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
		echo '<div class="step success">✅ TEST COMPLETE!</div>';
		echo '<div class="step success">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';

	} catch ( Exception $e ) {
		echo '<div class="step error">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
		echo '<div class="step error">❌ ERROR: ' . esc_html( $e->getMessage() ) . '</div>';
		echo '<div class="step error">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>';
	}
	?>

	<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #475569;">
		<a href="<?php echo get_permalink( $post_id ); ?>" target="_blank" style="color: #10b981;">→ View Generated Page</a> |
		<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" style="color: #10b981;">→ Back to SEO Pages</a>
	</div>
</body>
</html>
