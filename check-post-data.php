<?php
/**
 * Check Post Data
 */

require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	die( 'No permission' );
}

$post_id = 2124;

$blocks_generated = get_post_meta( $post_id, '_blocks_generated', true );
$blocks_failed = get_post_meta( $post_id, '_blocks_failed', true );

// Get all post meta
$all_meta = get_post_meta( $post_id );

// Check specifically for related_links
$related_links = get_post_meta( $post_id, 'related_links', true );
$related_links_raw = get_post_meta( $post_id, 'related_links', false );

?>
<!DOCTYPE html>
<html>
<head>
	<title>Post Data</title>
	<style>
		body { font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; font-size: 13px; }
		pre { background: #0f172a; padding: 10px; border-radius: 4px; overflow-x: auto; }
		.section { margin: 20px 0; border: 1px solid #475569; padding: 15px; border-radius: 6px; }
		h2 { color: #10b981; }
	</style>
</head>
<body>
	<h1>Post #<?php echo $post_id; ?> Data</h1>

	<div class="section">
		<h2>Generation Stats</h2>
		<p>Blocks Generated: <?php echo $blocks_generated ?: 'none'; ?></p>
		<p>Blocks Failed: <?php echo $blocks_failed ?: 'none'; ?></p>
	</div>

	<div class="section">
		<h2>Related Links (get_post_meta single)</h2>
		<pre><?php var_dump( $related_links ); ?></pre>
	</div>

	<div class="section">
		<h2>Related Links (get_post_meta array)</h2>
		<pre><?php var_dump( $related_links_raw ); ?></pre>
	</div>

	<div class="section">
		<h2>All Post Meta Keys</h2>
		<pre><?php
		$keys = array_keys( $all_meta );
		sort( $keys );
		foreach ( $keys as $key ) {
			echo esc_html( $key ) . "\n";
		}
		?></pre>
	</div>

	<div class="section">
		<h2>Fields That Match 'link'</h2>
		<pre><?php
		foreach ( $all_meta as $key => $value ) {
			if ( stripos( $key, 'link' ) !== false ) {
				echo esc_html( $key ) . ": ";
				var_dump( $value );
				echo "\n";
			}
		}
		?></pre>
	</div>
</body>
</html>
