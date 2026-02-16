<?php
/**
 * Prompt Template Engine
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Services;

use SEOGenerator\Data\DefaultPrompts;

defined( 'ABSPATH' ) || exit;

/**
 * Manages prompt templates with variable substitution and caching.
 */
class PromptTemplateEngine {
	/**
	 * Option name for custom templates.
	 */
	private const OPTION_NAME = 'seo_generator_prompt_templates';

	/**
	 * Cache group for templates.
	 */
	private const CACHE_GROUP = 'seo_generator';

	/**
	 * Cache expiration time (1 hour).
	 */
	private const CACHE_EXPIRATION = HOUR_IN_SECONDS;

	/**
	 * Render prompt with variable substitution.
	 *
	 * @param string $block_type Block type identifier.
	 * @param array  $context Context variables for substitution.
	 * @return array Rendered template with system and user messages.
	 * @throws \InvalidArgumentException If template not found.
	 */
	public function renderPrompt( string $block_type, array $context ): array {
		$template = $this->getTemplate( $block_type );

		if ( null === $template ) {
			throw new \InvalidArgumentException( "Template not found for block type: {$block_type}" );
		}

		// Define variable substitutions.
		// Fallback business_type to "content" if empty (used in system message).
		$business_type = ! empty( $context['business_type'] ) ? $context['business_type'] : 'content';

		$variables = array(
			'{page_title}'          => $context['page_title'] ?? '',
			'{page_topic}'          => $context['page_topic'] ?? '',
			'{focus_keyword}'       => $context['focus_keyword'] ?? '',
			'{page_type}'           => $context['page_type'] ?? '',
			'{business_name}'       => $context['business_name'] ?? '',
			'{business_type}'       => $business_type,
			'{business_description}' => $context['business_description'] ?? '',
			'{business_address}'    => $context['business_address'] ?? '',
			'{service_area}'        => $context['service_area'] ?? '',
			'{business_phone}'      => $context['business_phone'] ?? '',
			'{business_email}'      => $context['business_email'] ?? '',
			'{business_url}'        => $context['business_url'] ?? '',
			'{years_in_business}'   => $context['years_in_business'] ?? '',
			'{usps}'                => $context['usps'] ?? '',
			'{certifications}'      => $context['certifications'] ?? '',
		);

		// Perform substitution on both system and user messages.
		return array(
			'system' => str_replace( array_keys( $variables ), array_values( $variables ), $template['system'] ),
			'user'   => str_replace( array_keys( $variables ), array_values( $variables ), $template['user'] ),
		);
	}

	/**
	 * Get template for a block type.
	 *
	 * Retrieves custom template from database, falling back to default template.
	 * Templates are cached for 1 hour.
	 *
	 * @param string $block_type Block type identifier.
	 * @return array|null Template with system and user messages, or null if not found.
	 */
	public function getTemplate( string $block_type ): ?array {
		// Try to get from cache first.
		$cache_key = $this->getCacheKey( $block_type );
		$template  = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $template ) {
			return $template;
		}

		// Get custom templates from database.
		$custom_templates = get_option( self::OPTION_NAME, array() );

		// Use custom template if exists, otherwise use default.
		if ( isset( $custom_templates[ $block_type ] ) && is_array( $custom_templates[ $block_type ] ) ) {
			$template = $custom_templates[ $block_type ];
		} else {
			$template = DefaultPrompts::get( $block_type );
		}

		// Cache the template.
		if ( null !== $template ) {
			wp_cache_set( $cache_key, $template, self::CACHE_GROUP, self::CACHE_EXPIRATION );
		}

		return $template;
	}

	/**
	 * Update custom template for a block type.
	 *
	 * @param string $block_type Block type identifier.
	 * @param array  $template Template with system and user messages.
	 * @return bool True on success, false on failure.
	 * @throws \InvalidArgumentException If template validation fails.
	 */
	public function updateTemplate( string $block_type, array $template ): bool {
		// Validate template.
		$validation_errors = $this->validateTemplate( $template, $block_type );

		if ( ! empty( $validation_errors ) ) {
			throw new \InvalidArgumentException(
				'Template validation failed: ' . implode( ', ', $validation_errors )
			);
		}

		// Get existing custom templates.
		$custom_templates = get_option( self::OPTION_NAME, array() );

		// Update template.
		$custom_templates[ $block_type ] = $template;

		// Save to database.
		$success = update_option( self::OPTION_NAME, $custom_templates );

		if ( $success ) {
			// Clear cache.
			$this->clearCache( $block_type );
		}

		return $success;
	}

	/**
	 * Reset template to default for a block type.
	 *
	 * Removes custom template and restores default.
	 *
	 * @param string $block_type Block type identifier.
	 * @return bool True if custom template was removed, false if no custom template existed.
	 */
	public function resetTemplate( string $block_type ): bool {
		// Get existing custom templates.
		$custom_templates = get_option( self::OPTION_NAME, array() );

		// Check if custom template exists.
		if ( ! isset( $custom_templates[ $block_type ] ) ) {
			return false;
		}

		// Remove custom template.
		unset( $custom_templates[ $block_type ] );

		// Save to database.
		update_option( self::OPTION_NAME, $custom_templates );

		// Clear cache.
		$this->clearCache( $block_type );

		return true;
	}

	/**
	 * Build context from post data.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $additional_context Additional context to merge.
	 * @return array Context array with page data.
	 */
	public function buildContext( int $post_id, array $additional_context = array() ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return $additional_context;
		}

		// Get topic from taxonomy.
		$topic_terms = wp_get_object_terms( $post_id, 'seo-topic' );
		$page_topic  = '';

		if ( ! is_wp_error( $topic_terms ) && ! empty( $topic_terms ) ) {
			$page_topic = $topic_terms[0]->name;
		}

		// Get focus keyword from ACF field.
		$focus_keyword = '';
		if ( function_exists( 'get_field' ) ) {
			$focus_keyword = get_field( 'seo_focus_keyword', $post_id );
		}

		// Fallback: Use page title as focus keyword if not set.
		if ( empty( $focus_keyword ) ) {
			$focus_keyword = $post->post_title;
		}

		// Build base context.
		$context = array(
			'page_title'    => $post->post_title,
			'page_topic'    => $page_topic,
			'focus_keyword' => $focus_keyword,
			'page_type'     => $this->inferPageType( $topic_terms ),
		);

		// Merge business settings from Default Content tab.
		$settings        = get_option( 'seo_generator_settings', array() );
		$business_fields = array(
			'business_name', 'business_type', 'business_description',
			'business_address', 'service_area', 'business_phone',
			'business_email', 'business_url', 'years_in_business',
		);
		foreach ( $business_fields as $field ) {
			$context[ $field ] = $settings[ $field ] ?? '';
		}

		// USPs and certifications as comma-separated strings.
		$usps_raw = $settings['usps'] ?? '';
		$context['usps'] = ! empty( $usps_raw )
			? implode( ', ', array_filter( array_map( 'trim', explode( "\n", $usps_raw ) ) ) )
			: '';

		$certs_raw = $settings['certifications'] ?? '';
		$context['certifications'] = ! empty( $certs_raw )
			? implode( ', ', array_filter( array_map( 'trim', explode( "\n", $certs_raw ) ) ) )
			: '';

		// Merge with additional context (additional_context takes priority).
		return array_merge( $context, $additional_context );
	}

	/**
	 * Validate template structure and content.
	 *
	 * @param array  $template Template to validate.
	 * @param string $block_type Block type for context.
	 * @return array Array of validation error messages (empty if valid).
	 */
	public function validateTemplate( array $template, string $block_type ): array {
		$errors = array();

		// Check for required keys.
		if ( ! isset( $template['system'] ) ) {
			$errors[] = 'Template missing "system" message';
		}

		if ( ! isset( $template['user'] ) ) {
			$errors[] = 'Template missing "user" message';
		}

		// Check that messages are not empty.
		if ( isset( $template['system'] ) && empty( trim( $template['system'] ) ) ) {
			$errors[] = 'System message cannot be empty';
		}

		if ( isset( $template['user'] ) && empty( trim( $template['user'] ) ) ) {
			$errors[] = 'User message cannot be empty';
		}

		// Check that messages are strings.
		if ( isset( $template['system'] ) && ! is_string( $template['system'] ) ) {
			$errors[] = 'System message must be a string';
		}

		if ( isset( $template['user'] ) && ! is_string( $template['user'] ) ) {
			$errors[] = 'User message must be a string';
		}

		return $errors;
	}

	/**
	 * Infer page type from topic taxonomy terms.
	 *
	 * @param array|\WP_Error $topic_terms Topic terms.
	 * @return string Inferred page type.
	 */
	private function inferPageType( $topic_terms ): string {
		if ( is_wp_error( $topic_terms ) || empty( $topic_terms ) ) {
			return 'general';
		}

		$topic = strtolower( $topic_terms[0]->name );

		if ( strpos( $topic, 'comparison' ) !== false ) {
			return 'comparison';
		}

		if ( strpos( $topic, 'education' ) !== false ) {
			return 'education';
		}

		return 'collection';
	}

	/**
	 * Get cache key for a block type.
	 *
	 * @param string $block_type Block type identifier.
	 * @return string Cache key.
	 */
	private function getCacheKey( string $block_type ): string {
		return 'prompt_' . $block_type;
	}

	/**
	 * Clear cache for a block type.
	 *
	 * @param string $block_type Block type identifier.
	 * @return void
	 */
	private function clearCache( string $block_type ): void {
		$cache_key = $this->getCacheKey( $block_type );
		wp_cache_delete( $cache_key, self::CACHE_GROUP );
	}

	/**
	 * Get all available block types.
	 *
	 * @return array Array of block type identifiers.
	 */
	public function getAvailableBlockTypes(): array {
		return array_keys( DefaultPrompts::getAll() );
	}
}
