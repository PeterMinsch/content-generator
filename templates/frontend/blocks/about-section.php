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
	$icons = array(
		// Original 4 icons
		'shipping' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.2419 11.5509H21.8947V7.21924H6.27444C5.04713 7.21924 4.04297 8.19387 4.04297 9.38508V21.2972H6.27444C6.27444 23.0948 7.76952 24.546 9.62164 24.546C11.4738 24.546 12.9689 23.0948 12.9689 21.2972H19.6633C19.6633 23.0948 21.1583 24.546 23.0105 24.546C24.8626 24.546 26.3577 23.0948 26.3577 21.2972H28.5891V15.8826L25.2419 11.5509ZM24.6841 13.1753L26.8709 15.8826H21.8947V13.1753H24.6841ZM9.62164 22.3801C9.00799 22.3801 8.50591 21.8928 8.50591 21.2972C8.50591 20.7016 9.00799 20.2143 9.62164 20.2143C10.2353 20.2143 10.7374 20.7016 10.7374 21.2972C10.7374 21.8928 10.2353 22.3801 9.62164 22.3801ZM12.0986 19.1314C11.4849 18.4708 10.6146 18.0484 9.62164 18.0484C8.62864 18.0484 7.75837 18.4708 7.14471 19.1314H6.27444V9.38508H19.6633V19.1314H12.0986ZM23.0105 22.3801C22.3968 22.3801 21.8947 21.8928 21.8947 21.2972C21.8947 20.7016 22.3968 20.2143 23.0105 20.2143C23.6241 20.2143 24.1262 20.7016 24.1262 21.2972C24.1262 21.8928 23.6241 22.3801 23.0105 22.3801Z" fill="currentColor"/></svg>',

		'returns'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.333 10.6668L19.9997 16.0002H23.9997C23.9997 20.4135 20.413 24.0002 15.9997 24.0002C14.653 24.0002 13.373 23.6668 12.2663 23.0668L10.3197 25.0135C11.9597 26.0535 13.9063 26.6668 15.9997 26.6668C21.893 26.6668 26.6663 21.8935 26.6663 16.0002H30.6663L25.333 10.6668ZM7.99967 16.0002C7.99967 11.5868 11.5863 8.00016 15.9997 8.00016C17.3463 8.00016 18.6263 8.3335 19.733 8.9335L21.6797 6.98683C20.0397 5.94683 18.093 5.3335 15.9997 5.3335C10.1063 5.3335 5.33301 10.1068 5.33301 16.0002H1.33301L6.66634 21.3335L11.9997 16.0002H7.99967Z" fill="currentColor"/></svg>',

		'warranty' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.9997 5.26255L24.296 8.94847V14.5188C24.296 19.8759 20.7641 24.8181 15.9997 26.2877C11.2352 24.8181 7.70338 19.8759 7.70338 14.5188V8.94847L15.9997 5.26255ZM15.9997 2.66699L5.33301 7.40773V14.5188C5.33301 21.0966 9.88412 27.2477 15.9997 28.7411C22.1152 27.2477 26.6663 21.0966 26.6663 14.5188V7.40773L15.9997 2.66699Z" fill="currentColor"/><path d="M20.9654 11.3267C20.7644 11.1258 20.4386 11.1258 20.2377 11.3267L15.4454 16.119C15.3449 16.2195 15.182 16.2195 15.0815 16.119L12.9583 13.9958C12.7573 13.7948 12.4315 13.7948 12.2306 13.9958L11.6285 14.5979C11.4275 14.7988 11.4275 15.1246 11.6285 15.3256L15.0815 18.7786C15.182 18.8791 15.3449 18.8791 15.4454 18.7786L21.5675 12.6565C21.7684 12.4556 21.7684 12.1298 21.5675 11.9288L20.9654 11.3267Z" fill="currentColor"/></svg>',

		'finance'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.6665H27.667C28.2191 7.66668 28.667 8.11433 28.667 8.6665V23.3335C28.6668 23.8855 28.219 24.3333 27.667 24.3335H5C4.44783 24.3335 4.00018 23.8856 4 23.3335V8.6665C4 8.11422 4.44772 7.6665 5 7.6665Z" stroke="currentColor" stroke-width="2"/></svg>',

		// New icons for expanded guarantees
		'quality'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 4L18.472 11.528L26 14L18.472 16.472L16 24L13.528 16.472L6 14L13.528 11.528L16 4Z" fill="currentColor"/><path d="M8 6L9.236 9.764L13 11L9.236 12.236L8 16L6.764 12.236L3 11L6.764 9.764L8 6Z" fill="currentColor"/><path d="M24 18L25.236 21.764L29 23L25.236 24.236L24 28L22.764 24.236L19 23L22.764 21.764L24 18Z" fill="currentColor"/></svg>',

		'secure'   => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 2.66699L5.33301 7.40773V14.5188C5.33301 21.0966 9.88412 27.2477 15.9997 28.7411C22.1152 27.2477 26.6663 21.0966 26.6663 14.5188V7.40773L16 2.66699ZM16 15.3335H24C23.52 19.6535 21.0667 23.6002 16 25.2802V16.0002H8V8.85352L16 5.48685V15.3335Z" fill="currentColor"/></svg>',

		'support'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 5.33301C9.37301 5.33301 3.99967 10.7063 3.99967 17.333C3.99967 20.133 4.99967 22.6663 6.66634 24.693V28.6663L10.3997 26.7997C12.1197 27.493 13.9997 27.9063 15.9997 27.9063C22.6263 27.9063 27.9997 22.533 27.9997 15.9063C27.9997 9.27967 22.6263 3.90634 15.9997 3.90634L16 5.33301ZM17.333 22.6663H14.6663V19.9997H17.333V22.6663ZM19.893 13.5463L18.8797 14.5863C17.9997 15.4797 17.333 16.2663 17.333 18.6663H14.6663V17.9997C14.6663 16.453 15.333 15.0663 16.213 14.1597L17.6397 12.7063C18.093 12.2663 18.3997 11.653 18.3997 10.9863C18.3997 9.6263 17.3463 8.5463 15.9997 8.5463C14.653 8.5463 13.5997 9.6263 13.5997 10.9863H10.933C10.933 8.15967 13.1863 5.87967 15.9997 5.87967C18.813 5.87967 21.0663 8.15967 21.0663 10.9863C21.0663 12.1463 20.5997 13.1863 19.893 13.5463Z" fill="currentColor"/></svg>',

		'eco'      => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.333 3.99967C11.2397 5.33301 6.66634 10.8263 6.66634 17.333C6.66634 24.6663 12.6663 30.6663 19.9997 30.6663C26.5063 30.6663 31.9997 26.093 33.333 19.9997H29.9997C28.7463 24.4263 24.7463 27.9997 19.9997 27.9997C14.1063 27.9997 9.33301 23.2263 9.33301 17.333C9.33301 12.5863 12.9063 8.58634 17.333 7.33301V3.99967ZM29.333 5.33301L19.9997 14.6663V8.66634C19.9997 8.66634 25.9997 8.66634 29.333 12.0263V5.33301Z" fill="currentColor"/></svg>',

		'diamond'  => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.333 4H10.6663L4 13.3333L16 28L28 13.3333L21.333 4ZM11.9997 6.66667H20.0397L22.7063 11.3333H9.29301L11.9997 6.66667ZM17.333 14V23.3467L23.4663 14H17.333ZM14.6663 14H8.53301L14.6663 23.3467V14ZM6.98634 14H13.0663V13.9733L13.1597 14H18.8397L18.933 13.9733V14H25.013L16 24.5067L6.98634 14Z" fill="currentColor"/></svg>',

		'resize'   => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 2.66699C8.63967 2.66699 2.66634 8.64033 2.66634 16.0003C2.66634 23.3603 8.63967 29.3337 16 29.3337C23.36 29.3337 29.333 23.3603 29.333 16.0003C29.333 8.64033 23.36 2.66699 16 2.66699ZM16 26.667C10.1063 26.667 5.33301 21.8937 5.33301 16.0003C5.33301 10.107 10.1063 5.33366 16 5.33366C21.8937 5.33366 26.6663 10.107 26.6663 16.0003C26.6663 21.8937 21.8937 26.667 16 26.667ZM12 17.3337H14.6663V22.667H17.333V17.3337H20L16 13.3337L12 17.3337ZM12 14.667L16 10.667L20 14.667H17.333V9.33366H14.6663V14.667H12Z" fill="currentColor"/></svg>',

		'gift'     => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.667 9.33301H20.4003C20.7603 8.78634 21.0003 8.10634 21.0003 7.33301C21.0003 5.48634 19.5137 3.99967 17.667 3.99967C16.3603 3.99967 15.2137 4.69967 14.667 5.73967C14.1203 4.69967 12.9737 3.99967 11.667 3.99967C9.82033 3.99967 8.33366 5.48634 8.33366 7.33301C8.33366 8.10634 8.57366 8.78634 8.93366 9.33301H6.66699C5.18699 9.33301 4.00033 10.5197 4.00033 11.9997V14.6663C4.00033 15.773 4.89366 16.6663 6.00033 16.6663H6.66699V27.333C6.66699 28.0663 7.26699 28.6663 8.00033 28.6663H24.0003C24.7337 28.6663 25.3337 28.0663 25.3337 27.333V16.6663H26.0003C27.107 16.6663 28.0003 15.773 28.0003 14.6663V11.9997C28.0003 10.5197 26.8137 9.33301 25.3337 9.33301H22.667ZM17.667 6.66634C18.0403 6.66634 18.3337 6.95967 18.3337 7.33301C18.3337 7.70634 18.0403 7.99967 17.667 7.99967H15.8403C16.067 7.21301 16.8137 6.66634 17.667 6.66634ZM11.667 6.66634C12.5203 6.66634 13.267 7.21301 13.4937 7.99967H11.667C11.2937 7.99967 11.0003 7.70634 11.0003 7.33301C11.0003 6.95967 11.2937 6.66634 11.667 6.66634ZM6.66699 11.9997H14.667V14.6663H6.66699V11.9997ZM9.33366 25.9997V16.6663H14.667V25.9997H9.33366ZM22.667 25.9997H17.3337V16.6663H22.667V25.9997ZM25.3337 14.6663H17.3337V11.9997H25.3337V14.6663Z" fill="currentColor"/></svg>',

		'repair'   => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.9063 9.42699L22.573 6.09366L24.2263 4.44033C25.3863 3.28033 27.2663 3.28033 28.4263 4.44033C29.5863 5.60033 29.5863 7.48033 28.4263 8.64033L25.9063 9.42699ZM23.6397 10.3603L8.90634 25.0937C8.50634 25.4937 8.05301 25.8137 7.55967 26.0537L2.66634 28.3203L4.93301 23.427C5.17301 22.9337 5.49301 22.4937 5.89301 22.0937L20.6263 7.36033L23.6397 10.3603Z" fill="currentColor"/></svg>',
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
						<div class="w-16 h-16 rounded-full border border-[#CA9652] flex items-center justify-center flex-shrink-0 text-[#CA9652]">
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
