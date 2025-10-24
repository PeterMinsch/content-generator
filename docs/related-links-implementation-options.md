# Related Links Block: Implementation Options

**Date:** 2025-10-24
**Status:** Decision Required
**Context:** New "Related Pages" block (Block 14) needs a data source for page suggestions

---

## Overview

The Related Links block displays 4 internal page links to help users discover related content. Two implementation approaches are available:

1. **Option A:** Automatic selection using existing `InternalLinkingService`
2. **Option B:** AI-powered selection from predefined dataset

This document compares both approaches to inform the implementation decision.

---

## Option A: Automatic Selection (InternalLinkingService)

### How It Works

Uses the existing `InternalLinkingService` and `KeywordMatcher` to automatically find related pages based on content similarity.

#### Selection Algorithm

**Step 1: Keyword Extraction**
- Extracts keywords from focus keyword field
- Applies weighted scoring:
  - **High-value keywords** (3x weight): engagement, wedding, bridal, proposal, anniversary, eternity, bride, groom
  - **Metals/Stones** (2x weight): platinum, gold, diamond, sapphire, ruby, emerald, etc.
  - **Generic keywords** (0.5x weight): ring, band, jewelry, necklace, bracelet
  - **Other keywords** (1x weight): All other relevant terms

**Step 2: Candidate Scoring**
Each candidate page receives a score based on:

| Factor | Points | Description |
|--------|--------|-------------|
| Keyword Similarity | 0-50 | Weighted keyword overlap between pages |
| Same Topic | +20 | Both pages in same taxonomy category |
| Title Match | +15 | Candidate title contains source focus keyword |
| Meta Match | +10 | Candidate meta description contains keyword |
| Age Bonus | +5 | Established pages (>30 days old) |
| Age Penalty | -10 | New pages (<7 days old) |

**Step 3: Filtering & Selection**
- Minimum threshold: 5.0 points
- Returns top 5 pages sorted by relevance
- Excludes source page
- Auto-refreshes every 7 days

#### Example Scoring

**Source Page:** "Platinum Engagement Rings"
**Focus Keyword:** "platinum engagement rings"

**Candidates:**
1. "Diamond Engagement Rings" → **Score: 43**
   - Keyword match: "engagement" (3×3=9 points)
   - Same topic: Engagement Rings (+20)
   - Title match: contains "engagement rings" (+15)
   - Established page (+5)
   - Meta match: mentions engagement (+10)
   - **Selected: #1**

2. "Platinum Wedding Bands" → **Score: 25**
   - Keyword match: "platinum" (2×2=4) + "wedding" (3×1=3) = 7 points
   - Same topic: Wedding Jewelry (+20)
   - Age penalty: 3 days old (-10)
   - Meta match: mentions platinum (+10)
   - **Selected: #2**

3. "Gold Necklaces" → **Score: 2**
   - Keyword match: "gold" metal (2 points)
   - Different topic (0)
   - ❌ **Filtered out** (below 5.0 threshold)

### Technical Implementation

#### Storage
- Post meta: `_related_links` (array of related page data)
- Post meta: `_related_links_timestamp` (refresh tracking)

#### Data Structure
```php
array(
    'links' => array(
        array(
            'id'      => 123,      // Post ID
            'score'   => 43.5,     // Relevance score
            'reasons' => array(    // Score breakdown
                'Keyword similarity: 9.0',
                'Same topic: Engagement Rings (+20)',
                'Title contains focus keyword (+15)',
            ),
        ),
        // ... up to 5 related pages
    ),
    'timestamp' => 1729785600,
);
```

#### Refresh Logic
- Generated automatically during page generation
- Called in `GenerationService::processQueuedPage()` after block generation
- Background refresh via WP-Cron (weekly)
- Manual refresh available via admin interface

### Pros ✅

**Longevity & Maintenance**
- ✅ **Zero ongoing maintenance** - Fully automatic
- ✅ **Self-healing** - Adapts when pages added/removed/updated
- ✅ **Scales infinitely** - Works with any number of pages
- ✅ **No data decay** - Always current, never stale
- ✅ **Battle-tested** - Already in production since Story 8.1

**Quality**
- ✅ **Content-aware** - Understands jewelry terminology
- ✅ **Semantically relevant** - Keyword weighting ensures quality matches
- ✅ **Topic-aware** - Respects taxonomy relationships
- ✅ **Quality thresholds** - Minimum score filters weak matches

**Technical**
- ✅ **Already implemented** - 5-minute integration, no new code
- ✅ **Fewer failure points** - Simple, proven system
- ✅ **Performance optimized** - Cached in post meta, minimal queries

### Cons ❌

**Control**
- ❌ **No editorial control** - Can't force specific pages to appear
- ❌ **Can't prioritize strategic pages** - Algorithm-only selection
- ❌ **No business rules** - Can't enforce "always link to X"

**Limitations**
- ❌ **Requires focus keywords** - Pages without keywords won't be matched
- ❌ **English-centric** - Keyword matching optimized for English
- ❌ **No cross-category promotion** - Rarely links across unrelated topics

### Use Cases

**Ideal For:**
- Large content libraries (100+ pages)
- Organic, content-driven linking
- Minimal maintenance resources
- SEO-focused internal linking
- Long-term sustainability

**Not Ideal For:**
- Highly curated link selection
- Cross-selling specific products
- Marketing-driven page promotion
- Multi-language sites

---

## Option B: AI-Powered Selection from Predefined Dataset

### How It Works

Maintains a curated dataset of available pages and uses OpenAI to select the 4 most relevant pages based on context and business rules.

#### Architecture

**Data Source Options:**

1. **CSV File** (Simplest)
   - Location: `/data/related-pages.csv`
   - Columns: title, url, description, category, image_url, item_count
   - Uploaded/updated manually via FTP or admin interface

2. **Database Table** (Most Robust)
   - New table: `wp_seo_related_pages_dataset`
   - Full CRUD interface in WordPress admin
   - Import/export functionality

3. **WordPress Options** (Quick Start)
   - Stored in: `seo_generator_related_pages_dataset`
   - Managed via settings page
   - Limited to ~500 pages due to serialization limits

#### Selection Process

**Step 1: Load Dataset**
```php
// Example dataset structure
array(
    array(
        'title'       => 'Engagement Rings',
        'url'         => '/engagement-rings/',
        'description' => 'Explore our stunning collection of engagement rings...',
        'category'    => 'Rings',
        'image_url'   => '/images/engagement-rings.jpg',
        'item_count'  => '122 Items',
        'tags'        => array('engagement', 'wedding', 'bridal'),
    ),
    // ... 50-500 pages
);
```

**Step 2: Pass to AI**
```
System: You are an expert at selecting related pages for SEO content.

User: Select 4 most relevant pages from this dataset for "{page_title}".

Context:
- Page topic: {page_topic}
- Focus keyword: {focus_keyword}

Dataset:
[JSON of available pages]

Return 4 pages that:
- Complement the current topic
- Provide value to users
- Help users continue their journey
- Maintain topical relevance
```

**Step 3: AI Returns Selection**
```json
{
  "section_heading": "SHOP ENGAGEMENT RINGS",
  "links": [
    {
      "title": "Diamond Engagement Rings",
      "url": "/diamond-engagement-rings/",
      "description": "Discover brilliant diamond rings...",
      "category": "Rings",
      "item_count": "89 Items"
    },
    // ... 3 more
  ]
}
```

### Implementation Requirements

#### New Components Needed

1. **Dataset Storage Class**
   - `includes/Data/RelatedPagesDataset.php`
   - Load/save dataset from chosen source
   - Validation and error handling

2. **Dataset Manager Admin Page**
   - `includes/Admin/RelatedPagesManager.php`
   - CRUD interface for dataset
   - CSV import/export
   - Bulk operations

3. **AI Prompt Enhancement**
   - Modify `DefaultPrompts::getRelatedLinksTemplate()`
   - Inject full dataset into prompt
   - Handle large datasets (token limits)

4. **Content Generator Integration**
   - Load dataset before generation
   - Pass to OpenAI with prompt
   - Parse and validate AI response

#### Database Schema (if using DB option)
```sql
CREATE TABLE wp_seo_related_pages_dataset (
  id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  url VARCHAR(500) NOT NULL,
  description TEXT,
  category VARCHAR(100),
  tags TEXT, -- JSON array
  image_url VARCHAR(500),
  item_count VARCHAR(50),
  priority INT DEFAULT 0, -- Higher = more likely to appear
  status VARCHAR(20) DEFAULT 'active', -- active, inactive, archived
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_category (category),
  INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Pros ✅

**Control**
- ✅ **Full editorial control** - Curate exact pages available for linking
- ✅ **Business rule enforcement** - Prioritize strategic pages
- ✅ **Cross-category promotion** - Link to any page regardless of topic
- ✅ **Marketing flexibility** - Promote seasonal/featured content

**AI Benefits**
- ✅ **Context-aware** - AI understands nuanced relationships
- ✅ **Natural language** - Can follow complex selection rules
- ✅ **Adaptive reasoning** - Selects based on user intent, not just keywords

### Cons ❌

**Maintenance**
- ❌ **Manual curation required** - Someone must maintain dataset
- ❌ **Data rot** - URLs break, pages get deleted, descriptions become stale
- ❌ **Organizational dependency** - Needs owner to keep current
- ❌ **Ongoing effort** - Must update as site evolves

**Technical**
- ❌ **Complex implementation** - 2-3 days of development work
- ❌ **More failure points** - Dataset loading, AI calls, parsing, validation
- ❌ **Performance concerns** - Large datasets increase API token usage
- ❌ **Cost increase** - More tokens per generation (~500-2000 extra)

**Scaling**
- ❌ **Manual scaling** - Each new page requires dataset update
- ❌ **Token limits** - Large datasets (>500 pages) may exceed prompt limits
- ❌ **Coordination overhead** - Multiple people editing dataset = conflicts

**Brittleness**
- ❌ **URL changes break links** - Requires manual updates
- ❌ **Deleted pages** - Must remove from dataset manually
- ❌ **Stale metadata** - Item counts, descriptions become outdated

### Use Cases

**Ideal For:**
- Small, curated content libraries (<100 pages)
- High-touch content management
- Strategic page promotion
- Marketing campaigns
- Cross-category recommendations

**Not Ideal For:**
- Large, dynamic content libraries
- Minimal maintenance resources
- Long-term sustainability
- Rapid content growth

---

## Hybrid Approach (Future Enhancement)

Combine both systems for maximum flexibility:

```php
// Priority waterfall
1. Manual overrides (set by editor) → Use if available
2. InternalLinkingService (automatic) → Default fallback
3. AI + Dataset (strategic) → Optional enhancement
```

**Benefits:**
- ✅ Automatic by default (low maintenance)
- ✅ Manual control when needed (editorial flexibility)
- ✅ Best of both worlds

**Implementation:**
- Add `_related_links_override` post meta field
- Check override first, fall back to automatic
- Admin UI to set manual links per page

---

## Comparison Matrix

| Factor | Option A (Automatic) | Option B (Dataset + AI) |
|--------|---------------------|-------------------------|
| **Initial Setup** | 5 minutes | 2-3 days |
| **Ongoing Maintenance** | None | Weekly/monthly updates |
| **Scalability** | Infinite | Limited by dataset size |
| **Quality of Matches** | High (keyword-based) | Variable (AI-dependent) |
| **Editorial Control** | None | Full control |
| **Cost per Generation** | $0 | +$0.02-0.10 (extra tokens) |
| **Failure Risk** | Very low | Medium-high |
| **Data Freshness** | Auto-updated | Manual updates required |
| **Long-term Viability** | Excellent | Requires commitment |
| **Technical Complexity** | Low | High |

---

## Recommendation

### For Long-term Longevity: **Option A (Automatic)**

**Reasoning:**
1. **Zero maintenance** - Critical for sustainability
2. **Proven in production** - Already works, no surprises
3. **Scales with content** - Grows naturally with site
4. **Quality matching** - Keyword weighting is sophisticated
5. **Fast implementation** - Live in 10 minutes

### When to Consider Option B

Only if you have:
- ✅ Dedicated content manager (ongoing role)
- ✅ Small, stable content library (<100 pages)
- ✅ Specific business requirements for page promotion
- ✅ Resources for long-term dataset maintenance

---

## Implementation Timeline

### Option A: Automatic (Recommended)
- **Time Required:** 10-15 minutes
- **Files to Modify:** 1 (block template)
- **Testing:** 5 minutes
- **Total:** ~20 minutes to production

**Steps:**
1. Modify `templates/frontend/blocks/related-links.php`
2. Replace AI data structure with InternalLinkingService call
3. Format data for Figma design
4. Test on sample page

### Option B: Dataset + AI
- **Time Required:** 2-3 days
- **Files to Create:** 3-4 new classes
- **Database Changes:** 1 new table (optional)
- **Admin Interface:** Full CRUD system
- **Testing:** 2-4 hours
- **Total:** ~16-24 hours to production

**Steps:**
1. Design dataset schema
2. Implement storage layer
3. Build admin interface
4. Enhance AI prompt
5. Update block template
6. Create initial dataset
7. Test and validate

---

## Technical Notes

### Current Block Configuration

**Config:** `config/block-definitions.php`
```php
'related_links' => [
    'label'             => __( 'Related Pages', 'seo-generator' ),
    'order'             => 14,
    'enabled'           => true,
    'ai_prompt'         => 'Select 4 most relevant pages...',
    'frontend_template' => 'blocks/related-links.php',
    'fields'            => [
        'section_heading' => ['type' => 'text'],
        'links'           => ['type' => 'repeater', 'max' => 4],
    ],
],
```

**AI Prompt:** `includes/Data/DefaultPrompts.php`
- Currently expects dataset in prompt (not implemented)
- Will fail or generate fake data without dataset

**Template:** `templates/frontend/blocks/related-links.php`
- Figma-matched design complete
- 4-card grid with images
- Gold accents, gradient overlays
- Ready to receive data from either source

### Integration Points

**Option A Changes:**
```php
// In BlockContentParser or template
$linking_service = new InternalLinkingService();
$related = $linking_service->getRelatedLinks($post_id);

// Transform to block format
$links = array_map(function($link) {
    return [
        'title'       => get_the_title($link['id']),
        'url'         => get_permalink($link['id']),
        'description' => get_field('seo_meta_description', $link['id']),
        'category'    => /* get topic term */,
        'image'       => get_field('hero_image', $link['id']),
        'item_count'  => '', // Not applicable
    ];
}, $related);
```

**Option B Changes:**
- New `RelatedPagesDataset` class
- Load dataset before generation
- Inject into AI prompt as JSON
- Parse AI response
- No changes to template needed

---

## Questions to Answer

Before proceeding, consider:

1. **Who will maintain the dataset?** (if Option B)
2. **How often do pages change?** (impacts maintenance burden)
3. **Is editorial control critical?** (business requirement)
4. **What's the expected page count?** (50, 500, 5000?)
5. **What's the risk tolerance?** (automatic vs manual)
6. **What are maintenance resources?** (time, people, budget)

---

## Next Steps

### Decision Required
- [ ] Review both options with stakeholders
- [ ] Evaluate maintenance capacity
- [ ] Choose implementation approach
- [ ] Set implementation timeline

### Post-Decision
- [ ] Implement chosen approach
- [ ] Test with real pages
- [ ] Document usage for content team
- [ ] Monitor performance and quality

---

## References

- **Story 8.1:** Automated Internal Linking (original implementation)
- **InternalLinkingService:** `includes/Services/InternalLinkingService.php`
- **KeywordMatcher:** `includes/Services/KeywordMatcher.php`
- **Block Definition:** `config/block-definitions.php:591-646`
- **AI Prompt:** `includes/Data/DefaultPrompts.php:596-665`
- **Template:** `templates/frontend/blocks/related-links.php`

---

**Document Version:** 1.0
**Last Updated:** 2025-10-24
**Author:** Claude (AI Assistant)
