<?php
/**
 * FAQs Block Template
 *
 * Displays frequently asked questions and answers.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--faqs">
	<?php if ( ! empty( $faqs_heading ) ) : ?>
		<h2 class="faqs__heading"><?php echo esc_html( $faqs_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $faq_items ) && is_array( $faq_items ) ) : ?>
		<dl class="faqs__list">
			<?php foreach ( $faq_items as $faq ) : ?>
				<?php if ( ! empty( $faq['question'] ) ) : ?>
					<dt class="faqs__question"><?php echo esc_html( $faq['question'] ); ?></dt>
					<?php if ( ! empty( $faq['answer'] ) ) : ?>
						<dd class="faqs__answer"><?php echo wp_kses_post( $faq['answer'] ); ?></dd>
					<?php endif; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
</section>
