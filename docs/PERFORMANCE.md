# Performance Optimizations

This document details the performance optimizations implemented in the SEO Generator plugin to achieve fast page load times (< 3 seconds on 3G).

## Implemented Optimizations

### 1. Image Lazy Loading

**Implementation:** All images below the fold use native lazy loading via the `loading="lazy"` attribute.

**Files Modified:**
- `templates/frontend/blocks/process.php` - Step images lazy loaded
- `templates/frontend/blocks/product-showcase.php` - Product images lazy loaded
- `templates/frontend/blocks/size-fit.php` - Size chart image lazy loaded

**Hero Image Exception:** The hero image uses `loading="eager"` since it's above the fold and needs to load immediately for good LCP (Largest Contentful Paint) scores.

**Impact:** Reduces initial page payload by deferring off-screen images until they're needed.

### 2. Optimized Image Sizes

**Implementation:** Templates use appropriate WordPress image sizes instead of full-resolution images.

**Image Size Strategy:**
- **Hero image:** `large` (1024x1024) - Above fold, needs quality
- **Process step images:** `medium` (300x300) - Smaller display area
- **Product showcase images:** External URLs with lazy loading
- **Size chart:** `large` (1024x1024) - Needs to be readable

**Impact:** Reduces bandwidth usage and improves load times by serving appropriately-sized images.

### 3. Minified CSS Assets

**Implementation:** Created minified CSS version for production use.

**Files:**
- `assets/css/frontend.css` - Development version (18KB)
- `assets/css/frontend.min.css` - Production version (13KB)

**Loading Strategy:**
```php
// Uses minified CSS in production, unminified when SCRIPT_DEBUG is true
$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
wp_enqueue_style('seo-generator-frontend', "assets/css/frontend{$suffix}.css", ...);
```

**Impact:** 28% reduction in CSS file size (18KB → 13KB).

### 4. Conditional Asset Loading

**Implementation:** Frontend CSS only loads on `seo-page` post type.

**File Modified:** `includes/Templates/TemplateLoader.php`

```php
public function enqueueFrontendStyles(): void {
    // Only enqueue on single seo-page posts
    if (!is_singular('seo-page')) {
        return;
    }
    // ... enqueue stylesheet
}
```

**Impact:** Prevents unnecessary CSS from loading on other pages, reducing global overhead.

### 5. Optimized Database Queries

**Implementation:** Minimized duplicate database queries through strategic caching.

**Optimization: Taxonomy Term Caching**

The plugin retrieves taxonomy terms once and leverages WordPress's built-in term cache for subsequent calls.

**File Modified:** `includes/functions.php`

```php
function seo_generator_output_schema(): void {
    // Pre-fetch taxonomy terms once to prime WordPress term cache
    $topics = get_the_terms($post_id, 'seo-topic');

    // Subsequent calls to get_the_terms() in breadcrumb/schema functions
    // will use cached data instead of making new database queries
    // ...
}
```

**Impact:** Eliminates duplicate queries for taxonomy terms. WordPress caches the result, so:
- `seo_generator_breadcrumbs()` call to `get_the_terms()` - Uses cache (no query)
- `seo_generator_build_breadcrumb_schema()` call to `get_the_terms()` - Uses cache (no query)

### 6. ACF Field Retrieval Optimization

**Implementation:** Block rendering function retrieves only required fields for each block type.

**File:** `includes/functions.php`

```php
function seo_generator_render_block(string $block_type, int $post_id = 0): void {
    // Map block types to their specific field groups
    $field_map = array(
        'hero' => array('hero_title', 'hero_subtitle', 'hero_summary', 'hero_image'),
        'process' => array('process_heading', 'process_steps'),
        // ... other blocks
    );

    // Only retrieve fields needed for this block
    foreach ($field_map[$block_type] as $field_name) {
        $fields[$field_name] = get_field($field_name, $post_id);
    }
}
```

**Impact:** Avoids retrieving unnecessary fields, reducing query overhead.

## Performance Targets

Based on Story 4.6 requirements:

| Metric | Target | Status |
|--------|--------|--------|
| Page Load Time (3G) | < 3 seconds | Ready for testing |
| LCP (Largest Contentful Paint) | < 2.5 seconds | Ready for testing |
| Total Database Queries | < 30 queries | Optimized |
| Slow Queries (>0.05s) | 0 queries | Optimized |
| CSS File Size | Minimized | 28% reduction |
| Image Loading | Lazy loaded | Implemented |
| Asset Loading | Conditional | Implemented |

## Testing Recommendations

### Tools to Use:
1. **Google PageSpeed Insights** (https://pagespeed.web.dev/)
   - Test mobile and desktop performance
   - Check Core Web Vitals (LCP, FID, CLS)
   - Target: 90+ mobile, 95+ desktop

2. **Query Monitor Plugin**
   - Install: https://wordpress.org/plugins/query-monitor/
   - Check total queries, slow queries, duplicate queries
   - Verify taxonomy term caching is working

3. **GTmetrix** (https://gtmetrix.com/)
   - Test page load time
   - View waterfall chart
   - Target: Load time < 3 seconds

4. **WebPageTest** (https://www.webpagetest.org/)
   - Test on 3G connection
   - Verify lazy loading behavior
   - Check Time to First Byte (TTFB)

### Manual Testing Steps:

1. **Verify Lazy Loading:**
   - Open browser DevTools (Network tab)
   - Load an SEO page
   - Verify off-screen images don't load initially
   - Scroll down and verify images load as they enter viewport

2. **Verify Minified CSS:**
   - View page source
   - Find `<link>` tag for frontend CSS
   - Verify it references `frontend.min.css` (not `frontend.css`)
   - Set `define('SCRIPT_DEBUG', true);` in wp-config.php
   - Verify it switches to `frontend.css`

3. **Verify Conditional Loading:**
   - Visit a regular blog post (not seo-page)
   - View page source
   - Verify `seo-generator-frontend` CSS is NOT loaded

4. **Check Database Queries:**
   - Install Query Monitor plugin
   - Load an SEO page
   - Check "Queries" panel in Query Monitor
   - Look for:
     - Total queries < 30
     - No duplicate `get_the_terms` queries for seo-topic
     - No queries > 0.05 seconds

## Future Optimization Opportunities

### Not Yet Implemented:

1. **Critical CSS Inlining (Optional)**
   - Extract above-the-fold CSS
   - Inline in `<head>` for faster First Contentful Paint
   - Load full CSS asynchronously
   - Reference: Story 4.6, Task 13

2. **Object Caching**
   - If using Redis/Memcached, verify ACF fields are cached
   - Consider caching schema output with transients
   - Use post modified time as cache key

3. **CDN Integration**
   - Serve static assets (CSS, images) from CDN
   - Reduces server load and improves global performance

4. **Image Optimization**
   - Consider WebP format for better compression
   - Implement responsive images with `srcset`
   - Use image optimization plugins (ShortPixel, Imagify)

## Performance Monitoring

### Baseline Metrics:
Document initial performance before optimizations for comparison.

| Test | Before | After | Improvement |
|------|--------|-------|-------------|
| PageSpeed Mobile | TBD | TBD | TBD |
| PageSpeed Desktop | TBD | TBD | TBD |
| Total Queries | TBD | TBD | TBD |
| CSS Size | 18KB | 13KB | 28% |
| Page Load (3G) | TBD | TBD | TBD |

*Note: Fill in baseline and after metrics during QA testing*

## Conclusion

The implemented optimizations focus on:
- **Reducing payload** - Lazy loading, minified CSS, appropriate image sizes
- **Minimizing queries** - Taxonomy term caching, conditional loading
- **Improving perceived performance** - Hero image eager loading for good LCP

These changes provide a solid foundation for fast page loads while maintaining code maintainability and extensibility.

For questions or suggestions, refer to Story 4.6 in `docs/stories/4.6.page-load-performance.md`.
