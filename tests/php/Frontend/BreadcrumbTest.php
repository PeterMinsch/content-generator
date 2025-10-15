<?php
/**
 * Breadcrumb Navigation Tests
 *
 * Tests for breadcrumb navigation functionality.
 *
 * @package SEOGenerator
 * @subpackage Tests
 */

namespace SEOGenerator\Tests\Frontend;

use WP_UnitTestCase;
use WP_Term;

/**
 * Test breadcrumb navigation functionality.
 */
class BreadcrumbTest extends WP_UnitTestCase {

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Test topic term.
	 *
	 * @var WP_Term
	 */
	private $topic_term;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test SEO page.
		$this->post_id = $this->factory->post->create(
			array(
				'post_type'   => 'seo-page',
				'post_title'  => 'Platinum Wedding Bands',
				'post_status' => 'publish',
			)
		);

		// Create topic term.
		$term             = $this->factory->term->create(
			array(
				'taxonomy' => 'seo-topic',
				'name'     => 'Wedding Bands',
			)
		);
		$this->topic_term = get_term( $term, 'seo-topic' );
	}

	/**
	 * Test breadcrumb function exists.
	 *
	 * @return void
	 */
	public function test_breadcrumb_function_exists() {
		$this->assertTrue( function_exists( 'seo_generator_breadcrumbs' ) );
	}

	/**
	 * Test breadcrumb output with topic assigned.
	 *
	 * @return void
	 */
	public function test_breadcrumb_output_with_topic() {
		wp_set_object_terms( $this->post_id, array( $this->topic_term->term_id ), 'seo-topic' );
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Verify structure.
		$this->assertStringContainsString( '<nav aria-label="Breadcrumb"', $output );
		$this->assertStringContainsString( '<ol class="breadcrumbs">', $output );
		$this->assertStringContainsString( 'Home', $output );
		$this->assertStringContainsString( 'Wedding Bands', $output );
		$this->assertStringContainsString( 'Platinum Wedding Bands', $output );
	}

	/**
	 * Test breadcrumb output without topic assigned.
	 *
	 * @return void
	 */
	public function test_breadcrumb_output_without_topic() {
		// Don't assign topic.
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Should have Home and Page, but not Topic.
		$this->assertStringContainsString( 'Home', $output );
		$this->assertStringContainsString( 'Platinum Wedding Bands', $output );
		$this->assertStringNotContainsString( 'Wedding Bands', $output ); // Topic not assigned.
	}

	/**
	 * Test breadcrumb output with multiple topics (uses first).
	 *
	 * @return void
	 */
	public function test_breadcrumb_output_with_multiple_topics() {
		// Create second topic.
		$term2 = $this->factory->term->create(
			array(
				'taxonomy' => 'seo-topic',
				'name'     => 'Rings',
			)
		);

		// Assign both topics.
		wp_set_object_terms( $this->post_id, array( $this->topic_term->term_id, $term2 ), 'seo-topic' );
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Should use first topic only.
		$this->assertStringContainsString( 'Wedding Bands', $output );
		$this->assertStringNotContainsString( 'Rings', $output );
	}

	/**
	 * Test breadcrumb Home link is present.
	 *
	 * @return void
	 */
	public function test_breadcrumb_home_link_is_present() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$home_url = home_url( '/' );
		$this->assertStringContainsString( esc_url( $home_url ), $output );
		$this->assertStringContainsString( '>Home</a>', $output );
	}

	/**
	 * Test breadcrumb Topic link is present when topic assigned.
	 *
	 * @return void
	 */
	public function test_breadcrumb_topic_link_is_present() {
		wp_set_object_terms( $this->post_id, array( $this->topic_term->term_id ), 'seo-topic' );
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$topic_url = get_term_link( $this->topic_term );
		$this->assertStringContainsString( esc_url( $topic_url ), $output );
		$this->assertStringContainsString( '>Wedding Bands</a>', $output );
	}

	/**
	 * Test breadcrumb current page is plain text (no link).
	 *
	 * @return void
	 */
	public function test_breadcrumb_current_page_is_plain_text() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Current page should be in <span>, not <a>.
		$this->assertStringContainsString( '<span>Platinum Wedding Bands</span>', $output );

		// Should NOT be a link to itself.
		$page_url = get_permalink( $this->post_id );
		$this->assertStringNotContainsString( '<a href="' . esc_url( $page_url ) . '">Platinum Wedding Bands</a>', $output );
	}

	/**
	 * Test breadcrumb HTML structure is semantic.
	 *
	 * @return void
	 */
	public function test_breadcrumb_html_structure_is_semantic() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Check for semantic HTML.
		$this->assertStringContainsString( '<nav', $output );
		$this->assertStringContainsString( '<ol', $output );
		$this->assertStringContainsString( '<li', $output );
		$this->assertStringContainsString( '</nav>', $output );
		$this->assertStringContainsString( '</ol>', $output );
		$this->assertStringContainsString( '</li>', $output );
	}

	/**
	 * Test breadcrumb aria-label is present.
	 *
	 * @return void
	 */
	public function test_breadcrumb_aria_label_is_present() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'aria-label="Breadcrumb"', $output );
	}

	/**
	 * Test breadcrumb aria-current on last item.
	 *
	 * @return void
	 */
	public function test_breadcrumb_aria_current_on_last_item() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'aria-current="page"', $output );
	}

	/**
	 * Test breadcrumb escaping with special characters.
	 *
	 * @return void
	 */
	public function test_breadcrumb_escaping_with_special_characters() {
		// Update post title with special characters.
		wp_update_post(
			array(
				'ID'         => $this->post_id,
				'post_title' => 'Test "Quoted" & Ampersand\'s Title',
			)
		);

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// esc_html will encode special characters.
		$this->assertStringContainsString( 'Test &quot;Quoted&quot; &amp; Ampersand&#039;s Title', $output );
	}

	/**
	 * Test breadcrumb filter hook for home label.
	 *
	 * @return void
	 */
	public function test_breadcrumb_filter_hook_home_label() {
		add_filter(
			'seo_generator_breadcrumb_home_label',
			function ( $label ) {
				return 'Custom Home';
			}
		);

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Custom Home', $output );
		$this->assertStringNotContainsString( '>Home<', $output );
	}

	/**
	 * Test breadcrumb filter hook for items array.
	 *
	 * @return void
	 */
	public function test_breadcrumb_filter_hook_items_array() {
		add_filter(
			'seo_generator_breadcrumb_items',
			function ( $items, $post_id ) {
				// Add custom item.
				$items[] = array(
					'name' => 'Custom Item',
					'url'  => 'https://example.com/custom',
				);
				return $items;
			},
			10,
			2
		);

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Custom Item', $output );
	}

	/**
	 * Test breadcrumb filter hook for complete HTML output.
	 *
	 * @return void
	 */
	public function test_breadcrumb_filter_hook_html_output() {
		add_filter(
			'seo_generator_breadcrumb_html',
			function ( $output, $post_id ) {
				return '<!-- Modified by filter -->' . $output;
			},
			10,
			2
		);

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<!-- Modified by filter -->', $output );
	}

	/**
	 * Test breadcrumb does not output on non-seo-page post types.
	 *
	 * @return void
	 */
	public function test_breadcrumb_does_not_output_on_regular_post() {
		// Create regular post.
		$regular_post_id = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		$this->go_to( get_permalink( $regular_post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertEmpty( $output ); // No breadcrumbs for regular posts.
	}

	/**
	 * Test breadcrumb CSS classes are present.
	 *
	 * @return void
	 */
	public function test_breadcrumb_css_classes_are_present() {
		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'class="breadcrumb-nav"', $output );
		$this->assertStringContainsString( 'class="breadcrumbs"', $output );
		$this->assertStringContainsString( 'class="breadcrumb-item"', $output );
		$this->assertStringContainsString( 'class="breadcrumb-link"', $output );
	}

	/**
	 * Test breadcrumb handles WP_Error from get_term_link gracefully.
	 *
	 * @return void
	 */
	public function test_breadcrumb_handles_invalid_term_link() {
		// Create a topic but delete it immediately to cause WP_Error.
		$term_id = $this->factory->term->create(
			array(
				'taxonomy' => 'seo-topic',
				'name'     => 'Test Topic',
			)
		);

		wp_set_object_terms( $this->post_id, array( $term_id ), 'seo-topic' );

		// Delete the term to cause get_term_link to return WP_Error.
		wp_delete_term( $term_id, 'seo-topic' );

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Should still output breadcrumbs without the topic.
		$this->assertStringContainsString( 'Home', $output );
		$this->assertStringContainsString( 'Platinum Wedding Bands', $output );
	}

	/**
	 * Test breadcrumb with very long page title.
	 *
	 * @return void
	 */
	public function test_breadcrumb_with_long_page_title() {
		// Update post with very long title.
		wp_update_post(
			array(
				'ID'         => $this->post_id,
				'post_title' => 'This is a very long page title that might wrap on mobile devices and should still display correctly in the breadcrumb navigation',
			)
		);

		$this->go_to( get_permalink( $this->post_id ) );

		ob_start();
		seo_generator_breadcrumbs();
		$output = ob_get_clean();

		// Should contain the full title.
		$this->assertStringContainsString( 'This is a very long page title', $output );
	}
}
