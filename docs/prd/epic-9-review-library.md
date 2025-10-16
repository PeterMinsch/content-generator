# Epic 9: Automated Review Integration

**Timeline:** Week 12 (6-8 hours)
**Status:** Not Started
**Priority:** Medium
**Dependencies:** Epic 1 (Foundation - database patterns), Epic 2 (Content Generation Service)

## Epic Goal

Automatically fetch and cache Google Business Profile reviews for "Bravo Jewelers" during page generation when a review block is requested. Reviews are stored in database for reuse across multiple pages, with automatic refresh capability.

## Success Criteria

- Reviews automatically fetched from Google Business Profile API when review block requested
- Reviews cached in custom database table `wp_seo_reviews` for reuse
- Google API credentials hardcoded (Bravo Jewelers business)
- Duplicate reviews prevented via unique constraints
- Review data available to review block developer via helper functions
- Cache refresh mechanism (fetch new reviews if cache older than X days)
- No admin UI required - completely automated

---

## Story 9.1: Implement Review Database Schema and Repository

**As a** plugin developer
**I want** a custom database table and repository pattern for cached review data
**So that** reviews can be stored and reused across multiple page generations

### Acceptance Criteria

1. Custom table `{prefix}_seo_reviews` created on plugin activation:
   - Fields: id, source, external_review_id, reviewer_name, reviewer_avatar_url, rating, review_text, review_date, last_fetched_at
   - Unique constraint on (source, external_review_id) to prevent duplicates
   - Index on source and last_fetched_at for cache validation queries
2. Repository class provides methods:
   - `getAll(int $limit = 10): array` - get all reviews (default: 10 most recent)
   - `getByRating(float $min_rating, int $limit = 10): array` - filter by minimum rating
   - `getTopRated(int $limit = 5): array` - get highest rated reviews
   - `save(array $review_data): int` - insert new review (duplicate-safe)
   - `deleteAll(): int` - clear all cached reviews
   - `getCacheAge(): int` - return age of oldest review in days
   - `needsRefresh(int $max_days = 30): bool` - check if cache is stale
3. Table created/updated via activation hook
4. Table uses InnoDB engine with utf8mb4 charset
5. All database operations use prepared statements (security)
6. Repository returns associative arrays (consistent format)

### Technical Requirements

**Database Schema:**
```sql
CREATE TABLE {prefix}_seo_reviews (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL DEFAULT 'google',
    external_review_id VARCHAR(255) NOT NULL,
    reviewer_name VARCHAR(255),
    reviewer_avatar_url VARCHAR(500),
    rating DECIMAL(2,1),
    review_text TEXT,
    review_date DATETIME,
    last_fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_review (source, external_review_id),
    INDEX idx_source (source),
    INDEX idx_last_fetched (last_fetched_at),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Repository Class:**
- File: `includes/Repositories/ReviewRepository.php`
- Use `$wpdb` global for database access
- Follow same pattern as existing repositories in plugin
- Methods return `array` (not WP_Query objects)
- Use `INSERT IGNORE` or check for duplicates before saving

**Activation Hook:**
- Add table creation to `includes/Activation.php`
- Check table version with option `seo_generator_review_db_version`
- Support upgrades if schema changes in future versions

### Definition of Done

- [ ] Table created on plugin activation
- [ ] All repository methods implemented and tested
- [ ] Duplicate prevention working (unique constraint)
- [ ] Queries use prepared statements
- [ ] Cache age calculation working
- [ ] Activation hook creates table successfully
- [ ] Code documented with PHPDoc

---

## Story 9.2: Build Google Business Profile API Integration

**As a** plugin developer
**I want** to fetch reviews from Google Business Profile API
**So that** reviews can be cached and used in review blocks

### Acceptance Criteria

1. **GoogleBusinessService** class handles:
   - OAuth 2.0 authentication with hardcoded credentials
   - API endpoint: `https://mybusiness.googleapis.com/v4/accounts/{accountId}/locations/{locationId}/reviews`
   - Parsing review data into standard format
   - Error handling (invalid credentials, rate limits, network errors)
   - Rate limit handling (429 status codes)
2. Hardcoded configuration for Bravo Jewelers:
   - Business name: "Bravo Jewelers"
   - Location ID, Account ID hardcoded as class constants
   - OAuth credentials (client ID, secret, refresh token) hardcoded
3. Service normalizes data to standard format:
   ```php
   [
       'source' => 'google',
       'external_review_id' => 'google_review_id_123',
       'reviewer_name' => 'John Smith',
       'reviewer_avatar_url' => 'https://...',
       'rating' => 5.0,
       'review_text' => 'Excellent service and beautiful rings!',
       'review_date' => '2025-09-20 14:30:00',
   ]
   ```
4. Fetch method returns array of normalized reviews
5. Error handling returns empty array (graceful degradation)
6. API responses logged to debug.log for troubleshooting

### Technical Requirements

**Service Class:**
- File: `includes/Services/GoogleBusinessService.php`
- Method: `fetchReviews(): array`
- Hardcoded constants:
  ```php
  const BUSINESS_NAME = 'Bravo Jewelers';
  const ACCOUNT_ID = 'YOUR_ACCOUNT_ID';
  const LOCATION_ID = 'YOUR_LOCATION_ID';
  const CLIENT_ID = 'YOUR_CLIENT_ID';
  const CLIENT_SECRET = 'YOUR_CLIENT_SECRET';
  const REFRESH_TOKEN = 'YOUR_REFRESH_TOKEN';
  ```

**API Documentation:**
- Google Business Profile API: https://developers.google.com/my-business/reference/rest
- OAuth 2.0 flow for server-to-server: https://developers.google.com/identity/protocols/oauth2

**HTTP Client:**
- Use `wp_remote_get()` and `wp_remote_post()` for API calls
- Set timeout: 30 seconds
- Check for `is_wp_error()` responses

**Error Handling:**
- Catch API exceptions and log to debug.log
- Return empty array on error (don't break page generation)
- Log format: `[Google Reviews] Error: {message}`

**Rate Limiting:**
- Detect 429 status codes
- Log rate limit errors
- Return cached reviews if rate limited

### Definition of Done

- [ ] GoogleBusinessService class implemented
- [ ] OAuth authentication working with hardcoded credentials
- [ ] Reviews fetched and parsed correctly
- [ ] Standardized data format working
- [ ] Error handling covers all common failures
- [ ] Rate limiting handled gracefully
- [ ] Integration tested with real API credentials
- [ ] API calls logged to debug.log

---

## Story 9.3: Build Review Fetch Service with Auto-Refresh

**As a** content manager
**I want** reviews automatically fetched and cached when needed
**So that** review blocks always have fresh data without manual intervention

### Acceptance Criteria

1. **ReviewFetchService** orchestrates review fetching:
   - `getReviews(int $limit = 10, bool $force_refresh = false): array`
   - Checks cache age before fetching
   - If cache fresh (< 30 days old), returns cached reviews
   - If cache stale or empty, fetches from Google API
   - Saves fetched reviews to database (via ReviewRepository)
   - Returns reviews for use in blocks
2. Cache refresh logic:
   - Default cache lifetime: 30 days
   - Force refresh parameter bypasses cache
   - Duplicate reviews skipped during save
   - Log cache hits vs. API fetches
3. Integration with page generation:
   - Called automatically when review block detected in block order
   - Runs during `ContentGenerationService::generateBlock('review_section')`
   - Reviews returned and stored in ACF fields for block
4. Error handling:
   - If API fails and cache exists, use cached reviews (graceful degradation)
   - If API fails and no cache, return empty array (block shows placeholder)
   - Log all fetch operations
5. Performance considerations:
   - Single API call per generation session (cache result in object)
   - Don't fetch if review block not in generation plan

### Technical Requirements

**Service Class:**
- File: `includes/Services/ReviewFetchService.php`
- Constructor injects `GoogleBusinessService` and `ReviewRepository`
- Method: `getReviews(int $limit = 10, bool $force_refresh = false): array`

**Cache Logic:**
```php
public function getReviews(int $limit = 10, bool $force_refresh = false): array {
    // Check if cache needs refresh
    if (!$force_refresh && !$this->repository->needsRefresh(30)) {
        // Return cached reviews
        return $this->repository->getAll($limit);
    }

    // Fetch fresh reviews from Google API
    $reviews = $this->googleService->fetchReviews();

    // Save to database (skip duplicates)
    foreach ($reviews as $review) {
        $this->repository->save($review);
    }

    // Return reviews
    return $this->repository->getAll($limit);
}
```

**Integration Point:**
- Called in `ContentGenerationService` when review block is in generation plan
- Store reviews in post meta or pass to block developer's ACF fields

**Logging:**
- Log format: `[Review Fetch] Cache age: X days | Action: fetch|cache | Count: Y reviews`
- Log all API calls and cache hits

### Definition of Done

- [ ] ReviewFetchService class implemented
- [ ] Cache age checking working
- [ ] Automatic refresh when cache stale
- [ ] Force refresh parameter working
- [ ] Duplicate prevention during save
- [ ] Graceful degradation on API errors
- [ ] Integration with ContentGenerationService
- [ ] All operations logged

---

## Story 9.4: Integrate Review Data with Page Generation

**As a** content manager
**I want** reviews automatically available when generating pages with review blocks
**So that** review blocks display real customer feedback from Google

### Acceptance Criteria

1. Detection of review block in generation plan:
   - Check if 'review_section' exists in block order
   - Only fetch reviews if block requested
2. Review fetching during generation:
   - In `ContentGenerationService::generateBlock('review_section')`
   - Call `ReviewFetchService::getReviews()` to get cached or fresh reviews
   - Default: fetch top 5 highest-rated reviews
3. Review data passed to block developer:
   - Store reviews in post meta: `_seo_reviews_data` (JSON-encoded array)
   - Alternative: Store in ACF field if review block uses ACF
   - Provide helper function for block developer to retrieve data
4. Helper functions for block rendering:
   - `seo_get_page_reviews(int $post_id): array` - get reviews for a page
   - `seo_format_review_rating(float $rating): string` - format rating as stars
   - `seo_get_review_avatar(string $url): string` - get avatar with fallback
5. Default behavior when no reviews available:
   - Store empty array in post meta
   - Block shows placeholder: "No reviews available yet"
6. Data structure for block developer:
   ```php
   [
       [
           'reviewer_name' => 'John Smith',
           'reviewer_avatar' => 'https://...',
           'rating' => 5.0,
           'review_text' => 'Excellent service!',
           'review_date' => 'September 20, 2025',
           'platform' => 'Google',
       ],
       // ... more reviews
   ]
   ```

### Technical Requirements

**Integration in ContentGenerationService:**
- Modify `generateBlock()` method to handle 'review_section'
- Check for review block before fetching:
  ```php
  if (in_array('review_section', $this->getBlockOrder($post_id))) {
      $reviews = $this->reviewFetchService->getReviews(5);
      update_post_meta($post_id, '_seo_reviews_data', wp_json_encode($reviews));
  }
  ```

**Helper Functions:**
- File: `includes/functions.php`
- Functions:
  ```php
  function seo_get_page_reviews(int $post_id): array {
      $json = get_post_meta($post_id, '_seo_reviews_data', true);
      return json_decode($json, true) ?: [];
  }

  function seo_format_review_rating(float $rating): string {
      $full_stars = floor($rating);
      $half_star = ($rating - $full_stars) >= 0.5;
      // Return star HTML/emoji
  }

  function seo_get_review_avatar(string $url, int $size = 64): string {
      // Return avatar HTML with fallback to default avatar
  }
  ```

**Documentation for Block Developer:**
- Create: `docs/review-block-integration.md`
- Document data structure
- Document helper functions
- Provide code examples

**Coordination:**
- Share data structure with block developer
- Ensure block can handle empty array gracefully
- Test integration with mock review block

### Definition of Done

- [ ] Review block detection working in generation flow
- [ ] Reviews fetched only when review block requested
- [ ] Review data stored in post meta
- [ ] Helper functions implemented and tested
- [ ] Data structure documented for block developer
- [ ] Graceful handling of missing reviews
- [ ] Integration tested with mock data
- [ ] Documentation created for block developer

---

## Epic Dependencies

- Epic 1: Foundation Setup (database patterns, activation hooks)
- Epic 2: Content Generation Service (block generation flow)
- Coordination with review block developer (separate work stream)

## Risks & Mitigations

**Risk:** Google OAuth credentials complex to obtain
**Mitigation:** Provide step-by-step setup guide, use service account if possible

**Risk:** Google API rate limits hit during high-volume generation
**Mitigation:** 30-day cache prevents frequent API calls, cache shared across all pages

**Risk:** Review block not ready when data layer complete
**Mitigation:** Build helper functions and documentation first, easy integration later

**Risk:** API credentials expire or become invalid
**Mitigation:** Log all API errors clearly, provide troubleshooting documentation

**Risk:** Cached reviews become very stale (> 30 days)
**Mitigation:** Acceptable per requirements - reviews don't need to be real-time fresh

## Technical Notes

### Simplified Architecture (No Admin UI)

This epic uses a **streamlined automated approach**:
- ✅ Custom database table for caching
- ✅ Repository pattern for data access
- ✅ Service layer for API integration
- ❌ No admin page (fully automated)
- ❌ No manual fetch buttons (automatic refresh)
- ✅ Integration with page generation workflow

### Hardcoded Configuration

For "Bravo Jewelers" single-business use case:
- Business name, location ID, account ID hardcoded in `GoogleBusinessService`
- OAuth credentials hardcoded (not in settings)
- Default cache lifetime: 30 days (can be adjusted in code)
- Default review count: 5 reviews per page (can be adjusted)

### Security Considerations

- OAuth credentials in code (not ideal for open-source, but acceptable for single-business plugin)
- Future: Move credentials to wp-config.php constants
- Sanitize review text before display (prevent XSS)
- Validate review data before saving to database

### Performance Considerations

- Database indexes on frequently queried columns (source, last_fetched_at, rating)
- Cache prevents API calls on every page generation
- Single fetch per generation session (object-level cache)
- Only fetch when review block actually requested

### Future Enhancements (Out of Scope for Epic 9)

- Support for Yelp and Facebook APIs (Stories 9.5, 9.6 later)
- Admin UI for manual refresh or review management
- Configurable cache lifetime via settings
- Multiple business/location support
- Review moderation/filtering options
- Cron job for automatic background refresh
