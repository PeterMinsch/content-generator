<?php
/**
 * SERP Answer Block Template
 *
 * Displays a featured answer optimized for search engine result pages.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--serp-answer">
	<?php if ( ! empty( $answer_heading ) ) : ?>
		<h2 class="serp-answer__heading"><?php echo esc_html( $answer_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $answer_paragraph ) ) : ?>
		<div class="serp-answer__paragraph">
			<p><?php echo wp_kses_post( $answer_paragraph ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $answer_bullets ) && is_array( $answer_bullets ) ) : ?>
		<ul class="serp-answer__bullets">
			<?php foreach ( $answer_bullets as $bullet ) : ?>
				<?php if ( ! empty( $bullet['bullet_text'] ) ) : ?>
					<li><?php echo esc_html( $bullet['bullet_text'] ); ?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
