<?php
/**
 * Ethics & Origin Block Template
 *
 * Displays ethical sourcing information and certifications.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--ethics">
	<?php if ( ! empty( $ethics_heading ) ) : ?>
		<h2 class="ethics__heading"><?php echo esc_html( $ethics_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $ethics_text ) ) : ?>
		<div class="ethics__text">
			<p><?php echo wp_kses_post( $ethics_text ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $certifications ) && is_array( $certifications ) ) : ?>
		<div class="ethics__certifications">
			<h3><?php esc_html_e( 'Certifications', 'seo-generator' ); ?></h3>
			<ul class="ethics__certification-list">
				<?php foreach ( $certifications as $cert ) : ?>
					<?php if ( ! empty( $cert['cert_name'] ) ) : ?>
						<li>
							<?php if ( ! empty( $cert['cert_link'] ) ) : ?>
								<a href="<?php echo esc_url( $cert['cert_link'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $cert['cert_name'] ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $cert['cert_name'] ); ?>
							<?php endif; ?>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
