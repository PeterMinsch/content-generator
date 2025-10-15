<?php
/**
 * SEO Topic Taxonomy
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * SEO Topic Taxonomy Class
 */
class SEOTopic {
	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	public const TAXONOMY = 'seo-topic';

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'                       => _x( 'SEO Topics', 'Taxonomy General Name', 'seo-generator' ),
			'singular_name'              => _x( 'SEO Topic', 'Taxonomy Singular Name', 'seo-generator' ),
			'menu_name'                  => __( 'SEO Topics', 'seo-generator' ),
			'all_items'                  => __( 'All Topics', 'seo-generator' ),
			'parent_item'                => __( 'Parent Topic', 'seo-generator' ),
			'parent_item_colon'          => __( 'Parent Topic:', 'seo-generator' ),
			'new_item_name'              => __( 'New Topic Name', 'seo-generator' ),
			'add_new_item'               => __( 'Add New Topic', 'seo-generator' ),
			'edit_item'                  => __( 'Edit Topic', 'seo-generator' ),
			'update_item'                => __( 'Update Topic', 'seo-generator' ),
			'view_item'                  => __( 'View Topic', 'seo-generator' ),
			'separate_items_with_commas' => __( 'Separate topics with commas', 'seo-generator' ),
			'add_or_remove_items'        => __( 'Add or remove topics', 'seo-generator' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'seo-generator' ),
			'popular_items'              => __( 'Popular Topics', 'seo-generator' ),
			'search_items'               => __( 'Search Topics', 'seo-generator' ),
			'not_found'                  => __( 'Not Found', 'seo-generator' ),
			'no_terms'                   => __( 'No topics', 'seo-generator' ),
			'items_list'                 => __( 'Topics list', 'seo-generator' ),
			'items_list_navigation'      => __( 'Topics list navigation', 'seo-generator' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'seo-topics',
		);

		register_taxonomy( self::TAXONOMY, array( 'seo-page' ), $args );
	}
}
