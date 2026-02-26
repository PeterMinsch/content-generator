<?php
/**
 * Next.js Block Definitions Configuration
 *
 * Master catalog of ALL widget blocks organized by group.
 * Both page tabs can use any block from any group.
 * Each page tab has its own default order.
 *
 * content_slots define overridable text content per block for dynamic pages.
 * Slot types: text | textarea | html
 * Each slot has: type, max_length (char limit for AI), ai_hint (generation instruction)
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

return [

	/**
	 * ──────────────────────────────────────────────────────────────
	 * Shared block catalog — every widget available site-wide.
	 * Organized by group for the block picker UI.
	 * ──────────────────────────────────────────────────────────────
	 */
	'groups' => [

		'homepage' => [
			'label'  => __( 'Homepage', 'seo-generator' ),
			'blocks' => [
				'hero_slider' => [
					'label'       => __( 'Hero Slider', 'seo-generator' ),
					'description' => __( 'Full-width hero with video/image slides', 'seo-generator' ),
					'import_path' => '@widgets/hero-slider',
					'export_name' => 'HeroSlider',
					'props'       => " page='default'",
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Main hero headline, compelling and keyword-rich' ],
						'subheading'  => [ 'type' => 'text',     'max_length' => 120, 'ai_hint' => 'Supporting subtitle that expands on the headline' ],
						'cta_text'    => [ 'type' => 'text',     'max_length' => 30,  'ai_hint' => 'Call-to-action button text' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Brief paragraph below the hero' ],
					],
				],
				'about_bravo' => [
					'label'       => __( 'About Bravo', 'seo-generator' ),
					'description' => __( 'Family-owned, master jewelers, GIA gemologists, lifetime care', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/about-bravo',
					'export_name' => 'AboutBravo',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Section heading about the jeweler' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Company story paragraph highlighting expertise, family heritage, and trust' ],
						'cta_text'    => [ 'type' => 'text',     'max_length' => 30,  'ai_hint' => 'CTA button text like "Learn More" or "Our Story"' ],
					],
				],
				'featured_services' => [
					'label'       => __( 'Featured Services', 'seo-generator' ),
					'description' => __( 'Custom design, repairs, resizing, watch repairs, gold buying, appraisals', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/featured-services',
					'export_name' => 'FeaturedServices',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Section heading for services offered' ],
						'subheading'  => [ 'type' => 'text',     'max_length' => 120, 'ai_hint' => 'Brief subtitle about service quality' ],
					],
				],
				'shop_categories' => [
					'label'       => __( 'Shop Categories', 'seo-generator' ),
					'description' => __( 'Browse jewelry by category with image cards', 'seo-generator' ),
					'import_path' => '@widgets/shop-categories',
					'export_name' => 'ShopCategories',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Section heading for shopping categories' ],
					],
				],
				'new_collection' => [
					'label'       => __( 'New Collection', 'seo-generator' ),
					'description' => __( 'Featured collection spotlight with product details', 'seo-generator' ),
					'import_path' => '@widgets/new-collection',
					'export_name' => 'NewCollection',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Collection name or spotlight heading' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 250, 'ai_hint' => 'Brief description of the featured collection' ],
					],
				],
				'diamond_explore' => [
					'label'       => __( 'Diamond Explorer', 'seo-generator' ),
					'description' => __( 'Interactive diamond shape explorer', 'seo-generator' ),
					'import_path' => '@widgets/diamond-explore',
					'export_name' => 'DiamondExplore',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for diamond exploration section' ],
					],
				],
				'top_rated' => [
					'label'       => __( 'Top Rated Jeweler', 'seo-generator' ),
					'description' => __( 'Customer reviews with Google, Yelp, and Facebook ratings', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/top-rated-jeweler',
					'export_name' => 'TopRated',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading emphasizing top ratings and reviews' ],
						'subheading'  => [ 'type' => 'text',     'max_length' => 120, 'ai_hint' => 'Subtitle about customer satisfaction or rating count' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Brief paragraph about why customers love this jeweler' ],
					],
				],
				'own_design' => [
					'label'       => __( 'Design Your Own Ring', 'seo-generator' ),
					'description' => __( 'Custom engagement ring design CTA with animated stones', 'seo-generator' ),
					'import_path' => '@widgets/own-design',
					'export_name' => 'OwnDesign',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Compelling heading about designing your own ring' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 250, 'ai_hint' => 'Brief description of the custom design experience' ],
						'cta_text'    => [ 'type' => 'text',     'max_length' => 30,  'ai_hint' => 'CTA button text like "Start Designing"' ],
					],
				],
				'handcrafted' => [
					'label'       => __( 'Handcrafted in California', 'seo-generator' ),
					'description' => __( 'Workshop image carousel showcasing craftsmanship', 'seo-generator' ),
					'import_path' => '@widgets/handcrafted',
					'export_name' => 'Handcrafted',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading about handcrafted jewelry and local craftsmanship' ],
					],
				],
				'intro_gallery' => [
					'label'       => __( 'Instagram Gallery', 'seo-generator' ),
					'description' => __( 'Social media photo grid — "Be part of Something Brilliant"', 'seo-generator' ),
					'import_path' => '@widgets/intro-gallery',
					'export_name' => 'IntroGallery',
					'props'       => '',
					'content_slots' => [],
				],
				'we_here' => [
					'label'       => __( "We're Here For You", 'seo-generator' ),
					'description' => __( 'Appointment booking form with business hours', 'seo-generator' ),
					'import_path' => '@widgets/we-heare',
					'export_name' => 'WeHere',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Welcoming heading about availability and service' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 250, 'ai_hint' => 'Brief paragraph inviting visitors to book an appointment' ],
					],
				],
			],
		],

		'about' => [
			'label'  => __( 'About Us', 'seo-generator' ),
			'blocks' => [
				'about_menu' => [
					'label'       => __( 'Navigation Menu', 'seo-generator' ),
					'description' => __( 'Transparent overlay menu bar', 'seo-generator' ),
					'import_path' => '@/features/menu',
					'export_name' => 'Menu',
					'props'       => " className='relative z-30'",
					'content_slots' => [],
				],
				'about_hero_slider' => [
					'label'       => __( 'Hero Slider (About)', 'seo-generator' ),
					'description' => __( 'Hero banner for the About Us page', 'seo-generator' ),
					'import_path' => '@/widgets/hero-slider',
					'export_name' => 'HeroSlider',
					'props'       => " page='about' className='-mt-[5.75rem] md:-mt-[6.75rem] lg:-mt-[7.25rem]'",
					'content_slots' => [
						'heading'    => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'About page hero headline' ],
						'subheading' => [ 'type' => 'text',     'max_length' => 120, 'ai_hint' => 'About page hero subtitle' ],
					],
				],
				'mission_statement' => [
					'label'       => __( 'Mission Statement', 'seo-generator' ),
					'description' => __( 'Company mission and values section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/mission-statement',
					'export_name' => 'MissionStatement',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Mission statement heading' ],
						'mission'     => [ 'type' => 'textarea', 'max_length' => 500, 'ai_hint' => 'Company mission statement paragraph about values and commitment' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Supporting text about company philosophy and approach' ],
					],
				],
				'why_custom_founders' => [
					'label'       => __( 'Meet the Founders', 'seo-generator' ),
					'description' => __( 'Why Custom section customized as founders introduction', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/why-custom/WhyCustom',
					'export_name' => 'WhyCustom',
					'props'       => " titlePart='THE ' titleAccent='FOUNDERS' title='MEET '",
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about the founders or team leaders' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Description of the founders, their background and vision' ],
					],
				],
				'video_block' => [
					'label'       => __( 'Video Block', 'seo-generator' ),
					'description' => __( 'Featured video about Bravo Jewellers', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/video-block',
					'export_name' => 'VideoBlock',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Video section heading' ],
					],
				],
				'piece_mind' => [
					'label'       => __( 'Piece of Mind', 'seo-generator' ),
					'description' => __( 'Peace of mind / trust section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/piece-of-mind',
					'export_name' => 'PieceMind',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Trust/assurance section heading' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text about quality guarantees, warranties, and customer peace of mind' ],
					],
				],
				'beyond_adornment' => [
					'label'       => __( 'Beyond Adornment', 'seo-generator' ),
					'description' => __( 'Jewelry philosophy and craftsmanship section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/beyond-adornment',
					'export_name' => 'BeyondAdornment',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about jewelry being more than decoration' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Philosophical text about jewelry craftsmanship and meaning' ],
					],
				],
				'beyond_adornment_gem' => [
					'label'       => __( 'Beyond Adornment — Gem', 'seo-generator' ),
					'description' => __( 'Gemstone-focused continuation of Beyond Adornment', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/beyond-adornment-gem',
					'export_name' => 'BeyondAdornmentGem',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about gemstone quality and sourcing' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text about gemstone expertise, sourcing, and quality standards' ],
					],
				],
				'our_team' => [
					'label'       => __( 'Our Team', 'seo-generator' ),
					'description' => __( 'Team member profiles and photos', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/our-team',
					'export_name' => 'OurTeam',
					'props'       => '',
					'content_slots' => [
						'heading'    => [ 'type' => 'text', 'max_length' => 80,  'ai_hint' => 'Team section heading' ],
						'subheading' => [ 'type' => 'text', 'max_length' => 120, 'ai_hint' => 'Subtitle about the team expertise and dedication' ],
					],
				],
				'our_experts' => [
					'label'       => __( 'Our Experts', 'seo-generator' ),
					'description' => __( 'Expert gemologists and jewelers highlight', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/our-experts',
					'export_name' => 'OurExperts',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about expert gemologists and jewelers' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Brief text about expert qualifications and certifications' ],
					],
				],
				'our_instagram' => [
					'label'       => __( 'Our Instagram', 'seo-generator' ),
					'description' => __( 'Instagram feed integration', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/our-instagram',
					'export_name' => 'OurInst',
					'props'       => '',
					'content_slots' => [],
				],
			],
		],

		'diamonds' => [
			'label'  => __( 'Diamonds', 'seo-generator' ),
			'blocks' => [
				'cs_of_diamonds' => [
					'label'       => __( '4 Cs of Diamonds', 'seo-generator' ),
					'description' => __( 'Cut, color, clarity, carat education', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/cs-of-diamonds',
					'export_name' => 'CsOfDiamonds',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about the 4 Cs of diamond quality' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Introduction to diamond grading: cut, color, clarity, carat' ],
					],
				],
				'diamond_shapes' => [
					'label'       => __( 'Diamond Shapes', 'seo-generator' ),
					'description' => __( 'Diamond shape selection guide', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/diamond-shapes',
					'export_name' => 'DiamondShapes',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for diamond shapes guide' ],
					],
				],
				'natural_vs_labgrown' => [
					'label'       => __( 'Natural vs Lab-Grown', 'seo-generator' ),
					'description' => __( 'Natural vs lab-grown diamond comparison', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/natural-vs-labgrown',
					'export_name' => 'NaturalVsLabgrown',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading for natural vs lab-grown comparison' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Balanced comparison of natural and lab-grown diamonds' ],
					],
				],
				'perfect_diamond' => [
					'label'       => __( 'Perfect Diamond', 'seo-generator' ),
					'description' => __( 'Find your perfect diamond section', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/perfect-diamond',
					'export_name' => 'PerfectDiamond',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about finding the perfect diamond' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Guide text about selecting the right diamond' ],
						'cta_text'    => [ 'type' => 'text',     'max_length' => 30,  'ai_hint' => 'CTA button text' ],
					],
				],
				'diamonds_price' => [
					'label'       => __( 'Diamond Pricing', 'seo-generator' ),
					'description' => __( 'Diamond pricing guide and calculator', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/diamonds-price',
					'export_name' => 'DiamondsPrice',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for diamond pricing section' ],
					],
				],
				'beyond_the_five' => [
					'label'       => __( 'Beyond the 5 Cs', 'seo-generator' ),
					'description' => __( 'Advanced diamond quality factors', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/beyond-the-five',
					'export_name' => 'Beyond',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about advanced diamond quality factors' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text about factors beyond the basic 4 Cs' ],
					],
				],
				'assurance_block' => [
					'label'       => __( 'Assurance Block', 'seo-generator' ),
					'description' => __( 'Diamond quality assurance and certification', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/assurance-block',
					'export_name' => 'Assurance',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about diamond certification and assurance' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Text about GIA certification, quality guarantees' ],
					],
				],
				'shop_engagement_rings' => [
					'label'       => __( 'Shop Engagement Rings', 'seo-generator' ),
					'description' => __( 'Engagement ring shopping CTA', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/shop-engagement-rings',
					'export_name' => 'EngagementRings',
					'props'       => '',
					'content_slots' => [
						'heading'  => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'CTA heading for shopping engagement rings' ],
						'cta_text' => [ 'type' => 'text', 'max_length' => 30, 'ai_hint' => 'Button text like "Shop Now" or "Browse Collection"' ],
					],
				],
			],
		],

		'custom_design' => [
			'label'  => __( 'Custom Design', 'seo-generator' ),
			'blocks' => [
				'why_custom' => [
					'label'       => __( 'Why Custom', 'seo-generator' ),
					'description' => __( 'Why choose custom jewelry design', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/why-custom/WhyCustom',
					'export_name' => 'WhyCustom',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about benefits of custom jewelry' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text explaining why custom design is worth it' ],
					],
				],
				'bravo_difference' => [
					'label'       => __( 'The Bravo Difference', 'seo-generator' ),
					'description' => __( 'What sets Bravo apart from competitors', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/bravo-difference',
					'export_name' => 'BravoDifference',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about what makes this jeweler unique' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text about differentiators: craftsmanship, service, expertise' ],
					],
				],
				'crafts_manship' => [
					'label'       => __( 'Craftsmanship', 'seo-generator' ),
					'description' => __( 'Craftsmanship showcase section', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/crafts-manship',
					'export_name' => 'CraftsManship',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about artisan craftsmanship' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text about handcrafted techniques and quality materials' ],
					],
				],
				'process_section' => [
					'label'       => __( 'Design Process', 'seo-generator' ),
					'description' => __( 'Step-by-step custom design process', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/process-section',
					'export_name' => 'ProcessSection',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about the custom design process' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Brief intro to the step-by-step design journey' ],
					],
				],
				'master_piece' => [
					'label'       => __( 'Masterpiece', 'seo-generator' ),
					'description' => __( 'Featured custom masterpiece gallery', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/master-piece',
					'export_name' => 'MasterPiece',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for masterpiece gallery section' ],
					],
				],
				'comparison_rings' => [
					'label'       => __( 'Ring Comparison', 'seo-generator' ),
					'description' => __( 'Custom vs mass-produced ring comparison', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/comparison-rings',
					'export_name' => 'ComparisonRings',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading comparing custom vs mass-produced rings' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Text explaining the advantages of custom over mass-produced' ],
					],
				],
				'describe_your_design' => [
					'label'       => __( 'Describe Your Design', 'seo-generator' ),
					'description' => __( 'Design intake / description form', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/describe-your-design',
					'export_name' => 'DescribeDesign',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading inviting users to describe their dream design' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 250, 'ai_hint' => 'Brief text encouraging users to share their vision' ],
					],
				],
				'calculator' => [
					'label'       => __( 'Price Calculator', 'seo-generator' ),
					'description' => __( 'Custom jewelry price estimator', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/calculator',
					'export_name' => 'CustomCalculator',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for the price calculator tool' ],
					],
				],
			],
		],

		'engagement' => [
			'label'  => __( 'Engagement Rings', 'seo-generator' ),
			'blocks' => [
				'choose_ring_styles' => [
					'label'       => __( 'Choose Ring Styles', 'seo-generator' ),
					'description' => __( 'Engagement ring style guide', 'seo-generator' ),
					'import_path'  => '@/widgets/engagement-rings/choose-ring-styles/RingsByStyles',
					'export_name'  => 'RingsByStyles',
					'import_type'  => 'default',
					'data_imports' => [
						[ 'name' => 'ringsCategories', 'path' => '@/widgets/engagement-rings/choose-ring-styles/ringsCategories' ],
					],
					'props'        => " categories={ringsCategories} viewStoreHref='/shop'",
					'content_slots' => [
						'heading'    => [ 'type' => 'text', 'max_length' => 80,  'ai_hint' => 'Heading for ring style selection' ],
						'subheading' => [ 'type' => 'text', 'max_length' => 120, 'ai_hint' => 'Subtitle about finding the perfect style' ],
					],
				],
				'choose_your_diamond' => [
					'label'       => __( 'Choose Your Diamond', 'seo-generator' ),
					'description' => __( 'Diamond selection guide for rings', 'seo-generator' ),
					'import_path'  => '@/widgets/engagement-rings/choose-your-diamond/ChooseYourDiamond',
					'export_name'  => 'ChooseYourDiamond',
					'import_type'  => 'default',
					'data_imports' => [
						[ 'name' => 'diamondCategories', 'path' => '@/widgets/engagement-rings/choose-your-diamond/diamond' ],
					],
					'props'        => ' categories={diamondCategories}',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for diamond selection guide' ],
					],
				],
				'choose_diamond_color' => [
					'label'       => __( 'Diamond Color Guide', 'seo-generator' ),
					'description' => __( 'Diamond color selection guide', 'seo-generator' ),
					'import_path'  => '@/widgets/engagement-rings/сhoose-diamond-color/ChooseByDiamondColor',
					'export_name'  => 'ChooseByDiamondColor',
					'import_type'  => 'default',
					'data_imports' => [
						[ 'name' => 'diamondColorGroups', 'path' => '@/widgets/engagement-rings/сhoose-diamond-color/colorsData' ],
					],
					'props'        => ' groups={diamondColorGroups}',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for diamond color guide' ],
					],
				],
				'metals_selector' => [
					'label'       => __( 'Metal Selector', 'seo-generator' ),
					'description' => __( 'Choose ring metal type', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/metals-selector',
					'export_name'  => 'MetalsSelector',
					'import_type'  => 'default',
					'data_imports' => [
						[ 'name' => 'METALS', 'path' => '@/widgets/engagement-rings/metals-selector/metals' ],
					],
					'props'        => ' metals={METALS}',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading for metal type selection' ],
					],
				],
				'simple_buying' => [
					'label'       => __( 'Simple Buying Guide', 'seo-generator' ),
					'description' => __( 'Simplified ring buying process', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/simple-buying',
					'export_name' => 'SimpleBuying',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about easy ring buying' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Brief text about the simplified buying process' ],
					],
				],
				'rings_why_choose' => [
					'label'       => __( 'Why Choose Bravo', 'seo-generator' ),
					'description' => __( 'Reasons to choose Bravo for engagement rings', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/rings-why-choose/AdvantagesSection',
					'export_name' => 'AdvantagesSection',
					'import_type' => 'default',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about advantages of choosing this jeweler' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 400, 'ai_hint' => 'Text about key advantages: expertise, quality, value' ],
					],
				],
				'complete_experience' => [
					'label'       => __( 'Complete Experience', 'seo-generator' ),
					'description' => __( 'Full engagement ring experience', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/complete-experience',
					'export_name' => 'CompleteExperience',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about the complete ring shopping experience' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Text about the end-to-end experience from selection to delivery' ],
					],
				],
				'currently_trending' => [
					'label'       => __( 'Currently Trending', 'seo-generator' ),
					'description' => __( 'Trending ring styles and designs', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/currently-trend',
					'export_name' => 'CurrentlyTrend',
					'props'       => '',
					'content_slots' => [
						'heading'    => [ 'type' => 'text', 'max_length' => 80,  'ai_hint' => 'Heading about trending ring styles' ],
						'subheading' => [ 'type' => 'text', 'max_length' => 120, 'ai_hint' => 'Subtitle about current jewelry trends' ],
					],
				],
				'custom_design_cta' => [
					'label'       => __( 'Custom Design CTA', 'seo-generator' ),
					'description' => __( 'Custom engagement ring design call-to-action', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/custom-design',
					'export_name' => 'CustomDesign',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'CTA heading about custom engagement ring design' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 250, 'ai_hint' => 'Brief text encouraging custom design consultation' ],
						'cta_text'    => [ 'type' => 'text',     'max_length' => 30,  'ai_hint' => 'Button text like "Design Yours" or "Get Started"' ],
					],
				],
				'financing_available' => [
					'label'       => __( 'Financing Available', 'seo-generator' ),
					'description' => __( 'Financing options section', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/financing-available',
					'export_name' => 'FinancingAvailable',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about financing options' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Text about flexible payment plans and financing' ],
					],
				],
				'lifestyle' => [
					'label'       => __( 'Lifestyle', 'seo-generator' ),
					'description' => __( 'Lifestyle imagery and context', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/lifestyle/Lifestyle',
					'export_name' => 'Lifestyle',
					'import_type' => 'default',
					'props'       => '',
					'content_slots' => [],
				],
				'photo_scroll' => [
					'label'       => __( 'Photo Scroll', 'seo-generator' ),
					'description' => __( 'Horizontal scrolling photo gallery', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/photo-scroll',
					'export_name' => 'PhotoScroll',
					'props'       => '',
					'content_slots' => [],
				],
				'book_appointment' => [
					'label'       => __( 'Book Appointment', 'seo-generator' ),
					'description' => __( 'Appointment booking section', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/book-appointment',
					'export_name' => 'BookAppointment',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading inviting to book an appointment' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 250, 'ai_hint' => 'Text about the in-store consultation experience' ],
						'cta_text'    => [ 'type' => 'text',     'max_length' => 30,  'ai_hint' => 'Button text like "Book Now" or "Schedule Visit"' ],
					],
				],
				'bravo_faq' => [
					'label'       => __( 'FAQ', 'seo-generator' ),
					'description' => __( 'Frequently asked questions accordion', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/bravo-faq',
					'export_name' => 'BravoFAQ',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'FAQ section heading' ],
					],
				],
			],
		],

		'contacts' => [
			'label'  => __( 'Contacts', 'seo-generator' ),
			'blocks' => [
				'contact_form_with_map' => [
					'label'       => __( 'Contact Form + Map', 'seo-generator' ),
					'description' => __( 'Contact form with embedded Google Map', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/contact-form-with-map',
					'export_name' => 'ContactFormWithMap',
					'props'       => '',
					'content_slots' => [],
				],
				'covered' => [
					'label'       => __( 'Covered Section', 'seo-generator' ),
					'description' => __( 'Services coverage overview', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/covered',
					'export_name' => 'Covered',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading about services coverage area' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Text about service areas and coverage' ],
					],
				],
				'discover' => [
					'label'       => __( 'Discover', 'seo-generator' ),
					'description' => __( 'Discover Bravo section', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/discover',
					'export_name' => 'Discover',
					'props'       => '',
					'content_slots' => [
						'heading'     => [ 'type' => 'text',     'max_length' => 80,  'ai_hint' => 'Heading inviting visitors to discover the store' ],
						'description' => [ 'type' => 'textarea', 'max_length' => 300, 'ai_hint' => 'Text about what visitors can discover at the store' ],
					],
				],
				'loves_us' => [
					'label'       => __( 'Loves Us', 'seo-generator' ),
					'description' => __( 'Customer love / testimonials', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/loves-us',
					'export_name' => 'LoveUs',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading about customer love and testimonials' ],
					],
				],
				'need_us' => [
					'label'       => __( 'Need Us', 'seo-generator' ),
					'description' => __( 'Contact info and store location', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/need-us',
					'export_name' => 'NeedUs',
					'props'       => '',
					'content_slots' => [
						'heading' => [ 'type' => 'text', 'max_length' => 80, 'ai_hint' => 'Heading about how to reach the store' ],
					],
				],
			],
		],

	],

	/**
	 * ──────────────────────────────────────────────────────────────
	 * Page tabs — each has a default block order but can use
	 * ANY block from the shared catalog above.
	 * ──────────────────────────────────────────────────────────────
	 */
	'pages' => [

		'homepage' => [
			'label'         => __( 'Homepage', 'seo-generator' ),
			'original_path' => 'src/app/page.tsx',
			'preview_route' => '/preview',
			'use_client'    => true,
			'wrapper_open'  => '',
			'wrapper_close' => '',
			'default_order' => [
				'hero_slider',
				'about_bravo',
				'featured_services',
				'shop_categories',
				'new_collection',
				'diamond_explore',
				'top_rated',
				'own_design',
				'handcrafted',
				'intro_gallery',
				'we_here',
			],
		],

		'about' => [
			'label'         => __( 'About Us', 'seo-generator' ),
			'original_path' => 'src/app/about/page.tsx',
			'preview_route' => '/preview',
			'use_client'    => true,
			'wrapper_open'  => "<div className='flex flex-col gap-[200px] max-[1439px]:gap-[132px] max-md:gap-[100px]'>",
			'wrapper_close' => '</div>',
			'default_metadata' => [
				'title'       => 'About Us - Bravo Jewellers | Our Story, Team & Mission',
				'description' => 'Discover the story behind Bravo Jewellers. Meet our expert team of jewelers, gemologists, and designers.',
			],
			'default_order' => [
				'about_menu',
				'about_hero_slider',
				'mission_statement',
				'why_custom_founders',
				'video_block',
				'piece_mind',
				'beyond_adornment',
				'beyond_adornment_gem',
				'our_team',
				'our_experts',
				'our_instagram',
				'we_here',
			],
		],

	],

];
