<?php
/**
 * Frontend Template: Pricing Hero Block
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get ACF fields or fallback to hardcoded defaults
$pricing_hero_title = get_field( 'pricing_hero_title' ) ?: get_post_meta( get_the_ID(), 'pricing_hero_title', true ) ?: 'DIMENSIONAL ACCURACY FOR YOUR COMFORT';
$pricing_hero_description = get_field( 'pricing_hero_description' ) ?: get_post_meta( get_the_ID(), 'pricing_hero_description', true ) ?: 'Discover the ideal fit for your favorite rings at our jewelry store. Our trained crafts utilize cutting-edge sizing, using both traditional and modern methods. Choose from a range of standard sizes for a comfortable wear. We offer personalized sizing services to cater to your unique needs. Enjoy the perfect ring, perfectly sized. With us.';
$pricing_items = get_field( 'pricing_items' ) ?: get_post_meta( get_the_ID(), 'pricing_items', true );

// Default pricing items if empty
if ( empty( $pricing_items ) ) {
	$pricing_items = array(
		array(
			'category'       => 'Gold Rings',
			'downsize_label' => 'Downsize',
			'downsize_price' => '50$',
			'upsize_label'   => 'Upsize',
			'upsize_price'   => '60$',
		),
		array(
			'category'       => 'Silver Rings',
			'downsize_label' => 'Downsize',
			'downsize_price' => '40$',
			'upsize_label'   => 'Upsize',
			'upsize_price'   => '80$',
		),
		array(
			'category'       => 'Platinum Rings',
			'downsize_label' => 'Downsize',
			'downsize_price' => '120$',
			'upsize_label'   => 'Upsize',
			'upsize_price'   => '90$',
		),
		array(
			'category'       => 'Rings with Stones',
			'downsize_label' => 'Downsize',
			'downsize_price' => '75$',
			'upsize_label'   => 'Upsize',
			'upsize_price'   => '65$',
		),
		array(
			'category'    => 'Custom Designs',
			'custom_text' => 'Prices available upon consultation',
		),
	);
}

// If pricing_items is a string (serialized), unserialize it
if ( is_string( $pricing_items ) ) {
	$pricing_items = maybe_unserialize( $pricing_items );
}
?>

<section class="pricing-hero-block" style="background-color: #F5F1E8; padding: 80px 20px; position: relative; overflow: hidden;">
	<div class="container" style="max-width: 1200px; margin: 0 auto; position: relative;">

		<!-- Decorative circular images on left -->
		<div style="position: absolute; left: -50px; top: 50px; width: 180px; height: 250px; border-radius: 50%; overflow: hidden; opacity: 0.9; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transform: rotate(-5deg);">
			<!-- Placeholder for jewelry image - you can replace with actual images -->
			<div style="width: 100%; height: 100%; background: linear-gradient(135deg, #8B7355 0%, #D4A574 100%);"></div>
		</div>

		<!-- Decorative circular images on right -->
		<div style="position: absolute; right: -50px; bottom: 100px; width: 180px; height: 250px; border-radius: 50%; overflow: hidden; opacity: 0.9; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transform: rotate(5deg);">
			<!-- Placeholder for jewelry image -->
			<div style="width: 100%; height: 100%; background: linear-gradient(135deg, #D4A574 0%, #8B7355 100%);"></div>
		</div>

		<!-- Hero Title -->
		<h1 class="pricing-hero-title" style="font-family: 'Cormorant Garamond', 'Cormorant', serif; font-size: clamp(36px, 6vw, 56px); text-align: center; margin-bottom: 32px; font-weight: 400; line-height: 1.2; letter-spacing: 1px;">
			<?php
			// Split title and style specific words
			$title = strtoupper( $pricing_hero_title );

			// Color mapping: "FOR" and "COMFORT" in gray, "YOUR" in gold, rest in dark
			$words = explode( ' ', $title );
			foreach ( $words as $word ) {
				$word_upper = strtoupper( $word );
				if ( $word_upper === 'FOR' || $word_upper === 'COMFORT' ) {
					echo '<span style="color: #8B8B8B; font-weight: 300;">' . esc_html( $word ) . '</span> ';
				} elseif ( $word_upper === 'YOUR' ) {
					echo '<span style="color: #CA9652; font-weight: 400;">' . esc_html( $word ) . '</span> ';
				} else {
					echo '<span style="color: #272521;">' . esc_html( $word ) . '</span> ';
				}
			}
			?>
		</h1>

		<!-- Hero Description -->
		<p class="pricing-hero-description" style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 13px; text-align: center; max-width: 550px; margin: 0 auto 60px; color: #272521; line-height: 1.6; font-weight: 400;">
			<?php echo esc_html( $pricing_hero_description ); ?>
		</p>

		<!-- Pricing Card -->
		<div class="pricing-card" style="max-width: 460px; margin: 0 auto; background: #FFFFFF; border: 1px solid #CA9652; border-radius: 12px; padding: 40px 36px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); position: relative;">

			<!-- Crown Icon -->
			<div style="text-align: center; margin-bottom: 16px;">
				<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M16 4L20 12L28 10L24 20H8L4 10L12 12L16 4Z" stroke="#CA9652" stroke-width="1.5" fill="none"/>
					<rect x="6" y="22" width="20" height="3" rx="0.5" fill="#CA9652"/>
				</svg>
			</div>

			<!-- Price List Title -->
			<h2 style="font-family: 'Cormorant Garamond', 'Cormorant', serif; font-size: 24px; text-align: center; color: #CA9652; text-transform: uppercase; margin-bottom: 8px; font-weight: 500; letter-spacing: 2px;">
				PRICE LIST
			</h2>

			<!-- Price List Subtitle -->
			<p style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 11px; text-align: center; color: #CA9652; margin-bottom: 32px; line-height: 1.4; font-style: italic;">
				Please note: Pricing is subject to change.<br>1 to 3 hours to complete, depending on the intricacy of the design<br>and the materials involved
			</p>

			<!-- Pricing Items -->
			<?php if ( ! empty( $pricing_items ) && is_array( $pricing_items ) ) : ?>
				<?php foreach ( $pricing_items as $index => $item ) : ?>
					<?php if ( $index > 0 ) : ?>
						<!-- Divider between categories -->
						<div style="height: 1px; background: #E5E5E5; margin: 20px 0;"></div>
					<?php endif; ?>

					<div class="pricing-item">
						<!-- Category -->
						<h3 style="font-family: 'Cormorant Garamond', 'Cormorant', serif; font-size: 16px; text-align: center; color: #272521; margin-bottom: 12px; font-weight: 600;">
							<?php echo esc_html( $item['category'] ); ?>:
						</h3>

						<?php if ( isset( $item['custom_text'] ) ) : ?>
							<!-- Custom Text (for consultation items) -->
							<p style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 12px; text-align: center; color: #CA9652; font-style: italic; margin: 0;">
								<?php echo esc_html( $item['custom_text'] ); ?>
							</p>
						<?php else : ?>
							<!-- Downsize Row -->
							<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
								<span style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 13px; color: #CA9652; font-weight: 400;">
									<?php echo esc_html( $item['downsize_label'] ?? 'Downsize' ); ?>
								</span>
								<span style="flex: 1; border-bottom: 1px dotted #CA9652; margin: 0 10px; opacity: 0.5;"></span>
								<span style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 13px; color: #272521; font-weight: 500;">
									<?php echo esc_html( $item['downsize_price'] ?? '' ); ?>
								</span>
							</div>

							<!-- Upsize Row -->
							<div style="display: flex; align-items: center; justify-content: space-between;">
								<span style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 13px; color: #CA9652; font-weight: 400;">
									<?php echo esc_html( $item['upsize_label'] ?? 'Upsize' ); ?>
								</span>
								<span style="flex: 1; border-bottom: 1px dotted #CA9652; margin: 0 10px; opacity: 0.5;"></span>
								<span style="font-family: 'Avenir', 'Arial', sans-serif; font-size: 13px; color: #272521; font-weight: 500;">
									<?php echo esc_html( $item['upsize_price'] ?? '' ); ?>
								</span>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
