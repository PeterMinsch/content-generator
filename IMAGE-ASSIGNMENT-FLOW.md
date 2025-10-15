# Image Assignment Flow - Complete Process

## Overview
When you generate a hero block for a page, the plugin attempts to automatically find and assign a matching image from your image library.

---

## Step-by-Step Process

### STEP 1: User Triggers Generation
- User clicks "Generate" button for hero block on an seo-page post
- AJAX request sent to `/wp-admin/admin-ajax.php?action=seo_generate_content`

### STEP 2: ContentGenerationService.generateSingleBlock()
**File**: `includes/Services/ContentGenerationService.php:113`

1. Validates the post exists and is type `seo-page`
2. Builds generation context by calling `PromptTemplateEngine.buildContext()`

### STEP 3: Build Generation Context
**File**: `includes/Services/PromptTemplateEngine.php:170`

**What it does**: Gathers information about the page to use for content generation

**Data collected**:
```php
$context = array(
    'page_title'    => "Platinum Wedding Bands", // From post title
    'page_topic'    => "Wedding Bands",          // From seo-topic taxonomy
    'focus_keyword' => "platinum wedding band",  // From post meta 'focus_keyword'
    'page_type'     => "collection"              // Inferred from topic
);
```

**IMPORTANT**: The key is `page_topic`, not `topic`!

### STEP 4: Generate Content with OpenAI
**File**: `includes/Services/ContentGenerationService.php:135-140`

- Sends prompt to OpenAI API
- Receives hero block content (headline, subheadline, body text)
- Parses the generated content

### STEP 5: Update ACF Fields
**File**: `includes/Services/ContentGenerationService.php:149`

- Saves generated content to ACF fields
- Sets `_seo_gen_hero_generated` and `_seo_gen_hero_timestamp` meta

### STEP 6: Auto-Assign Images (IF ENABLED)
**File**: `includes/Services/ContentGenerationService.php:152`

**First checks**: Is auto-assignment enabled?
```php
$settings = get_option( 'seo_generator_settings', array() );
$enabled = $settings['enable_auto_assignment'] ?? true;
```

**If disabled**: Stops here. No image assignment happens.

**If enabled**: Continues to step 7...

### STEP 7: Assign Hero Image
**File**: `includes/Services/ContentGenerationService.php:285`

#### 7a. Build Image Context
Calls `buildImageContext()` to extract keywords for image matching:

```php
$image_context = array(
    'focus_keyword' => "platinum wedding band",  // From generation context
    'topic'         => "Wedding Bands"           // From generation context (page_topic key)
);
```

#### 7b. DEBUG LOGGING
**File**: `ContentGenerationService.php:290-293`

At this point, you should see these debug logs in your error log:
```
[SEO Generator - Hero Image Assignment DEBUG]
Post ID: 123
Generation context: {"page_title":"Platinum Wedding Bands","page_topic":"Wedding Bands","focus_keyword":"platinum wedding band","page_type":"collection"}
Image context: {"focus_keyword":"platinum wedding band","topic":"Wedding Bands"}
```

**CHECK YOUR LOGS**: If you don't see these logs, auto-assignment is either:
- Disabled in settings
- Not reaching this code (JavaScript error, AJAX failure, etc.)

### STEP 8: Find Matching Image
**File**: `includes/Services/ImageMatchingService.php:49`

#### 8a. Extract Keywords
Calls `extractKeywords()` to convert context into searchable tags:

```php
Input: {
    'focus_keyword' => "platinum wedding band",
    'topic'         => "Wedding Bands"
}

Output: ["platinum", "wedding", "band", "bands"]
```

**How it works**:
- Splits multi-word strings on spaces, hyphens, underscores
- Converts to lowercase
- Sanitizes to slug format
- Removes words shorter than 3 characters
- Removes duplicates

#### 8b. Attempt 0: Folder-Based Matching (FIRST PRIORITY)
**File**: `ImageMatchingService.php:59-62`

Looks for images with folder tags matching the keywords.

**Query**:
```php
// Try to find images with _seo_image_folder meta AND matching tag
WP_Query([
    'post_type'  => 'attachment',
    'meta_query' => [['key' => '_seo_library_image', 'value' => '1']],
    'tax_query'  => [['taxonomy' => 'image_tag', 'terms' => 'wedding-bands']]
])
```

**If match found**: Returns image ID, DONE!
**If no match**: Continues to Attempt 1...

#### 8c. Attempt 1: Match ALL Tags (AND operator)
**File**: `ImageMatchingService.php:65-68`

**Query**:
```php
WP_Query([
    'post_type'  => 'attachment',
    'meta_query' => [['key' => '_seo_library_image', 'value' => '1']],
    'tax_query'  => [[
        'taxonomy' => 'image_tag',
        'terms'    => ['platinum', 'wedding', 'band', 'bands'],
        'operator' => 'AND'  // Image must have ALL these tags
    ]]
])
```

**Requirement**: Image must have ALL 4 tags: `platinum`, `wedding`, `band`, `bands`

**If match found**: Returns image ID, DONE!
**If no match**: Continues to Attempt 2...

#### 8d. Attempt 2: Match First 2 Tags
**File**: `ImageMatchingService.php:71-76`

**Query**:
```php
WP_Query([
    'post_type'  => 'attachment',
    'meta_query' => [['key' => '_seo_library_image', 'value' => '1']],
    'tax_query'  => [[
        'taxonomy' => 'image_tag',
        'terms'    => ['platinum', 'wedding'],  // Only first 2 tags
        'operator' => 'AND'
    ]]
])
```

**Requirement**: Image must have both `platinum` AND `wedding` tags

**If match found**: Returns image ID, DONE!
**If no match**: Continues to Attempt 3...

#### 8e. Attempt 3: Match First 1 Tag
**File**: `ImageMatchingService.php:79-84`

**Query**:
```php
WP_Query([
    'post_type'  => 'attachment',
    'meta_query' => [['key' => '_seo_library_image', 'value' => '1']],
    'tax_query'  => [[
        'taxonomy' => 'image_tag',
        'terms'    => ['platinum'],  // Only first tag
        'operator' => 'AND'
    ]]
])
```

**Requirement**: Image must have `platinum` tag

**If match found**: Returns image ID, DONE!
**If no match**: Continues to Attempt 4...

#### 8f. Attempt 4: Default Image (FINAL FALLBACK)
**File**: `ImageMatchingService.php:87-94`

Checks settings for a default hero image:

```php
$settings = get_option( 'seo_generator_image_settings', array() );
$default_id = $settings['default_image_id'] ?? null;
```

**If default image exists**: Returns image ID, DONE!
**If no default**: Returns NULL - No image assigned

### STEP 9: Assign Image to Post
**File**: `includes/Services/ContentGenerationService.php:298-318`

If an image ID was found:

1. **Generate alt text** using `ImageMatchingService.assignImageWithMetadata()`
2. **Update hero_image ACF field** using `update_field( 'hero_image', $image_id, $post_id )`
3. **Log success** to error log

If no image found:
- **Log failure** to error log
- Hero image field remains empty

---

## Common Failure Points

### 1. Auto-Assignment Disabled
**Check**: Settings > Images > "Auto-Assign Images" checkbox
**Fix**: Enable the checkbox and save settings

### 2. No Images in Library
**Check**: Do you have images with `_seo_library_image = 1` meta?
**Fix**: Upload images through the Image Library page

### 3. No Matching Tags
**Check**: Do your images have tags matching your focus keywords?
**Example**:
- Page focus keyword: "platinum wedding band"
- Image needs tags: `platinum`, `wedding`, `band`

**Fix**: Edit images and add matching tags from the image_tag taxonomy

### 4. Wrong Taxonomy
**Check**: Are you using the `image_tag` taxonomy or standard WordPress tags?
**Fix**: Images must use the custom `image_tag` taxonomy

### 5. Focus Keyword Not Set
**Check**: Does the page have a `focus_keyword` post meta value?
**Fix**: Ensure focus_keyword is populated (not an ACF field, regular post meta)

### 6. Topic Not Assigned
**Check**: Is the page assigned to an `seo-topic` taxonomy term?
**Fix**: Assign the page to a topic like "Wedding Bands"

---

## How to Debug

### Step 1: Check Your Error Log
Location: `wp-content/debug.log` (if WP_DEBUG is enabled)

Look for these log entries:
```
[SEO Generator - Hero Image Assignment DEBUG]
Post ID: 123
Generation context: {"page_title":"...","page_topic":"...","focus_keyword":"...","page_type":"..."}
Image context: {"focus_keyword":"...","topic":"..."}
```

And then:
```
[SEO Generator - Auto-Assignment] Post: 123 | Block: hero | Image: 456 | Context: {...}
```
OR
```
[SEO Generator - Auto-Assignment] Post: 123 | Block: hero | Image: none | Context: {...}
```

### Step 2: Use the Diagnostic Script
Add `?debug_images=1` to any admin URL to see:
- Current settings values
- Available library images with tags
- Test image matching with sample contexts

### Step 3: Check Image Matching Logs
Look for these in error log:
```
[SEO Generator - Image Matching] Tags: platinum, wedding, band, bands | Attempt: 1 | Found: 0 | Selected: none
[SEO Generator - Image Matching] Tags: platinum, wedding | Attempt: 2 | Found: 0 | Selected: none
[SEO Generator - Image Matching] Tags: platinum | Attempt: 3 | Found: 0 | Selected: none
[SEO Generator - Image Matching] Tags: none | Attempt: 4 | Found: 0 | Selected: none
```

This shows you exactly which attempts are failing and why.

---

## Quick Checklist

Before generating content, verify:

- [ ] Auto-assignment is enabled in Settings > Images
- [ ] Page has a focus_keyword post meta value
- [ ] Page is assigned to an seo-topic term
- [ ] Images exist in library (uploaded through Image Library page)
- [ ] Images have `_seo_library_image = 1` meta
- [ ] Images are tagged with image_tag taxonomy terms
- [ ] Image tags match the focus keyword words (e.g., "platinum", "wedding", "band")
- [ ] WP_DEBUG is enabled to see error logs

If all checked and still failing, share your error log output!
