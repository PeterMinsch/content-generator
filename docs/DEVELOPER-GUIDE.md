# Content Generator Plugin - Developer Guide

## Overview

The Content Generator is a WordPress plugin that automates the creation of SEO-optimized pages using AI (OpenAI GPT-4). It supports bulk CSV import, intelligent block-based content generation, image management, and queue-based processing.

**WordPress Admin URL:** `http://146.190.142.219/wp-admin/`
**REST API Base:** `http://146.190.142.219/wp-json/seo-generator/v1/`

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [CSV Import Workflow](#csv-import-workflow)
3. [Content Generation](#content-generation)
4. [Block Types Reference](#block-types-reference)
5. [REST API Endpoints](#rest-api-endpoints)
6. [Settings Configuration](#settings-configuration)
7. [Queue System](#queue-system)
8. [WP-CLI Commands](#wp-cli-commands)
9. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites
- OpenAI API key configured in **SEO Page > Settings > API Config**
- Business identity filled in under **SEO Page > Settings > Default Content** (optional but recommended)

### Generate Your First Page
1. Go to **SEO Page > Import Keywords**
2. Upload a CSV file (see format below)
3. Map columns to fields
4. Choose block order
5. Click **Proceed with Import**
6. Pages are created as drafts — trigger generation from the queue or individually

---

## CSV Import Workflow

### Step 1: Prepare Your CSV

**Required column:** A column that maps to **Page Title** (this becomes the post title)

**Example CSV:**
```csv
title,keyword,location,page_type,page_topic
Diamond Rings Carlsbad,diamond rings,Carlsbad,Product Pages,Jewelry
Ring Sizing Guide,ring sizing,Carlsbad,How-To Guides,Jewelry
Platinum Wedding Bands,platinum wedding bands,San Diego,Product Pages,Jewelry
```

**Supported columns and auto-detection rules:**

| Target Field | Auto-Detected Column Names | Required? |
|---|---|---|
| Page Title | `keyword`, `title`, `query` | Yes |
| Focus Keyword | `focus_keyword`, `search_query` | No |
| Topic/Category | `intent`, `category`, `topic` | No |
| Image URL | `image_url`, `image` | No |
| Skip (ignored) | `search_volume`, `volume`, `searches` | N/A |

Any column that doesn't match the above is auto-mapped to "skip" (ignored). You can change mappings manually in the UI.

**CSV Limits:**
- Max file size: WordPress upload limit (default ~2MB)
- Max rows: 10,000 per import
- Encoding: UTF-8 recommended
- Format: `.csv` only

### Step 2: Upload

1. Navigate to **SEO Page > Import Keywords**
2. Select "Custom CSV" as the import source
3. Click **Upload CSV** and select your file
4. The plugin parses the file and shows a 3-row preview

### Step 3: Map Columns

The plugin auto-detects column mappings based on header names. Review and adjust if needed.

**Validation:** At least one column **must** be mapped to "Page Title" or the import will fail.

**Options at this step:**
- **Generation Mode:**
  - `Drafts Only` (default) — Creates draft posts, no AI generation yet
  - `Auto Generate` — Creates drafts AND queues them for immediate AI generation
- **Check for Duplicates:** When enabled, skips rows where a post with the same title already exists

### Step 4: Customize Block Order

Before importing, you can:
- **Drag and drop** blocks to reorder them
- **Remove blocks** you don't want generated (click the X)
- Preview the page layout on the right panel (mobile/tablet/desktop views)

### Step 5: Import

Click **Proceed with Import**. The plugin processes rows in batches of 10.

**Import Results:**
- **Created** — Posts successfully created as drafts
- **Skipped** — Posts skipped due to duplicate titles (if duplicate checking is enabled)
- **Errors** — Rows that failed (e.g., missing page title)

---

## Content Generation

### How It Works

1. Each SEO page is made up of **content blocks** (hero, FAQs, about section, etc.)
2. Each block has an AI prompt template that uses context variables like `{page_title}`, `{focus_keyword}`, `{business_name}`
3. The plugin sends the prompt to OpenAI and parses the response into structured ACF fields
4. Images are auto-assigned from the Image Library based on keyword matching

### Generation Modes

**Manual (per block):**
- Open an SEO page in the editor
- Click "Generate" on individual blocks
- Uses REST API: `POST /seo-generator/v1/pages/{id}/generate`

**Manual (all blocks):**
- Click "Generate All" to generate every enabled block for a page
- Uses REST API: `POST /seo-generator/v1/pages/{id}/generate-all`

**Automatic (queue-based):**
- When importing with "Auto Generate" mode, pages are queued
- WordPress Cron processes the queue with 10-second spacing between jobs
- Each job generates all enabled blocks for one page

### Context Variables Available in Prompts

These variables are substituted into AI prompts:

| Variable | Source |
|---|---|
| `{page_title}` | Post title |
| `{focus_keyword}` | ACF field: seo_focus_keyword |
| `{page_topic}` | SEO Topic taxonomy |
| `{business_name}` | Settings: Default Content > Business Name |
| `{business_type}` | Settings: Default Content > Business Type |
| `{business_description}` | Settings: Default Content > Business Description |
| `{business_address}` | Settings: Default Content > Business Address |
| `{service_area}` | Settings: Default Content > Service Area |
| `{business_phone}` | Settings: Default Content > Phone |
| `{business_email}` | Settings: Default Content > Email |
| `{business_url}` | Settings: Default Content > Website URL |
| `{years_in_business}` | Settings: Default Content > Years in Business |
| `{usps}` | Settings: Default Content > USPs (comma-separated) |
| `{certifications}` | Settings: Default Content > Certifications (comma-separated) |

---

## Block Types Reference

There are 15 block types. Each generates specific ACF fields.

### 1. Hero Section
- **Order:** 1 | **Enabled by default:** Yes
- **Fields:** `hero_title` (text, max 100), `hero_subtitle` (text, max 150), `hero_summary` (textarea, max 400), `hero_image` (image)

### 2. SERP Answer
- **Order:** 2 | **Enabled by default:** No
- **Fields:** `answer_heading` (text, max 100), `answer_paragraph` (textarea, max 600), `answer_bullets` (repeater: `bullet_text`)

### 3. Product Criteria
- **Order:** 3
- **Fields:** `criteria_heading` (text), `criteria_items` (repeater: `name`, `explanation`)

### 4. Materials Explained
- **Order:** 4
- **Fields:** `materials_heading` (text), `materials_items` (repeater: `material`, `pros`, `cons`, `best_for`, `allergy_notes`, `care`)

### 5. Process
- **Order:** 5 | **Max items:** 4
- **Fields:** `process_heading` (text), `process_steps` (repeater: `step_title`, `step_text`, `step_image`)

### 6. Comparison
- **Order:** 6
- **Fields:** `comparison_heading`, `left_label`, `right_label`, `summary`, `comparison_rows` (repeater: `attribute`, `left_text`, `right_text`)

### 7. Product Showcase
- **Order:** 7
- **Fields:** `showcase_heading`, `intro`, `showcase_products` (repeater: `product_sku`, `alt_image_url`)

### 8. Size & Fit
- **Order:** 8
- **Fields:** `size_heading` (text), `size_chart_image` (image), `comfort_fit_notes` (textarea)

### 9. Care & Warranty
- **Order:** 9
- **Fields:** `care_heading` (text), `care_bullets` (repeater: `bullet`), `warranty_heading`, `warranty_text`

### 10. Ethics & Origin
- **Order:** 10
- **Fields:** `ethics_heading` (text), `ethics_text` (textarea, max 800), `certifications` (repeater: `cert_name`, `cert_link`)

### 11. FAQs
- **Order:** 11
- **Fields:** `faqs_heading` (text), `faq_items` (repeater: `question`, `answer`)

### 12. CTA (Call to Action)
- **Order:** 12
- **Fields:** `cta_heading`, `cta_text`, `cta_primary_label`, `cta_primary_url`, `cta_secondary_label`, `cta_secondary_url`

### 13. About Section
- **Order:** 13 | **Enabled by default:** Yes
- **Fields:** `about_heading`, `about_description` (auto-filled from business settings), `about_features` (repeater, 4 items: `icon_type`, `feature_title`, `feature_description`)
- **Icon types:** shipping, returns, warranty, finance, quality, secure, support, eco, diamond, resize, gift, repair

### 14. Related Pages
- **Order:** 14 | **Enabled by default:** Yes | **Max items:** 4
- **Fields:** `section_heading` (text, max 100), `links` (repeater: `link_title`, `link_url`, `link_description`, `link_category`, `link_image`, `link_item_count`)

### 15. Pricing Hero
- **Order:** 15 | **Enabled by default:** Yes
- **Fields:** `pricing_hero_title` (text, max 100), `pricing_hero_description` (textarea, max 600), `pricing_items` (repeater, max 5: `category`, `downsize_label`, `downsize_price`, `upsize_label`, `upsize_price`, `custom_text`)

---

## REST API Endpoints

**Base URL:** `http://146.190.142.219/wp-json/`

### WordPress Standard Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/wp/v2/seo-pages` | List all SEO pages |
| GET | `/wp/v2/seo-pages/{id}` | Get single page (with ACF fields in `acf` key) |
| GET | `/wp/v2/seo-topics` | List all SEO topic categories |

### Plugin Custom Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/seo-generator/v1/pages/{id}` | Get page with all block data |
| PUT | `/seo-generator/v1/pages/{id}` | Update page and ACF fields |
| POST | `/seo-generator/v1/pages/{id}/generate` | Generate a single block |
| POST | `/seo-generator/v1/pages/{id}/generate-all` | Generate all enabled blocks |
| GET | `/seo-generator/v1/pages/{id}/generate-progress` | Check generation progress |

### Example: Get Page Data

```bash
curl http://146.190.142.219/wp-json/seo-generator/v1/pages/123
```

**Response:**
```json
{
  "title": "Diamond Rings Carlsbad",
  "slug": "diamond-rings-carlsbad",
  "focusKeyword": "diamond rings",
  "blocks": {
    "hero": {
      "hero_title": "Premium Diamond Rings in Carlsbad",
      "hero_subtitle": "Handcrafted elegance for every occasion",
      "hero_summary": "Discover our collection of...",
      "hero_image": { "url": "...", "alt": "..." }
    },
    "faqs": {
      "faqs_heading": "Diamond Ring FAQs",
      "faq_items": [
        { "question": "How do I choose...", "answer": "When selecting..." }
      ]
    }
  }
}
```

### Example: Generate a Single Block

```bash
curl -X POST http://146.190.142.219/wp-json/seo-generator/v1/pages/123/generate \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{"block_type": "hero"}'
```

**Response:**
```json
{
  "success": true,
  "content": {
    "hero_title": "...",
    "hero_subtitle": "...",
    "hero_summary": "..."
  },
  "metadata": {
    "promptTokens": 150,
    "completionTokens": 280,
    "totalTokens": 430,
    "cost": 0.0125,
    "generationTime": 2.34,
    "model": "gpt-4"
  }
}
```

### For Next.js Developers

To consume SEO page data in a Next.js frontend:

```javascript
// Fetch all published SEO pages
const res = await fetch('http://146.190.142.219/wp-json/wp/v2/seo-pages?status=publish&per_page=100');
const pages = await res.json();

// Fetch a single page with all block data
const pageRes = await fetch(`http://146.190.142.219/wp-json/seo-generator/v1/pages/${pageId}`);
const pageData = await pageRes.json();

// Access block content
const heroTitle = pageData.blocks.hero.hero_title;
const faqItems = pageData.blocks.faqs.faq_items;
```

**Note:** Authentication is required for draft posts. Use WordPress Application Passwords or JWT for authenticated requests.

---

## Settings Configuration

Navigate to **SEO Page > Settings** in the WordPress admin.

### API Config Tab
| Setting | Default | Description |
|---|---|---|
| OpenAI API Key | — | Required. Encrypted at rest. |
| Model | gpt-4 | AI model (gpt-4, gpt-3.5-turbo) |
| Temperature | 0.7 | Creativity (0.1 = focused, 1.0 = creative) |
| Max Tokens | 1000 | Max response length per block |

### Default Content Tab
Business identity fields that get injected into AI prompts as context variables. Fill these in so generated content references your actual business.

**Business Identity:** name, type, description, years in business, USPs, certifications
**Contact Info:** address, service area, phone, email, website URL
**Default CTA:** heading, text, button label, button URL, warranty text, care text

### Image Library Tab
| Setting | Default | Description |
|---|---|---|
| Auto-Assignment | Enabled | Automatically match images to blocks |
| AI Alt Text | Disabled | Generate alt text via AI |
| Download Timeout | 30s | For CSV image URL downloads |
| Max Image Size | 5MB | Skip images larger than this |

### Review Integration Tab
For pulling Google Business reviews via Apify:
- Apify API Token
- Google Maps Place URL
- Max reviews to fetch

---

## Queue System

### How the Queue Works

When pages are imported with "Auto Generate" mode:
1. Each page is added to the generation queue
2. Jobs are spaced 10 seconds apart to avoid API rate limits
3. WordPress Cron processes one job at a time
4. Each job generates all enabled blocks for one page

### Queue Status Values
- **pending** — Waiting to be processed
- **processing** — Currently generating content
- **completed** — All blocks generated successfully
- **failed** — Failed after 3 retry attempts

### Retry Logic
- Max retries: 3
- Backoff: 30s, 60s, 120s (exponential)
- After 3 failures, job is marked as permanently failed

### Monitoring
- Check queue status in the WordPress admin under **SEO Page > Queue**
- Or use WP-CLI: `wp seo-generator queue status`

---

## WP-CLI Commands

```bash
# List queued jobs
wp seo-generator queue list
wp seo-generator queue list --status=pending --format=json

# Process the next job manually
wp seo-generator queue process

# View queue statistics
wp seo-generator queue status

# Clear all pending jobs
wp seo-generator queue clear
```

---

## Troubleshooting

### "Skipped: N" during import
Posts with the same title already exist. Either delete existing pages first or uncheck "Check for duplicates" during import.

### "Failed to decrypt API key"
The API key was stored without encryption. Re-save it through the WordPress admin UI at **Settings > API Config**.

### "Failed to move uploaded file"
Upload directory permissions issue. Fix with:
```bash
chown -R www-data:www-data /var/www/wordpress/wp-content/uploads
```

### Blocks not showing in Page Preview
The JavaScript build files are missing. Copy from a local build or run:
```bash
cd /var/www/wordpress/wp-content/plugins/content-generator
npm install && npm run build
```

### 504 Gateway Timeout
Server running out of memory. Add swap space:
```bash
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### Generation stuck or not processing
WordPress Cron may not be firing. Check with:
```bash
wp cron event list --allow-root
```
Or process the queue manually:
```bash
wp seo-generator queue process --allow-root
```

---

## Architecture Summary

```
CSV Upload → Column Mapping → Block Selection → Batch Import (10 rows/batch)
                                                        ↓
                                              Draft Posts Created
                                                        ↓
                                    ┌──────────────────────────────────────┐
                                    │  Manual: Click Generate in editor    │
                                    │  Auto: Queue → Cron → Process        │
                                    └──────────────────────────────────────┘
                                                        ↓
                                    Prompt Template + Context Variables
                                                        ↓
                                           OpenAI API (GPT-4)
                                                        ↓
                                    Parse Response → ACF Fields Updated
                                                        ↓
                                        Image Auto-Assignment
                                                        ↓
                                    Page Ready → REST API → Next.js Frontend
```
