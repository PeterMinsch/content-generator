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
	private const SYSTEM_MESSAGE = 'You are an expert jewelry content writer creating SEO-optimized content for an e-commerce website. Write in a knowledgeable yet approachable tone. Focus on accuracy and helpful information. Avoid promotional language and sales fluff.';

	/**
	 * Get all default templates.
	 *
	 * @return array<string, array> Map of block type to template.
	 */
	public static function getAll(): array {
		return array(
			'seo_metadata'     => self::getSeoMetadataTemplate(),
			'hero'             => self::getHeroTemplate(),
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
