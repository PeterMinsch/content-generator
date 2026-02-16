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

    <?php
    // Render ALL content blocks in custom order (if set) or default order
    // Get custom block order from post meta (set during import)
    $custom_order_json = get_post_meta( $post_id, '_seo_block_order', true );

    if ( ! empty( $custom_order_json ) ) {
        $blocks_to_render = json_decode( $custom_order_json, true );

        // Remove 'seo_metadata' as it's handled separately (meta tags, not visual content)
        $blocks_to_render = array_filter( $blocks_to_render, function( $block ) {
            return $block !== 'seo_metadata';
        } );
    } else {
        // Fallback to default order (hero first, then other blocks)
        $blocks_to_render = array( 'hero', 'about_section', 'serp_answer', 'product_criteria', 'materials', 'process', 'comparison', 'product_showcase', 'size_fit', 'care_warranty', 'ethics', 'faqs', 'cta', 'related_links', 'pricing_hero' );
    }

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
