<?php
/**
 * Product Showcase Block Template
 *
 * Displays a grid of featured products.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--product-showcase">
	<?php if ( ! empty( $showcase_heading ) ) : ?>
		<h2 class="product-showcase__heading"><?php echo esc_html( $showcase_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $showcase_intro ) ) : ?>
		<div class="product-showcase__intro">
			<p><?php echo wp_kses_post( $showcase_intro ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $showcase_products ) && is_array( $showcase_products ) ) : ?>
		<div class="product-grid">
			<?php foreach ( $showcase_products as $product ) : ?>
				<?php if ( ! empty( $product['product_sku'] ) || ! empty( $product['alt_image_url'] ) ) : ?>
					<figure class="product-grid__item">
						<?php if ( ! empty( $product['alt_image_url'] ) ) : ?>
							<img
								src="<?php echo esc_url( $product['alt_image_url'] ); ?>"
								alt="<?php echo ! empty( $product['product_sku'] ) ? esc_attr( $product['product_sku'] ) : ''; ?>"
								class="product-grid__image"
								loading="lazy"
							/>
						<?php endif; ?>

						<?php if ( ! empty( $product['product_sku'] ) ) : ?>
							<figcaption class="product-grid__caption"><?php echo esc_html( $product['product_sku'] ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
