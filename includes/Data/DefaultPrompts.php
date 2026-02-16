<?php
/**
 * Default Prompt Templates
 *
 * @package SEOGenerator
 */

namespace SEOGenerator\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Provides default prompt templates for all content blocks.
 */
class DefaultPrompts {
	/**
	 * Default system message for all prompts.
	 */
	private const SYSTEM_MESSAGE = 'You are an expert {business_type} content writer creating SEO-optimized content for an e-commerce website. Write in a knowledgeable yet approachable tone. Focus on accuracy and helpful information. Avoid promotional language and sales fluff.';

	/**
	 * Get all default templates.
	 *
	 * @return array<string, array> Map of block type to template.
	 */
	public static function getAll(): array {
		return array(
			'seo_metadata'     => self::getSeoMetadataTemplate(),
			'hero'             => self::getHeroTemplate(),
			'about_section'    => self::getAboutSectionTemplate(),
			'serp_answer'      => self::getSerpAnswerTemplate(),
			'product_criteria' => self::getProductCriteriaTemplate(),
			'materials'        => self::getMaterialsTemplate(),
			'process'          => self::getProcessTemplate(),
			'comparison'       => self::getComparisonTemplate(),
			'product_showcase' => self::getProductShowcaseTemplate(),
			'size_fit'         => self::getSizeFitTemplate(),
			'care_warranty'    => self::getCareWarrantyTemplate(),
			'ethics'           => self::getEthicsTemplate(),
			'faqs'             => self::getFaqsTemplate(),
			'cta'              => self::getCtaTemplate(),
			'related_links'    => self::getRelatedLinksTemplate(),
			'pricing_hero'     => self::getPricingHeroTemplate(),
		);
	}

	/**
	 * Get default template for SEO metadata.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getSeoMetadataTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Generate SEO metadata for a page about {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target audience: jewelry shoppers researching this topic

Requirements:
- Focus Keyword: 2-4 words, primary search term for this page
- SEO Title: 50-60 characters optimized for search engines
  - Include focus keyword near the beginning
  - Compelling and click-worthy
  - Match search intent
- Meta Description: 120-155 characters (STRICT MAXIMUM 155)
  - Include focus keyword naturally
  - Clear value proposition
  - Call-to-action or benefit statement
  - Enticing snippet for search results
  - MUST be under 155 characters - this is critical for display in search results
- All fields must be concise, keyword-optimized, and follow SEO best practices

Output as JSON:
{
  "focus_keyword": "primary keyword phrase",
  "seo_title": "Your SEO title here",
  "meta_description": "Your meta description here"
}',
		);
	}

	/**
	 * Get default template for hero section.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getHeroTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write a compelling hero section for a page about {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Headline: 6-10 words, engaging and keyword-optimized
- Subheadline: 15-20 words providing context and drawing readers in
- Summary: 60-80 words describing what the page covers, key benefits, and target audience
- Natural keyword integration
- Clear value proposition
- No promotional language
- Focus on information and guidance

Output as JSON:
{
  "headline": "Your headline here",
  "subheadline": "Your subheadline here",
  "summary": "Your summary here"
}',
		);
	}

	/**
	 * Get default template for about section.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getAboutSectionTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Generate 4 key trust factors and guarantees for a jewelry company page about {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}
- Company: {business_name} - {business_type} in {service_area}

Requirements:
- Generate exactly 4 UNIQUE guarantees/trust factors that are RELEVANT to this specific page topic
- CRITICAL: Use DIVERSE icon combinations - avoid repetitive patterns across different pages
- Each feature must have:
  - Icon type: Choose from 12 available options. Mix up your selections creatively.
  - Available icons (interpret broadly): shipping, returns, warranty, finance, quality, secure, support, eco, diamond, resize, gift, repair
  - Feature title: 2-3 words maximum (prefer 2 words)
  - Feature description: 3-6 words explaining the benefit

Icon Selection Strategy:
1. Think beyond literal meanings:
   - "diamond" = certification, premium quality, verified authenticity (ANY product)
   - "resize" = customization, adjustments, personalization (ANY service)
   - "repair" = maintenance, ongoing care, support (ANY long-term commitment)
   - "eco" = sustainability, ethics, responsibility (ANY category)
   - "gift" = special occasions, presentation, experiences (ANY celebratory context)

2. VARY your combinations - don\'t default to the same 4 icons every time:
   - Care Guide: Try "repair, support, warranty, quality"
   - Buying Guide: Try "diamond, finance, secure, gift"
   - Product Collection: Try "quality, gift, resize, eco"
   - Size Guide: Try "resize, support, returns, secure"
   - Sustainability: Try "eco, diamond, quality, support"

3. Consider the page\'s unique angle:
   - What makes THIS page different from others?
   - What trust factors are MOST relevant here?
   - Which icons haven\'t been overused?

Your Mission: Create a FRESH, UNIQUE set of 4 guarantees that feel specifically tailored to this page\'s topic. Challenge yourself to use different icon combinations than you might typically choose.

Output as JSON (features array only):
{
  "features": [
    {
      "icon_type": "appropriate_icon_for_page",
      "feature_title": "2-3 words",
      "feature_description": "3-6 words"
    },
    {
      "icon_type": "appropriate_icon_for_page",
      "feature_title": "2-3 words",
      "feature_description": "3-6 words"
    },
    {
      "icon_type": "appropriate_icon_for_page",
      "feature_title": "2-3 words",
      "feature_description": "3-6 words"
    },
    {
      "icon_type": "appropriate_icon_for_page",
      "feature_title": "2-3 words",
      "feature_description": "3-6 words"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for SERP answer.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getSerpAnswerTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write a concise SERP answer for the question: {page_title}

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Exactly 40-60 words
- Direct answer to the implied question
- Include 1-2 key facts or data points
- Natural keyword integration
- Informative and authoritative tone
- No filler words or promotional language

Output as plain text.',
		);
	}

	/**
	 * Get default template for product criteria.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getProductCriteriaTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write 3-4 key criteria for selecting products related to {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- 3-4 distinct criteria
- Each criterion: title (3-5 words) + explanation (30-40 words)
- Focus on practical decision factors
- Include specific considerations (materials, budget, style, etc.)
- Actionable and educational
- No product recommendations or promotions

Output as JSON:
{
  "criteria": [
    {
      "title": "Criterion title",
      "explanation": "Explanation text"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for materials explanation.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getMaterialsTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write an educational explanation about materials related to {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Introduction: 40-60 words explaining why materials matter
- 3-5 material types with:
  - Material name (2-4 words)
  - Description (50-70 words)
  - Key properties and characteristics
  - Pros and cons where relevant
- Educational and informative tone
- Technical accuracy
- No product promotions

Output as JSON:
{
  "introduction": "Introduction text",
  "materials": [
    {
      "name": "Material name",
      "description": "Material description"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for process explanation.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getProcessTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write a step-by-step process guide related to {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Introduction: 40-50 words explaining the process
- 4-6 steps with:
  - Step title (4-6 words)
  - Description (50-70 words)
  - Practical tips and considerations
- Logical sequential flow
- Clear and actionable
- Educational tone

Output as JSON:
{
  "introduction": "Introduction text",
  "steps": [
    {
      "title": "Step title",
      "description": "Step description"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for comparison table.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getComparisonTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Create a comparison table for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Introduction: 40-50 words explaining what is being compared
- 2-4 options to compare
- 4-6 comparison factors (price, durability, style, maintenance, etc.)
- Each cell: concise information (5-15 words)
- Objective and factual
- No product promotions or bias
- Include "Best For" row summarizing ideal use cases

Output as JSON:
{
  "introduction": "Introduction text",
  "factors": ["Factor 1", "Factor 2", "Factor 3"],
  "options": [
    {
      "name": "Option name",
      "values": ["Value for factor 1", "Value for factor 2"],
      "best_for": "Ideal use case"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for product showcase.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getProductShowcaseTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write educational product category descriptions for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Introduction: 40-60 words about the product category
- 3-5 product types/styles with:
  - Type name (2-4 words)
  - Description (60-80 words)
  - Key features and characteristics
  - When to choose this type
- Educational focus, not promotional
- Highlight variety and options

Output as JSON:
{
  "introduction": "Introduction text",
  "products": [
    {
      "name": "Product type name",
      "description": "Product type description"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for size and fit guidance.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getSizeFitTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write size and fit guidance for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Introduction: 40-50 words on importance of proper fit
- 3-5 sizing tips with:
  - Tip title (4-6 words)
  - Explanation (50-70 words)
  - Practical measurement advice
  - Common mistakes to avoid
- Measurement guide information
- Actionable and practical
- Professional advice tone

Output as JSON:
{
  "introduction": "Introduction text",
  "tips": [
    {
      "title": "Tip title",
      "explanation": "Tip explanation"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for care and warranty.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getCareWarrantyTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write care and warranty information for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Care section:
  - Introduction (30-40 words)
  - 4-6 care tips (each 40-60 words)
  - Specific dos and don\'ts
  - Maintenance best practices
- Warranty section:
  - General warranty information (60-80 words)
  - What\'s typically covered
  - Care requirements for warranty validity
- Informative and educational
- Build confidence in product longevity

Output as JSON:
{
  "care": {
    "introduction": "Care introduction",
    "tips": ["Tip 1", "Tip 2"]
  },
  "warranty": {
    "information": "Warranty information"
  }
}',
		);
	}

	/**
	 * Get default template for ethics and origin.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getEthicsTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write about ethical sourcing and origin for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Introduction: 50-70 words on importance of ethical sourcing
- 3-4 key aspects:
  - Aspect title (3-5 words)
  - Explanation (60-80 words)
  - Certifications or standards where relevant
  - Why it matters to consumers
- Topics may include: conflict-free sourcing, fair labor, environmental impact, traceability
- Educational and informative
- Build trust and transparency

Output as JSON:
{
  "introduction": "Introduction text",
  "aspects": [
    {
      "title": "Aspect title",
      "explanation": "Aspect explanation"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for FAQs.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getFaqsTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Generate 5-7 frequently asked questions and answers about {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- 5-7 question-answer pairs
- Questions: natural language, 6-12 words
- Answers: comprehensive but concise, 50-80 words
- Cover common concerns, technical details, buying guidance
- Informative and helpful
- Include keyword variations naturally
- Address real customer questions

Output as JSON:
{
  "faqs": [
    {
      "question": "Question text?",
      "answer": "Answer text"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for CTA section.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getCtaTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Write a call-to-action section for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Heading: 6-10 words, action-oriented
- Body: 80-120 words
  - Summarize key value from page content
  - Encourage next step (explore collection, learn more, etc.)
  - Build confidence in decision
  - Friendly and helpful tone
- Not overly promotional
- Focus on customer benefit and empowerment

Output as JSON:
{
  "heading": "CTA heading",
  "body": "CTA body text"
}',
		);
	}

	/**
	 * Get default template for related links section.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getRelatedLinksTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Generate 4 plausible related page suggestions for {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Section heading: 4-8 words, action-oriented (e.g., "SHOP ENGAGEMENT RINGS", "EXPLORE MORE STYLES")
  - First word should be a verb in uppercase (SHOP, EXPLORE, DISCOVER, BROWSE)
  - Rest of heading should describe the category or theme
- Generate 4 realistic related page suggestions based on common jewelry categories
- For each suggestion:
  - Title: 2-5 words, category or collection name (e.g., "Engagement Rings", "Men\'s Wedding Bands", "Diamond Collections")
  - URL: Logical slug format (e.g., /engagement-rings/, /mens-wedding-bands/, /diamond-collections/)
  - Description: 15-25 words explaining what readers would find on that page
  - Category: Single category tag (e.g., "Rings", "Bands", "Collections", "Guides")
  - Item count: Realistic number like "122 Items", "89 Items", "156 Items", etc.
- Choose suggestions that:
  - Complement the current topic naturally
  - Represent different but related categories
  - Help users explore related products or information
  - Cover a variety: products, guides, comparisons, collections
- Create realistic, diverse suggestions that make sense for a jewelry e-commerce site
- Use your knowledge of jewelry to generate logical related topics

IMPORTANT: Generate realistic suggestions even though these pages may not exist yet. The goal is to show what related content COULD be linked. Make them contextually relevant to {page_title}.

Output as JSON:
{
  "section_heading": "VERB CATEGORY NAME",
  "links": [
    {
      "link_title": "Category name",
      "link_url": "/category-url/",
      "link_description": "Description of what this page covers",
      "link_category": "Category tag",
      "link_item_count": "XX Items"
    },
    {
      "link_title": "Category name",
      "link_url": "/category-url/",
      "link_description": "Description of what this page covers",
      "link_category": "Category tag",
      "link_item_count": "XX Items"
    },
    {
      "link_title": "Category name",
      "link_url": "/category-url/",
      "link_description": "Description of what this page covers",
      "link_category": "Category tag",
      "link_item_count": "XX Items"
    },
    {
      "link_title": "Category name",
      "link_url": "/category-url/",
      "link_description": "Description of what this page covers",
      "link_category": "Category tag",
      "link_item_count": "XX Items"
    }
  ]
}',
		);
	}

	/**
	 * Get default template for pricing hero section.
	 *
	 * @return array Template with system and user messages.
	 */
	public static function getPricingHeroTemplate(): array {
		return array(
			'system' => self::SYSTEM_MESSAGE,
			'user'   => 'Generate a contextual pricing hero section for a page about {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

IMPORTANT:
1. Generate service categories and subservice labels that MATCH the page topic
2. Use PLACEHOLDER prices ($XX) - the store owner will manually add real prices later

Requirements:
- Hero Title: 6-12 words, compelling and relevant to page topic
  - Should emphasize the service type related to the page
  - Use uppercase for impact (e.g., "EXPERT RING SERVICES", "PROFESSIONAL WATCH REPAIR")
  - Make it relevant to the page topic
- Hero Description: 60-100 words
  - Describe the services available that relate to the page topic
  - Mention expertise, methods, and quality
  - Emphasize professional care
  - Professional yet approachable tone
- Pricing Items: Generate exactly 4-5 categories
  - Categories must be relevant to the page topic
  - Subservice labels should be contextual (Downsize/Upsize, Basic/Premium, etc.)
  - ALL PRICES must be "$XX" placeholder

CONTEXTUAL SERVICE CATEGORIES BY TOPIC:

For RING-related pages (rings, bands, engagement, wedding):
- Ring Sizing (Downsize / Upsize)
- Prong Retipping (2 Prongs / 4 Prongs)
- Stone Replacement (Small Stone / Large Stone)
- Ring Cleaning & Polishing (Basic / Deep Clean)
- Custom Resizing (consultation text)

For WATCH-related pages (watches, timepieces):
- Watch Servicing (Basic / Full Service)
- Battery Replacement (Standard / Premium)
- Band Adjustment (Resize / Replace)
- Crystal Repair (Polish / Replace)
- Movement Service (consultation text)

For BRACELET-related pages:
- Bracelet Sizing (Shorten / Lengthen)
- Link Repair (Single Link / Multiple Links)
- Clasp Replacement (Standard / Security Clasp)
- Bracelet Cleaning & Polishing (Basic / Deep Clean)
- Custom Modifications (consultation text)

For NECKLACE/CHAIN-related pages:
- Pearl Restringing (Hand Knotted / Without Knots)
- Clasp Replacement (Standard / Magnetic)
- Necklace Shortening (1-2 inches / 3+ inches)
- Necklace Lengthening (Add Chain / Add Pearls)
- Cleaning & Restoration (consultation text)

For EARRING-related pages:
- Earring Repair (Post Repair / Backing Replacement)
- Clasp Conversion (Post to Clip / Clip to Post)
- Pearl Replacement (Single / Pair)
- Earring Cleaning (Basic / Deep Clean)
- Custom Modifications (consultation text)

For REPAIR/RESTORATION pages:
- Stone Setting (Small / Large)
- Prong Retipping (2 Prongs / 4 Prongs)
- Clasp Replacement (Standard / Secure)
- Chain Repair (Simple / Complex)
- Full Restoration (consultation text)

For PENDANT/CHARM-related pages:
- Pendant Repair (Bail Repair / Full Replacement)
- Charm Attachment (Single / Multiple)
- Engraving (Text Only / Design)
- Pendant Polishing (Basic / Deep Clean)
- Custom Pendant Creation (consultation text)

For APPRAISAL pages:
- Insurance Appraisal (Single Item / Multiple Items)
- Estate Appraisal (Small Collection / Large Collection)
- Damage Assessment (Minor / Major)
- Verbal Appraisal (Quick / Detailed)
- Written Report (consultation text)

For ENGRAVING service pages:
- Text Engraving (Short Message / Long Message)
- Design Engraving (Simple / Complex)
- Inside Ring Engraving (Text / Symbols)
- Pendant Engraving (Front / Back)
- Custom Artwork (consultation text)

For CUSTOM DESIGN pages:
- Design Consultation (1 Hour / 2 Hours)
- CAD Rendering (Basic / Detailed)
- Wax Model (Simple / Complex)
- Metal Casting (Standard / Premium Metal)
- Final Assembly (consultation text)

For GEMSTONE-specific pages (sapphire, ruby, emerald, diamond, etc.):
- Stone Sourcing (Standard / Premium)
- Stone Setting (Simple / Complex)
- Stone Replacement (Match Existing / Upgrade)
- Stone Cleaning (Basic / Professional)
- Certification (consultation text)

For METAL-specific pages (gold, silver, platinum):
- Metal Testing (Basic / Detailed)
- Replating (Small Item / Large Item)
- Metal Polishing (Basic / Mirror Finish)
- Rhodium Plating (Partial / Full)
- Metal Conversion (consultation text)

For BROOCH/PIN pages:
- Pin Repair (Clasp / Full Mechanism)
- Pin Conversion (Brooch to Pendant / Pendant to Brooch)
- Pin Cleaning (Basic / Detailed)
- Stone Tightening (Single / Multiple)
- Restoration (consultation text)

For ANKLET pages:
- Anklet Sizing (Shorten / Lengthen)
- Clasp Replacement (Standard / Secure)
- Charm Addition (Single / Multiple)
- Anklet Cleaning (Basic / Deep Clean)
- Custom Design (consultation text)

For CUFFLINK/TIE ACCESSORY pages:
- Cufflink Repair (Post / Mechanism)
- Engraving (Single / Pair)
- Stone Replacement (Small / Large)
- Polishing (Basic / Premium)
- Custom Creation (consultation text)

For WEDDING/BRIDAL service pages:
- Wedding Band Sizing (His / Hers)
- Engraving (Inside / Outside)
- Ring Polishing (Single / Both)
- Rush Service (1 Day / Same Day)
- Wedding Set Consultation (consultation text)

For GENERAL jewelry or mixed topics:
- Ring Sizing (Downsize / Upsize)
- Bracelet Sizing (Shorten / Lengthen)
- Chain Repair (Simple / Complex)
- Stone Setting (Small / Large)
- Custom Work (consultation text)

STRUCTURE:
For standard categories (most items):
- Category name: 2-5 words describing the service type (e.g., "Ring Sizing", "Prong Retipping", "Bracelet Cleaning & Polishing")
- Downsize label: Service variation 1 (e.g., "Downsize", "Basic", "Shorten", "Small Stone", "2 Prongs", "Single Link")
- Downsize price: "$XX" (ALWAYS use placeholder)
- Upsize label: Service variation 2 (e.g., "Upsize", "Premium", "Lengthen", "Large Stone", "4 Prongs", "Multiple Links")
- Upsize price: "$XX" (ALWAYS use placeholder)

For custom/consultation category (last item):
- Category name: Related to services (e.g., "Custom Resizing", "Movement Service", "Custom Modifications")
- Custom text: Brief message (e.g., "Prices available upon consultation", "Contact us for estimate")

Output as JSON:
{
  "hero_title": "CONTEXTUAL TITLE MATCHING PAGE TOPIC IN UPPERCASE",
  "hero_description": "Your 60-100 word description about the relevant services for this page topic",
  "pricing_items": [
    {
      "category": "Contextual Category Name 1",
      "downsize_label": "Contextual Variation 1",
      "downsize_price": "$XX",
      "upsize_label": "Contextual Variation 2",
      "upsize_price": "$XX"
    },
    {
      "category": "Contextual Category Name 2",
      "downsize_label": "Contextual Variation 1",
      "downsize_price": "$XX",
      "upsize_label": "Contextual Variation 2",
      "upsize_price": "$XX"
    },
    {
      "category": "Contextual Category Name 3",
      "downsize_label": "Contextual Variation 1",
      "downsize_price": "$XX",
      "upsize_label": "Contextual Variation 2",
      "upsize_price": "$XX"
    },
    {
      "category": "Contextual Category Name 4",
      "downsize_label": "Contextual Variation 1",
      "downsize_price": "$XX",
      "upsize_label": "Contextual Variation 2",
      "upsize_price": "$XX"
    },
    {
      "category": "Custom/Specialty Service",
      "custom_text": "Prices available upon consultation"
    }
  ]
}

CRITICAL:
- Match services to page topic:
  * Ring pages = ring sizing services
  * Watch pages = watch servicing
  * Bracelet pages = bracelet sizing/repair
  * Necklace pages = necklace/chain services
  * Pendant/Charm pages = pendant repair/attachment
  * Appraisal pages = appraisal services
  * Engraving pages = engraving services
  * Custom Design pages = design consultation services
  * Gemstone pages = stone sourcing/setting/cleaning
  * Metal pages = metal testing/plating/polishing
  * Brooch pages = pin repair/conversion
  * Anklet pages = anklet sizing/repair
  * Cufflink pages = cufflink repair/engraving
  * Wedding/Bridal pages = wedding-specific services
- Use contextual category names and subservice labels
- ALL PRICES must be "$XX" - the store owner will manually fill in real prices',
		);
	}

	/**
	 * Get template for a specific block type.
	 *
	 * @param string $block_type Block type identifier.
	 * @return array|null Template or null if not found.
	 */
	public static function get( string $block_type ): ?array {
		$templates = self::getAll();
		return $templates[ $block_type ] ?? null;
	}
}
