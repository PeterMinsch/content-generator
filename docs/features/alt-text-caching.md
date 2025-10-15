# Alt Text Caching (Tier 1 Implementation)

**Status**: ✅ Implemented
**Date**: 2025-10-09
**Implementation Time**: 15 minutes

---

## Overview

AI-generated alt text is now cached in WordPress metadata to prevent duplicate API calls and reduce costs.

---

## How It Works

### 1. First Generation (API Call)
When an image is auto-assigned to a block:
```php
generateAltTextWithAI($image_id, $context)
  → Check cache (empty)
  → Call OpenAI API ($0.001 cost)
  → Generate: "Men's platinum wedding band with comfort fit"
  → Save to cache
  → Return alt text
```

**Metadata stored:**
- `_ai_generated_alt_text` - The alt text itself
- `_ai_alt_text_generated_at` - Unix timestamp
- `_ai_alt_text_context_hash` - MD5 hash of context (for debugging)

### 2. Subsequent Uses (Cached)
If the same image is used again:
```php
generateAltTextWithAI($image_id, $context)
  → Check cache (found!)
  → Return cached alt text immediately
  → NO API call = $0 cost ✅
```

---

## Benefits

✅ **Cost Savings**: Reusing images = free alt text generation
✅ **Performance**: Instant alt text (no API wait time)
✅ **Context-Aware**: Still uses focus keyword + page context for quality
✅ **Deduplication**: Same image across 10 pages = 1 API call instead of 10

---

## Cost Example

**Scenario**: Creating 10 pages, each reusing the same 5 images

**Without Caching:**
- 10 pages × 5 images = 50 API calls
- Cost: 50 × $0.001 = $0.05

**With Caching:**
- First page: 5 API calls ($0.005)
- Pages 2-10: 0 API calls ($0.00)
- **Total Cost: $0.005** (90% savings!)

---

## Cache Management

### Automatic Cache
Cache is automatically populated when:
- "Generate" button clicked on Hero block
- "Generate" button clicked on Process block
- "Generate All Blocks" runs

### Manual Cache Clearing
Clear cache for an image (forces regeneration):
```php
$image_matching_service->clearAltTextCache( $image_id );
```

### Cache Expiration
Currently: **Never expires** (cache is permanent)

To add expiration in the future, uncomment in `getCachedAltText()`:
```php
if ( time() - $cached_timestamp > 30 * DAY_IN_SECONDS ) {
    return null; // Expire after 30 days
}
```

---

## Logging

When `WP_DEBUG` is enabled, cache activity is logged:

```
[SEO Generator - Image Matching] ai_alt_text_cache | Using cached alt text: Men's platinum wedding band
[SEO Generator - Image Matching] ai_alt_text_cached | Cached alt text: Men's platinum wedding band
[SEO Generator - Image Matching] ai_alt_text_cache_cleared | Cache cleared for manual regeneration
```

---

## Database Impact

**Per Image with Cached Alt Text:**
- 3 meta rows in `wp_postmeta` table
- ~200 bytes of storage
- Negligible performance impact

**For 1,000 cached images:**
- 3,000 meta rows
- ~200 KB storage

---

## Testing

### Test 1: Cache Miss (First Generation)
1. Create new SEO page
2. Set focus keyword: "platinum wedding bands"
3. Click "Generate" on Hero block
4. Check logs: Should see "Cached alt text: ..."
5. Check database: `SELECT * FROM wp_postmeta WHERE meta_key = '_ai_generated_alt_text'`

### Test 2: Cache Hit (Reuse)
1. Create second SEO page with same focus keyword
2. Click "Generate" on Hero block (reuses same image)
3. Check logs: Should see "Using cached alt text: ..."
4. Verify: Second generation is instant (no API delay)

### Test 3: Manual Cache Clear
```php
$image_matching = new ImageMatchingService($openai_service);
$image_matching->clearAltTextCache(123); // Replace with actual image ID
```

---

## Future Enhancements (Tier 2)

Tier 1 (this implementation) provides the foundation. Future enhancements could include:

1. **Manual Bulk Pre-Generation** (5-8 hours)
   - Media Library bulk action: "Generate AI Alt Text"
   - Process 10-50 images at once
   - Progress bar

2. **Admin UI Indicators** (3-4 hours)
   - Show cache status in Media Library
   - Badge overlay: ✅ "Alt Text Cached"

3. **Context-Specific Caching** (2-3 hours)
   - Different cache entries for different contexts
   - Example: Same image with different focus keywords

---

## Code Locations

**Main Implementation:**
- `includes/Services/ImageMatchingService.php:445-561`

**Methods Added:**
- `generateAltTextWithAI()` - Modified to check/save cache
- `getCachedAltText()` - Check if cached alt text exists
- `cacheAltText()` - Save alt text to cache
- `buildContextHash()` - Create context fingerprint
- `clearAltTextCache()` - Clear cache for an image

---

## Notes

- Cache is tied to image ID, not context (simple implementation)
- Same alt text used regardless of context (acceptable for most use cases)
- To implement context-specific caching, use `_ai_alt_text_context_hash` for cache key lookup
