<?php
/**
 * Template for SEO Page (single-seo-page.php)
 *
 * Displays SEO pages with Block Editor content in custom styled layout
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 */

// Disable caching for testing
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start WordPress loop
if ( have_posts() ) :
    while ( have_posts() ) : the_post();

// Get the block content
global $wpdb;
$post_id = get_the_ID();
$block_content = $wpdb->get_var( $wpdb->prepare(
    "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d",
    $post_id
) );

// Handle null content
if ( $block_content === null ) {
    $block_content = '';
}

// Parse blocks to extract content - THIS IS THE ONLY SOURCE OF TRUTH
$blocks = parse_blocks( $block_content );
$page_title = get_the_title();
$hero_text_1 = '';
$hero_text_2 = '';
$hero_image_url = '';

// Extract content from blocks
if ( ! empty( $blocks ) ) {
    foreach ( $blocks as $block ) {
        if ( $block['blockName'] === 'core/columns' && ! empty( $block['innerBlocks'] ) ) {
            // First column - text content
            if ( ! empty( $block['innerBlocks'][0]['innerBlocks'] ) ) {
                $left_column = $block['innerBlocks'][0]['innerBlocks'];
                foreach ( $left_column as $inner_block ) {
                    if ( $inner_block['blockName'] === 'core/heading' ) {
                        $page_title = strip_tags( $inner_block['innerHTML'] );
                    }
                    if ( $inner_block['blockName'] === 'core/paragraph' ) {
                        if ( empty( $hero_text_1 ) ) {
                            $hero_text_1 = strip_tags( $inner_block['innerHTML'] );
                        } else if ( empty( $hero_text_2 ) ) {
                            $hero_text_2 = strip_tags( $inner_block['innerHTML'] );
                        }
                    }
                }
            }
            // Second column - image
            if ( ! empty( $block['innerBlocks'][1]['innerBlocks'] ) ) {
                foreach ( $block['innerBlocks'][1]['innerBlocks'] as $inner_block ) {
                    if ( $inner_block['blockName'] === 'core/image' ) {
                        // Extract image URL from HTML
                        preg_match('/<img[^>]+src="([^"]+)"/', $inner_block['innerHTML'], $matches);
                        if ( ! empty( $matches[1] ) ) {
                            $hero_image_url = $matches[1];
                        }
                    }
                }
            }
        }
    }
}

// Fallback: If no blocks found, try featured image
if ( empty( $hero_image_url ) ) {
    $featured_image_id = get_post_thumbnail_id( $post_id );
    if ( $featured_image_id ) {
        $hero_image_url = wp_get_attachment_image_url( $featured_image_id, 'full' );
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - <?php bloginfo('name'); ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Avenir:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'cormorant': ['Cormorant Garamond', 'serif'],
                        'avenir': ['Avenir', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        'gold-500': '#CA9652',
                        'charcoal-900': '#272521',
                    },
                    fontSize: {
                        '7xl': ['84px', { lineHeight: '100%', letterSpacing: '-4.62px' }],
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --gold-500: #CA9652;
            --charcoal-900: #272521;
        }

        .text-gold-500 {
            color: #CA9652;
        }

        .text-charcoal-900 {
            color: #272521;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php
    // Output breadcrumb navigation
    if ( function_exists( 'seo_generator_breadcrumbs' ) ) {
        seo_generator_breadcrumbs();
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
                                    <?php echo esc_html($page_title); ?>
                                </span>
                            </h1>

                            <!-- Description Text Columns -->
                            <div class="grid sm:grid-cols-2 gap-8 pt-4">
                                <div class="space-y-4">
                                    <p class="font-avenir text-charcoal-900 text-sm leading-relaxed">
                                        <?php echo esc_html($hero_text_1 ?: 'Edit this page in the Block Editor to customize this text.'); ?>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <p class="font-avenir text-charcoal-900 text-sm leading-relaxed">
                                        <?php echo esc_html($hero_text_2 ?: 'Add more content using the Block Editor\'s drag-and-drop interface.'); ?>
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Right Images Column -->
                    <div class="flex items-center justify-center lg:justify-end relative pt-20 lg:pt-0">
                        <div class="relative w-full max-w-lg lg:max-w-none lg:w-full h-[500px] lg:h-[784px] flex items-center justify-end">
                            <!-- Single Artistic Composition Image -->
                            <?php if ($hero_image_url): ?>
                                <img
                                    src="<?php echo esc_url($hero_image_url); ?>"
                                    alt="<?php echo esc_attr($page_title); ?>"
                                    class="w-full h-auto max-w-full max-h-full object-contain transform translate-x-40 scale-125"
                                />
                            <?php else: ?>
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

    <?php
    // Render additional content blocks (about_section, serp_answer, etc.)
    $blocks_to_render = array( 'about_section', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics', 'faqs', 'cta' );

    foreach ( $blocks_to_render as $block_type ) {
        if ( function_exists( 'seo_generator_render_block' ) ) {
            seo_generator_render_block( $block_type, $post_id );
        }
    }
    ?>

    <?php
    // Output JSON-LD Schema.
    if ( function_exists( 'seo_generator_output_schema' ) ) {
        seo_generator_output_schema();
    }
    ?>

    <?php wp_footer(); ?>
</body>
</html>
<?php

endwhile; // End WordPress loop
endif;
