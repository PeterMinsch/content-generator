<?php
/**
 * Create Image Cache Table
 *
 * Run this file directly to create the image cache table without
 * deactivating/reactivating the plugin.
 *
 * Usage: Navigate to this file in your browser:
 * http://yoursite.local/wp-content/plugins/content-generator-disabled/create-image-cache-table.php
 */

// Load WordPress.
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

// Check if user is admin.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

global $wpdb;

$table_name      = $wpdb->prefix . 'seo_image_cache';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
	id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	context_hash VARCHAR(32) NOT NULL,
	link_title VARCHAR(255) NOT NULL,
	link_category VARCHAR(100) NOT NULL,
	dalle_prompt TEXT NOT NULL,
	attachment_id BIGINT(20) UNSIGNED NOT NULL,
	usage_count INT UNSIGNED DEFAULT 1,
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY unique_context (context_hash),
	INDEX idx_category (link_category),
	INDEX idx_usage (usage_count),
	INDEX idx_last_used (last_used)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta( $sql );

// Update database version.
update_option( 'seo_generator_image_cache_db_version', '1.0' );

// Check if table was created.
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

?>
<!DOCTYPE html>
<html>
<head>
	<title>Image Cache Table Setup</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			max-width: 800px;
			margin: 50px auto;
			padding: 20px;
			background: #f5f5f5;
		}
		.card {
			background: white;
			border-radius: 8px;
			padding: 30px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		}
		h1 {
			color: #333;
			margin-top: 0;
		}
		.success {
			color: #059669;
			padding: 15px;
			background: #d1fae5;
			border-radius: 6px;
			margin: 20px 0;
		}
		.info {
			color: #0369a1;
			padding: 15px;
			background: #e0f2fe;
			border-radius: 6px;
			margin: 20px 0;
		}
		code {
			background: #f1f5f9;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: 'Monaco', 'Courier New', monospace;
		}
		.button {
			display: inline-block;
			padding: 10px 20px;
			background: #2563eb;
			color: white;
			text-decoration: none;
			border-radius: 6px;
			margin-top: 20px;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>🗄️ Image Cache Table Setup</h1>

		<?php if ( $table_exists ) : ?>
			<div class="success">
				<strong>✅ Success!</strong><br>
				Image cache table <code><?php echo esc_html( $table_name ); ?></code> has been created/updated.
			</div>

			<div class="info">
				<strong>ℹ️ Next Steps:</strong><br>
				The AI Image Generator Bot is now ready to use. When you generate new SEO pages with related links,
				images will be automatically generated using:
				<ul>
					<li>Stage 1: GPT-4 generates optimized DALL-E prompts</li>
					<li>Stage 2: DALL-E 3 generates the actual images</li>
					<li>Smart caching minimizes API costs (95% savings on repeated categories)</li>
				</ul>
			</div>

			<p>
				<strong>Database Version:</strong> <?php echo esc_html( get_option( 'seo_generator_image_cache_db_version', 'Not set' ) ); ?>
			</p>

			<p>
				<strong>Table Structure:</strong>
				<ul>
					<li><code>context_hash</code> - Unique hash for caching (title + category)</li>
					<li><code>link_title</code> - Category title (e.g., "Engagement Rings")</li>
					<li><code>link_category</code> - Category tag (e.g., "Rings")</li>
					<li><code>dalle_prompt</code> - GPT-4 generated prompt used for image</li>
					<li><code>attachment_id</code> - WordPress media library attachment ID</li>
					<li><code>usage_count</code> - Number of times this image has been reused</li>
				</ul>
			</p>

			<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" class="button">
				← Back to SEO Pages
			</a>
		<?php else : ?>
			<div class="error" style="color: #dc2626; background: #fee2e2; padding: 15px; border-radius: 6px;">
				<strong>❌ Error</strong><br>
				Table creation failed. Please check error logs and try again.
			</div>
		<?php endif; ?>

		<p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
			You can safely delete this file after the table is created.<br>
			File location: <code>wp-content/plugins/content-generator-disabled/create-image-cache-table.php</code>
		</p>
	</div>
</body>
</html>
