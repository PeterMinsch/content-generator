<?php
/**
 * Block Content Parser
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Parses AI-generated content for different block types.
 */
class BlockContentParser {
	/**
	 * Parse AI-generated content based on block type.
	 *
	 * @param string $block_type Block type identifier.
	 * @param string $raw_content Raw content from AI.
	 * @return array Parsed content ready for ACF field update.
	 * @throws \Exception If parsing fails.
	 */
	public function parse( string $block_type, string $raw_content ): array {
		// Try to parse as JSON first (most blocks use JSON format).
		$json_data = $this->tryParseJson( $raw_content );

		// Use block-specific parsing logic.
		return match ( $block_type ) {
			'seo_metadata' => $this->parseSeoMetadata( $json_data, $raw_content ),
			'hero' => $this->parseHero( $json_data, $raw_content ),
			'about_section' => $this->parseAboutSection( $json_data, $raw_content ),
			'serp_answer' => $this->parseSerpAnswer( $json_data, $raw_content ),
			'product_criteria' => $this->parseProductCriteria( $json_data, $raw_content ),
			'materials' => $this->parseMaterials( $json_data, $raw_content ),
			'process' => $this->parseProcess( $json_data, $raw_content ),
			'comparison' => $this->parseComparison( $json_data, $raw_content ),
			'product_showcase' => $this->parseProductShowcase( $json_data, $raw_content ),
			'size_fit' => $this->parseSizeFit( $json_data, $raw_content ),
			'care_warranty' => $this->parseCareWarranty( $json_data, $raw_content ),
			'ethics' => $this->parseEthics( $json_data, $raw_content ),
			'faqs' => $this->parseFaqs( $json_data, $raw_content ),
			'cta' => $this->parseCta( $json_data, $raw_content ),
			default => throw new \Exception( "Unknown block type: {$block_type}" ),
		};
	}

	/**
	 * Try to parse content as JSON.
	 *
	 * @param string $content Content to parse.
	 * @return array|null Parsed JSON or null if not valid JSON.
	 */
	private function tryParseJson( string $content ): ?array {
		error_log( '🔍 [JSON Parser] Starting parse attempt' );
		error_log( 'Raw content length: ' . strlen( $content ) );
		error_log( 'Raw content (first 200 chars): ' . substr( $content, 0, 200 ) );
		error_log( 'Raw content (last 200 chars): ' . substr( $content, -200 ) );

		// Extract JSON from markdown code blocks if present.
		if ( preg_match( '/```json\s*(.*?)\s*```/s', $content, $matches ) ) {
			error_log( '✅ Matched ```json code block' );
			$content = $matches[1];
			error_log( 'Extracted JSON length: ' . strlen( $content ) );
			error_log( 'Extracted JSON (first 200 chars): ' . substr( $content, 0, 200 ) );
		} elseif ( preg_match( '/```\s*(.*?)\s*```/s', $content, $matches ) ) {
			error_log( '✅ Matched generic ``` code block' );
			$content = $matches[1];
			error_log( 'Extracted content length: ' . strlen( $content ) );
			error_log( 'Extracted content (first 200 chars): ' . substr( $content, 0, 200 ) );
		} else {
			error_log( '⚠️  No code block markers found, treating as raw JSON' );
		}

		$decoded = json_decode( trim( $content ), true );

		if ( JSON_ERROR_NONE === json_last_error() ) {
			error_log( '✅ JSON decoded successfully' );
			error_log( 'Decoded keys: ' . implode( ', ', array_keys( $decoded ) ) );
			return $decoded;
		} else {
			error_log( '❌ JSON decode failed!' );
			error_log( 'JSON Error: ' . json_last_error_msg() );
			error_log( 'Content being decoded (first 500 chars): ' . substr( trim( $content ), 0, 500 ) );
			return null;
		}
	}

	/**
	 * Parse SEO metadata content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseSeoMetadata( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['focus_keyword'], $json['seo_title'], $json['meta_description'] ) ) {
			error_log( '[SEO Generator] SEO metadata parse failed. Raw content: ' . substr( $raw_content, 0, 500 ) );
			error_log( '[SEO Generator] SEO metadata parse failed. JSON data: ' . print_r( $json, true ) );
			throw new \Exception( 'Invalid SEO metadata format. Expected JSON with focus_keyword, seo_title, and meta_description.' );
		}

		// Get the post permalink for canonical URL.
		global $post;
		$canonical = '';
		if ( $post ) {
			$canonical = get_permalink( $post->ID );
		}

		return array(
			'seo_focus_keyword'    => sanitize_text_field( $json['focus_keyword'] ),
			'seo_title'            => sanitize_text_field( $json['seo_title'] ),
			'seo_meta_description' => sanitize_text_field( $json['meta_description'] ),
			'seo_canonical'        => $canonical ? esc_url_raw( $canonical ) : '',
		);
	}

	/**
	 * Parse hero section content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseHero( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['headline'], $json['subheadline'] ) ) {
			// Log the raw content for debugging.
			error_log( '[SEO Generator] Hero parse failed. Raw content: ' . substr( $raw_content, 0, 500 ) );
			error_log( '[SEO Generator] Hero parse failed. JSON data: ' . print_r( $json, true ) );
			throw new \Exception( 'Invalid hero content format. Expected JSON with headline and subheadline.' );
		}

		return array(
			'hero_title'    => sanitize_text_field( $json['headline'] ),
			'hero_subtitle' => sanitize_text_field( $json['subheadline'] ),
			'hero_summary'  => isset( $json['summary'] ) ? sanitize_textarea_field( $json['summary'] ) : '',
		);
	}

	/**
	 * Parse about section content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseAboutSection( ?array $json, string $raw_content ): array {
		// Only expect features array from AI (heading and description are hardcoded)
		if ( null === $json || ! isset( $json['features'] ) ) {
			error_log( '[SEO Generator] About section parse failed. Raw content: ' . substr( $raw_content, 0, 500 ) );
			error_log( '[SEO Generator] About section parse failed. JSON data: ' . print_r( $json, true ) );
			throw new \Exception( 'Invalid about section format. Expected JSON with features array.' );
		}

		// Parse AI-generated features
		$features = array();
		foreach ( $json['features'] as $item ) {
			if ( isset( $item['icon_type'], $item['title'], $item['description'] ) ) {
				$features[] = array(
					'icon_type'   => sanitize_text_field( $item['icon_type'] ),
					'title'       => sanitize_text_field( $item['title'] ),
					'description' => sanitize_text_field( $item['description'] ),
				);
			}
		}

		// Return hardcoded heading and description with AI-generated features
		return array(
			'about_heading'     => 'ABOUT BRAVO JEWELERS',
			'about_description' => 'Family-run and handcrafted in Carlsbad, Bravo Jewelers has over 25 years of experience serving San Diego County with timeless craftsmanship.',
			'about_features'    => $features,
		);
	}

	/**
	 * Parse SERP answer content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 */
	private function parseSerpAnswer( ?array $json, string $raw_content ): array {
		// SERP answer can be plain text or JSON.
		if ( $json && isset( $json['answer'] ) ) {
			$text = $json['answer'];
		} else {
			$text = trim( $raw_content );
		}

		// Extract heading and paragraph if structured.
		$heading = '';
		$paragraph = $text;
		$bullets = array();

		if ( $json && isset( $json['heading'] ) ) {
			$heading = $json['heading'];
		}
		if ( $json && isset( $json['paragraph'] ) ) {
			$paragraph = $json['paragraph'];
		}
		if ( $json && isset( $json['bullets'] ) && is_array( $json['bullets'] ) ) {
			foreach ( $json['bullets'] as $bullet ) {
				$bullets[] = array( 'bullet_text' => sanitize_text_field( $bullet ) );
			}
		}

		return array(
			'answer_heading'   => sanitize_text_field( $heading ),
			'answer_paragraph' => sanitize_textarea_field( $paragraph ),
			'answer_bullets'   => $bullets,
		);
	}

	/**
	 * Parse product criteria content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseProductCriteria( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['criteria'] ) || ! is_array( $json['criteria'] ) ) {
			throw new \Exception( 'Invalid product criteria format. Expected JSON with criteria array.' );
		}

		$criteria = array();
		foreach ( $json['criteria'] as $item ) {
			if ( isset( $item['title'], $item['explanation'] ) ) {
				$criteria[] = array(
					'name'        => sanitize_text_field( $item['title'] ),
					'explanation' => sanitize_textarea_field( $item['explanation'] ),
				);
			}
		}

		return array(
			'criteria_heading' => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'criteria_items'   => $criteria,
		);
	}

	/**
	 * Parse materials content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseMaterials( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['introduction'], $json['materials'] ) ) {
			error_log( '❌ [Materials Parser] Invalid JSON structure' );
			error_log( 'Raw content (first 500 chars): ' . substr( $raw_content, 0, 500 ) );
			error_log( 'Parsed JSON: ' . wp_json_encode( $json ) );
			error_log( 'Has introduction key: ' . ( isset( $json['introduction'] ) ? 'YES' : 'NO' ) );
			error_log( 'Has materials key: ' . ( isset( $json['materials'] ) ? 'YES' : 'NO' ) );
			throw new \Exception( 'Invalid materials format. Expected JSON with introduction and materials array.' );
		}

		$materials = array();
		foreach ( $json['materials'] as $item ) {
			if ( isset( $item['name'], $item['description'] ) ) {
				$materials[] = array(
					'material'      => sanitize_text_field( $item['name'] ),
					'pros'          => isset( $item['pros'] ) ? sanitize_textarea_field( $item['pros'] ) : '',
					'cons'          => isset( $item['cons'] ) ? sanitize_textarea_field( $item['cons'] ) : '',
					'best_for'      => isset( $item['best_for'] ) ? sanitize_text_field( $item['best_for'] ) : '',
					'allergy_notes' => isset( $item['allergy_notes'] ) ? sanitize_text_field( $item['allergy_notes'] ) : '',
					'care'          => isset( $item['care'] ) ? sanitize_text_field( $item['care'] ) : '',
				);
			}
		}

		return array(
			'materials_heading' => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'materials_items'   => $materials,
		);
	}

	/**
	 * Parse process content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseProcess( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['introduction'], $json['steps'] ) ) {
			error_log( '❌ [Process Parser] Invalid JSON structure' );
			error_log( 'Raw content (first 500 chars): ' . substr( $raw_content, 0, 500 ) );
			error_log( 'Parsed JSON: ' . wp_json_encode( $json ) );
			error_log( 'Has introduction key: ' . ( isset( $json['introduction'] ) ? 'YES' : 'NO' ) );
			error_log( 'Has steps key: ' . ( isset( $json['steps'] ) ? 'YES' : 'NO' ) );
			throw new \Exception( 'Invalid process format. Expected JSON with introduction and steps array.' );
		}

		$steps = array();
		foreach ( $json['steps'] as $item ) {
			if ( isset( $item['title'], $item['description'] ) ) {
				$steps[] = array(
					'step_title' => sanitize_text_field( $item['title'] ),
					'step_text'  => sanitize_textarea_field( $item['description'] ),
				);
			}
		}

		return array(
			'process_heading' => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'process_steps'   => $steps,
		);
	}

	/**
	 * Parse comparison content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseComparison( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['introduction'], $json['factors'], $json['options'] ) ) {
			throw new \Exception( 'Invalid comparison format. Expected JSON with introduction, factors, and options.' );
		}

		// Build comparison rows from factors and options.
		$rows = array();
		$left_label  = '';
		$right_label = '';

		if ( isset( $json['options'][0]['name'] ) ) {
			$left_label = $json['options'][0]['name'];
		}
		if ( isset( $json['options'][1]['name'] ) ) {
			$right_label = $json['options'][1]['name'];
		}

		foreach ( $json['factors'] as $index => $factor ) {
			$left_value  = $json['options'][0]['values'][ $index ] ?? '';
			$right_value = $json['options'][1]['values'][ $index ] ?? '';

			$rows[] = array(
				'attribute'  => sanitize_text_field( $factor ),
				'left_text'  => sanitize_text_field( $left_value ),
				'right_text' => sanitize_text_field( $right_value ),
			);
		}

		return array(
			'comparison_heading'     => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'comparison_left_label'  => sanitize_text_field( $left_label ),
			'comparison_right_label' => sanitize_text_field( $right_label ),
			'comparison_summary'     => sanitize_textarea_field( $json['introduction'] ),
			'comparison_rows'        => $rows,
		);
	}

	/**
	 * Parse product showcase content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseProductShowcase( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['introduction'], $json['products'] ) ) {
			throw new \Exception( 'Invalid product showcase format. Expected JSON with introduction and products array.' );
		}

		$products = array();
		foreach ( $json['products'] as $item ) {
			if ( isset( $item['name'] ) ) {
				$products[] = array(
					'product_sku'   => isset( $item['sku'] ) ? sanitize_text_field( $item['sku'] ) : '',
					'alt_image_url' => isset( $item['image_url'] ) ? esc_url_raw( $item['image_url'] ) : '',
				);
			}
		}

		return array(
			'showcase_heading'  => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'showcase_intro'    => sanitize_textarea_field( $json['introduction'] ),
			'showcase_products' => $products,
		);
	}

	/**
	 * Parse size and fit content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseSizeFit( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['introduction'], $json['tips'] ) ) {
			throw new \Exception( 'Invalid size & fit format. Expected JSON with introduction and tips array.' );
		}

		$comfort_notes = $json['introduction'];
		if ( isset( $json['comfort_notes'] ) ) {
			$comfort_notes = $json['comfort_notes'];
		}

		return array(
			'size_heading'      => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'comfort_fit_notes' => sanitize_textarea_field( $comfort_notes ),
		);
	}

	/**
	 * Parse care and warranty content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseCareWarranty( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['care'], $json['warranty'] ) ) {
			throw new \Exception( 'Invalid care & warranty format. Expected JSON with care and warranty sections.' );
		}

		$care_bullets = array();
		if ( isset( $json['care']['tips'] ) && is_array( $json['care']['tips'] ) ) {
			foreach ( $json['care']['tips'] as $tip ) {
				$care_bullets[] = array(
					'bullet' => sanitize_textarea_field( $tip ),
				);
			}
		}

		return array(
			'care_heading'     => isset( $json['care']['heading'] ) ? sanitize_text_field( $json['care']['heading'] ) : '',
			'care_bullets'     => $care_bullets,
			'warranty_heading' => isset( $json['warranty']['heading'] ) ? sanitize_text_field( $json['warranty']['heading'] ) : '',
			'warranty_text'    => isset( $json['warranty']['information'] ) ? sanitize_textarea_field( $json['warranty']['information'] ) : '',
		);
	}

	/**
	 * Parse ethics and origin content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseEthics( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['introduction'], $json['aspects'] ) ) {
			throw new \Exception( 'Invalid ethics format. Expected JSON with introduction and aspects array.' );
		}

		$certifications = array();
		if ( isset( $json['certifications'] ) && is_array( $json['certifications'] ) ) {
			foreach ( $json['certifications'] as $cert ) {
				if ( isset( $cert['name'] ) ) {
					$certifications[] = array(
						'cert_name' => sanitize_text_field( $cert['name'] ),
						'cert_link' => isset( $cert['link'] ) ? esc_url_raw( $cert['link'] ) : '',
					);
				}
			}
		}

		return array(
			'ethics_heading'  => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'ethics_text'     => sanitize_textarea_field( $json['introduction'] ),
			'certifications'  => $certifications,
		);
	}

	/**
	 * Parse FAQs content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseFaqs( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['faqs'] ) || ! is_array( $json['faqs'] ) ) {
			error_log( '❌ [FAQs Parser] Invalid JSON structure' );
			error_log( 'Raw content (first 500 chars): ' . substr( $raw_content, 0, 500 ) );
			error_log( 'Parsed JSON: ' . wp_json_encode( $json ) );
			error_log( 'Has faqs key: ' . ( isset( $json['faqs'] ) ? 'YES' : 'NO' ) );
			error_log( 'Is faqs an array: ' . ( isset( $json['faqs'] ) && is_array( $json['faqs'] ) ? 'YES' : 'NO' ) );
			throw new \Exception( 'Invalid FAQs format. Expected JSON with faqs array.' );
		}

		$faqs = array();
		foreach ( $json['faqs'] as $item ) {
			if ( isset( $item['question'], $item['answer'] ) ) {
				$faqs[] = array(
					'question' => sanitize_text_field( $item['question'] ),
					'answer'   => sanitize_textarea_field( $item['answer'] ),
				);
			}
		}

		return array(
			'faqs_heading' => isset( $json['heading'] ) ? sanitize_text_field( $json['heading'] ) : '',
			'faq_items'    => $faqs,
		);
	}

	/**
	 * Parse CTA content.
	 *
	 * @param array|null $json JSON data.
	 * @param string     $raw_content Raw content.
	 * @return array Parsed content.
	 * @throws \Exception If parsing fails.
	 */
	private function parseCta( ?array $json, string $raw_content ): array {
		if ( null === $json || ! isset( $json['heading'], $json['body'] ) ) {
			throw new \Exception( 'Invalid CTA format. Expected JSON with heading and body.' );
		}

		return array(
			'cta_heading'         => sanitize_text_field( $json['heading'] ),
			'cta_text'            => sanitize_textarea_field( $json['body'] ),
			'cta_primary_label'   => isset( $json['primary_label'] ) ? sanitize_text_field( $json['primary_label'] ) : '',
			'cta_primary_url'     => isset( $json['primary_url'] ) ? esc_url_raw( $json['primary_url'] ) : '',
			'cta_secondary_label' => isset( $json['secondary_label'] ) ? sanitize_text_field( $json['secondary_label'] ) : '',
			'cta_secondary_url'   => isset( $json['secondary_url'] ) ? esc_url_raw( $json['secondary_url'] ) : '',
		);
	}
}
