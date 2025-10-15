# Known Bugs

## ✅ FIXED: Undefined method logError() causing 500 error

**Issue:** When clicking "Generate All Blocks", the API returns 500 Internal Server Error.

**Root Cause:** `ContentGenerationService.php` was calling `$this->logError()` at lines 284 and 292, but this method doesn't exist.

**Fix:** Removed the redundant `logError()` calls since `error_log()` is already being used.

**Status:** Fixed

---

## React Editor Not Loading on New SEO Page

**Issue:** The "Generate All Blocks" button and React interface may not appear when creating a new SEO page.

**Root Cause:** In `includes/Admin/PageEditor.php:36-44`, the script enqueue logic checks `global $post` object. On the `post-new.php` screen for a brand new page, `$post` may not exist yet, causing the script to return early and never load the React app.

**Current Code (Line 36-44):**
```php
global $post;
if ( ! $post || 'seo-page' !== $post->post_type ) {
    return;
}
```

**Fix:** Check for post type from `$_GET['post_type']` when `$post` doesn't exist:
```php
// Check post type from global $post or query string.
$post_type = '';
global $post;

if ( $post ) {
    $post_type = $post->post_type;
} elseif ( isset( $_GET['post_type'] ) ) {
    $post_type = sanitize_text_field( $_GET['post_type'] );
}

if ( 'seo-page' !== $post_type ) {
    return;
}
```

**Workaround:**
1. Save the page as a draft first (without the React interface)
2. Then reload the page - the React editor should appear on the edit screen

**File:** `includes/Admin/PageEditor.php:36-44`
