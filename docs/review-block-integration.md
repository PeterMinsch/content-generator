# Review Block Integration Guide

This guide explains how to use review data in your review block template.

## Overview

When a page is generated with a review block, the plugin automatically:
1. Fetches reviews from Google Business Profile (cached for 30 days)
2. Stores top 5 reviews in post meta: `_seo_reviews_data`
3. Makes reviews available via helper functions

## Review Data Structure

Each review contains:

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `id` | int | Database ID | `123` |
| `source` | string | Platform (always 'google' for now) | `'google'` |
| `external_review_id` | string | Google's review ID | `'ChZDSUhN...'` |
| `reviewer_name` | string | Reviewer's display name | `'Sarah Johnson'` |
| `reviewer_avatar_url` | string | Avatar image URL (can be empty) | `'https://...'` |
| `rating` | string | Rating as decimal string | `'5.0'`, `'4.5'` |
| `review_text` | string | Review comment (can be empty) | `'Excellent service!'` |
| `review_date` | string | Review date (MySQL datetime) | `'2025-09-20 10:30:00'` |

## Helper Functions

### seo_get_page_reviews()

Retrieves reviews for a page.

**Signature:**
```php
function seo_get_page_reviews(int $post_id): array
```

**Example:**
```php
$reviews = seo_get_page_reviews(get_the_ID());

if (empty($reviews)) {
    echo '<p>No reviews available yet.</p>';
} else {
    foreach ($reviews as $review) {
        echo '<div class="review">';
        echo '<h4>' . esc_html($review['reviewer_name']) . '</h4>';
        echo '<p>' . esc_html($review['review_text']) . '</p>';
        echo '</div>';
    }
}
```

### seo_format_review_rating()

Formats rating as star emojis.

**Signature:**
```php
function seo_format_review_rating(float $rating): string
```

**Example:**
```php
$rating = (float) $review['rating'];
echo seo_format_review_rating($rating); // ⭐⭐⭐⭐⭐
```

### seo_get_review_avatar()

Returns avatar image HTML.

**Signature:**
```php
function seo_get_review_avatar(string $url, int $size = 64): string
```

**Example:**
```php
echo seo_get_review_avatar($review['reviewer_avatar_url'], 80);
// <img src="..." alt="Reviewer avatar" width="80" height="80">
```

## Sample Block Template

```php
<?php
// Get reviews for current page
$reviews = seo_get_page_reviews(get_the_ID());

if (empty($reviews)) {
    // Empty state
    echo '<div class="review-section-empty">';
    echo '<p>No customer reviews available yet. Check back soon!</p>';
    echo '</div>';
    return;
}
?>

<div class="review-section">
    <h2>Customer Reviews</h2>

    <div class="review-grid">
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <?php echo seo_get_review_avatar($review['reviewer_avatar_url'], 64); ?>
                    <div class="review-meta">
                        <h4 class="reviewer-name"><?php echo esc_html($review['reviewer_name']); ?></h4>
                        <div class="review-rating">
                            <?php echo seo_format_review_rating((float) $review['rating']); ?>
                        </div>
                        <time datetime="<?php echo esc_attr($review['review_date']); ?>">
                            <?php echo esc_html(date('F j, Y', strtotime($review['review_date']))); ?>
                        </time>
                    </div>
                </div>

                <?php if (!empty($review['review_text'])): ?>
                    <div class="review-text">
                        <p><?php echo esc_html($review['review_text']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="review-source">
                    <span class="google-badge">Google Review</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

## Handling Empty Reviews

Always check if reviews array is empty before rendering:

```php
$reviews = seo_get_page_reviews(get_the_ID());

if (empty($reviews)) {
    // Show placeholder or hide block
    return;
}
```

## Troubleshooting

**No reviews showing?**
- Check `wp-content/debug.log` for "[Review Integration]" entries
- Verify post meta exists: `get_post_meta($post_id, '_seo_reviews_data', true)`
- Confirm review block in generation plan (block order)

**Reviews not updating?**
- Reviews cached for 30 days (by design)
- Force refresh: regenerate page content
- Check Google API credentials in `GoogleBusinessService.php`

## API Rate Limits

Reviews are cached for 30 days to conserve Google API quota. Fresh reviews only fetched when:
- Cache is empty (first generation)
- Cache is stale (> 30 days old)
- Page is regenerated

This prevents hitting Google's rate limits during normal operation.
