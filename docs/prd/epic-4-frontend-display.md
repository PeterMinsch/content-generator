# Epic 4: Frontend Display & Schema

**Timeline:** Week 7 (12-16 hours)
**Status:** Not Started
**Priority:** High
**Dependencies:** Epic 1 (Foundation), Epic 2 (Core Generation)

## Epic Goal

Create public-facing templates for displaying SEO pages with all 12 content blocks, implement structured data (schema.org) for SEO, add breadcrumb navigation, and ensure mobile responsiveness.

## Success Criteria

- Single post template renders all 12 content blocks correctly
- Empty blocks are skipped (not displayed)
- Structured data (JSON-LD) output for Article, FAQ, and Breadcrumb schemas
- Breadcrumbs functional and styled
- Page loads in under 3 seconds
- Mobile responsive on devices 320px and above
- Valid HTML5 markup
- Passes Google Rich Results Test

---

## Story 4.1: Create Single SEO Page Template

**As a** site visitor
**I want** to view SEO content pages on the frontend
**So that** I can read the jewelry information

### Acceptance Criteria

1. Template file created: `templates/frontend/single-seo-page.php`
2. Template loads via WordPress template hierarchy for `seo-page` post type
3. Template structure includes:
   - WordPress header (`get_header()`)
   - Breadcrumbs
   - Article wrapper with post ID
   - All 12 content blocks (conditionally rendered)
   - Schema output
   - WordPress footer (`get_footer()`)
4. Template integrates with active theme layout
5. Template follows WordPress coding standards
6. Template is responsive and mobile-friendly

### Technical Requirements

- File location: `templates/frontend/single-seo-page.php`
- Load via filter: `single_template` or `template_include`
- Use `while (have_posts()) : the_post()` loop
- Call block rendering function for each block
- Enqueue frontend CSS if needed
- Source: PRD "Frontend Display" section

---

## Story 4.2: Implement Block Rendering System

**As a** plugin developer
**I want** a function to render each content block from ACF data
**So that** blocks can be displayed consistently on the frontend

### Acceptance Criteria

1. Function created: `seo_generator_render_block($block_type)`
2. Function retrieves ACF field data for specified block
3. Function checks if block has content (not empty)
4. If block is empty, function returns early (skips rendering)
5. If block has content, function loads template part for that block
6. 12 block template parts created in `templates/frontend/blocks/`:
   - hero.php, serp-answer.php, product-criteria.php, materials.php, process.php, comparison.php, product-showcase.php, size-fit.php, care-warranty.php, ethics.php, faqs.php, cta.php
7. Each template part:
   - Receives field data as parameter
   - Uses proper escaping functions (`esc_html()`, `esc_url()`, `esc_attr()`)
   - Has semantic HTML structure
   - Includes CSS classes for styling

### Technical Requirements

- Function location: `includes/functions.php`
- Use `get_field()` to retrieve ACF data
- Use `get_template_part()` or custom template loader
- Check for content: `!empty(array_filter($fields))`
- Escape all output appropriately
- HTML structure: use semantic tags (section, article, header, etc.)
- CSS classes: `seo-block`, `seo-block--{block-type}`
- Source: PRD "Block Rendering Function" section

---

## Story 4.3: Create Frontend Styles for Content Blocks

**As a** site visitor
**I want** well-styled, readable content blocks
**So that** the page is visually appealing and easy to navigate

### Acceptance Criteria

1. Frontend CSS file created: `assets/css/frontend.css`
2. Styles defined for all 12 block types:
   - Typography (headings, paragraphs, lists)
   - Spacing (margins, padding)
   - Layout (flexbox/grid where appropriate)
   - Images (responsive sizing)
   - Lists (bullets, numbered)
3. Responsive design:
   - Mobile (320px-767px): single column, stacked layout
   - Tablet (768px-1023px): comfortable spacing
   - Desktop (1024px+): optimal reading width
4. Styles don't conflict with theme styles (use specific selectors)
5. Accessible color contrast (WCAG AA compliance)
6. Print styles included for printer-friendly output

### Technical Requirements

- File: `assets/css/frontend.css`
- Enqueue on frontend only for `seo-page` post type
- Use `.seo-page-content` wrapper class for scoping
- Use CSS custom properties for colors/spacing (easy theme customization)
- Media queries for responsive breakpoints
- Consider using CSS Grid for complex layouts
- Source: PRD "Frontend Display" requirements

---

## Story 4.4: Implement Schema.org Structured Data

**As a** site administrator
**I want** schema.org structured data on SEO pages
**So that** search engines understand and display content in rich results

### Acceptance Criteria

1. Function created: `seo_generator_output_schema()`
2. Function outputs JSON-LD schema in `<script type="application/ld+json">` tags
3. Three schema types implemented:
   - **Article Schema** (always output):
     - @type: "Article"
     - headline: from hero_title
     - description: from seo_meta_description
     - author: organization name
     - datePublished, dateModified: post dates
   - **FAQPage Schema** (if FAQ block has content):
     - @type: "FAQPage"
     - mainEntity: array of Question objects
     - Each question includes name and acceptedAnswer
   - **BreadcrumbList Schema** (always output):
     - @type: "BreadcrumbList"
     - itemListElement: Home > Topic > Page
4. Schema validates in Google Rich Results Test
5. Schema properly escaped and JSON-encoded
6. Multiple schemas combined in single script tag with @graph

### Technical Requirements

- Function location: `includes/functions.php`
- Use `wp_json_encode()` for proper JSON encoding
- Schema context: `https://schema.org`
- Output in footer (`wp_footer` hook) or in template
- Validate output with: https://search.google.com/test/rich-results
- Source: PRD "Schema Output" section

---

## Story 4.5: Add Breadcrumb Navigation

**As a** site visitor
**I want** breadcrumb navigation on SEO pages
**So that** I can understand my location and navigate back

### Acceptance Criteria

1. Function created: `seo_generator_breadcrumbs()`
2. Breadcrumb structure:
   - Home > {Topic Name} > {Page Title}
   - Example: "Home > Wedding Bands > Platinum Men's Wedding Bands"
3. Breadcrumbs display:
   - Each level is a clickable link (except current page)
   - Current page shown as plain text
   - Separator: ">" or custom character
4. Breadcrumbs styled:
   - Horizontal list with separators
   - Responsive (wraps on mobile if needed)
   - Accessible (ARIA labels, semantic markup)
5. Breadcrumbs include microdata or integrate with schema (BreadcrumbList)
6. Function returns empty if on Home or if topic not assigned

### Technical Requirements

- Function location: `includes/functions.php`
- HTML structure: `<nav aria-label="Breadcrumb"><ol><li>...</li></ol></nav>`
- Get topic: `get_the_terms($post_id, 'seo-topic')`
- Home URL: `home_url('/')`
- Topic archive: `get_term_link($topic)`
- CSS classes: `.breadcrumbs`, `.breadcrumb-item`, `.breadcrumb-separator`
- Source: PRD "Frontend Display" template structure

---

## Story 4.6: Optimize Page Load Performance

**As a** site visitor
**I want** SEO pages to load quickly
**So that** I have a smooth browsing experience

### Acceptance Criteria

1. Page load time under 3 seconds on 3G connection
2. Performance optimizations implemented:
   - Lazy loading for images (use `loading="lazy"` attribute)
   - Minified CSS and JavaScript
   - Caching headers set for static assets
   - Optimized database queries (minimal queries per page)
3. ACF field values cached appropriately
4. No N+1 query problems (check with Query Monitor)
5. Images properly sized (use WordPress image sizes, not full resolution)
6. Critical CSS inlined for above-the-fold content (optional)

### Technical Requirements

- Use `loading="lazy"` on all images below fold
- Enqueue minified assets in production
- Cache ACF field groups (ACF does this by default)
- Avoid querying in loops
- Use `get_fields()` to retrieve all fields at once
- Image sizes: use `medium` or `large` for display, not `full`
- Test with: Google PageSpeed Insights, WebPageTest
- Source: PRD "Performance" section

---

## Story 4.7: Ensure Mobile Responsiveness

**As a** mobile user
**I want** SEO pages to display correctly on my phone or tablet
**So that** I can read content comfortably

### Acceptance Criteria

1. All content blocks display correctly on:
   - Mobile (320px-767px)
   - Tablet (768px-1023px)
   - Desktop (1024px+)
2. Typography scales appropriately:
   - Readable font sizes (minimum 16px body text on mobile)
   - Headings scale with viewport
3. Images scale responsively:
   - Never exceed container width
   - Maintain aspect ratio
   - Use `srcset` for responsive images
4. Tables (comparison block) handle overflow gracefully:
   - Horizontal scroll on mobile if needed
   - Or responsive table design
5. Buttons and links are touch-friendly (minimum 44x44px tap target)
6. Viewport meta tag present: `<meta name="viewport" content="width=device-width, initial-scale=1">`

### Technical Requirements

- Test on real devices or browser dev tools
- Use CSS media queries for breakpoints
- Images: `max-width: 100%; height: auto;`
- Consider using `@wordpress/responsive` utilities
- Touch targets: CSS min-width/height 44px
- Tables: `overflow-x: auto` wrapper or responsive table plugin
- Source: PRD "Frontend Display" and "Browser Support" sections

---

## Epic Dependencies

- Epic 1: Foundation Setup (custom post type, ACF fields, taxonomies)
- Epic 2: Core Generation (content must exist to display)

## Risks & Mitigations

**Risk:** Theme CSS conflicts with plugin styles
**Mitigation:** Use specific CSS selectors, scope styles with wrapper class, test with popular themes

**Risk:** Large images slowing page load
**Mitigation:** Lazy loading, proper image sizing, recommend image optimization plugins

**Risk:** Schema validation errors
**Mitigation:** Thorough testing with Google Rich Results Test, validate JSON structure

**Risk:** Accessibility issues
**Mitigation:** Use semantic HTML, ARIA labels, keyboard navigation support, test with screen readers
