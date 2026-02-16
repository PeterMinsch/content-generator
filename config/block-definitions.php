<?php
/**
 * Block Definitions Configuration
 *
 * This file defines all content blocks for SEO pages. Modify this file to add,
 * remove, or change blocks without touching the codebase.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block Definition Structure:
 *
 * 'block_id' => [
 *     'label'            => 'Block Display Name',
 *     'description'      => 'What this block is for',
 *     'order'            => 1,  // Display order (1-12)
 *     'enabled'          => true,  // Can be disabled without deleting
 *     'acf_wrapper_class' => 'acf-block-id',  // CSS class for admin
 *     'ai_prompt'        => 'Template for AI generation...',
 *     'frontend_template' => 'blocks/block-id.php',  // Template file path
 *     'fields'           => [
 *         'field_name' => [
 *             'label'      => 'Field Label',
 *             'type'       => 'text|textarea|repeater|image|url',
 *             'required'   => true|false,
 *             'maxlength'  => 100,
 *             'rows'       => 4,  // For textarea
 *             'max'        => 4,  // For repeater max items
 *             'return_format' => 'array',  // For image fields
 *             'preview_size'  => 'medium',  // For image fields
 *             'sub_fields' => [],  // For repeater fields
 *         ],
 *     ],
 * ]
 */

return [
	'blocks' => [

		/**
		 * Block 1: Hero Section
		 */
		'hero' => [
			'label'             => __( 'Hero Section', 'seo-generator' ),
			'description'       => __( 'Main hero content at the top of the page', 'seo-generator' ),
			'order'             => 1,
			'enabled'           => true,
			'acf_wrapper_class' => 'acf-block-hero',
			'ai_prompt'         => 'Generate a compelling hero section for {page_title} in the {page_topic} category. Include a title (max 100 chars), subtitle (max 150 chars), and summary (60-80 words). Target keyword: {focus_keyword}.',
			'frontend_template' => 'blocks/hero.php',
			'fields'            => [
				'hero_title'    => [
					'label'     => __( 'Hero Title', 'seo-generator' ),
					'type'      => 'text',
					'required'  => false,
					'maxlength' => 100,
				],
				'hero_subtitle' => [
					'label'     => __( 'Hero Subtitle', 'seo-generator' ),
					'type'      => 'text',
					'maxlength' => 150,
				],
				'hero_summary'  => [
					'label'     => __( 'Hero Summary', 'seo-generator' ),
					'type'      => 'textarea',
					'maxlength' => 400,
					'rows'      => 4,
				],
				'hero_image'    => [
					'label'         => __( 'Hero Image', 'seo-generator' ),
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				],
			],
		],

		/**
		 * Block 2: SERP Answer
		 */
		'serp_answer' => [
			'label'             => __( 'SERP Answer', 'seo-generator' ),
			'description'       => __( 'Quick answer for search engine results', 'seo-generator' ),
			'order'             => 2,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-serp-answer',
			'ai_prompt'         => 'Generate a SERP answer block for {page_title}. Include heading (max 100 chars), paragraph (max 600 chars), and 3-5 bullet points (max 150 chars each).',
			'frontend_template' => 'blocks/serp-answer.php',
			'fields'            => [
				'answer_heading'   => [
					'label'     => __( 'Answer Heading', 'seo-generator' ),
					'type'      => 'text',
					'maxlength' => 100,
				],
				'answer_paragraph' => [
					'label'     => __( 'Answer Paragraph', 'seo-generator' ),
					'type'      => 'textarea',
					'maxlength' => 600,
					'rows'      => 6,
				],
				'answer_bullets'   => [
					'label'      => __( 'Answer Bullets', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'table',
					'sub_fields' => [
						'bullet_text' => [
							'label'     => __( 'Bullet Text', 'seo-generator' ),
							'type'      => 'text',
							'maxlength' => 150,
						],
					],
				],
			],
		],

		/**
		 * Block 3: Product Criteria
		 */
		'product_criteria' => [
			'label'             => __( 'Product Criteria', 'seo-generator' ),
			'description'       => __( 'What to look for when choosing this product', 'seo-generator' ),
			'order'             => 3,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-criteria',
			'ai_prompt'         => 'Generate product criteria for {page_title}. Include heading and 4-6 criteria with names and explanations.',
			'frontend_template' => 'blocks/product-criteria.php',
			'fields'            => [
				'criteria_heading' => [
					'label' => __( 'Criteria Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'criteria_items'   => [
					'label'      => __( 'Criteria Items', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'row',
					'sub_fields' => [
						'name'        => [
							'label' => __( 'Name', 'seo-generator' ),
							'type'  => 'text',
						],
						'explanation' => [
							'label' => __( 'Explanation', 'seo-generator' ),
							'type'  => 'textarea',
							'rows'  => 3,
						],
					],
				],
			],
		],

		/**
		 * Block 4: Materials Explained
		 */
		'materials' => [
			'label'             => __( 'Materials Explained', 'seo-generator' ),
			'description'       => __( 'Detailed material comparisons', 'seo-generator' ),
			'order'             => 4,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-materials',
			'ai_prompt'         => 'Generate materials comparison for {page_title}. Include 3-5 materials with pros, cons, best_for, allergy notes, and care instructions.',
			'frontend_template' => 'blocks/materials.php',
			'fields'            => [
				'materials_heading' => [
					'label' => __( 'Materials Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'materials_items'   => [
					'label'      => __( 'Materials Items', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'row',
					'sub_fields' => [
						'material'      => [
							'label' => __( 'Material', 'seo-generator' ),
							'type'  => 'text',
						],
						'pros'          => [
							'label' => __( 'Pros', 'seo-generator' ),
							'type'  => 'textarea',
							'rows'  => 3,
						],
						'cons'          => [
							'label' => __( 'Cons', 'seo-generator' ),
							'type'  => 'textarea',
							'rows'  => 3,
						],
						'best_for'      => [
							'label' => __( 'Best For', 'seo-generator' ),
							'type'  => 'text',
						],
						'allergy_notes' => [
							'label' => __( 'Allergy Notes', 'seo-generator' ),
							'type'  => 'text',
						],
						'care'          => [
							'label' => __( 'Care', 'seo-generator' ),
							'type'  => 'text',
						],
					],
				],
			],
		],

		/**
		 * Block 5: Process
		 */
		'process' => [
			'label'             => __( 'Process', 'seo-generator' ),
			'description'       => __( 'Step-by-step process or guide', 'seo-generator' ),
			'order'             => 5,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-process',
			'ai_prompt'         => 'Generate a process guide for {page_title}. Include 3-4 steps with titles (short), descriptions (max 400 chars each).',
			'frontend_template' => 'blocks/process.php',
			'fields'            => [
				'process_heading' => [
					'label' => __( 'Process Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'process_steps'   => [
					'label'      => __( 'Process Steps', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'row',
					'max'        => 4,
					'sub_fields' => [
						'step_title' => [
							'label' => __( 'Step Title', 'seo-generator' ),
							'type'  => 'text',
						],
						'step_text'  => [
							'label'     => __( 'Step Text', 'seo-generator' ),
							'type'      => 'textarea',
							'maxlength' => 400,
							'rows'      => 4,
						],
						'step_image' => [
							'label'         => __( 'Step Image', 'seo-generator' ),
							'type'          => 'image',
							'return_format' => 'array',
							'preview_size'  => 'thumbnail',
						],
					],
				],
			],
		],

		/**
		 * Block 6: Comparison
		 */
		'comparison' => [
			'label'             => __( 'Comparison', 'seo-generator' ),
			'description'       => __( 'Side-by-side comparison table', 'seo-generator' ),
			'order'             => 6,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-comparison',
			'ai_prompt'         => 'Generate a comparison for {page_title}. Include heading, left/right labels, summary, and 5-7 comparison rows.',
			'frontend_template' => 'blocks/comparison.php',
			'fields'            => [
				'comparison_heading'     => [
					'label' => __( 'Comparison Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'comparison_left_label'  => [
					'label' => __( 'Left Label', 'seo-generator' ),
					'type'  => 'text',
				],
				'comparison_right_label' => [
					'label' => __( 'Right Label', 'seo-generator' ),
					'type'  => 'text',
				],
				'comparison_summary'     => [
					'label' => __( 'Summary', 'seo-generator' ),
					'type'  => 'textarea',
					'rows'  => 4,
				],
				'comparison_rows'        => [
					'label'      => __( 'Comparison Rows', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'table',
					'sub_fields' => [
						'attribute'  => [
							'label' => __( 'Attribute', 'seo-generator' ),
							'type'  => 'text',
						],
						'left_text'  => [
							'label'     => __( 'Left Text', 'seo-generator' ),
							'type'      => 'text',
							'maxlength' => 200,
						],
						'right_text' => [
							'label'     => __( 'Right Text', 'seo-generator' ),
							'type'      => 'text',
							'maxlength' => 200,
						],
					],
				],
			],
		],

		/**
		 * Block 7: Product Showcase
		 */
		'product_showcase' => [
			'label'             => __( 'Product Showcase', 'seo-generator' ),
			'description'       => __( 'Featured product listings', 'seo-generator' ),
			'order'             => 7,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-showcase',
			'ai_prompt'         => 'Generate product showcase for {page_title}. Include heading, intro text, and suggest 3-5 relevant product SKUs.',
			'frontend_template' => 'blocks/product-showcase.php',
			'fields'            => [
				'showcase_heading'  => [
					'label' => __( 'Showcase Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'showcase_intro'    => [
					'label' => __( 'Showcase Intro', 'seo-generator' ),
					'type'  => 'textarea',
					'rows'  => 3,
				],
				'showcase_products' => [
					'label'      => __( 'Products', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'table',
					'sub_fields' => [
						'product_sku'    => [
							'label' => __( 'Product SKU', 'seo-generator' ),
							'type'  => 'text',
						],
						'alt_image_url'  => [
							'label' => __( 'Alt Image URL', 'seo-generator' ),
							'type'  => 'url',
						],
					],
				],
			],
		],

		/**
		 * Block 8: Size & Fit
		 */
		'size_fit' => [
			'label'             => __( 'Size & Fit', 'seo-generator' ),
			'description'       => __( 'Sizing information and fit guide', 'seo-generator' ),
			'order'             => 8,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-size-fit',
			'ai_prompt'         => 'Generate sizing and fit information for {page_title}. Include heading and comfort fit notes.',
			'frontend_template' => 'blocks/size-fit.php',
			'fields'            => [
				'size_heading'       => [
					'label' => __( 'Size Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'size_chart_image'   => [
					'label'         => __( 'Size Chart Image', 'seo-generator' ),
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				],
				'comfort_fit_notes'  => [
					'label' => __( 'Comfort Fit Notes', 'seo-generator' ),
					'type'  => 'textarea',
					'rows'  => 4,
				],
			],
		],

		/**
		 * Block 9: Care & Warranty
		 */
		'care_warranty' => [
			'label'             => __( 'Care & Warranty', 'seo-generator' ),
			'description'       => __( 'Product care instructions and warranty', 'seo-generator' ),
			'order'             => 9,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-care-warranty',
			'ai_prompt'         => 'Generate care and warranty information for {page_title}. Include care heading, 4-6 care bullets, warranty heading, and warranty text.',
			'frontend_template' => 'blocks/care-warranty.php',
			'fields'            => [
				'care_heading'     => [
					'label' => __( 'Care Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'care_bullets'     => [
					'label'      => __( 'Care Bullets', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'table',
					'sub_fields' => [
						'bullet' => [
							'label' => __( 'Bullet', 'seo-generator' ),
							'type'  => 'text',
						],
					],
				],
				'warranty_heading' => [
					'label' => __( 'Warranty Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'warranty_text'    => [
					'label' => __( 'Warranty Text', 'seo-generator' ),
					'type'  => 'textarea',
					'rows'  => 4,
				],
			],
		],

		/**
		 * Block 10: Ethics & Origin
		 */
		'ethics' => [
			'label'             => __( 'Ethics & Origin', 'seo-generator' ),
			'description'       => __( 'Ethical sourcing and certifications', 'seo-generator' ),
			'order'             => 10,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-ethics',
			'ai_prompt'         => 'Generate ethics and origin content for {page_title}. Include heading, ethics text (max 800 chars), and 2-3 relevant certifications.',
			'frontend_template' => 'blocks/ethics.php',
			'fields'            => [
				'ethics_heading'   => [
					'label' => __( 'Ethics Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'ethics_text'      => [
					'label'     => __( 'Ethics Text', 'seo-generator' ),
					'type'      => 'textarea',
					'maxlength' => 800,
					'rows'      => 6,
				],
				'certifications'   => [
					'label'      => __( 'Certifications', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'table',
					'sub_fields' => [
						'cert_name' => [
							'label' => __( 'Certification Name', 'seo-generator' ),
							'type'  => 'text',
						],
						'cert_link' => [
							'label' => __( 'Certification Link', 'seo-generator' ),
							'type'  => 'url',
						],
					],
				],
			],
		],

		/**
		 * Block 11: FAQs
		 */
		'faqs' => [
			'label'             => __( 'FAQs', 'seo-generator' ),
			'description'       => __( 'Frequently asked questions', 'seo-generator' ),
			'order'             => 11,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-faqs',
			'ai_prompt'         => 'Generate 5-7 frequently asked questions for {page_title}. Include heading and Q&A pairs (answers max 600 chars).',
			'frontend_template' => 'blocks/faqs.php',
			'fields'            => [
				'faqs_heading' => [
					'label' => __( 'FAQs Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'faq_items'    => [
					'label'      => __( 'FAQ Items', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'row',
					'sub_fields' => [
						'question' => [
							'label' => __( 'Question', 'seo-generator' ),
							'type'  => 'text',
						],
						'answer'   => [
							'label'     => __( 'Answer', 'seo-generator' ),
							'type'      => 'textarea',
							'maxlength' => 600,
							'rows'      => 4,
						],
					],
				],
			],
		],

		/**
		 * Block 12: CTA
		 */
		'cta' => [
			'label'             => __( 'CTA (Call to Action)', 'seo-generator' ),
			'description'       => __( 'Call to action section', 'seo-generator' ),
			'order'             => 12,
			'enabled'           => false,
			'acf_wrapper_class' => 'acf-block-cta',
			'ai_prompt'         => 'Generate compelling CTA for {page_title}. Include heading, persuasive text, and suggest button labels.',
			'frontend_template' => 'blocks/cta.php',
			'fields'            => [
				'cta_heading'        => [
					'label' => __( 'CTA Heading', 'seo-generator' ),
					'type'  => 'text',
				],
				'cta_text'           => [
					'label' => __( 'CTA Text', 'seo-generator' ),
					'type'  => 'textarea',
					'rows'  => 3,
				],
				'cta_primary_label'  => [
					'label' => __( 'Primary Button Label', 'seo-generator' ),
					'type'  => 'text',
				],
				'cta_primary_url'    => [
					'label' => __( 'Primary Button URL', 'seo-generator' ),
					'type'  => 'url',
				],
				'cta_secondary_label' => [
					'label' => __( 'Secondary Button Label', 'seo-generator' ),
					'type'  => 'text',
				],
				'cta_secondary_url'  => [
					'label' => __( 'Secondary Button URL', 'seo-generator' ),
					'type'  => 'url',
				],
			],
		],

		/**
		 * Block 13: About Section
		 * NOTE: Heading and description are hardcoded in BlockContentParser.
		 * Only the features are AI-generated.
		 */
		'about_section' => [
			'label'             => __( 'About Section', 'seo-generator' ),
			'description'       => __( 'About company with AI-generated contextual guarantees (heading/description hardcoded)', 'seo-generator' ),
			'order'             => 13,
			'enabled'           => true,
			'acf_wrapper_class' => 'acf-block-about-section',
			'ai_prompt'         => 'Generate 4 contextual trust factors/guarantees for {page_title}. Choose relevant icons: shipping, returns, warranty, finance, quality, secure, support, eco, diamond, resize, gift, repair. Titles: 2-3 words. Descriptions: 3-6 words.',
			'frontend_template' => 'blocks/about-section.php',
			'fields'            => [
				'about_heading'     => [
					'label'     => __( 'About Heading (Auto-filled)', 'seo-generator' ),
					'type'      => 'text',
					'maxlength' => 100,
				],
				'about_description' => [
					'label'     => __( 'About Description (Auto-filled)', 'seo-generator' ),
					'type'      => 'textarea',
					'maxlength' => 300,
					'rows'      => 4,
				],
				'about_features'    => [
					'label'      => __( 'Features', 'seo-generator' ),
					'type'       => 'repeater',
					'layout'     => 'row',
					'max'        => 4,
					'min'        => 4,
					'sub_fields' => [
						'icon_type'           => [
							'label'   => __( 'Icon Type', 'seo-generator' ),
							'type'    => 'select',
							'choices' => [
								'shipping' => __( 'Shipping', 'seo-generator' ),
								'returns'  => __( 'Returns/Exchanges', 'seo-generator' ),
								'warranty' => __( 'Warranty/Protection', 'seo-generator' ),
								'finance'  => __( 'Financing/Payment', 'seo-generator' ),
								'quality'  => __( 'Quality/Craftsmanship', 'seo-generator' ),
								'secure'   => __( 'Secure Shopping', 'seo-generator' ),
								'support'  => __( 'Customer Support', 'seo-generator' ),
								'eco'      => __( 'Eco-Friendly/Sustainable', 'seo-generator' ),
								'diamond'  => __( 'Diamond Certification', 'seo-generator' ),
								'resize'   => __( 'Sizing/Adjustments', 'seo-generator' ),
								'gift'     => __( 'Gift Services', 'seo-generator' ),
								'repair'   => __( 'Repair/Maintenance', 'seo-generator' ),
							],
						],
						'feature_title'       => [
							'label'     => __( 'Feature Title', 'seo-generator' ),
							'type'      => 'text',
							'maxlength' => 50,
						],
						'feature_description' => [
							'label'     => __( 'Feature Description', 'seo-generator' ),
							'type'      => 'text',
							'maxlength' => 100,
						],
					],
				],
			],
		],

		/**
		 * Block 14: Related Links
		 */
		'related_links' => [
			'label'             => __( 'Related Pages', 'seo-generator' ),
			'description'       => __( 'Internal links to related content pages', 'seo-generator' ),
			'order'             => 14,
			'enabled'           => true,
			'acf_wrapper_class' => 'acf-block-related-links',
			'ai_prompt'         => 'Select 4 most relevant pages from the provided dataset for {page_title}. Choose pages that complement the topic and provide value to users interested in {page_topic}. Return structured data for each link.',
			'frontend_template' => 'blocks/related-links.php',
			'fields'            => [
				'section_heading' => [
					'label'     => __( 'Section Heading', 'seo-generator' ),
					'type'      => 'text',
					'maxlength' => 100,
				],
				'links'           => [
					'label'      => __( 'Related Links', 'seo-generator' ),
					'type'       => 'repeater',
					'max'        => 4,
					'layout'     => 'row',
					'sub_fields' => [
						'link_title'       => [
							'label'     => __( 'Link Title', 'seo-generator' ),
							'type'      => 'text',
							'maxlength' => 50,
						],
						'link_url'         => [
							'label' => __( 'URL', 'seo-generator' ),
							'type'  => 'url',
						],
						'link_description' => [
							'label'     => __( 'Description', 'seo-generator' ),
							'type'      => 'textarea',
							'maxlength' => 200,
							'rows'      => 2,
						],
						'link_category'    => [
							'label' => __( 'Category', 'seo-generator' ),
							'type'  => 'text',
						],
						'link_image'       => [
							'label'         => __( 'Card Image', 'seo-generator' ),
							'type'          => 'image',
							'return_format' => 'array',
							'preview_size'  => 'medium',
						],
						'link_item_count'  => [
							'label' => __( 'Item Count', 'seo-generator' ),
							'type'  => 'text',
						],
					],
				],
			],
		],

		/**
		 * Block 15: Pricing Hero
		 */
		'pricing_hero' => [
			'label'             => __( 'Pricing Hero', 'seo-generator' ),
			'description'       => __( 'Hero section with ring sizing pricing list', 'seo-generator' ),
			'order'             => 15,
			'enabled'           => true,
			'acf_wrapper_class' => 'acf-block-pricing-hero',
			'ai_prompt'         => 'Generate a pricing hero section for ring sizing services. Include a compelling title about dimensional accuracy and comfort, description text about sizing services (60-100 words), and pricing for 5 categories: Gold Rings, Silver Rings, Platinum Rings, Rings with Stones (each with downsize/upsize prices), and Custom Designs (consultation text).',
			'frontend_template' => 'blocks/pricing-hero.php',
			'fields'            => [
				'pricing_hero_title'       => [
					'label'     => __( 'Pricing Hero Title', 'seo-generator' ),
					'type'      => 'text',
					'maxlength' => 100,
				],
				'pricing_hero_description' => [
					'label'     => __( 'Pricing Hero Description', 'seo-generator' ),
					'type'      => 'textarea',
					'maxlength' => 600,
					'rows'      => 4,
				],
				'pricing_items'    => [
					'label'      => __( 'Pricing Items', 'seo-generator' ),
					'type'       => 'repeater',
					'max'        => 5,
					'layout'     => 'row',
					'sub_fields' => [
						'category'       => [
							'label' => __( 'Category', 'seo-generator' ),
							'type'  => 'text',
						],
						'downsize_label' => [
							'label'   => __( 'Downsize Label', 'seo-generator' ),
							'type'    => 'text',
							'default' => 'Downsize',
						],
						'downsize_price' => [
							'label' => __( 'Downsize Price', 'seo-generator' ),
							'type'  => 'text',
						],
						'upsize_label'   => [
							'label'   => __( 'Upsize Label', 'seo-generator' ),
							'type'    => 'text',
							'default' => 'Upsize',
						],
						'upsize_price'   => [
							'label' => __( 'Upsize Price', 'seo-generator' ),
							'type'  => 'text',
						],
						'custom_text'    => [
							'label' => __( 'Custom Text (for consultation items)', 'seo-generator' ),
							'type'  => 'text',
						],
					],
				],
			],
		],

	],

	/**
	 * Global Settings
	 */
	'settings' => [
		'allow_custom_blocks'   => apply_filters( 'seo_generator_allow_custom_blocks', false ),
		'enable_block_ordering' => apply_filters( 'seo_generator_enable_block_ordering', false ),
	],
];
