<?php
/**
 * About Section Block Template
 *
 * Displays company about section with heading, description, and features grid.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get SVG icons for each icon type.
function seo_get_feature_icon( $icon_type ) {
	$icons = array(
		'shipping' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.2419 11.5509H21.8947V7.21924H6.27444C5.04713 7.21924 4.04297 8.19387 4.04297 9.38508V21.2972H6.27444C6.27444 23.0948 7.76952 24.546 9.62164 24.546C11.4738 24.546 12.9689 23.0948 12.9689 21.2972H19.6633C19.6633 23.0948 21.1583 24.546 23.0105 24.546C24.8626 24.546 26.3577 23.0948 26.3577 21.2972H28.5891V15.8826L25.2419 11.5509ZM24.6841 13.1753L26.8709 15.8826H21.8947V13.1753H24.6841ZM9.62164 22.3801C9.00799 22.3801 8.50591 21.8928 8.50591 21.2972C8.50591 20.7016 9.00799 20.2143 9.62164 20.2143C10.2353 20.2143 10.7374 20.7016 10.7374 21.2972C10.7374 21.8928 10.2353 22.3801 9.62164 22.3801ZM12.0986 19.1314C11.4849 18.4708 10.6146 18.0484 9.62164 18.0484C8.62864 18.0484 7.75837 18.4708 7.14471 19.1314H6.27444V9.38508H19.6633V19.1314H12.0986ZM23.0105 22.3801C22.3968 22.3801 21.8947 21.8928 21.8947 21.2972C21.8947 20.7016 22.3968 20.2143 23.0105 20.2143C23.6241 20.2143 24.1262 20.7016 24.1262 21.2972C24.1262 21.8928 23.6241 22.3801 23.0105 22.3801Z" fill="currentColor"/></svg>',

		'returns'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.333 10.6668L19.9997 16.0002H23.9997C23.9997 20.4135 20.413 24.0002 15.9997 24.0002C14.653 24.0002 13.373 23.6668 12.2663 23.0668L10.3197 25.0135C11.9597 26.0535 13.9063 26.6668 15.9997 26.6668C21.893 26.6668 26.6663 21.8935 26.6663 16.0002H30.6663L25.333 10.6668ZM7.99967 16.0002C7.99967 11.5868 11.5863 8.00016 15.9997 8.00016C17.3463 8.00016 18.6263 8.3335 19.733 8.9335L21.6797 6.98683C20.0397 5.94683 18.093 5.3335 15.9997 5.3335C10.1063 5.3335 5.33301 10.1068 5.33301 16.0002H1.33301L6.66634 21.3335L11.9997 16.0002H7.99967Z" fill="currentColor"/></svg>',

		'warranty' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.9997 5.26255L24.296 8.94847V14.5188C24.296 19.8759 20.7641 24.8181 15.9997 26.2877C11.2352 24.8181 7.70338 19.8759 7.70338 14.5188V8.94847L15.9997 5.26255ZM15.9997 2.66699L5.33301 7.40773V14.5188C5.33301 21.0966 9.88412 27.2477 15.9997 28.7411C22.1152 27.2477 26.6663 21.0966 26.6663 14.5188V7.40773L15.9997 2.66699Z" fill="currentColor"/><path d="M20.9654 11.3267C20.7644 11.1258 20.4386 11.1258 20.2377 11.3267L15.4454 16.119C15.3449 16.2195 15.182 16.2195 15.0815 16.119L12.9583 13.9958C12.7573 13.7948 12.4315 13.7948 12.2306 13.9958L11.6285 14.5979C11.4275 14.7988 11.4275 15.1246 11.6285 15.3256L15.0815 18.7786C15.182 18.8791 15.3449 18.8791 15.4454 18.7786L21.5675 12.6565C21.7684 12.4556 21.7684 12.1298 21.5675 11.9288L20.9654 11.3267Z" fill="currentColor"/></svg>',

		'finance'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.6665H27.667C28.2191 7.66668 28.667 8.11433 28.667 8.6665V23.3335C28.6668 23.8855 28.219 24.3333 27.667 24.3335H5C4.44783 24.3335 4.00018 23.8856 4 23.3335V8.6665C4 8.11422 4.44772 7.6665 5 7.6665Z" stroke="currentColor" stroke-width="2"/></svg>',
	);

	return isset( $icons[ $icon_type ] ) ? $icons[ $icon_type ] : $icons['shipping'];
}

?>

<section class="seo-block seo-block--about-section">
	<!-- Decorative Background Shapes -->
	<div class="about-section__bg-shapes">
		<!-- Left decorative shape -->
		<div class="about-section__bg-left" aria-hidden="true">
			<svg width="584" height="612" viewBox="0 0 584 612" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M404.756 481.541C328.042 363.051 432.618 142.833 123.993 135.537C-259.166 126.48 -420.963 -188.168 -433.217 -289.656" stroke="url(#paint0_linear_left)" stroke-width="0.315831" stroke-linecap="round"/>
				<path d="M392.585 548.372C315.871 429.881 420.447 209.664 111.823 202.368C-271.336 193.311 -443.239 -196.976 -455.492 -298.463" stroke="url(#paint1_linear_left)" stroke-width="0.315831" stroke-linecap="round"/>
				<path d="M252.175 541.858C206.278 408.372 360.842 219.847 63.0728 138.389C-205.613 64.8875 -321.911 -137.102 -360.577 -280.931" stroke="url(#paint2_linear_left)" stroke-width="0.315831" stroke-linecap="round"/>
				<path d="M365.296 501.748C288.582 383.258 386.083 96.255 77.4587 88.9594C-305.7 79.9018 -466.704 -195.583 -478.957 -297.07" stroke="url(#paint3_linear_left)" stroke-width="0.315831" stroke-linecap="round"/>
				<defs>
					<linearGradient id="paint0_linear_left" x1="-429.359" y1="-299.545" x2="404.097" y2="481.814" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.13694" stop-color="#BC8C4D" stop-opacity="0.86306"/>
						<stop offset="0.608696" stop-color="#8C6839"/>
						<stop offset="1" stop-color="#644A29" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="paint1_linear_left" x1="-453.302" y1="-234.709" x2="397.018" y2="500.166" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.0581478" stop-color="#CA9652" stop-opacity="0.941852"/>
						<stop offset="1" stop-color="#CA9652" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="paint2_linear_left" x1="-363.178" y1="-289.321" x2="270.854" y2="490.337" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.112134" stop-color="#BE8E4E" stop-opacity="0.887866"/>
						<stop offset="0.681946" stop-color="#846336"/>
						<stop offset="1" stop-color="#644A29" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="paint3_linear_left" x1="-475.901" y1="-258.653" x2="236.476" y2="295.268" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.112876" stop-color="#CA9652" stop-opacity="0.887124"/>
						<stop offset="0.809639" stop-color="#CA9652"/>
						<stop offset="1" stop-color="#CA9652" stop-opacity="0"/>
					</linearGradient>
				</defs>
			</svg>
		</div>

		<!-- Right decorative shape -->
		<div class="about-section__bg-right" aria-hidden="true">
			<svg width="395" height="374" viewBox="0 0 395 374" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M630.558 563.283C584.566 492.245 647.262 360.218 462.232 355.844C232.517 350.413 135.514 161.772 128.168 100.928" stroke="url(#paint0_linear_right)" stroke-width="0.315831" stroke-linecap="round"/>
				<path d="M623.262 603.351C577.27 532.312 639.966 400.285 454.937 395.911C225.221 390.481 122.161 156.492 114.814 95.6478" stroke="url(#paint1_linear_right)" stroke-width="0.315831" stroke-linecap="round"/>
				<path d="M539.082 599.446C511.566 519.417 604.232 406.391 425.71 357.554C264.625 313.488 194.901 192.389 171.719 106.159" stroke="url(#paint2_linear_right)" stroke-width="0.315831" stroke-linecap="round"/>
				<path d="M606.901 575.398C560.909 504.359 619.364 332.293 434.334 327.919C204.619 322.488 108.092 157.327 100.746 96.4822" stroke="url(#paint3_linear_right)" stroke-width="0.315831" stroke-linecap="round"/>
				<defs>
					<linearGradient id="paint0_linear_right" x1="130.481" y1="94.9989" x2="630.163" y2="563.447" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.13694" stop-color="#BC8C4D" stop-opacity="0.86306"/>
						<stop offset="0.608696" stop-color="#8C6839"/>
						<stop offset="1" stop-color="#644A29" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="paint1_linear_right" x1="116.127" y1="133.87" x2="625.92" y2="574.45" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.0581478" stop-color="#CA9652" stop-opacity="0.941852"/>
						<stop offset="1" stop-color="#CA9652" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="paint2_linear_right" x1="170.16" y1="101.129" x2="550.281" y2="568.558" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.112134" stop-color="#BE8E4E" stop-opacity="0.887866"/>
						<stop offset="0.681946" stop-color="#846336"/>
						<stop offset="1" stop-color="#644A29" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="paint3_linear_right" x1="102.579" y1="119.514" x2="529.669" y2="451.607" gradientUnits="userSpaceOnUse">
						<stop stop-color="#CA9652" stop-opacity="0"/>
						<stop offset="0.112876" stop-color="#CA9652" stop-opacity="0.887124"/>
						<stop offset="0.809639" stop-color="#CA9652"/>
						<stop offset="1" stop-color="#CA9652" stop-opacity="0"/>
					</linearGradient>
				</defs>
			</svg>
		</div>
	</div>

	<!-- Main Content -->
	<div class="about-section__content">
		<!-- Header Section -->
		<?php if ( ! empty( $about_heading ) || ! empty( $about_description ) ) : ?>
			<header class="about-section__header">
				<?php if ( ! empty( $about_heading ) ) : ?>
					<h2 class="about-section__heading"><?php echo esc_html( $about_heading ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $about_description ) ) : ?>
					<p class="about-section__description"><?php echo esc_html( $about_description ); ?></p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<!-- Features Grid -->
		<?php if ( ! empty( $about_features ) && is_array( $about_features ) ) : ?>
			<div class="about-section__features">
				<?php foreach ( $about_features as $feature ) : ?>
					<div class="about-section__feature">
						<!-- Icon -->
						<div class="about-section__feature-icon">
							<?php echo seo_get_feature_icon( $feature['icon_type'] ?? 'shipping' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<!-- Text Content -->
						<div class="about-section__feature-content">
							<?php if ( ! empty( $feature['title'] ) ) : ?>
								<h3 class="about-section__feature-title"><?php echo esc_html( $feature['title'] ); ?></h3>
							<?php endif; ?>

							<?php if ( ! empty( $feature['description'] ) ) : ?>
								<p class="about-section__feature-desc"><?php echo esc_html( $feature['description'] ); ?></p>
			<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
