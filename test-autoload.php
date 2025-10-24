<?php
/**
 * Test Autoload
 *
 * Tests if the new image generation services can be loaded.
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

$errors = array();
$warnings = array();

// Test 1: Check if files exist
$files = array(
	'PromptGeneratorService' => plugin_dir_path( __FILE__ ) . 'includes/Services/PromptGeneratorService.php',
	'DalleService' => plugin_dir_path( __FILE__ ) . 'includes/Services/DalleService.php',
	'ImageGeneratorService' => plugin_dir_path( __FILE__ ) . 'includes/Services/ImageGeneratorService.php',
);

foreach ( $files as $name => $path ) {
	if ( ! file_exists( $path ) ) {
		$errors[] = "$name file does not exist at: $path";
	}
}

// Test 2: Try to load classes
$classes = array(
	'PromptGeneratorService' => 'SEOGenerator\\Services\\PromptGeneratorService',
	'DalleService' => 'SEOGenerator\\Services\\DalleService',
	'ImageGeneratorService' => 'SEOGenerator\\Services\\ImageGeneratorService',
);

foreach ( $classes as $name => $class ) {
	if ( ! class_exists( $class ) ) {
		$errors[] = "$name class cannot be loaded: $class";
	}
}

// Test 3: Try to instantiate (without requiring API key)
if ( empty( $errors ) ) {
	try {
		// Create a mock settings service without API key requirement
		$settings = new \SEOGenerator\Services\SettingsService();

		$prompt_gen = new \SEOGenerator\Services\PromptGeneratorService( $settings );
		$dalle = new \SEOGenerator\Services\DalleService( $settings );
		$image_gen = new \SEOGenerator\Services\ImageGeneratorService();

	} catch ( Exception $e ) {
		$warnings[] = "Could not instantiate services: " . $e->getMessage();
	}
}

// Test 4: Check if database table exists
global $wpdb;
$table_name = $wpdb->prefix . 'seo_image_cache';
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
if ( ! $table_exists ) {
	$errors[] = "Database table '$table_name' does not exist. Run create-image-cache-table.php first!";
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Autoload Test</title>
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
			background: #d1fae5;
			border: 1px solid #a7f3d0;
			color: #059669;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.error {
			background: #fee2e2;
			border: 1px solid #fecaca;
			color: #dc2626;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		.warning {
			background: #fef3c7;
			border: 1px solid #fcd34d;
			color: #92400e;
			padding: 15px;
			border-radius: 6px;
			margin: 20px 0;
		}
		ul {
			margin: 10px 0;
			padding-left: 20px;
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
			margin-top: 10px;
			margin-right: 10px;
		}
	</style>
</head>
<body>
	<div class="card">
		<h1>🧪 Autoload Test Results</h1>

		<?php if ( empty( $errors ) ) : ?>
			<div class="success">
				<strong>✅ All Tests Passed!</strong><br>
				All image generation services are properly loaded and ready to use.
			</div>
		<?php else : ?>
			<div class="error">
				<strong>❌ Errors Found:</strong>
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $warnings ) ) : ?>
			<div class="warning">
				<strong>⚠️ Warnings:</strong>
				<ul>
					<?php foreach ( $warnings as $warning ) : ?>
						<li><?php echo esc_html( $warning ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<h2>Test Details</h2>
		<table style="width: 100%; border-collapse: collapse;">
			<tr style="border-bottom: 1px solid #e5e7eb;">
				<th style="text-align: left; padding: 10px; background: #f9fafb;">Test</th>
				<th style="text-align: left; padding: 10px; background: #f9fafb;">Status</th>
			</tr>
			<tr style="border-bottom: 1px solid #e5e7eb;">
				<td style="padding: 10px;">Files exist</td>
				<td style="padding: 10px;">
					<?php
					$file_count = count( $files );
					$file_errors = 0;
					foreach ( $files as $name => $path ) {
						if ( ! file_exists( $path ) ) {
							$file_errors++;
						}
					}
					if ( $file_errors === 0 ) {
						echo '✅ All ' . $file_count . ' files found';
					} else {
						echo '❌ ' . $file_errors . ' of ' . $file_count . ' files missing';
					}
					?>
				</td>
			</tr>
			<tr style="border-bottom: 1px solid #e5e7eb;">
				<td style="padding: 10px;">Classes autoload</td>
				<td style="padding: 10px;">
					<?php
					$class_count = count( $classes );
					$class_errors = 0;
					foreach ( $classes as $name => $class ) {
						if ( ! class_exists( $class ) ) {
							$class_errors++;
						}
					}
					if ( $class_errors === 0 ) {
						echo '✅ All ' . $class_count . ' classes loaded';
					} else {
						echo '❌ ' . $class_errors . ' of ' . $class_count . ' classes not loaded';
					}
					?>
				</td>
			</tr>
			<tr style="border-bottom: 1px solid #e5e7eb;">
				<td style="padding: 10px;">Services instantiate</td>
				<td style="padding: 10px;">
					<?php
					if ( empty( $warnings ) && empty( $errors ) ) {
						echo '✅ All services can be instantiated';
					} elseif ( ! empty( $warnings ) ) {
						echo '⚠️ Some issues (check warnings)';
					} else {
						echo '❌ Cannot instantiate';
					}
					?>
				</td>
			</tr>
			<tr style="border-bottom: 1px solid #e5e7eb;">
				<td style="padding: 10px;">Database table exists</td>
				<td style="padding: 10px;">
					<?php
					if ( $table_exists ) {
						echo '✅ Table exists: ' . $table_name;
					} else {
						echo '❌ Table missing: ' . $table_name;
					}
					?>
				</td>
			</tr>
		</table>

		<?php if ( ! empty( $errors ) ) : ?>
			<h2>How to Fix</h2>
			<div class="warning">
				<strong>If classes cannot be loaded:</strong>
				<ol>
					<li>The autoloader cache may need regeneration</li>
					<li>Files should be in: <code>includes/Services/</code></li>
					<li>Classes should use namespace: <code>SEOGenerator\Services</code></li>
				</ol>

				<strong>If database table is missing:</strong>
				<ol>
					<li>Run: <a href="create-image-cache-table.php" class="button">Create Image Cache Table</a></li>
				</ol>
			</div>
		<?php endif; ?>

		<div style="margin-top: 30px;">
			<a href="trigger-generation-now.php" class="button">Try Generation Again</a>
			<a href="<?php echo admin_url( 'edit.php?post_type=seo-page' ); ?>" class="button">← Back to SEO Pages</a>
		</div>
	</div>
</body>
</html>
