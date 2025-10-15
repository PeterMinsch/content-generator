<?php
/**
 * Product Criteria Block Template
 *
 * Displays product selection criteria with explanations.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--product-criteria">
	<?php if ( ! empty( $criteria_heading ) ) : ?>
		<h2 class="product-criteria__heading"><?php echo esc_html( $criteria_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $criteria_items ) && is_array( $criteria_items ) ) : ?>
		<dl class="product-criteria__list">
			<?php foreach ( $criteria_items as $item ) : ?>
				<?php if ( ! empty( $item['name'] ) ) : ?>
					<dt class="product-criteria__name"><?php echo esc_html( $item['name'] ); ?></dt>
					<?php if ( ! empty( $item['explanation'] ) ) : ?>
						<dd class="product-criteria__explanation"><?php echo wp_kses_post( $item['explanation'] ); ?></dd>
					<?php endif; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
</section>
