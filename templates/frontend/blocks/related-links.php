<?php
/**
 * Template: Related Links Block
 *
 * Displays internal links to related pages with images in a 4-card grid.
 * Design based on Figma specifications - simple and clean.
 *
 * @package SEOGenerator
 * @var string $section_heading Section heading with action verb
 * @var array  $links           Array of related link data
 */

defined( 'ABSPATH' ) || exit;

// Get field values (already extracted from $args).
$section_heading = isset( $section_heading ) ? $section_heading : 'SHOP MORE STYLES';
$links = isset( $links ) ? $links : array();

// Don't render if no links.
if ( empty( $links ) || ! is_array( $links ) ) {
	return;
}
?>

<section class="py-16 bg-white">
	<div class="container mx-auto px-6 lg:px-12 max-w-7xl">
		<!-- Section Heading -->
		<?php if ( $section_heading ) : ?>
			<?php
			// Split heading to style first word differently (e.g., "SHOP" in gold).
			$words = explode( ' ', $section_heading, 2 );
			$first_word = isset( $words[0] ) ? $words[0] : '';
			$rest_words = isset( $words[1] ) ? $words[1] : '';
			?>
			<h2 class="text-center mb-[72px] font-cormorant text-[72px] font-light leading-none uppercase" style="letter-spacing: -3.96px;">
				<span class="text-[#ca9652]"><?php echo esc_html( $first_word ); ?></span>
				<?php if ( $rest_words ) : ?>
					<span class="text-[#272521]"> <?php echo esc_html( $rest_words ); ?></span>
				<?php endif; ?>
			</h2>
		<?php endif; ?>

		<!-- Links Grid (4 columns) -->
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-[24px]">
			<?php foreach ( $links as $link ) : ?>
				<?php
				$title       = isset( $link['link_title'] ) ? $link['link_title'] : '';
				$url         = isset( $link['link_url'] ) ? $link['link_url'] : '#';
				$description = isset( $link['link_description'] ) ? $link['link_description'] : '';
				$item_count  = isset( $link['link_item_count'] ) ? $link['link_item_count'] : '';
				$image       = isset( $link['link_image'] ) ? $link['link_image'] : '';

				// Get image URL - handles multiple formats (ID, array, URL string).
				$image_url = '';
				if ( ! empty( $image ) ) {
					if ( is_numeric( $image ) ) {
						$image_url = wp_get_attachment_image_url( $image, 'large' );
					} elseif ( is_array( $image ) && isset( $image['url'] ) ) {
						$image_url = $image['url'];
					} elseif ( is_string( $image ) && filter_var( $image, FILTER_VALIDATE_URL ) ) {
						$image_url = $image;
					}
				}

				// Skip if no title or URL.
				if ( empty( $title ) || empty( $url ) ) {
					continue;
				}
				?>

				<!-- Card -->
				<a href="<?php echo esc_url( $url ); ?>"
				   class="group block relative overflow-hidden rounded-[18px] border border-[#ca9652] transition-transform duration-300 hover:scale-105"
				   style="height: 280px; border-width: 0.557px;">

					<!-- Background Image -->
					<?php if ( $image_url ) : ?>
						<img src="<?php echo esc_url( $image_url ); ?>"
						     alt="<?php echo esc_attr( $title ); ?>"
						     class="absolute inset-0 w-full h-full object-cover rounded-[18px]"
						     loading="lazy" />
					<?php else : ?>
						<!-- Fallback gradient if no image -->
						<div class="absolute inset-0 w-full h-full bg-gradient-to-br from-gray-300 to-gray-500 rounded-[18px]"></div>
					<?php endif; ?>

					<!-- Dark Gradient Overlay (for text readability) -->
					<div class="absolute bottom-0 left-0 right-0 h-[147px] bg-gradient-to-t from-[rgba(24,24,24,0.9)] to-transparent"></div>

					<!-- Card Content (Bottom Left) -->
					<div class="absolute bottom-0 left-0 right-0 p-6 text-white">
						<!-- Title -->
						<h3 class="font-cormorant text-[24px] font-normal leading-none uppercase mb-2">
							<?php echo esc_html( $title ); ?>
						</h3>

						<!-- Description -->
						<?php if ( $description ) : ?>
							<p class="font-avenir text-[13px] font-normal mb-2 opacity-90" style="line-height: 1.5;">
								<?php echo esc_html( $description ); ?>
							</p>
						<?php endif; ?>

						<!-- Item Count -->
						<?php if ( $item_count ) : ?>
							<p class="font-avenir text-[14px] font-medium" style="line-height: 1.4;">
								<?php echo esc_html( $item_count ); ?>
							</p>
						<?php endif; ?>
					</div>
				</a>

			<?php endforeach; ?>
		</div>
	</div>
</section>
