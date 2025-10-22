<?php
/**
 * Hero Block Template
 *
 * Displays the hero section with title, subtitle, summary, and optional image.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--hero">
	<header class="hero__header">
		<?php if ( ! empty( $hero_title ) ) : ?>
			<h1 class="hero__title"><?php echo esc_html( $hero_title ); ?></h1>
		<?php endif; ?>

		<?php if ( ! empty( $hero_subtitle ) ) : ?>
			<h2 class="hero__subtitle"><?php echo esc_html( $hero_subtitle ); ?></h2>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $hero_summary ) ) : ?>
		<div class="hero__summary">
			<p><?php echo esc_html( $hero_summary ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $hero_image ) ) : ?>
		<figure class="hero__image">
			<?php
			echo wp_get_attachment_image(
				$hero_image,
				'large',
				false,
				array(
					'class'   => 'hero__img',
					'loading' => 'eager', // Hero images should load immediately.
				)
			);
			?>
		</figure>
	<?php endif; ?>
</section>
