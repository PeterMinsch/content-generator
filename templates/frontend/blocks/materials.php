<?php
/**
 * Materials Block Template
 *
 * Displays information about different materials with pros, cons, and care instructions.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--materials">
	<?php if ( ! empty( $materials_heading ) ) : ?>
		<h2 class="materials__heading"><?php echo esc_html( $materials_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $materials_items ) && is_array( $materials_items ) ) : ?>
		<div class="materials__list">
			<?php foreach ( $materials_items as $material ) : ?>
				<?php if ( ! empty( $material['material'] ) ) : ?>
					<article class="materials__item">
						<h3 class="materials__name"><?php echo esc_html( $material['material'] ); ?></h3>

						<?php if ( ! empty( $material['pros'] ) ) : ?>
							<div class="materials__pros">
								<h4><?php esc_html_e( 'Pros:', 'seo-generator' ); ?></h4>
								<p><?php echo wp_kses_post( $material['pros'] ); ?></p>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $material['cons'] ) ) : ?>
							<div class="materials__cons">
								<h4><?php esc_html_e( 'Cons:', 'seo-generator' ); ?></h4>
								<p><?php echo wp_kses_post( $material['cons'] ); ?></p>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $material['best_for'] ) ) : ?>
							<p class="materials__best-for">
								<strong><?php esc_html_e( 'Best for:', 'seo-generator' ); ?></strong>
								<?php echo esc_html( $material['best_for'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( ! empty( $material['allergy_notes'] ) ) : ?>
							<p class="materials__allergy-notes">
								<strong><?php esc_html_e( 'Allergy notes:', 'seo-generator' ); ?></strong>
								<?php echo esc_html( $material['allergy_notes'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( ! empty( $material['care'] ) ) : ?>
							<div class="materials__care">
								<h4><?php esc_html_e( 'Care:', 'seo-generator' ); ?></h4>
								<p><?php echo wp_kses_post( $material['care'] ); ?></p>
							</div>
						<?php endif; ?>
					</article>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
