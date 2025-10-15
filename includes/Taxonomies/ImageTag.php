<?php
/**
 * Image Tag Taxonomy
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Image Tag Taxonomy Class
 */
class ImageTag {
	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	public const TAXONOMY = 'image_tag';

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'                       => _x( 'Image Tags', 'Taxonomy General Name', 'seo-generator' ),
			'singular_name'              => _x( 'Image Tag', 'Taxonomy Singular Name', 'seo-generator' ),
			'menu_name'                  => __( 'Image Tags', 'seo-generator' ),
			'all_items'                  => __( 'All Tags', 'seo-generator' ),
			'parent_item'                => __( 'Parent Tag', 'seo-generator' ),
			'parent_item_colon'          => __( 'Parent Tag:', 'seo-generator' ),
			'new_item_name'              => __( 'New Tag Name', 'seo-generator' ),
			'add_new_item'               => __( 'Add New Tag', 'seo-generator' ),
			'edit_item'                  => __( 'Edit Tag', 'seo-generator' ),
			'update_item'                => __( 'Update Tag', 'seo-generator' ),
			'view_item'                  => __( 'View Tag', 'seo-generator' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'seo-generator' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'seo-generator' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'seo-generator' ),
			'popular_items'              => __( 'Popular Tags', 'seo-generator' ),
			'search_items'               => __( 'Search Tags', 'seo-generator' ),
			'not_found'                  => __( 'Not Found', 'seo-generator' ),
			'no_terms'                   => __( 'No tags', 'seo-generator' ),
			'items_list'                 => __( 'Tags list', 'seo-generator' ),
			'items_list_navigation'      => __( 'Tags list navigation', 'seo-generator' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => true,
			'rest_base'         => 'image-tags',
		);

		register_taxonomy( self::TAXONOMY, array( 'attachment' ), $args );
	}
}
