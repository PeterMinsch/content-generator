<?php
/**
 * Template: Related Links Block
 *
 * Displays automatically generated internal links to related pages.
 *
 * @package SEOGenerator
 * @var array $related_links Array of related link data from InternalLinkingService
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $related_links ) || ! is_array( $related_links ) ) {
	return;
}
?>

<section class="related-links-section">
	<div class="related-links-container">
		<h2 class="related-links-heading">Related Articles</h2>
		<p class="related-links-intro">Explore more helpful guides on similar topics:</p>

		<div class="related-links-grid">
			<?php foreach ( $related_links as $link ) : ?>
				<?php
				$linked_post = get_post( $link['id'] );
				if ( ! $linked_post ) {
					continue;
				}

				$permalink   = get_permalink( $link['id'] );
				$title       = get_the_title( $link['id'] );
				$excerpt     = get_field( 'seo_meta_description', $link['id'] );
				$topic_terms = get_the_terms( $link['id'], 'seo-topic' );
				$topic       = $topic_terms && ! is_wp_error( $topic_terms ) ? $topic_terms[0]->name : '';
				$hero_image  = get_field( 'hero_image', $link['id'] );
				?>

				<article class="related-link-card">
					<?php if ( $hero_image ) : ?>
						<div class="related-link-image">
							<a href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
								<img src="<?php echo esc_url( $hero_image ); ?>"
									 alt="<?php echo esc_attr( $title ); ?>"
									 loading="lazy">
							</a>
						</div>
					<?php endif; ?>

					<div class="related-link-content">
						<?php if ( $topic ) : ?>
							<span class="related-link-topic"><?php echo esc_html( $topic ); ?></span>
						<?php endif; ?>

						<h3 class="related-link-title">
							<a href="<?php echo esc_url( $permalink ); ?>">
								<?php echo esc_html( $title ); ?>
							</a>
						</h3>

						<?php if ( $excerpt ) : ?>
							<p class="related-link-excerpt">
								<?php echo esc_html( $excerpt ); ?>
							</p>
						<?php endif; ?>

						<a href="<?php echo esc_url( $permalink ); ?>" class="related-link-button">
							Read More
							<span class="related-link-arrow" aria-hidden="true">→</span>
						</a>

						<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
							<span class="related-link-score" title="Relevance score">
								Score: <?php echo esc_html( number_format( $link['score'], 1 ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				</article>

			<?php endforeach; ?>
		</div>
	</div>
</section>

<style>
/* Related Links Styling */
.related-links-section {
	margin: 60px 0;
	padding: 40px 20px;
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	border-radius: 12px;
}

.related-links-container {
	max-width: 1200px;
	margin: 0 auto;
}

.related-links-heading {
	font-size: 32px;
	font-weight: 700;
	color: #212529;
	margin: 0 0 10px 0;
	text-align: center;
}

.related-links-intro {
	font-size: 16px;
	color: #6c757d;
	margin: 0 0 40px 0;
	text-align: center;
}

.related-links-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 24px;
}

.related-link-card {
	background: #ffffff;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.related-link-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.related-link-image {
	position: relative;
	padding-top: 56.25%; /* 16:9 aspect ratio */
	overflow: hidden;
	background: #e9ecef;
}

.related-link-image img {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.3s ease;
}

.related-link-card:hover .related-link-image img {
	transform: scale(1.05);
}

.related-link-content {
	padding: 20px;
	position: relative;
}

.related-link-topic {
	display: inline-block;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	color: #6c757d;
	margin-bottom: 8px;
}

.related-link-title {
	font-size: 20px;
	font-weight: 600;
	margin: 0 0 12px 0;
	line-height: 1.3;
}

.related-link-title a {
	color: #212529;
	text-decoration: none;
	transition: color 0.2s ease;
}

.related-link-title a:hover {
	color: #007bff;
}

.related-link-excerpt {
	font-size: 14px;
	color: #6c757d;
	line-height: 1.6;
	margin: 0 0 16px 0;
	display: -webkit-box;
	-webkit-line-clamp: 3;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

.related-link-button {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	font-weight: 600;
	color: #007bff;
	text-decoration: none;
	transition: gap 0.2s ease;
}

.related-link-button:hover {
	gap: 12px;
}

.related-link-arrow {
	transition: transform 0.2s ease;
}

.related-link-button:hover .related-link-arrow {
	transform: translateX(4px);
}

.related-link-score {
	position: absolute;
	top: 20px;
	right: 20px;
	font-size: 11px;
	padding: 4px 8px;
	background: #ffc107;
	color: #212529;
	border-radius: 4px;
	font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
	.related-links-section {
		margin: 40px 0;
		padding: 30px 15px;
	}

	.related-links-heading {
		font-size: 24px;
	}

	.related-links-grid {
		grid-template-columns: 1fr;
		gap: 20px;
	}

	.related-link-title {
		font-size: 18px;
	}
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
	.related-links-section {
		background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
	}

	.related-link-card {
		background: #2d2d2d;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
	}

	.related-link-card:hover {
		box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
	}

	.related-links-heading,
	.related-link-title a {
		color: #f8f9fa;
	}

	.related-links-intro,
	.related-link-topic,
	.related-link-excerpt {
		color: #adb5bd;
	}

	.related-link-title a:hover {
		color: #4dabf7;
	}

	.related-link-button {
		color: #4dabf7;
	}
}
</style>
