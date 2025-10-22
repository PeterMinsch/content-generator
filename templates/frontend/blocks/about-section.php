<?php
/**
 * About Section Block Template
 *
 * Displays company about section with heading, description, and features grid.
 * Design matches Figma specifications exactly.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// Get SVG icons for each icon type.
function seo_get_feature_icon( $icon_type ) {
	// Lucide Icons - All icons use 24x24 viewBox, scaled to 32x32 for consistency
	$icons = array(
		// truck icon
		'shipping' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',

		// undo-2 icon
		'returns'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>',

		// shield-check icon
		'warranty' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>',

		// credit-card icon
		'finance'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',

		// award icon
		'quality'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',

		// shield-check icon (can reuse for secure)
		'secure'   => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>',

		// headphones icon
		'support'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/></svg>',

		// leaf icon
		'eco'      => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>',

		// gem icon
		'diamond'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 18 3 22 9 12 22 2 9 6 3"/><path d="m12 22 4-13-10 0"/><path d="M2 9h20"/></svg>',

		// maximize-2 icon
		'resize'   => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" x2="14" y1="3" y2="10"/><line x1="3" x2="10" y1="21" y2="14"/></svg>',

		// gift icon
		'gift'     => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>',

		// wrench icon
		'repair'   => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
	);

	return isset( $icons[ $icon_type ] ) ? $icons[ $icon_type ] : $icons['quality'];
}

?>

<section class="relative bg-[#FEF9F4] py-[124px] px-6 lg:px-[223px] overflow-hidden">
	<!-- Decorative Background Shapes -->
	<div class="absolute inset-0 pointer-events-none overflow-hidden">
		<!-- Left decorative shape -->
		<div class="absolute left-0 top-0 -translate-x-1/2 -translate-y-1/3 w-[600px] h-[600px] opacity-40 hidden lg:block">
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
		<div class="absolute right-0 bottom-0 translate-x-1/3 translate-y-1/4 w-[600px] h-[600px] opacity-40 hidden lg:block">
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
	<div class="relative z-10 max-w-[1200px] mx-auto flex flex-col items-center gap-[72px]">
		<!-- Header Section -->
		<header class="text-center max-w-[634px] flex flex-col gap-4">
			<h2 class="font-cormorant text-[40px] lg:text-[80px] font-light leading-none tracking-[-2px] lg:tracking-[-4.4px] uppercase m-0 text-[#272521]">
				About <span class="text-[#CA9652]">Bravo Jewelers</span>
			</h2>

			<p class="font-avenir text-[18px] leading-[1.4] font-medium text-[rgba(39,37,33,0.65)] m-0">
				Family-run and handcrafted in Carlsbad, Bravo Jewelers has over 25 years of experience serving San Diego County with timeless craftsmanship.
			</p>
		</header>

		<!-- Features Grid -->
		<?php if ( ! empty( $about_features ) && is_array( $about_features ) ) : ?>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-[32px] w-full max-w-[1200px]">
				<?php foreach ( $about_features as $feature ) : ?>
					<div class="flex flex-col items-center gap-6 text-center">
						<!-- Icon -->
						<div class="w-16 h-16 rounded-full border border-[#CA9652] flex items-center justify-center flex-shrink-0 text-[#CA9652] opacity-80">
							<?php echo seo_get_feature_icon( $feature['icon_type'] ?? 'shipping' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<!-- Text Content -->
						<div class="flex flex-col items-center gap-2">
							<?php if ( ! empty( $feature['title'] ) ) : ?>
								<h3 class="font-cormorant text-[28px] leading-none uppercase text-[#272521] m-0 font-normal">
									<?php echo esc_html( $feature['title'] ); ?>
								</h3>
							<?php endif; ?>

							<?php if ( ! empty( $feature['description'] ) ) : ?>
								<p class="font-avenir text-[16px] leading-[1.4] font-medium text-[#8F8F8F] m-0">
									<?php echo esc_html( $feature['description'] ); ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
