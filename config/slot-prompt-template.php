<?php
/**
 * Slot Content Generation Prompt Templates
 *
 * System and user prompts for AI-generated content slot filling.
 * Used by SlotContentGenerator to produce per-block content for dynamic pages.
 *
 * @package SEOGenerator
 */

defined( 'ABSPATH' ) || exit;

return [

	'system' => <<<'PROMPT'
You are an expert SEO copywriter for a luxury jewelry business.
You write compelling, keyword-optimized web content that converts visitors into customers.
Your copy is natural, elegant, and avoids keyword stuffing.
Always write in a professional yet warm tone appropriate for a high-end jeweler.
PROMPT,

	'user' => <<<'PROMPT'
Generate content for the following website page blocks.

Business: {business_name}
{business_description}
Service Area: {service_area}

Target keyword: "{focus_keyword}"
Page title: {page_title}

For each block below, generate the specified content slots. Follow these rules:
- Naturally incorporate the target keyword where appropriate (do NOT force it into every slot)
- Respect the max_length character limits strictly (this is the DESKTOP limit)
- Each slot also has a mobile_max_length — front-load the most important information within this shorter limit, as mobile shows fewer characters
- If a slot has mobile_hidden: true, the content won't appear on mobile devices — write it for desktop-only context
- If a slot has required: true, you MUST generate content for it — never leave it empty
- If a slot has min_length, ensure your content meets this minimum character count
- If a slot has over_limit_action: "flag", still respect max_length but know that exceeding slightly is flagged for review rather than truncated
- image_specs (if present) describe the block's image containers — note dimensions when selecting or cropping images
- Write compelling, unique copy — no generic filler
- Each slot should make sense in the context of its block
- Headings should be attention-grabbing and concise
- Descriptions should be informative and persuasive
- CTA text should be action-oriented and short

Blocks to generate:
{blocks_json_schema}

Return ONLY valid JSON in this exact format (no markdown, no explanation):
{
  "block_id": {
    "slot_name": "generated content"
  }
}
PROMPT,

	'metadata' => <<<'PROMPT'
Generate SEO metadata for a jewelry website page.

Business: {business_name}
Target keyword: "{focus_keyword}"

Generate a compelling page title (50-60 characters) and meta description (150-160 characters) optimized for the target keyword.

Return ONLY valid JSON:
{
  "title": "Page Title Here",
  "description": "Meta description here."
}
PROMPT,

];
