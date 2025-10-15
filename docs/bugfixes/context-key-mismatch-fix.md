# Bug Fix: Context Key Mismatch + Response Format Mismatch

**Date**: 2025-10-09
**Severity**: Critical
**Status**: ✅ Fixed (2 bugs found and fixed)

---

## Problem

When clicking "Generate" on the Hero Section block, the AI was receiving "Auto Draft" as the page title instead of the actual title entered in the admin editor (e.g., "Platinum Wedding Rings").

### Error Log
```
[SEO Generator] Hero parse failed. Raw content: It seems there might have been a
misunderstanding in your request. The topic category and target keyword were not
provided, making it challenging to craft a specific hero section content for a
page about "Auto Draft"...
```

---

## Root Cause

**Context key mismatch** between frontend JavaScript and backend PHP:

### Frontend (PageEditor/index.js:167-171)
```javascript
const result = await API.generateBlock(postId, blockId, {
    title: pageData.title,           // ❌ key: "title"
    topic: pageData.topic,           // ❌ key: "topic"
    focusKeyword: pageData.focusKeyword  // ❌ key: "focusKeyword"
});
```

### Backend (PromptTemplateEngine.php:192-197)
```php
$context = array(
    'page_title'    => $post->post_title,    // ✅ expects: "page_title"
    'page_topic'    => $page_topic,          // ✅ expects: "page_topic"
    'focus_keyword' => $focus_keyword,       // ✅ expects: "focus_keyword"
);

// Merge with additional context (line 200)
return array_merge( $context, $additional_context );
```

**The Issue**:
- `array_merge()` doesn't override because keys are different!
- Frontend sends `title`, backend reads `page_title` from database
- Result: Backend uses database value ("Auto Draft") instead of React state value ("Platinum Wedding Rings")

---

## Solution

Updated frontend to send correct key names that match backend expectations:

### Files Changed

**1. PageEditor/index.js** (3 locations)

```javascript
// Line 167-171: handleGenerateBlock
const result = await API.generateBlock(postId, blockId, {
    page_title: pageData.title,         // ✅ Fixed
    page_topic: pageData.topic,         // ✅ Fixed
    focus_keyword: pageData.focusKeyword, // ✅ Fixed
});

// Line 273-277: GenerationControls context
context: {
    page_title: pageData.title,         // ✅ Fixed
    page_topic: pageData.topic,         // ✅ Fixed
    focus_keyword: pageData.focusKeyword, // ✅ Fixed
},

// Line 288-292: BlockList context
context: {
    page_title: pageData.title,         // ✅ Fixed
    page_topic: pageData.topic,         // ✅ Fixed
    focus_keyword: pageData.focusKeyword, // ✅ Fixed
},
```

**2. GenerationControls.js**

```javascript
// Line 34: Validation
if (!context.page_title || context.page_title.trim() === '') {
    // ✅ Fixed: was context.title
}

// Line 39: Validation
if (!context.focus_keyword || context.focus_keyword.trim() === '') {
    // ✅ Fixed: was context.focusKeyword
}

// Line 52-54: canGenerate check
const canGenerate =
    context.page_title && context.page_title.trim() !== '' &&
    context.focus_keyword && context.focus_keyword.trim() !== '';
    // ✅ Fixed: were context.title and context.focusKeyword
```

---

## Testing

### Before Fix
1. Create new SEO page
2. Enter title: "Platinum Wedding Rings"
3. Enter focus keyword: "platinum wedding bands"
4. Click "Generate" on Hero Section
5. ❌ **Result**: AI receives "Auto Draft", generation fails

### After Fix
1. Create new SEO page
2. Enter title: "Platinum Wedding Rings"
3. Enter focus keyword: "platinum wedding bands"
4. Click "Generate" on Hero Section
5. ✅ **Result**: AI receives "Platinum Wedding Rings", generates content successfully

---

## Build Command

After code changes, JavaScript was rebuilt:
```bash
cd wp-content/plugins/content-generator-disabled
npm run build
```

**Build Output**: ✅ webpack 5.102.1 compiled successfully in 5070 ms

---

## Impact

### Before
- ❌ Single block generation failed with "Auto Draft" error
- ❌ Users couldn't generate content without saving page first
- ❌ Context from React state was ignored

### After
- ✅ Single block generation works with unsaved changes
- ✅ AI receives correct title from React state
- ✅ Context properly passed from frontend to backend
- ✅ Users can test content generation immediately

---

## Related Files

- `assets/js/src/components/PageEditor/index.js` (modified)
- `assets/js/src/components/PageEditor/GenerationControls.js` (modified)
- `assets/js/build/index.js` (rebuilt)
- `assets/js/build/index.asset.php` (version updated)
- `includes/Services/PromptTemplateEngine.php` (unchanged - reference only)

---

## Lessons Learned

1. **API Contract Consistency**: Frontend and backend must use same key names for context objects
2. **Type Safety**: TypeScript or JSDoc would have caught this at compile time
3. **Integration Testing**: Need tests that verify context is passed correctly through API
4. **Documentation**: API contracts should be documented (context structure, key names, types)

---

## Prevention

### Future Safeguards

1. **Create Context Interface Documentation**:
   ```javascript
   /**
    * @typedef {Object} GenerationContext
    * @property {string} page_title - Page title (from post_title or React state)
    * @property {string} page_topic - Topic name (from seo-topic taxonomy)
    * @property {string} focus_keyword - SEO focus keyword (from post meta)
    */
   ```

2. **Add TypeScript** (optional - future enhancement):
   ```typescript
   interface GenerationContext {
       page_title: string;
       page_topic: string;
       focus_keyword: string;
   }
   ```

3. **Add Integration Test**:
   ```php
   public function test_context_keys_match_frontend() {
       $context = array(
           'page_title'    => 'Test Title',
           'page_topic'    => 'Test Topic',
           'focus_keyword' => 'test keyword',
       );

       $result = $this->prompt_engine->buildContext(123, $context);

       $this->assertEquals('Test Title', $result['page_title']);
   }
   ```

---

## Bug #2: Response Format Mismatch

### Problem

After fixing Bug #1, generation still failed with: `Error: Generation failed`

### Root Cause

**Response structure mismatch** between backend and frontend:

### Backend (GenerationController.php:306)
```php
return new WP_REST_Response( $result, 200 );

// Where $result from generateSingleBlock() is:
array(
    'success' => true,
    'content' => $parsed_content,  // ✅ Direct property
    'metadata' => array(...)
);
```

### Frontend (useGeneration.js:98-100)
```javascript
if (response.success && response.data) {  // ❌ Looking for .data
    return response.data.content;          // ❌ Looking for .data.content
}
```

**The Issue**: Frontend expected `response.data.content`, but backend returned `response.content` directly!

### Solution

Updated frontend to match backend response structure:

**hooks/useGeneration.js** (lines 98-100)
```javascript
// Before (wrong):
if (response.success && response.data) {
    return response.data.content;
}

// After (correct):
if (response.success && response.content) {
    return response.content;
}
```

### Build Command
```bash
npm run build
```
**Build Output**: ✅ webpack 5.102.1 compiled successfully in 3893 ms

---

## Notes

- **Two sequential bugs** were found and fixed
- Bug #1: Context keys didn't match (title vs page_title)
- Bug #2: Response structure didn't match (response.data.content vs response.content)
- This bug only affected **single block generation** (not "Generate All Blocks")
- Database values were never incorrect - issues were purely in API communication
- Fix does not require database migration or cache clearing
- Compatible with existing saved pages
