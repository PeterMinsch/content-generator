<?php
/**
 * Hero Block Template
 *
 * Displays the hero section with title, subtitle, summary, and optional image.
 * Updated to match Figma design with Tailwind CSS.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

// DEBUG: Log what variables are available
error_log( '=== HERO BLOCK TEMPLATE DEBUG ===' );
error_log( 'hero_title: ' . ( isset( $hero_title ) ? $hero_title : 'NOT SET' ) );
error_log( 'hero_subtitle: ' . ( isset( $hero_subtitle ) ? $hero_subtitle : 'NOT SET' ) );
error_log( 'hero_summary: ' . ( isset( $hero_summary ) ? $hero_summary : 'NOT SET' ) );
error_log( 'hero_image type: ' . ( isset( $hero_image ) ? gettype( $hero_image ) : 'NOT SET' ) );
error_log( 'hero_image value: ' . ( isset( $hero_image ) ? print_r( $hero_image, true ) : 'NOT SET' ) );

// Get hero fields (these should be already extracted from the $args in seo_generator_get_template)
$hero_title = isset( $hero_title ) ? $hero_title : get_the_title();
$hero_subtitle = isset( $hero_subtitle ) ? $hero_subtitle : '';
$hero_summary = isset( $hero_summary ) ? $hero_summary : '';
$hero_image = isset( $hero_image ) ? $hero_image : '';

// Get image URL
$hero_image_url = '';
if ( ! empty( $hero_image ) ) {
    error_log( 'Hero image not empty, processing...' );
    if ( is_numeric( $hero_image ) ) {
        error_log( 'Hero image is numeric ID: ' . $hero_image );
        $hero_image_url = wp_get_attachment_image_url( $hero_image, 'full' );
        error_log( 'Resolved URL: ' . ( $hero_image_url ? $hero_image_url : 'FAILED' ) );
    } elseif ( is_array( $hero_image ) && isset( $hero_image['url'] ) ) {
        error_log( 'Hero image is array with URL' );
        $hero_image_url = $hero_image['url'];
    } elseif ( is_string( $hero_image ) && filter_var( $hero_image, FILTER_VALIDATE_URL ) ) {
        error_log( 'Hero image is URL string' );
        $hero_image_url = $hero_image;
    }
} else {
    error_log( 'Hero image is empty' );
}

// Fallback to featured image if no hero image
if ( empty( $hero_image_url ) ) {
    $featured_image_id = get_post_thumbnail_id( get_the_ID() );
    if ( $featured_image_id ) {
        $hero_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
    }
}

// Split summary into two paragraphs if it's long enough
$hero_text_1 = '';
$hero_text_2 = '';
if ( ! empty( $hero_summary ) ) {
    $sentences = preg_split('/(?<=[.!?])\s+/', $hero_summary, -1, PREG_SPLIT_NO_EMPTY);
    if ( count( $sentences ) > 1 ) {
        $midpoint = ceil( count( $sentences ) / 2 );
        $hero_text_1 = implode( ' ', array_slice( $sentences, 0, $midpoint ) );
        $hero_text_2 = implode( ' ', array_slice( $sentences, $midpoint ) );
    } else {
        $hero_text_1 = $hero_summary;
    }
}

?>

<div class="min-h-screen bg-white relative overflow-hidden">
    <!-- Decorative Background SVG -->
    <div class="absolute inset-0 pointer-events-none">
        <!-- Second decorative curve -->
        <svg
            class="absolute transform -rotate-75 "
            style="
                width: 2000px;
                height: 1446.613px;
                left: 900px;
                top: 100px;
            "
            width="1569"
            height="557"
            viewBox="0 0 1569 557"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M1531.1 179.503C1360.29 249.844 1308.69 564.739 971.29 342.621C552.404 66.8601 143.923 284.883 55.1243 384.961M1567.8 98.5198C1397 168.862 1345.4 483.756 1008 261.638C589.113 -14.1224 113.405 277.764 24.6065 377.841M1411.92 0.901385C1263.09 110.342 1288.88 428.382 907.893 294.157C434.898 127.515 90.9676 437.575 28.8935 556.105M1503.71 128.355C1332.91 198.697 1223.94 580.186 886.534 358.068C467.648 82.3074 89.2025 258.78 0.403487 358.857"
                stroke="url(#paint0_linear_decoration2)"
                stroke-width="0.9"
                stroke-linecap="round"
            />
            <defs>
                <linearGradient
                    id="paint0_linear_decoration2"
                    x1="1226.41"
                    y1="278.192"
                    x2="169.739"
                    y2="331.084"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop stop-color="#CA9652" stop-opacity="0"/>
                    <stop offset="0.290568" stop-color="#CA9652"/>
                    <stop offset="0.682898" stop-color="#CA9652"/>
                    <stop offset="1" stop-color="#CA9652" stop-opacity="0"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 min-h-screen">

                <!-- Left Content Column -->
                <div class="flex flex-col justify-center pt-20 lg:pt-32 pb-20">
                    <div class="space-y-8">

                        <!-- Main Title -->
                        <h1 class="font-cormorant text-charcoal-900 uppercase leading-none">
                            <span class="block text-5xl sm:text-6xl lg:text-7xl tracking-tight">
                                <?php echo esc_html( $hero_title ); ?>
                            </span>
                        </h1>

                        <!-- Subtitle (if provided) -->
                        <?php if ( ! empty( $hero_subtitle ) ) : ?>
                            <h2 class="font-cormorant text-gold-500 text-2xl sm:text-3xl tracking-tight">
                                <?php echo esc_html( $hero_subtitle ); ?>
                            </h2>
                        <?php endif; ?>

                        <!-- Description Text Columns -->
                        <div class="grid sm:grid-cols-2 gap-8 pt-4">
                            <div class="space-y-4">
                                <p class="font-avenir text-charcoal-900 text-sm leading-relaxed">
                                    <?php echo esc_html( $hero_text_1 ?: 'Edit this page in the Block Editor to customize this text.' ); ?>
                                </p>
                            </div>

                            <?php if ( ! empty( $hero_text_2 ) ) : ?>
                                <div class="space-y-4">
                                    <p class="font-avenir text-charcoal-900 text-sm leading-relaxed">
                                        <?php echo esc_html( $hero_text_2 ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- Right Images Column -->
                <div class="flex items-center justify-center lg:justify-end relative pt-20 lg:pt-0">
                    <div class="relative w-full max-w-lg lg:max-w-none lg:w-full h-[500px] lg:h-[784px] flex items-center justify-end">
                        <!-- Single Artistic Composition Image -->
                        <?php if ( $hero_image_url ) : ?>
                            <img
                                src="<?php echo esc_url( $hero_image_url ); ?>"
                                alt="<?php echo esc_attr( $hero_title ); ?>"
                                class="w-full h-auto max-w-full max-h-full object-contain transform translate-x-40 scale-125"
                            />
                        <?php else : ?>
                            <div class="text-center text-gray-400">
                                <p>Add an image in the Block Editor</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
