<?php
/**
 * Next.js Block Definitions Configuration
 *
 * Defines blocks for each page of the Bravo Jewellers Next.js site.
 * Each block maps to a self-contained React component — no HTML templates,
 * just metadata for the page builder to reference.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

return [

	/**
	 * ──────────────────────────────────────────────────────────────
	 * Page registry — each key is a page slug.
	 * ──────────────────────────────────────────────────────────────
	 */
	'pages' => [

		// ─── Homepage ─────────────────────────────────────────────
		'homepage' => [
			'label'          => __( 'Homepage', 'seo-generator' ),
			'file_path'      => 'src/app/page.tsx',
			'preview_route'  => '/preview',
			'wrapper_open'   => '',
			'wrapper_close'  => '',
			'blocks'         => [

				'hero_slider' => [
					'label'       => __( 'Hero Slider', 'seo-generator' ),
					'description' => __( 'Full-width hero with video/image slides', 'seo-generator' ),
					'import_path' => '@widgets/hero-slider',
					'export_name' => 'HeroSlider',
					'props'       => " page='default'",
					'order'       => 1,
				],

				'about_bravo' => [
					'label'       => __( 'About Bravo', 'seo-generator' ),
					'description' => __( 'Family-owned, master jewelers, GIA gemologists, lifetime care', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/about-bravo',
					'export_name' => 'AboutBravo',
					'props'       => '',
					'order'       => 2,
				],

				'featured_services' => [
					'label'       => __( 'Featured Services', 'seo-generator' ),
					'description' => __( 'Custom design, repairs, resizing, watch repairs, gold buying, appraisals', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/featured-services',
					'export_name' => 'FeaturedServices',
					'props'       => '',
					'order'       => 3,
				],

				'shop_categories' => [
					'label'       => __( 'Shop Categories', 'seo-generator' ),
					'description' => __( 'Browse jewelry by category with image cards', 'seo-generator' ),
					'import_path' => '@widgets/shop-categories',
					'export_name' => 'ShopCategories',
					'props'       => '',
					'order'       => 4,
				],

				'new_collection' => [
					'label'       => __( 'New Collection', 'seo-generator' ),
					'description' => __( 'Featured collection spotlight with product details', 'seo-generator' ),
					'import_path' => '@widgets/new-collection',
					'export_name' => 'NewCollection',
					'props'       => '',
					'order'       => 5,
				],

				'diamond_explore' => [
					'label'       => __( 'Diamond Explorer', 'seo-generator' ),
					'description' => __( 'Interactive diamond shape explorer', 'seo-generator' ),
					'import_path' => '@widgets/diamond-explore',
					'export_name' => 'DiamondExplore',
					'props'       => '',
					'order'       => 6,
				],

				'top_rated' => [
					'label'       => __( 'Top Rated Jeweler', 'seo-generator' ),
					'description' => __( 'Customer reviews with Google, Yelp, and Facebook ratings', 'seo-generator' ),
					'import_path' => '@/widgets/main-page/top-rated-jeweler',
					'export_name' => 'TopRated',
					'props'       => '',
					'order'       => 7,
				],

				'own_design' => [
					'label'       => __( 'Design Your Own Ring', 'seo-generator' ),
					'description' => __( 'Custom engagement ring design CTA with animated stones', 'seo-generator' ),
					'import_path' => '@widgets/own-design',
					'export_name' => 'OwnDesign',
					'props'       => '',
					'order'       => 8,
				],

				'handcrafted' => [
					'label'       => __( 'Handcrafted in California', 'seo-generator' ),
					'description' => __( 'Workshop image carousel showcasing craftsmanship', 'seo-generator' ),
					'import_path' => '@widgets/handcrafted',
					'export_name' => 'Handcrafted',
					'props'       => '',
					'order'       => 9,
				],

				'intro_gallery' => [
					'label'       => __( 'Instagram Gallery', 'seo-generator' ),
					'description' => __( 'Social media photo grid — "Be part of Something Brilliant"', 'seo-generator' ),
					'import_path' => '@widgets/intro-gallery',
					'export_name' => 'IntroGallery',
					'props'       => '',
					'order'       => 10,
				],

				'we_here' => [
					'label'       => __( "We're Here For You", 'seo-generator' ),
					'description' => __( 'Appointment booking form with business hours', 'seo-generator' ),
					'import_path' => '@widgets/we-heare',
					'export_name' => 'WeHere',
					'props'       => '',
					'order'       => 11,
				],

			],

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

		// ─── About Us ─────────────────────────────────────────────
		'about' => [
			'label'          => __( 'About Us', 'seo-generator' ),
			'file_path'      => 'src/app/about/page.tsx',
			'preview_route'  => '/preview/about',
			'wrapper_open'   => "<div className='flex flex-col gap-[200px] max-[1439px]:gap-[132px] max-md:gap-[100px]'>",
			'wrapper_close'  => '</div>',
			'metadata'       => [
				'title'       => "About Us - Bravo Jewellers | Our Story, Team & Mission",
				'description' => "Discover the story behind Bravo Jewellers. Meet our expert team of jewelers, gemologists, and designers. Learn about our mission to create exceptional custom jewelry with integrity and personal touch.",
			],
			'blocks'         => [

				'about_menu' => [
					'label'       => __( 'Navigation Menu', 'seo-generator' ),
					'description' => __( 'Transparent overlay menu bar', 'seo-generator' ),
					'import_path' => '@/features/menu',
					'export_name' => 'Menu',
					'props'       => " className='relative z-30'",
					'order'       => 1,
				],

				'about_hero_slider' => [
					'label'       => __( 'Hero Slider (About)', 'seo-generator' ),
					'description' => __( 'Hero banner for the About Us page', 'seo-generator' ),
					'import_path' => '@/widgets/hero-slider',
					'export_name' => 'HeroSlider',
					'props'       => " page='about' className='-mt-[5.75rem] md:-mt-[6.75rem] lg:-mt-[7.25rem]'",
					'order'       => 2,
				],

				'mission_statement' => [
					'label'       => __( 'Mission Statement', 'seo-generator' ),
					'description' => __( 'Company mission and values section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/mission-statement',
					'export_name' => 'MissionStatement',
					'props'       => '',
					'order'       => 3,
				],

				'why_custom_founders' => [
					'label'       => __( 'Meet the Founders', 'seo-generator' ),
					'description' => __( 'Why Custom section customized as founders introduction', 'seo-generator' ),
					'import_path' => '@/widgets/custom-page/why-custom/WhyCustom',
					'export_name' => 'WhyCustom',
					'props'       => " titlePart='THE ' titleAccent='FOUNDERS' title='MEET '",
					'order'       => 4,
				],

				'video_block' => [
					'label'       => __( 'Video Block', 'seo-generator' ),
					'description' => __( 'Featured video about Bravo Jewellers', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/video-block',
					'export_name' => 'VideoBlock',
					'props'       => '',
					'order'       => 5,
				],

				'piece_mind' => [
					'label'       => __( 'Piece of Mind', 'seo-generator' ),
					'description' => __( 'Peace of mind / trust section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/piece-of-mind',
					'export_name' => 'PieceMind',
					'props'       => '',
					'order'       => 6,
				],

				'beyond_adornment' => [
					'label'       => __( 'Beyond Adornment', 'seo-generator' ),
					'description' => __( 'Jewelry philosophy and craftsmanship section', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/beyond-adornment',
					'export_name' => 'BeyondAdornment',
					'props'       => '',
					'order'       => 7,
				],

				'beyond_adornment_gem' => [
					'label'       => __( 'Beyond Adornment — Gem', 'seo-generator' ),
					'description' => __( 'Gemstone-focused continuation of Beyond Adornment', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/beyond-adornment-gem',
					'export_name' => 'BeyondAdornmentGem',
					'props'       => '',
					'order'       => 8,
				],

				'our_team' => [
					'label'       => __( 'Our Team', 'seo-generator' ),
					'description' => __( 'Team member profiles and photos', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/our-team',
					'export_name' => 'OurTeam',
					'props'       => '',
					'order'       => 9,
				],

				'our_experts' => [
					'label'       => __( 'Our Experts', 'seo-generator' ),
					'description' => __( 'Expert gemologists and jewelers highlight', 'seo-generator' ),
					'import_path' => '@/widgets/diamonds/our-experts',
					'export_name' => 'OurExperts',
					'props'       => '',
					'order'       => 10,
				],

				'our_instagram' => [
					'label'       => __( 'Our Instagram', 'seo-generator' ),
					'description' => __( 'Instagram feed integration', 'seo-generator' ),
					'import_path' => '@/widgets/about-us/our-instagram',
					'export_name' => 'OurInst',
					'props'       => '',
					'order'       => 11,
				],

				'about_we_here' => [
					'label'       => __( "We're Here For You", 'seo-generator' ),
					'description' => __( 'Appointment booking form with business hours', 'seo-generator' ),
					'import_path' => '@widgets/we-heare',
					'export_name' => 'WeHere',
					'props'       => '',
					'order'       => 12,
				],

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
				'about_we_here',
			],
		],

	],
];
