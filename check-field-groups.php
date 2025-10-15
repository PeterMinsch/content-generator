<?php
/**
 * Check for ACF Field Groups
 *
 * Add ?check_field_groups=1 to any admin URL to run this
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Run on admin_init to ensure all functions are loaded
add_action( 'admin_init', function() {
	if ( ! isset( $_GET['check_field_groups'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_acf_field_groups_display();
} );

function check_acf_field_groups_display() {

echo '<div style="background: #fff; border: 2px solid #000; padding: 20px; margin: 20px; font-family: monospace;">';
echo '<h2>ACF Field Groups Check</h2>';

// Check for field groups in database
global $wpdb;
$field_groups = $wpdb->get_results(
	"SELECT ID, post_title, post_name, post_status
	FROM {$wpdb->posts}
	WHERE post_type = 'acf-field-group'
	ORDER BY post_title ASC"
);

echo '<h3>Field Groups in Database:</h3>';
if ( empty( $field_groups ) ) {
	echo '<p><strong>No field groups found in database.</strong></p>';
	echo '<p>All field groups are registered in code (good!).</p>';
} else {
	echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
	echo '<tr><th>ID</th><th>Title</th><th>Key</th><th>Status</th><th>Action</th></tr>';
	foreach ( $field_groups as $group ) {
		echo '<tr>';
		echo '<td>' . esc_html( $group->ID ) . '</td>';
		echo '<td><strong>' . esc_html( $group->post_title ) . '</strong></td>';
		echo '<td>' . esc_html( $group->post_name ) . '</td>';
		echo '<td>' . esc_html( $group->post_status ) . '</td>';
		echo '<td>';
		if ( $group->post_status !== 'trash' ) {
			echo '<a href="' . admin_url( 'post.php?post=' . $group->ID . '&action=edit' ) . '" target="_blank">Edit</a> | ';
			echo '<a href="' . admin_url( 'post.php?post=' . $group->ID . '&action=trash' ) . '" style="color: red;">Trash</a>';
		} else {
			echo 'In Trash';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
}

echo '<hr>';

// Check for field groups registered in code
echo '<h3>Field Groups Registered in Code:</h3>';

// Try to get ACF field groups
if ( function_exists( 'acf_get_field_groups' ) ) {
	$all_groups = acf_get_field_groups();

	if ( empty( $all_groups ) ) {
		echo '<p>No field groups registered.</p>';
	} else {
		echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
		echo '<tr><th>Key</th><th>Title</th><th>Location</th></tr>';
		foreach ( $all_groups as $group ) {
			echo '<tr>';
			echo '<td>' . esc_html( $group['key'] ) . '</td>';
			echo '<td><strong>' . esc_html( $group['title'] ) . '</strong></td>';

			// Show location rules
			$locations = '';
			if ( isset( $group['location'] ) && is_array( $group['location'] ) ) {
				foreach ( $group['location'] as $location_group ) {
					foreach ( $location_group as $rule ) {
						$locations .= sprintf(
							'%s %s %s<br>',
							esc_html( $rule['param'] ),
							esc_html( $rule['operator'] ),
							esc_html( $rule['value'] )
						);
					}
				}
			}
			echo '<td>' . $locations . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}
} else {
	echo '<p>ACF functions not available.</p>';
}

echo '</div>';

	// Stop WordPress from continuing - we just want to show this
	exit;
}
