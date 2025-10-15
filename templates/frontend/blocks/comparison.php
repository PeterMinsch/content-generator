<?php
/**
 * Comparison Block Template
 *
 * Displays a comparison table between two options.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--comparison">
	<?php if ( ! empty( $comparison_heading ) ) : ?>
		<h2 class="comparison__heading"><?php echo esc_html( $comparison_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $comparison_summary ) ) : ?>
		<div class="comparison__summary">
			<p><?php echo wp_kses_post( $comparison_summary ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $comparison_rows ) && is_array( $comparison_rows ) ) : ?>
		<div class="table-responsive">
			<table class="comparison__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Feature', 'seo-generator' ); ?></th>
						<th>
							<?php
							echo ! empty( $comparison_left_label ) ?
								esc_html( $comparison_left_label ) :
								esc_html__( 'Option 1', 'seo-generator' );
							?>
						</th>
						<th>
							<?php
							echo ! empty( $comparison_right_label ) ?
								esc_html( $comparison_right_label ) :
								esc_html__( 'Option 2', 'seo-generator' );
							?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $comparison_rows as $row ) : ?>
						<?php if ( ! empty( $row['attribute'] ) ) : ?>
							<tr>
								<td class="comparison__attribute"><?php echo esc_html( $row['attribute'] ); ?></td>
								<td class="comparison__left"><?php echo ! empty( $row['left_text'] ) ? esc_html( $row['left_text'] ) : '—'; ?></td>
								<td class="comparison__right"><?php echo ! empty( $row['right_text'] ) ? esc_html( $row['right_text'] ) : '—'; ?></td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>
