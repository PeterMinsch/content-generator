<?php
/**
 * ACF Field Groups Registration
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\ACF;

defined( 'ABSPATH' ) || exit;

/**
 * Registers all ACF field groups for SEO Pages.
 */
class FieldGroups {
	/**
	 * Register all field groups.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->registerContentBlocksFieldGroup();
		$this->registerSEOMetaFieldGroup();
		$this->disableACFValidationForBlockEditor();
	}

	/**
	 * Disable ACF validation when using Block Editor.
	 *
	 * Since we're using Block Editor for content (not ACF fields),
	 * we don't want ACF validation to block publishing.
	 *
	 * @return void
	 */
	private function disableACFValidationForBlockEditor(): void {
		add_filter(
			'acf/validate_save_post',
			function () {
				// Get current post type
				$post_id = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;
				if ($post_id) {
					$post_type = get_post_type($post_id);
					if ($post_type === 'seo-page') {
						// Remove all ACF validation errors for seo-page
						acf_reset_validation_errors();
					}
				}
			},
			999
		);
	}

	/**
	 * Register the main "SEO Page Content Blocks" field group with all 12 blocks.
	 *
	 * Uses BlockDefinitionParser to load blocks from config file.
	 *
	 * @return void
	 */
	private function registerContentBlocksFieldGroup(): void {
		$parser = new BlockDefinitionParser();

		acf_add_local_field_group(
			array(
				'key'      => 'group_seo_page_content_blocks',
				'title'    => 'SEO Page Content Blocks (Hidden - Use React UI Above)',
				'fields'   => $parser->convertAllBlocksToACFFields(),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'seo-page',
						),
					),
				),
				'position'           => 'normal',
				'style'              => 'seamless',
				'hide_on_screen'     => array(),
				'label_placement'    => 'top',
				'instruction_placement' => 'label',
			)
		);

		// Hide this field group using CSS instead of disabling it
		add_action(
			'admin_head',
			function () {
				global $post;
				if ( $post && 'seo-page' === $post->post_type ) {
					?>
					<style>
						/* Hide the native ACF fields - we use React UI instead */
						.acf-field-group[data-key="group_seo_page_content_blocks"] {
							display: none !important;
						}
					</style>
					<?php
				}
			}
		);
	}

	/**
	 * Register the "SEO Meta Fields" field group.
	 *
	 * @return void
	 */
	private function registerSEOMetaFieldGroup(): void {
		acf_add_local_field_group(
			array(
				'key'      => 'group_seo_meta_fields',
				'title'    => 'SEO Meta Fields',
				'fields'   => array(
					array(
						'key'   => 'field_seo_focus_keyword',
						'label' => 'Focus Keyword',
						'name'  => 'seo_focus_keyword',
						'type'  => 'text',
					),
					array(
						'key'           => 'field_seo_title',
						'label'         => 'SEO Title',
						'name'          => 'seo_title',
						'type'          => 'text',
						'maxlength'     => 65,
						'instructions'  => 'Max 65 characters for optimal display in search results.',
					),
					array(
						'key'           => 'field_seo_meta_description',
						'label'         => 'Meta Description',
						'name'          => 'seo_meta_description',
						'type'          => 'textarea',
						'maxlength'     => 165,
						'rows'          => 3,
						'instructions'  => 'Max 165 characters for optimal display in search results.',
					),
					array(
						'key'   => 'field_seo_canonical',
						'label' => 'Canonical URL',
						'name'  => 'seo_canonical',
						'type'  => 'url',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'seo-page',
						),
					),
				),
				'position' => 'side',
				'style'    => 'default',
			)
		);
	}
}
