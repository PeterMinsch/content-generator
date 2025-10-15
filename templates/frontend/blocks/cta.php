<?php
/**
 * Call to Action Block Template
 *
 * Displays call to action with heading, text, and action buttons.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--cta">
	<?php if ( ! empty( $cta_heading ) ) : ?>
		<h2 class="cta__heading"><?php echo esc_html( $cta_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $cta_text ) ) : ?>
		<div class="cta__text">
			<p><?php echo wp_kses_post( $cta_text ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $cta_primary_label ) || ! empty( $cta_secondary_label ) ) : ?>
		<div class="cta__buttons">
			<?php if ( ! empty( $cta_primary_label ) && ! empty( $cta_primary_url ) ) : ?>
				<a href="<?php echo esc_url( $cta_primary_url ); ?>" class="button button--primary cta__button cta__button--primary">
					<?php echo esc_html( $cta_primary_label ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $cta_secondary_label ) && ! empty( $cta_secondary_url ) ) : ?>
				<a href="<?php echo esc_url( $cta_secondary_url ); ?>" class="button button--secondary cta__button cta__button--secondary">
					<?php echo esc_html( $cta_secondary_label ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
