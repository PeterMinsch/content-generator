<?php
/**
 * Integration Tests for ACF Field Groups
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Tests\Integration;

use SEOGenerator\ACF\FieldGroups;
use SEOGenerator\PostTypes\SEOPage;
use WP_UnitTestCase;

/**
 * ACF Fields integration test case.
 */
class ACFFieldsTest extends WP_UnitTestCase {
	/**
	 * Set up before tests.
	 */
	public function setUp(): void {
		parent::setUp();

		// Register post type.
		$seo_page = new SEOPage();
		$seo_page->register();

		// Register ACF field groups.
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			$field_groups = new FieldGroups();
			$field_groups->register();
		}
	}

	/**
	 * Test that ACF field groups are registered.
	 */
	public function test_field_groups_are_registered() {
		if ( ! function_exists( 'acf_get_field_group' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		// Test content blocks field group.
		$content_blocks = acf_get_field_group( 'group_seo_page_content_blocks' );
		$this->assertNotFalse( $content_blocks, 'Content Blocks field group should be registered' );
		$this->assertEquals( 'SEO Page Content Blocks', $content_blocks['title'] );

		// Test SEO meta field group.
		$seo_meta = acf_get_field_group( 'group_seo_meta_fields' );
		$this->assertNotFalse( $seo_meta, 'SEO Meta Fields group should be registered' );
		$this->assertEquals( 'SEO Meta Fields', $seo_meta['title'] );
	}

	/**
	 * Test that field groups have correct location rules.
	 */
	public function test_field_groups_location_rules() {
		if ( ! function_exists( 'acf_get_field_group' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$content_blocks = acf_get_field_group( 'group_seo_page_content_blocks' );

		// Check location rule.
		$this->assertArrayHasKey( 'location', $content_blocks );
		$this->assertEquals( 'post_type', $content_blocks['location'][0][0]['param'] );
		$this->assertEquals( 'seo-page', $content_blocks['location'][0][0]['value'] );
	}

	/**
	 * Test saving and retrieving Hero Section fields.
	 */
	public function test_hero_section_fields_save_and_retrieve() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Save hero fields.
		update_field( 'hero_title', 'Test Hero Title', $post_id );
		update_field( 'hero_subtitle', 'Test Subtitle', $post_id );
		update_field( 'hero_summary', 'This is a test summary for the hero section.', $post_id );

		// Retrieve and verify.
		$this->assertEquals( 'Test Hero Title', get_field( 'hero_title', $post_id ) );
		$this->assertEquals( 'Test Subtitle', get_field( 'hero_subtitle', $post_id ) );
		$this->assertEquals( 'This is a test summary for the hero section.', get_field( 'hero_summary', $post_id ) );
	}

	/**
	 * Test saving and retrieving repeater field data.
	 */
	public function test_repeater_fields_save_and_retrieve() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Save repeater data.
		$answer_bullets = array(
			array( 'bullet_text' => 'First bullet point' ),
			array( 'bullet_text' => 'Second bullet point' ),
			array( 'bullet_text' => 'Third bullet point' ),
		);

		update_field( 'answer_bullets', $answer_bullets, $post_id );

		// Retrieve and verify.
		$retrieved_bullets = get_field( 'answer_bullets', $post_id );

		$this->assertIsArray( $retrieved_bullets );
		$this->assertCount( 3, $retrieved_bullets );
		$this->assertEquals( 'First bullet point', $retrieved_bullets[0]['bullet_text'] );
		$this->assertEquals( 'Second bullet point', $retrieved_bullets[1]['bullet_text'] );
		$this->assertEquals( 'Third bullet point', $retrieved_bullets[2]['bullet_text'] );
	}

	/**
	 * Test FAQ repeater fields.
	 */
	public function test_faq_repeater_fields() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Save FAQ data.
		$faq_items = array(
			array(
				'question' => 'What is the return policy?',
				'answer'   => 'We offer a 30-day return policy on all items.',
			),
			array(
				'question' => 'Do you ship internationally?',
				'answer'   => 'Yes, we ship to most countries worldwide.',
			),
		);

		update_field( 'faq_items', $faq_items, $post_id );

		// Retrieve and verify.
		$retrieved_faqs = get_field( 'faq_items', $post_id );

		$this->assertIsArray( $retrieved_faqs );
		$this->assertCount( 2, $retrieved_faqs );
		$this->assertEquals( 'What is the return policy?', $retrieved_faqs[0]['question'] );
		$this->assertEquals( 'We offer a 30-day return policy on all items.', $retrieved_faqs[0]['answer'] );
	}

	/**
	 * Test SEO Meta Fields.
	 */
	public function test_seo_meta_fields() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Save SEO meta data.
		update_field( 'seo_focus_keyword', 'platinum wedding bands', $post_id );
		update_field( 'seo_title', 'Best Platinum Wedding Bands | 2025 Guide', $post_id );
		update_field( 'seo_meta_description', 'Discover the finest platinum wedding bands. Expert guide to choosing the perfect ring for your special day.', $post_id );
		update_field( 'seo_canonical', 'https://example.com/platinum-wedding-bands/', $post_id );

		// Retrieve and verify.
		$this->assertEquals( 'platinum wedding bands', get_field( 'seo_focus_keyword', $post_id ) );
		$this->assertEquals( 'Best Platinum Wedding Bands | 2025 Guide', get_field( 'seo_title', $post_id ) );
		$this->assertEquals( 'Discover the finest platinum wedding bands. Expert guide to choosing the perfect ring for your special day.', get_field( 'seo_meta_description', $post_id ) );
		$this->assertEquals( 'https://example.com/platinum-wedding-bands/', get_field( 'seo_canonical', $post_id ) );
	}

	/**
	 * Test Materials repeater with all sub-fields.
	 */
	public function test_materials_repeater_fields() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Save materials data.
		$materials = array(
			array(
				'material'       => 'Platinum',
				'pros'           => 'Durable, hypoallergenic, maintains luster',
				'cons'           => 'Expensive, heavy',
				'best_for'       => 'Those with sensitive skin',
				'allergy_notes'  => 'Hypoallergenic',
				'care'           => 'Clean with mild soap and water',
			),
		);

		update_field( 'materials_items', $materials, $post_id );

		// Retrieve and verify.
		$retrieved_materials = get_field( 'materials_items', $post_id );

		$this->assertIsArray( $retrieved_materials );
		$this->assertCount( 1, $retrieved_materials );
		$this->assertEquals( 'Platinum', $retrieved_materials[0]['material'] );
		$this->assertEquals( 'Durable, hypoallergenic, maintains luster', $retrieved_materials[0]['pros'] );
		$this->assertEquals( 'Hypoallergenic', $retrieved_materials[0]['allergy_notes'] );
	}

	/**
	 * Test CTA fields.
	 */
	public function test_cta_fields() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Save CTA data.
		update_field( 'cta_heading', 'Ready to Find Your Perfect Ring?', $post_id );
		update_field( 'cta_text', 'Browse our collection or schedule a consultation with our experts.', $post_id );
		update_field( 'cta_primary_label', 'Browse Collection', $post_id );
		update_field( 'cta_primary_url', 'https://example.com/collection/', $post_id );
		update_field( 'cta_secondary_label', 'Schedule Consultation', $post_id );
		update_field( 'cta_secondary_url', 'https://example.com/consultation/', $post_id );

		// Retrieve and verify.
		$this->assertEquals( 'Ready to Find Your Perfect Ring?', get_field( 'cta_heading', $post_id ) );
		$this->assertEquals( 'Browse our collection or schedule a consultation with our experts.', get_field( 'cta_text', $post_id ) );
		$this->assertEquals( 'Browse Collection', get_field( 'cta_primary_label', $post_id ) );
		$this->assertEquals( 'https://example.com/collection/', get_field( 'cta_primary_url', $post_id ) );
	}

	/**
	 * Test Process steps with max limit.
	 */
	public function test_process_steps_max_limit() {
		if ( ! function_exists( 'update_field' ) ) {
			$this->markTestSkipped( 'ACF is not active' );
		}

		$post_id = $this->factory->post->create(
			array(
				'post_type' => SEOPage::POST_TYPE,
			)
		);

		// Create exactly 4 steps (max allowed).
		$steps = array(
			array(
				'step_title' => 'Step 1',
				'step_text'  => 'First step description',
			),
			array(
				'step_title' => 'Step 2',
				'step_text'  => 'Second step description',
			),
			array(
				'step_title' => 'Step 3',
				'step_text'  => 'Third step description',
			),
			array(
				'step_title' => 'Step 4',
				'step_text'  => 'Fourth step description',
			),
		);

		update_field( 'process_steps', $steps, $post_id );

		// Retrieve and verify.
		$retrieved_steps = get_field( 'process_steps', $post_id );

		$this->assertIsArray( $retrieved_steps );
		$this->assertCount( 4, $retrieved_steps );
		$this->assertEquals( 'Step 1', $retrieved_steps[0]['step_title'] );
		$this->assertEquals( 'Step 4', $retrieved_steps[3]['step_title'] );
	}
}
