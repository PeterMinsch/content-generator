<?php
/**
 * Next.js Block Definitions Configuration
 *
 * Master catalog of ALL widget blocks organized by group.
 * Both page tabs can use any block from any group.
 * Each page tab has its own default order.
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
				],
				'about_bravo' => [
					'label'       => __( 'About Bravo', 'seo-generator' ),
					'description' => __( 'Family-owned, master jewelers, GIA gemologists, lifetime care', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/about-bravo',
					'export_name' => 'AboutBravo',
					'props'       => '',
				],
				'featured_services' => [
					'label'       => __( 'Featured Services', 'seo-generator' ),
					'description' => __( 'Custom design, repairs, resizing, watch repairs, gold buying, appraisals', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/featured-services',
					'export_name' => 'FeaturedServices',
					'props'       => '',
				],
				'shop_categories' => [
					'label'       => __( 'Shop Categories', 'seo-generator' ),
					'description' => __( 'Browse jewelry by category with image cards', 'seo-generator' ),
					'import_path' => '@widgets/shop-categories',
					'export_name' => 'ShopCategories',
					'props'       => '',
				],
				'new_collection' => [
					'label'       => __( 'New Collection', 'seo-generator' ),
					'description' => __( 'Featured collection spotlight with product details', 'seo-generator' ),
					'import_path' => '@widgets/new-collection',
					'export_name' => 'NewCollection',
					'props'       => '',
				],
				'diamond_explore' => [
					'label'       => __( 'Diamond Explorer', 'seo-generator' ),
					'description' => __( 'Interactive diamond shape explorer', 'seo-generator' ),
					'import_path' => '@widgets/diamond-explore',
					'export_name' => 'DiamondExplore',
					'props'       => '',
				],
				'top_rated' => [
					'label'       => __( 'Top Rated Jeweler', 'seo-generator' ),
					'description' => __( 'Customer reviews with Google, Yelp, and Facebook ratings', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/top-rated-jeweler',
					'export_name' => 'TopRated',
					'props'       => '',
				],
				'own_design' => [
					'label'       => __( 'Design Your Own Ring', 'seo-generator' ),
					'description' => __( 'Custom engagement ring design CTA with animated stones', 'seo-generator' ),
					'import_path' => '@widgets/own-design',
					'export_name' => 'OwnDesign',
					'props'       => '',
				],
				'handcrafted' => [
					'label'       => __( 'Handcrafted in California', 'seo-generator' ),
					'description' => __( 'Workshop image carousel showcasing craftsmanship', 'seo-generator' ),
					'import_path' => '@widgets/handcrafted',
					'export_name' => 'Handcrafted',
					'props'       => '',
				],
				'intro_gallery' => [
					'label'       => __( 'Instagram Gallery', 'seo-generator' ),
					'description' => __( 'Social media photo grid — "Be part of Something Brilliant"', 'seo-generator' ),
					'import_path' => '@widgets/intro-gallery',
					'export_name' => 'IntroGallery',
					'props'       => '',
				],
				'we_here' => [
					'label'       => __( "We're Here For You", 'seo-generator' ),
					'description' => __( 'Appointment booking form with business hours', 'seo-generator' ),
					'import_path' => '@widgets/we-heare',
					'export_name' => 'WeHere',
					'props'       => '',
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
				],
				'about_hero_slider' => [
					'label'       => __( 'Hero Slider (About)', 'seo-generator' ),
					'description' => __( 'Hero banner for the About Us page', 'seo-generator' ),
					'import_path' => '@/widgets/hero-slider',
					'export_name' => 'HeroSlider',
					'props'       => " page='about' className='-mt-[5.75rem] md:-mt-[6.75rem] lg:-mt-[7.25rem]'",
				],
				'mission_statement' => [
					'label'       => __( 'Mission Statement', 'seo-generator' ),
					'description' => __( 'Company mission and values section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/mission-statement',
					'export_name' => 'MissionStatement',
					'props'       => '',
				],
				'why_custom_founders' => [
					'label'       => __( 'Meet the Founders', 'seo-generator' ),
					'description' => __( 'Why Custom section customized as founders introduction', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/why-custom/WhyCustom',
					'export_name' => 'WhyCustom',
					'props'       => " titlePart='THE ' titleAccent='FOUNDERS' title='MEET '",
				],
				'video_block' => [
					'label'       => __( 'Video Block', 'seo-generator' ),
					'description' => __( 'Featured video about Bravo Jewellers', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/video-block',
					'export_name' => 'VideoBlock',
					'props'       => '',
				],
				'piece_mind' => [
					'label'       => __( 'Piece of Mind', 'seo-generator' ),
					'description' => __( 'Peace of mind / trust section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/piece-of-mind',
					'export_name' => 'PieceMind',
					'props'       => '',
				],
				'beyond_adornment' => [
					'label'       => __( 'Beyond Adornment', 'seo-generator' ),
					'description' => __( 'Jewelry philosophy and craftsmanship section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/beyond-adornment',
					'export_name' => 'BeyondAdornment',
					'props'       => '',
				],
				'beyond_adornment_gem' => [
					'label'       => __( 'Beyond Adornment — Gem', 'seo-generator' ),
					'description' => __( 'Gemstone-focused continuation of Beyond Adornment', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/beyond-adornment-gem',
					'export_name' => 'BeyondAdornmentGem',
					'props'       => '',
				],
				'our_team' => [
					'label'       => __( 'Our Team', 'seo-generator' ),
					'description' => __( 'Team member profiles and photos', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/our-team',
					'export_name' => 'OurTeam',
					'props'       => '',
				],
				'our_experts' => [
					'label'       => __( 'Our Experts', 'seo-generator' ),
					'description' => __( 'Expert gemologists and jewelers highlight', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/our-experts',
					'export_name' => 'OurExperts',
					'props'       => '',
				],
				'our_instagram' => [
					'label'       => __( 'Our Instagram', 'seo-generator' ),
					'description' => __( 'Instagram feed integration', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/our-instagram',
					'export_name' => 'OurInst',
					'props'       => '',
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
				],
				'diamond_shapes' => [
					'label'       => __( 'Diamond Shapes', 'seo-generator' ),
					'description' => __( 'Diamond shape selection guide', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/diamond-shapes',
					'export_name' => 'DiamondShapes',
					'props'       => '',
				],
				'natural_vs_labgrown' => [
					'label'       => __( 'Natural vs Lab-Grown', 'seo-generator' ),
					'description' => __( 'Natural vs lab-grown diamond comparison', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/natural-vs-labgrown',
					'export_name' => 'NaturalVsLabgrown',
					'props'       => '',
				],
				'perfect_diamond' => [
					'label'       => __( 'Perfect Diamond', 'seo-generator' ),
					'description' => __( 'Find your perfect diamond section', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/perfect-diamond',
					'export_name' => 'PerfectDiamond',
					'props'       => '',
				],
				'diamonds_price' => [
					'label'       => __( 'Diamond Pricing', 'seo-generator' ),
					'description' => __( 'Diamond pricing guide and calculator', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/diamonds-price',
					'export_name' => 'DiamondsPrice',
					'props'       => '',
				],
				'beyond_the_five' => [
					'label'       => __( 'Beyond the 5 Cs', 'seo-generator' ),
					'description' => __( 'Advanced diamond quality factors', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/beyond-the-five',
					'export_name' => 'Beyond',
					'props'       => '',
				],
				'assurance_block' => [
					'label'       => __( 'Assurance Block', 'seo-generator' ),
					'description' => __( 'Diamond quality assurance and certification', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/assurance-block',
					'export_name' => 'Assurance',
					'props'       => '',
				],
				'shop_engagement_rings' => [
					'label'       => __( 'Shop Engagement Rings', 'seo-generator' ),
					'description' => __( 'Engagement ring shopping CTA', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/shop-engagement-rings',
					'export_name' => 'EngagementRings',
					'props'       => '',
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
				],
				'bravo_difference' => [
					'label'       => __( 'The Bravo Difference', 'seo-generator' ),
					'description' => __( 'What sets Bravo apart from competitors', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/bravo-difference',
					'export_name' => 'BravoDifference',
					'props'       => '',
				],
				'crafts_manship' => [
					'label'       => __( 'Craftsmanship', 'seo-generator' ),
					'description' => __( 'Craftsmanship showcase section', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/crafts-manship',
					'export_name' => 'CraftsManship',
					'props'       => '',
				],
				'process_section' => [
					'label'       => __( 'Design Process', 'seo-generator' ),
					'description' => __( 'Step-by-step custom design process', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/process-section',
					'export_name' => 'ProcessSection',
					'props'       => '',
				],
				'master_piece' => [
					'label'       => __( 'Masterpiece', 'seo-generator' ),
					'description' => __( 'Featured custom masterpiece gallery', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/master-piece',
					'export_name' => 'MasterPiece',
					'props'       => '',
				],
				'comparison_rings' => [
					'label'       => __( 'Ring Comparison', 'seo-generator' ),
					'description' => __( 'Custom vs mass-produced ring comparison', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/comparison-rings',
					'export_name' => 'ComparisonRings',
					'props'       => '',
				],
				'describe_your_design' => [
					'label'       => __( 'Describe Your Design', 'seo-generator' ),
					'description' => __( 'Design intake / description form', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/describe-your-design',
					'export_name' => 'DescribeDesign',
					'props'       => '',
				],
				'calculator' => [
					'label'       => __( 'Price Calculator', 'seo-generator' ),
					'description' => __( 'Custom jewelry price estimator', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/calculator',
					'export_name' => 'CustomCalculator',
					'props'       => '',
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
				],
				'metals_selector' => [
					'label'       => __( 'Metal Selector', 'seo-generator' ),
					'description' => __( 'Choose ring metal type', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/metals-selector',
					'export_name' => 'MetalsSelector',
					'import_type' => 'default',
					'props'       => '',
				],
				'simple_buying' => [
					'label'       => __( 'Simple Buying Guide', 'seo-generator' ),
					'description' => __( 'Simplified ring buying process', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/simple-buying',
					'export_name' => 'SimpleBuying',
					'props'       => '',
				],
				'rings_why_choose' => [
					'label'       => __( 'Why Choose Bravo', 'seo-generator' ),
					'description' => __( 'Reasons to choose Bravo for engagement rings', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/rings-why-choose/AdvantagesSection',
					'export_name' => 'AdvantagesSection',
					'import_type' => 'default',
					'props'       => '',
				],
				'complete_experience' => [
					'label'       => __( 'Complete Experience', 'seo-generator' ),
					'description' => __( 'Full engagement ring experience', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/complete-experience',
					'export_name' => 'CompleteExperience',
					'props'       => '',
				],
				'currently_trending' => [
					'label'       => __( 'Currently Trending', 'seo-generator' ),
					'description' => __( 'Trending ring styles and designs', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/currently-trend',
					'export_name' => 'CurrentlyTrend',
					'props'       => '',
				],
				'custom_design_cta' => [
					'label'       => __( 'Custom Design CTA', 'seo-generator' ),
					'description' => __( 'Custom engagement ring design call-to-action', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/custom-design',
					'export_name' => 'CustomDesign',
					'props'       => '',
				],
				'financing_available' => [
					'label'       => __( 'Financing Available', 'seo-generator' ),
					'description' => __( 'Financing options section', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/financing-available',
					'export_name' => 'FinancingAvailable',
					'props'       => '',
				],
				'lifestyle' => [
					'label'       => __( 'Lifestyle', 'seo-generator' ),
					'description' => __( 'Lifestyle imagery and context', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/lifestyle/Lifestyle',
					'export_name' => 'Lifestyle',
					'import_type' => 'default',
					'props'       => '',
				],
				'photo_scroll' => [
					'label'       => __( 'Photo Scroll', 'seo-generator' ),
					'description' => __( 'Horizontal scrolling photo gallery', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/photo-scroll',
					'export_name' => 'PhotoScroll',
					'props'       => '',
				],
				'book_appointment' => [
					'label'       => __( 'Book Appointment', 'seo-generator' ),
					'description' => __( 'Appointment booking section', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/book-appointment',
					'export_name' => 'BookAppointment',
					'props'       => '',
				],
				'bravo_faq' => [
					'label'       => __( 'FAQ', 'seo-generator' ),
					'description' => __( 'Frequently asked questions accordion', 'seo-generator' ),
					'import_path' => '@/widgets/engagement-rings/bravo-faq',
					'export_name' => 'BravoFAQ',
					'props'       => '',
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
				],
				'covered' => [
					'label'       => __( 'Covered Section', 'seo-generator' ),
					'description' => __( 'Services coverage overview', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/covered',
					'export_name' => 'Covered',
					'props'       => '',
				],
				'discover' => [
					'label'       => __( 'Discover', 'seo-generator' ),
					'description' => __( 'Discover Bravo section', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/discover',
					'export_name' => 'Discover',
					'props'       => '',
				],
				'loves_us' => [
					'label'       => __( 'Loves Us', 'seo-generator' ),
					'description' => __( 'Customer love / testimonials', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/loves-us',
					'export_name' => 'LoveUs',
					'props'       => '',
				],
				'need_us' => [
					'label'       => __( 'Need Us', 'seo-generator' ),
					'description' => __( 'Contact info and store location', 'seo-generator' ),
					'import_path' => '@/widgets/contacts-page/need-us',
					'export_name' => 'NeedUs',
					'props'       => '',
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
