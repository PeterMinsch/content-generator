<?php
/**
 * Process Block Template
 *
 * Displays a step-by-step process with titles, descriptions, and optional images.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="seo-block seo-block--process">
	<?php if ( ! empty( $process_heading ) ) : ?>
		<h2 class="process__heading"><?php echo esc_html( $process_heading ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $process_steps ) && is_array( $process_steps ) ) : ?>
		<ol class="process__steps">
			<?php foreach ( $process_steps as $step ) : ?>
				<?php if ( ! empty( $step['step_title'] ) || ! empty( $step['step_text'] ) ) : ?>
					<li class="process__step">
						<?php if ( ! empty( $step['step_title'] ) ) : ?>
							<h3 class="process__step-title"><?php echo esc_html( $step['step_title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $step['step_text'] ) ) : ?>
							<p class="process__step-text"><?php echo wp_kses_post( $step['step_text'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $step['step_image'] ) ) : ?>
							<figure class="process__step-image">
								<?php
								echo wp_get_attachment_image(
									$step['step_image'],
									'medium',
									false,
									array(
										'class'   => 'process__img',
										'loading' => 'lazy',
									)
								);
								?>
							</figure>
						<?php endif; ?>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</section>
