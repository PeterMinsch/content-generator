<?php
/**
 * Care & Warranty Block Template
 *
 * Displays care instructions and warranty information.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--care-warranty">
	<?php if ( ! empty( $care_heading ) ) : ?>
		<h2 class="care-warranty__care-heading"><?php echo esc_html( $care_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $care_bullets ) && is_array( $care_bullets ) ) : ?>
		<ul class="care-warranty__care-list">
			<?php foreach ( $care_bullets as $bullet ) : ?>
				<?php if ( ! empty( $bullet['care_tip'] ) ) : ?>
					<li><?php echo esc_html( $bullet['care_tip'] ); ?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( ! empty( $warranty_heading ) || ! empty( $warranty_text ) ) : ?>
		<div class="care-warranty__warranty">
			<?php if ( ! empty( $warranty_heading ) ) : ?>
				<h3 class="care-warranty__warranty-heading"><?php echo esc_html( $warranty_heading ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $warranty_text ) ) : ?>
				<p class="care-warranty__warranty-text"><?php echo wp_kses_post( $warranty_text ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
