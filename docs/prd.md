# Product Requirements Document (PRD)
## WordPress SEO Content Generator Plugin for Jewelry E-Commerce

**Version:** 1.0
**Date:** January 2025
**Author:** Development Team
**Status:** Active Development

---

## Executive Summary

### Objective
Build a WordPress plugin that generates structured, SEO-optimized content pages for jewelry e-commerce using OpenAI's GPT-4 API. The plugin enables content managers to create 100+ high-quality pages per month with minimal manual work through automated content generation and intelligent image assignment.

### Success Metrics

- Generate complete page in under 5 minutes
- 95%+ AI generation success rate per block
- Content requires less than 10 minutes of editing per page
- OpenAI cost under $3 per page
- User can create 100 pages/month independently
- Zero copy/paste required (full automation)

### Key Differentiators

- Jewelry-specific content structure and prompts
- 12-block ACF-based architecture for easy redesign
- Automated image library with tag-based matching
- CSV keyword import from SEMrush
- No monthly SaaS fees (only OpenAI API costs)

---

## Technical Requirements

### Environment

- **WordPress:** 6.0+
- **PHP:** 8.0+
- **MySQL/MariaDB:** 5.7+ / 10.3+
- **Server Memory:** 256MB minimum
- **Max Execution Time:** 300 seconds
- **HTTPS:** Required (for OpenAI API calls)

### Dependencies

- **Advanced Custom Fields (ACF):** Free version (no REST API needed)
- **OpenAI API:** GPT-4 or GPT-4-turbo access
- **WordPress REST API:** Native (built-in)

### Browser Support

- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

## Architecture

### Custom Post Type

**Slug:** `seo-page`

```php
register_post_type('seo-page', [
    'labels' => [
        'name' => 'SEO Pages',
        'singular_name' => 'SEO Page'
    ],
    'public' => true,
    'has_archive' => false,
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-admin-page'
]);
```

### Taxonomy

**Slug:** `seo-topic`

**Default Terms:**
- Engagement Rings
- Wedding Bands
- Men's Wedding Bands
- Women's Wedding Bands
- Education
- Comparisons

### Image Tag Taxonomy

**Slug:** `image_tag`

**Default Tags:**
- **Metals:** platinum, gold, white-gold, rose-gold, tungsten, titanium
- **Types:** wedding-band, engagement-ring, fashion
- **Gender:** mens, womens, unisex
- **Styles:** classic, modern, vintage, minimalist
- **Finishes:** polished, brushed, hammered, matte

---

## Content Structure: 12 ACF Block Groups

All fields belong to field group: **"SEO Page Content Blocks"**
Location rule: Post Type = `seo-page`

### Block 1: Hero Section
- `hero_title` (text, required, max 100 chars)
- `hero_subtitle` (text, max 150 chars)
- `hero_summary` (textarea, max 400 chars)
- `hero_image` (image, return array)

### Block 2: SERP Answer
- `answer_heading` (text, max 100 chars)
- `answer_paragraph` (textarea, max 600 chars)
- `answer_bullets` (repeater)
  - `bullet_text` (text, max 150 chars)

### Block 3: Product Criteria
- `criteria_heading` (text)
- `criteria_items` (repeater)
  - `name` (text)
  - `explanation` (textarea)

### Block 4: Materials Explained
- `materials_heading` (text)
- `materials_items` (repeater)
  - `material` (text)
  - `pros` (textarea)
  - `cons` (textarea)
  - `best_for` (text)
  - `allergy_notes` (text)
  - `care` (text)

### Block 5: Process
- `process_heading` (text)
- `process_steps` (repeater, max 4)
  - `step_title` (text)
  - `step_text` (textarea, max 400 chars)
  - `step_image` (image)

### Block 6: Comparison
- `comparison_heading` (text)
- `comparison_left_label` (text)
- `comparison_right_label` (text)
- `comparison_summary` (textarea)
- `comparison_rows` (repeater)
  - `attribute` (text)
  - `left_text` (text, max 200 chars)
  - `right_text` (text, max 200 chars)

### Block 7: Product Showcase
- `showcase_heading` (text)
- `showcase_intro` (textarea)
- `showcase_products` (repeater)
  - `product_sku` (text)
  - `alt_image_url` (URL)

### Block 8: Size & Fit
- `size_heading` (text)
- `size_chart_image` (image)
- `comfort_fit_notes` (textarea)

### Block 9: Care & Warranty
- `care_heading` (text)
- `care_bullets` (repeater)
  - `bullet` (text)
- `warranty_heading` (text)
- `warranty_text` (textarea)

### Block 10: Ethics & Origin
- `ethics_heading` (text)
- `ethics_text` (textarea, max 800 chars)
- `certifications` (repeater)
  - `cert_name` (text)
  - `cert_link` (URL)

### Block 11: FAQs
- `faqs_heading` (text)
- `faq_items` (repeater)
  - `question` (text)
  - `answer` (textarea, max 600 chars)

### Block 12: CTA
- `cta_heading` (text)
- `cta_text` (textarea)
- `cta_primary_label` (text)
- `cta_primary_url` (URL)
- `cta_secondary_label` (text)
- `cta_secondary_url` (URL)

### SEO Meta Fields
- `seo_focus_keyword` (text)
- `seo_title` (text, max 65 chars)
- `seo_meta_description` (textarea, max 165 chars)
- `seo_canonical` (URL)

---

## Admin Interface

### Menu Structure

**Main Menu:** "Content Generator" (dashicons-edit-large, position 30)

**Sub-menu Items:**
- New Page (default)
- All SEO Pages
- Image Library Manager
- Settings
- Analytics

### New Page Interface Layout

**Basic Information Section:**
- Page Title (required)
- URL Slug (auto-generated, editable)
- Topic (dropdown)
- Focus Keyword (text)
- [Generate All Blocks] [Save Draft] buttons

**Content Blocks Section:**
- 12 collapsible sections (collapsed by default except Hero)
- Each block header shows:
  - Block name
  - Status indicator (○ Not Generated | ⏳ Generating | ✓ Generated | ✗ Failed | ✏️ Edited)
  - [Generate] button
- Character counters on applicable fields
- Real-time validation

**Block States:**
- **Not Generated:** Gray circle, empty fields
- **Generating:** Spinner animation, disabled fields
- **Generated:** Green checkmark, populated fields
- **Error:** Red X with error message
- **Edited:** Blue pencil (user modified after generation)

### Progress Modal (During "Generate All Blocks")

```
Generating Content...
Progress: Block 4 of 12

Progress bar: 33%

✓ Hero Section (3 seconds)
✓ SERP Answer (5 seconds)
✓ Product Criteria (4 seconds)
⏳ Materials Explained (generating...)
○ Process (waiting)
...

Estimated time remaining: 2m 15s

[Cancel]
```

### All SEO Pages List View

**Columns:**
- Checkbox (bulk select)
- Title
- Topic
- Status (Generated/Not Generated/Published/Draft)
- Date

**Bulk Actions:**
- Generate Content (NEW)
- Edit
- Move to Trash
- Change Topic

**Filters:**
- All | Generated | Not Generated | Published | Draft

---

## OpenAI Integration

### API Configuration

**Model:** `gpt-4-turbo-preview` (fallback to `gpt-4`)

**Request Parameters:**
```json
{
  "model": "gpt-4-turbo-preview",
  "temperature": 0.7,
  "max_tokens": 1000,
  "top_p": 1,
  "frequency_penalty": 0.3,
  "presence_penalty": 0.3
}
```

**Timeout:** 60 seconds per request

### Prompt Template System

Each block has a dedicated prompt template with variables:
- `{page_title}` - User-entered page title
- `{page_topic}` - Selected topic category
- `{focus_keyword}` - Target keyword
- `{page_type}` - Inferred from topic (comparison, education, collection)

#### Example: Hero Summary Template

**System Message:**
```
You are an expert jewelry content writer creating SEO-optimized content for an e-commerce website. Write in a knowledgeable yet approachable tone. Focus on accuracy and helpful information. Avoid promotional language and sales fluff.
```

**User Message:**
```
Write a 60-80 word summary for a page about {page_title}.

Context:
- Page type: {page_type}
- Topic category: {page_topic}
- Target keyword: {focus_keyword}

Requirements:
- Exactly 60-80 words
- Include what the page covers
- Mention key benefit or differentiation
- Indicate target audience
- Natural keyword integration
- No promotional language
- Focus on information value
```

### Generation Flow

#### Single Block Generation:
1. User clicks "Generate" button
2. AJAX request with nonce verification
3. Retrieve prompt template
4. Replace variables with context
5. Call OpenAI API via `wp_remote_post()`
6. Parse response
7. Validate output
8. Return via `wp_send_json_success()`
9. JavaScript populates field(s)
10. Update status indicator

#### Bulk Generation:
1. User clicks "Generate All Blocks"
2. Show progress modal
3. Loop through 12 blocks sequentially
4. For each: trigger generation, wait for response
5. Update progress indicator
6. Handle errors gracefully (continue to next)
7. Display completion summary
8. Allow retry of failed blocks

### Error Handling

**Scenarios:**
- **API key missing:** Show setup notice with link to settings
- **Rate limit (429):** Queue request, retry after 60 seconds
- **Timeout:** Retry once after 5 seconds, then show error
- **Invalid response:** Log to debug.log, show user-friendly message
- **Network error:** Show connectivity message, provide retry button

### Cost Tracking

Log each request to custom table:
- Post ID
- Block type
- Prompt tokens
- Completion tokens
- Total tokens
- Estimated cost (GPT-4-turbo: $0.01/$0.03 per 1K tokens)
- Model used
- Timestamp
- User ID
- Status

**Table:** `{prefix}_seo_generation_log`

```sql
CREATE TABLE {prefix}_seo_generation_log (
    id bigint(20) unsigned AUTO_INCREMENT PRIMARY KEY,
    post_id bigint(20) unsigned,
    block_type varchar(50),
    prompt_tokens int,
    completion_tokens int,
    total_tokens int,
    cost decimal(10,6),
    model varchar(50),
    status varchar(20),
    error_message text,
    user_id bigint(20) unsigned,
    created_at datetime,
    INDEX idx_post_id (post_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);
```

---

## Image Library System

### Bulk Upload Interface

**Admin Page:** "Image Library Manager"

**Features:**
- Multiple file upload (HTML5)
- Drag-and-drop support
- Progress indicator
- Automatic WordPress media library integration
- Mark images with custom meta: `_seo_library_image = 1`

### Tagging System

**Grid view showing:**
- Thumbnail
- Filename
- Current tags (removable)
- Add tag input (autocomplete from existing tags)
- Bulk tag editor

**Tag Management:**
- Create new tags on-the-fly
- Tag suggestions based on filename
- Bulk operations (select multiple images, apply tags)

### Auto-Assignment Algorithm

**Matching logic:**

```php
function find_matching_image($page_context) {
    // Extract keywords
    $keywords = [
        $page_context['focus_keyword'],
        $page_context['topic'],
        $page_context['category']
    ];

    // Convert to tag slugs
    $tags = keywords_to_tags($keywords);
    // e.g., ["platinum", "mens", "wedding-band"]

    // Try matching with ALL tags
    $images = query_images_with_tags($tags, 'AND');

    if (empty($images)) {
        // Fallback: try 2 tags
        array_pop($tags);
        $images = query_images_with_tags($tags, 'AND');
    }

    if (empty($images)) {
        // Fallback: try 1 tag
        $tags = [$tags[0]];
        $images = query_images_with_tags($tags, 'AND');
    }

    // Return random from matches
    return !empty($images) ? $images[array_rand($images)]->ID : null;
}
```

**Integration:** During page generation, auto-assign image to `hero_image` field if setting enabled.

---

## CSV Import Feature

### Import Interface

**Admin Page:** "Import Keywords"

**Process:**
1. Upload CSV file
2. Map columns:
   - CSV column → Page Title
   - CSV column → Focus Keyword
   - CSV column → Topic Category
   - CSV column → Image URL (optional)
3. Options:
   - ○ Create drafts only (manual generation)
   - ● Auto-generate content (bulk)
4. Preview first 3 rows
5. [Import] button

### Import Processing

**Workflow:**

```php
foreach ($csv_rows as $index => $row) {
    // Create post
    $post_id = wp_insert_post([
        'post_type' => 'seo-page',
        'post_title' => $row['keyword'],
        'post_status' => 'draft'
    ]);

    // Set meta
    update_field('seo_focus_keyword', $row['keyword'], $post_id);

    // Map intent to topic
    $topic = map_intent_to_topic($row['intent']);
    wp_set_object_terms($post_id, $topic, 'seo-topic');

    // Queue for generation (if auto-generate enabled)
    if ($auto_generate) {
        wp_schedule_single_event(
            time() + ($index * 180), // 3 minutes apart
            'seo_generate_queued_page',
            [$post_id]
        );
    }

    // Download and attach image (if URL provided)
    if (!empty($row['image_url'])) {
        $image_id = media_sideload_image($row['image_url'], $post_id, null, 'id');
        update_field('hero_image', $image_id, $post_id);
    }
}
```

**Background Processing:** Use WP Cron for queued generation to avoid timeouts.

---

## Settings Page

### Tabs

#### Tab 1: API Configuration
- OpenAI API Key (password field, encrypted)
- [Test Connection] button
- Model (dropdown: gpt-4-turbo-preview, gpt-4, gpt-3.5-turbo)
- Temperature (slider: 0.1-1.0, default 0.7)
- Max Tokens (number, default 1000)

#### Tab 2: Default Content
- Default CTA Button Text
- Default CTA URL
- Default Warranty Text
- Default Care Instructions

#### Tab 3: Prompt Templates
- Sub-tabs for each block type
- Textarea for editing template
- Variable reference guide
- [Reset to Default] [Save] buttons

#### Tab 4: Image Library
- ☑ Auto-assign images during generation
- Matching strategy: Strict (3 tags) | Flexible (fallback to 1-2 tags)
- Default image (if no matches found)

#### Tab 5: Limits & Tracking
- ☑ Enable rate limiting
- Max generations per hour (default: 50)
- Max concurrent generations (default: 3)
- ☑ Enable cost tracking
- Monthly budget limit (USD)
- Alert threshold (%, default 80%)
- Current month usage (read-only)

---

## Frontend Display

### Template File

**Location:** `single-seo-page.php` (in theme or plugin)

**Structure:**

```php
<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>

    <!-- Breadcrumbs -->
    <?php seo_generator_breadcrumbs(); ?>

    <article id="post-<?php the_ID(); ?>">
        <?php
        // Render each block if content exists
        seo_generator_render_block('hero');
        seo_generator_render_block('serp_answer');
        seo_generator_render_block('product_criteria');
        seo_generator_render_block('materials');
        seo_generator_render_block('process');
        seo_generator_render_block('comparison');
        seo_generator_render_block('product_showcase');
        seo_generator_render_block('size_fit');
        seo_generator_render_block('care_warranty');
        seo_generator_render_block('ethics');
        seo_generator_render_block('faqs');
        seo_generator_render_block('cta');
        ?>
    </article>

    <!-- JSON-LD Schema -->
    <?php seo_generator_output_schema(); ?>

<?php endwhile; ?>

<?php get_footer(); ?>
```

### Block Rendering Function

```php
function seo_generator_render_block($block_type) {
    $fields = get_field_group($block_type);

    // Check if block has content
    if (empty(array_filter($fields))) {
        return; // Skip empty blocks
    }

    // Load template part
    get_template_part(
        'template-parts/blocks/' . $block_type,
        null,
        $fields
    );
}
```

### Schema Output

#### Article Schema (Education pages):

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{hero_title}",
  "description": "{seo_meta_description}",
  "author": {
    "@type": "Organization",
    "name": "{site_name}"
  },
  "datePublished": "{post_date}",
  "dateModified": "{post_modified}"
}
```

#### BreadcrumbList Schema:

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "{home_url}"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "{topic_name}",
      "item": "{topic_url}"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "{page_title}",
      "item": "{page_url}"
    }
  ]
}
```

#### FAQPage Schema (if FAQ block exists):

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "{question}",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "{answer}"
      }
    }
  ]
}
```

---

## Security

### API Key Storage

- Encrypt with WordPress salts before storing
- Never expose in frontend JavaScript
- Decrypt only server-side when making API calls

```php
function encrypt_api_key($value) {
    $key = wp_salt('auth');
    return openssl_encrypt($value, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}
```

### Input Sanitization

- **Text:** `sanitize_text_field()`
- **Textarea:** `sanitize_textarea_field()`
- **URLs:** `esc_url_raw()`
- **HTML:** `wp_kses_post()`

### Output Escaping

- **Text:** `esc_html()`
- **URLs:** `esc_url()`
- **Attributes:** `esc_attr()`

### Nonce Verification

All AJAX requests must include and verify nonces:

```php
check_ajax_referer('seo_generator_nonce');
```

### Capability Checks

```php
if (!current_user_can('edit_posts')) {
    wp_die('Unauthorized');
}
```

### Rate Limiting

```php
$user_id = get_current_user_id();
$transient_key = 'seo_gen_rate_' . $user_id;
$count = (int) get_transient($transient_key);

if ($count >= 50) {
    wp_send_json_error(['message' => 'Rate limit exceeded']);
}

set_transient($transient_key, $count + 1, HOUR_IN_SECONDS);
```

---

## Performance

### Caching Strategy

- Cache prompt templates (1 hour)
- Cache ACF field configurations (indefinite, clear on save)
- Cache settings values (1 hour)
- Don't cache generated content or user-specific data

### Database Optimization

- Index on `post_id`, `created_at`, `user_id` in generation log
- Cleanup old logs (30+ days) via daily cron
- Optimize queries for analytics

### Asset Loading

- Admin assets only on plugin pages
- Minify JS/CSS for production
- Use WordPress asset versioning

---

## Analytics & Reporting

### Dashboard Widget

**Widget:** "SEO Content Generation Stats"

**Display:**
- Pages generated this month
- Total API cost this month
- Average time per page
- Average cost per page
- [View Details] link

### Analytics Page

**Metrics:**
- Total pages all-time
- Total pages this month
- Total API cost all-time
- Total API cost this month

**Charts:**
- Pages per month (bar chart, 6 months)
- Cost per month (line chart, 6 months)
- Pages by topic (pie chart)
- Success rate (gauge)

**Tables:**
- Recent pages (last 10)
- Most expensive pages
- Failed generations

**Export:** CSV export with date range filter

---

## User Roles & Permissions

### Capabilities

- `edit_posts` - View/use content generator
- `publish_posts` - Publish SEO pages
- `manage_options` - Access settings

### Multi-User Workflow

- Author creates page, saves as draft
- Editor reviews and publishes
- WordPress native revisions track changes

---

## Testing Requirements

### Unit Tests (PHPUnit)

- Prompt template variable replacement
- API response parsing
- Field validation
- Cost calculation
- Encryption/decryption

### Integration Tests

- OpenAI API connectivity
- Post creation with ACF fields
- Schema output validity
- AJAX endpoints
- Settings persistence

### User Acceptance Testing

- Generate first page (all blocks)
- Generate page with some blocks
- Edit generated content
- Regenerate single block
- Bulk generate 5 pages
- Test error scenarios
- Verify frontend display
- Test on mobile
- Multi-user workflow
- Import/export

---

## Development Timeline

### Week 1-2: Foundation (20-24 hours)

- Custom post type
- Taxonomy
- ACF field groups
- Basic admin page
- Settings skeleton

### Week 3-4: Core Functionality (24-28 hours)

- OpenAI integration
- Single block generation
- Bulk generation
- Error handling
- Cost tracking

### Week 5-6: User Interface (16-20 hours)

- Admin interface complete
- JavaScript interactions
- Progress indicators
- Form validation
- Character counters

### Week 7: Frontend & Schema (12-16 hours)

- Display template
- Block rendering
- Schema output
- Breadcrumbs
- Mobile responsive

### Week 8: Image Library (8-12 hours)

- Bulk upload
- Tagging system
- Auto-assignment algorithm
- Integration with generation

### Week 9: CSV Import (6-8 hours)

- Upload interface
- Column mapping
- Bulk processing
- Queue system

### Week 10: Testing & Polish (12-16 hours)

- Bug fixes
- Documentation
- Performance optimization
- Final QA

**Total Estimated Hours:** 98-132 hours

---

## Success Criteria

### Performance:

- ✓ Generate complete page in under 5 minutes
- ✓ 95%+ block success rate
- ✓ OpenAI cost under $3/page
- ✓ Page load under 3 seconds

### Usability:

- ✓ Non-technical user can create page independently
- ✓ Training under 1 hour
- ✓ Content needs <10 minutes editing
- ✓ No critical bugs in first month

### Scale:

- ✓ Handle 100+ pages/month
- ✓ No performance degradation
- ✓ Predictable costs

### Security:

- ✓ No vulnerabilities in audit
- ✓ API key never exposed
- ✓ Proper permission checks
- ✓ Rate limiting prevents abuse

---

## Deployment

### Pre-Launch Checklist

- [ ] Code follows WordPress standards
- [ ] No PHP errors/warnings
- [ ] Security review complete
- [ ] All tests passing
- [ ] Documentation complete
- [ ] Plugin icon created
- [ ] Screenshots prepared

### Deployment Process

#### Staging:
1. Deploy to staging
2. Run final tests
3. Client review

#### Production:
1. Backup site
2. Upload plugin
3. Activate
4. Configure settings
5. Test 3 sample pages
6. Monitor 24 hours

### Post-Launch

- **Week 1:** Daily monitoring, critical bug fixes
- **Week 2-4:** Weekly check-ins, minor issues
- **Month 2+:** Monthly reviews, feature requests

---

## Cost Analysis

### Development

- In-house development: 100-130 hours
- OR hire developer: $8,000-12,000

### Ongoing Costs

- **Hosting:** $25-35/month (WordPress hosting)
- **OpenAI API:** $150-300/month (100 pages)
- **Total:** $175-335/month

### ROI

- Manual writing: 2 hours/page × 100 = 200 hours/month
- With plugin: 10 min/page × 100 = 17 hours/month
- Time saved: 183 hours/month
- At $50/hour: $9,150/month saved
- **Plugin pays for itself in first month.**

---

## Future Enhancements (Post-V1)

### Version 1.1

- Content templates library
- Scheduled publishing
- A/B testing for prompts
- DALL-E integration for images

### Version 1.2

- Multi-site support
- Team collaboration features
- Approval workflows
- Content calendar

### Version 1.3

- Google Analytics integration
- Performance tracking
- Auto content refresh
- Competitive analysis

### Version 2.0

- White-label solution
- Custom block builder
- External API
- Advanced analytics

---

## Appendix

### Key WordPress Functions Reference

```php
// Plugin paths
plugin_dir_path(__FILE__)
plugin_dir_url(__FILE__)

// ACF
get_field('field_name', $post_id)
update_field('field_name', $value, $post_id)

// Post meta
get_post_meta($post_id, 'key', true)
update_post_meta($post_id, 'key', $value)

// AJAX
wp_send_json_success($data)
wp_send_json_error($data)
check_ajax_referer('nonce_name')

// Options
get_option('option_name')
update_option('option_name', $value)

// Sanitization
sanitize_text_field($value)
sanitize_textarea_field($value)
esc_url_raw($value)

// Escaping
esc_html($value)
esc_url($value)
esc_attr($value)
```

### Resources

- **WordPress Plugin Handbook:** developer.wordpress.org/plugins/
- **ACF Documentation:** advancedcustomfields.com/resources/
- **OpenAI API Docs:** platform.openai.com/docs
- **WordPress Code Reference:** developer.wordpress.org/reference/
