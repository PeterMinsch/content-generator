<?php
/**
 * Plugin Helper Functions
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encrypt API key for secure storage.
 *
 * Uses WordPress salts as encryption key with AES-256-CBC cipher.
 * API keys are encrypted before storing in wp_options and only
 * decrypted server-side when making API calls.
 *
 * @param string $value API key to encrypt.
 * @return string|false Encrypted value or false on failure.
 */
function seo_generator_encrypt_api_key( string $value ) {
	if ( empty( $value ) ) {
		return false;
	}

	try {
		$key = wp_salt( 'auth' );
		$iv  = substr( hash( 'sha256', $key ), 0, 16 );

		$encrypted = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $encrypted ) {
			error_log( 'SEO Generator: Failed to encrypt API key' );
			return false;
		}

		return $encrypted;
	} catch ( \Exception $e ) {
		error_log( 'SEO Generator: Encryption error - ' . $e->getMessage() );
		return false;
	}
}

/**
 * Decrypt API key for use.
 *
 * Decrypts the API key stored in wp_options using the same
 * encryption key derived from WordPress salts.
 *
 * @param string $encrypted Encrypted API key.
 * @return string|false Decrypted value or false on failure.
 */
function seo_generator_decrypt_api_key( string $encrypted ) {
	if ( empty( $encrypted ) ) {
		return false;
	}

	try {
		$key = wp_salt( 'auth' );
		$iv  = substr( hash( 'sha256', $key ), 0, 16 );

		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $decrypted ) {
			error_log( 'SEO Generator: Failed to decrypt API key' );
			return false;
		}

		return $decrypted;
	} catch ( \Exception $e ) {
		error_log( 'SEO Generator: Decryption error - ' . $e->getMessage() );
		return false;
	}
}

/**
 * Get template file with theme override support.
 *
 * Allows themes to override plugin templates by placing them in:
 * {theme}/seo-generator/{template_name}
 *
 * @param string $template_name Template file name (e.g., 'blocks/hero.php').
 * @param array  $args          Variables to pass to template.
 * @return void
 */
function seo_generator_get_template( string $template_name, array $args = array() ): void {
	// Extract args to variables.
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Intended for template variables.
	}

	// Check for theme override.
	$template_path = locate_template( array( 'seo-generator/' . $template_name ) );

	// Fallback to plugin template.
	if ( ! $template_path ) {
		$template_path = SEO_GENERATOR_PLUGIN_DIR . 'templates/frontend/' . $template_name;
	}

	// Load template if exists.
	if ( file_exists( $template_path ) ) {
		include $template_path;
	}
}

/**
 * Render a content block.
 *
 * Retrieves ACF field data for the specified block type and renders
 * the appropriate template if the block has content.
 *
 * @param string $block_type Block type to render (e.g., 'hero', 'serp_answer').
 * @param int    $post_id    Post ID (defaults to current post).
 * @return void
 */
function seo_generator_render_block( string $block_type, int $post_id = 0 ): void {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	// Get all fields for this block type.
	$fields = array();

	// Map block types to their field groups.
	$field_map = array(
		'hero'              => array( 'hero_title', 'hero_subtitle', 'hero_summary', 'hero_image' ),
		'serp_answer'       => array( 'answer_heading', 'answer_paragraph', 'answer_bullets' ),
		'product_criteria'  => array( 'criteria_heading', 'criteria_items' ),
		'materials'         => array( 'materials_heading', 'materials_items' ),
		'process'           => array( 'process_heading', 'process_steps' ),
		'comparison'        => array( 'comparison_heading', 'comparison_left_label', 'comparison_right_label', 'comparison_summary', 'comparison_rows' ),
		'product_showcase'  => array( 'showcase_heading', 'showcase_intro', 'showcase_products' ),
		'size_fit'          => array( 'size_heading', 'size_chart_image', 'comfort_fit_notes' ),
		'care_warranty'     => array( 'care_heading', 'care_bullets', 'warranty_heading', 'warranty_text' ),
		'ethics'            => array( 'ethics_heading', 'ethics_text', 'certifications' ),
		'faqs'              => array( 'faqs_heading', 'faq_items' ),
		'cta'               => array( 'cta_heading', 'cta_text', 'cta_primary_label', 'cta_primary_url', 'cta_secondary_label', 'cta_secondary_url' ),
	);

	// Check if block type exists.
	if ( ! isset( $field_map[ $block_type ] ) ) {
		return;
	}

	// Retrieve field values.
	foreach ( $field_map[ $block_type ] as $field_name ) {
		$fields[ $field_name ] = get_field( $field_name, $post_id );
	}

	// Check if block has content (skip if empty).
	$has_content = false;
	foreach ( $fields as $value ) {
		if ( ! empty( $value ) ) {
			$has_content = true;
			break;
		}
	}

	if ( ! $has_content ) {
		return; // Skip empty blocks.
	}

	// Allow filtering of block data.
	$fields = apply_filters( "seo_generator_block_data_{$block_type}", $fields, $post_id );

	// Convert block_type to template filename (replace underscore with hyphen).
	$template_name = str_replace( '_', '-', $block_type ) . '.php';

	// Render template.
	seo_generator_get_template( 'blocks/' . $template_name, $fields );
}

/**
 * Output breadcrumbs navigation.
 *
 * Displays breadcrumb navigation with Home > Topic > Page structure.
 * Uses semantic HTML with proper ARIA labels for accessibility.
 *
 * @return void
 */
function seo_generator_breadcrumbs(): void {
	// Only output on singular seo-page post type.
	if ( ! is_singular( 'seo-page' ) ) {
		return;
	}

	$post_id = get_the_ID();

	// Build breadcrumb items array.
	$items = array();

	// Item 1: Home.
	$home_label = apply_filters( 'seo_generator_breadcrumb_home_label', 'Home' );
	$items[]    = array(
		'name' => $home_label,
		'url'  => home_url( '/' ),
	);

	// Item 2: Topic (if assigned).
	$topics = get_the_terms( $post_id, 'seo-topic' );
	if ( $topics && ! is_wp_error( $topics ) ) {
		$topic   = $topics[0]; // Use first topic.
		$topic_url = get_term_link( $topic );

		// Only add if we got a valid URL.
		if ( ! is_wp_error( $topic_url ) ) {
			$items[] = array(
				'name' => $topic->name,
				'url'  => $topic_url,
			);
		}
	}

	// Item 3: Current Page.
	$items[] = array(
		'name' => get_the_title( $post_id ),
		'url'  => null, // Current page has no link.
	);

	// Allow filtering of breadcrumb items.
	$items = apply_filters( 'seo_generator_breadcrumb_items', $items, $post_id );

	// Get separator.
	$separator = apply_filters( 'seo_generator_breadcrumb_separator', '>' );

	// Build HTML output.
	$output = '<nav aria-label="Breadcrumb" class="breadcrumb-nav">';
	$output .= '<ol class="breadcrumbs">';

	$total_items = count( $items );
	foreach ( $items as $index => $item ) {
		$is_last = ( $index === $total_items - 1 );

		$output .= '<li class="breadcrumb-item"';

		// Add aria-current to last item.
		if ( $is_last ) {
			$output .= ' aria-current="page"';
		}

		$output .= '>';

		if ( $item['url'] && ! $is_last ) {
			// Link for all items except current page.
			$output .= '<a href="' . esc_url( $item['url'] ) . '" class="breadcrumb-link">';
			$output .= esc_html( $item['name'] );
			$output .= '</a>';
		} else {
			// Plain text for current page.
			$output .= '<span>' . esc_html( $item['name'] ) . '</span>';
		}

		$output .= '</li>';
	}

	$output .= '</ol>';
	$output .= '</nav>';

	// Allow filtering of complete HTML output.
	$output = apply_filters( 'seo_generator_breadcrumb_html', $output, $post_id );

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above.
}

/**
 * Build Article schema for SEO page.
 *
 * Creates schema.org Article structured data from page content.
 *
 * @param int $post_id Post ID.
 * @return array Article schema structure.
 */
function seo_generator_build_article_schema( int $post_id ): array {
	$hero_title       = get_field( 'hero_title', $post_id );
	$meta_description = get_field( 'seo_meta_description', $post_id );
	$hero_image       = get_field( 'hero_image', $post_id );

	// Get image URL.
	$image_url = '';
	if ( $hero_image ) {
		if ( is_array( $hero_image ) ) {
			$image_url = $hero_image['url'] ?? '';
		} elseif ( is_numeric( $hero_image ) ) {
			$image_url = wp_get_attachment_url( $hero_image );
		}
	}

	// Fallback to post title if hero_title empty.
	$headline = $hero_title ?: get_the_title( $post_id );

	// Build schema.
	$schema = array(
		'@type'         => 'Article',
		'headline'      => esc_html( $headline ),
		'description'   => esc_html( $meta_description ?: get_the_excerpt( $post_id ) ),
		'author'        => array(
			'@type' => 'Organization',
			'name'  => esc_html( get_bloginfo( 'name' ) ),
		),
		'datePublished' => get_post_time( 'c', false, $post_id ),
		'dateModified'  => get_post_modified_time( 'c', false, $post_id ),
	);

	// Add image if available.
	if ( $image_url ) {
		$schema['image'] = esc_url( $image_url );
	}

	return apply_filters( 'seo_generator_article_schema', $schema, $post_id );
}

/**
 * Build FAQPage schema for SEO page.
 *
 * Creates schema.org FAQPage structured data from FAQ block content.
 * Returns null if no FAQ content exists.
 *
 * @param int $post_id Post ID.
 * @return array|null FAQPage schema structure or null if no FAQs.
 */
function seo_generator_build_faq_schema( int $post_id ): ?array {
	$faq_items = get_field( 'faq_items', $post_id );

	// Check if FAQ content exists.
	if ( empty( $faq_items ) || ! is_array( $faq_items ) ) {
		return null;
	}

	$questions = array();

	// Build Question objects.
	foreach ( $faq_items as $faq ) {
		$question_text = $faq['question'] ?? '';
		$answer_text   = $faq['answer'] ?? '';

		// Skip if either question or answer is empty.
		if ( empty( $question_text ) || empty( $answer_text ) ) {
			continue;
		}

		$questions[] = array(
			'@type'          => 'Question',
			'name'           => esc_html( $question_text ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => esc_html( $answer_text ),
			),
		);
	}

	// Return null if no valid questions.
	if ( empty( $questions ) ) {
		return null;
	}

	$schema = array(
		'@type'      => 'FAQPage',
		'mainEntity' => $questions,
	);

	return apply_filters( 'seo_generator_faq_schema', $schema, $post_id );
}

/**
 * Build BreadcrumbList schema for SEO page.
 *
 * Creates schema.org BreadcrumbList structured data with
 * Home > Topic > Page hierarchy.
 *
 * @param int $post_id Post ID.
 * @return array BreadcrumbList schema structure.
 */
function seo_generator_build_breadcrumb_schema( int $post_id ): array {
	$items = array();

	// Position 1: Home.
	$items[] = array(
		'@type'    => 'ListItem',
		'position' => 1,
		'name'     => 'Home',
		'item'     => esc_url( home_url( '/' ) ),
	);

	// Position 2: Topic (if assigned).
	$topics = get_the_terms( $post_id, 'seo-topic' );
	if ( $topics && ! is_wp_error( $topics ) ) {
		$topic       = $topics[0]; // Use first topic.
		$topic_url   = get_term_link( $topic );
		$items[]     = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => esc_html( $topic->name ),
			'item'     => esc_url( $topic_url ),
		);
		$position = 3;
	} else {
		$position = 2;
	}

	// Final position: Current page.
	$items[] = array(
		'@type'    => 'ListItem',
		'position' => $position,
		'name'     => esc_html( get_the_title( $post_id ) ),
		'item'     => esc_url( get_permalink( $post_id ) ),
	);

	$schema = array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	return apply_filters( 'seo_generator_breadcrumb_schema', $schema, $post_id );
}

/**
 * Output JSON-LD schema markup.
 *
 * Outputs combined schema.org structured data in JSON-LD format
 * for SEO pages. Includes Article, FAQPage (if applicable), and
 * BreadcrumbList schemas in a @graph structure.
 *
 * Optimized to retrieve taxonomy terms only once to avoid duplicate queries.
 *
 * @return void
 */
function seo_generator_output_schema(): void {
	// Only output on singular seo-page post type.
	if ( ! is_singular( 'seo-page' ) ) {
		return;
	}

	$post_id = get_the_ID();

	// Pre-fetch taxonomy terms once to avoid duplicate queries in breadcrumb schema.
	// This primes the WordPress term cache for subsequent calls.
	$topics = get_the_terms( $post_id, 'seo-topic' );

	// Build @graph array with all schemas.
	$graph = array();

	// Always add Article schema.
	$graph[] = seo_generator_build_article_schema( $post_id );

	// Add FAQPage schema if FAQ content exists.
	$faq_schema = seo_generator_build_faq_schema( $post_id );
	if ( $faq_schema ) {
		$graph[] = $faq_schema;
	}

	// Always add BreadcrumbList schema.
	$graph[] = seo_generator_build_breadcrumb_schema( $post_id );

	// Combine into complete schema structure.
	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	// Allow filtering of complete schema.
	$schema = apply_filters( 'seo_generator_complete_schema', $schema, $post_id );

	// Encode to JSON with proper formatting.
	$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

	// Output schema script tag.
	echo "\n<!-- JSON-LD Schema -->\n";
	echo '<script type="application/ld+json">' . "\n";
	echo $json . "\n";
	echo '</script>' . "\n";
}
