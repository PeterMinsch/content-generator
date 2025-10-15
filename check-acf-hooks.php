<?php
/**
 * Check ACF Registration Hooks
 *
 * Add ?check_acf_hooks=1 to any admin URL to run this
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Run on admin_notices to show at top of page
add_action( 'admin_notices', function() {
	if ( ! isset( $_GET['check_acf_hooks'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Get all actions hooked to acf/init
	global $wp_filter;

	echo '<div class="notice notice-info" style="padding: 20px; font-family: monospace;">';
	echo '<h2>ACF Registration Hooks Debug</h2>';

	// Check acf/init hook
	echo '<h3>Functions hooked to acf/init:</h3>';
	if ( isset( $wp_filter['acf/init'] ) && isset( $wp_filter['acf/init']->callbacks ) ) {
		echo '<pre>';
		foreach ( $wp_filter['acf/init']->callbacks as $priority => $callbacks ) {
			echo "Priority: $priority\n";
			foreach ( $callbacks as $callback ) {
				$function_name = 'Unknown';
				if ( is_array( $callback['function'] ) ) {
					if ( is_object( $callback['function'][0] ) ) {
						$function_name = get_class( $callback['function'][0] ) . '::' . $callback['function'][1];
					} else {
						$function_name = $callback['function'][0] . '::' . $callback['function'][1];
					}
				} elseif ( is_string( $callback['function'] ) ) {
					$function_name = $callback['function'];
				}
				echo "  - $function_name\n";
			}
		}
		echo '</pre>';
	} else {
		echo '<p>No callbacks found.</p>';
	}

	// Get all field groups currently registered
	echo '<h3>All Registered Field Groups (from ACF):</h3>';
	if ( function_exists( 'acf_get_field_groups' ) ) {
		$groups = acf_get_field_groups();
		echo '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
		echo '<tr><th>Key</th><th>Title</th><th>Style</th><th>Position</th><th>Hide on Screen</th></tr>';
		foreach ( $groups as $group ) {
			echo '<tr>';
			echo '<td>' . esc_html( $group['key'] ) . '</td>';
			echo '<td><strong>' . esc_html( $group['title'] ) . '</strong></td>';
			echo '<td>' . esc_html( $group['style'] ?? 'default' ) . '</td>';
			echo '<td>' . esc_html( $group['position'] ?? 'normal' ) . '</td>';
			echo '<td>' . ( isset( $group['hide_on_screen'] ) ? esc_html( implode( ', ', $group['hide_on_screen'] ) ) : 'none' ) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>ACF not available.</p>';
	}

	// Check active plugins
	echo '<h3>Active Plugins:</h3>';
	$active_plugins = get_option( 'active_plugins' );
	echo '<ul>';
	foreach ( $active_plugins as $plugin ) {
		echo '<li>' . esc_html( $plugin ) . '</li>';
	}
	echo '</ul>';

	echo '</div>';
} );
