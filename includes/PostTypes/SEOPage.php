<?php
/**
 * SEO Page Custom Post Type
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * SEO Page Custom Post Type Class
 */
class SEOPage {
	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'seo-page';

	/**
	 * Register the custom post type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = array(
			'name'                  => _x( 'SEO Pages', 'Post Type General Name', 'seo-generator' ),
			'singular_name'         => _x( 'SEO Page', 'Post Type Singular Name', 'seo-generator' ),
			'menu_name'             => __( 'SEO Pages', 'seo-generator' ),
			'name_admin_bar'        => __( 'SEO Page', 'seo-generator' ),
			'archives'              => __( 'SEO Page Archives', 'seo-generator' ),
			'attributes'            => __( 'SEO Page Attributes', 'seo-generator' ),
			'parent_item_colon'     => __( 'Parent SEO Page:', 'seo-generator' ),
			'all_items'             => __( 'All SEO Pages', 'seo-generator' ),
			'add_new_item'          => __( 'Add New SEO Page', 'seo-generator' ),
			'add_new'               => __( 'Add New', 'seo-generator' ),
			'new_item'              => __( 'New SEO Page', 'seo-generator' ),
			'edit_item'             => __( 'Edit SEO Page', 'seo-generator' ),
			'update_item'           => __( 'Update SEO Page', 'seo-generator' ),
			'view_item'             => __( 'View SEO Page', 'seo-generator' ),
			'view_items'            => __( 'View SEO Pages', 'seo-generator' ),
			'search_items'          => __( 'Search SEO Page', 'seo-generator' ),
			'not_found'             => __( 'Not found', 'seo-generator' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'seo-generator' ),
			'featured_image'        => __( 'Featured Image', 'seo-generator' ),
			'set_featured_image'    => __( 'Set featured image', 'seo-generator' ),
			'remove_featured_image' => __( 'Remove featured image', 'seo-generator' ),
			'use_featured_image'    => __( 'Use as featured image', 'seo-generator' ),
			'insert_into_item'      => __( 'Insert into SEO page', 'seo-generator' ),
			'uploaded_to_this_item' => __( 'Uploaded to this SEO page', 'seo-generator' ),
			'items_list'            => __( 'SEO Pages list', 'seo-generator' ),
			'items_list_navigation' => __( 'SEO Pages list navigation', 'seo-generator' ),
			'filter_items_list'     => __( 'Filter SEO pages list', 'seo-generator' ),
		);

		$args = array(
			'label'               => __( 'SEO Page', 'seo-generator' ),
			'description'         => __( 'AI-generated SEO content pages', 'seo-generator' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 30,
			'menu_icon'           => 'dashicons-admin-page',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true, // Enable block editor (Gutenberg)
			'rest_base'           => 'seo-pages',
			'template'            => array(
				// Hero Section with 2 columns
				array( 'core/columns', array(
					'className' => 'hero-section',
				), array(
					// Left column - Text content
					array( 'core/column', array( 'width' => '50%' ), array(
						array( 'core/heading', array(
							'level'       => 1,
							'placeholder' => 'Enter your hero title here...',
							'content'     => 'Exploring Unique Wide Band Diamond Rings',
						) ),
						array( 'core/paragraph', array(
							'placeholder' => 'Enter subtitle...',
							'content'     => 'In a world where the delicate and the dainty often take center stage, the allure of wide band diamond rings offers a refreshing deviation—a bold statement of elegance and individuality.',
						) ),
						array( 'core/paragraph', array(
							'placeholder' => 'Enter description...',
							'content'     => 'These rings, with their generous bands and captivating diamonds, do more than adorn a finger; they tell a story. A story of craftsmanship, tradition, and personal expression that transcends time and fashion.',
						) ),
					) ),
					// Right column - Image
					array( 'core/column', array( 'width' => '50%' ), array(
						array( 'core/image', array(
							'align' => 'center',
						) ),
					) ),
				) ),
			),
			'template_lock'       => false, // Allow full editing - users can add/remove/rearrange blocks
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
