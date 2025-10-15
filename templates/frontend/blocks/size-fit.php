<?php
/**
 * Size & Fit Block Template
 *
 * Displays sizing information, chart, and comfort notes.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--size-fit">
	<?php if ( ! empty( $size_heading ) ) : ?>
		<h2 class="size-fit__heading"><?php echo esc_html( $size_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $size_chart_image ) ) : ?>
		<figure class="size-fit__chart">
			<?php
			echo wp_get_attachment_image(
				$size_chart_image,
				'large',
				false,
				array(
					'class'   => 'size-fit__chart-img',
					'loading' => 'lazy',
				)
			);
			?>
		</figure>
	<?php endif; ?>

	<?php if ( ! empty( $comfort_fit_notes ) ) : ?>
		<div class="size-fit__notes">
			<p><?php echo wp_kses_post( $comfort_fit_notes ); ?></p>
		</div>
	<?php endif; ?>
</section>
