# Bug Fix: Hero Summary Field Empty After Generation

**Date**: 2025-10-09
**Severity**: Medium
**Status**: ✅ Fixed

---

## Problem

After generating hero section content, the `hero_title` and `hero_subtitle` fields were populated correctly, but the `hero_summary` field remained empty.

### User Report
```
"I noticed that I fills in the Hero Title and the Hero Subtitle correctly
but the Hero Summary is left empty and there's no Hero Image selected."
```

---

## Root Cause

**AI Prompt Missing Required Field**

### Parser Expectations (BlockContentParser.php:42-56)
```php
private function parseHeroContent( string $content ): array {
    $data = $this->parseJSON( $content );

    return array(
        'hero_title'    => $data['headline'] ?? '',     // ✅ Expected
        'hero_subtitle' => $data['subheadline'] ?? '',  // ✅ Expected
        'hero_summary'  => $data['summary'] ?? '',      // ✅ Expected (but missing!)
    );
}
```

### AI Prompt Template (DefaultPrompts.php - BEFORE FIX)
```php
// Lines 58-72 - BEFORE
Requirements:
- Headline: 6-10 words, engaging and keyword-optimized
- Subheadline: 15-20 words providing context and drawing readers in
// ❌ No mention of summary field!

Output as JSON:
{
  "headline": "Your headline here",
  "subheadline": "Your subheadline here"
  // ❌ Missing: "summary" field
}
```

**The Issue**:
- Parser expected 3 fields: `headline`, `subheadline`, `summary`
- AI prompt only requested 2 fields: `headline`, `subheadline`
- Result: AI didn't know to generate summary, so it was always empty

---

## Solution

Updated the hero prompt template to explicitly request the summary field.

### Files Changed

**includes/Data/DefaultPrompts.php** (lines 58-72)

```php
// AFTER FIX
Requirements:
- Headline: 6-10 words, engaging and keyword-optimized
- Subheadline: 15-20 words providing context and drawing readers in
- Summary: 60-80 words describing what the page covers, key benefits, and target audience  // ✅ ADDED
- Natural keyword integration
- Clear value proposition
- No promotional language
- Focus on information and guidance

Output as JSON:
{
  "headline": "Your headline here",
  "subheadline": "Your subheadline here",
  "summary": "Your summary here"  // ✅ ADDED
}
```

---

## Testing

### Before Fix
1. Create new SEO page
2. Enter title: "Platinum Wedding Rings"
3. Enter focus keyword: "platinum wedding bands"
4. Click "Generate" on Hero Section
5. ❌ **Result**: `hero_title` ✅, `hero_subtitle` ✅, `hero_summary` ❌ (empty)

### After Fix
1. Create new SEO page
2. Enter title: "Platinum Wedding Rings"
3. Enter focus keyword: "platinum wedding bands"
4. Click "Generate" on Hero Section
5. ✅ **Result**: All 3 fields populated correctly

---

## Build Command

**Not Required** - This was a PHP-only change. No JavaScript rebuild needed.

---

## Impact

### Before
- ❌ Hero summary field always empty after generation
- ❌ Incomplete hero section content
- ❌ Frontend template showed empty summary text
- ❌ Poor user experience with missing content

### After
- ✅ All 3 hero fields populated correctly
- ✅ Complete hero section with title, subtitle, and summary
- ✅ Frontend displays full content from Figma design
- ✅ AI generates comprehensive hero section

---

## Related Files

- `includes/Data/DefaultPrompts.php` (modified - lines 58-72)
- `includes/Services/BlockContentParser.php` (unchanged - reference only)
- `wp-content/themes/twentytwentyfive/single-seo-page.php` (unchanged - displays the summary)

---

## Note on Hero Image

The user also reported no hero image being selected. This is **not a bug**.

**Status**: Working as designed
**Explanation**:
- Auto-assignment functionality is working correctly
- No matching images found in media library for the given context
- To enable auto-assignment, user needs to upload images with appropriate tags

**How to Fix**:
1. Go to Media Library
2. Upload ring images
3. Add tags matching page topic (e.g., "wedding rings", "platinum rings")
4. Regenerate hero section - image will be auto-assigned

---

## Lessons Learned

1. **Prompt-Parser Alignment**: AI prompts must request ALL fields that the parser expects
2. **Field Validation**: Should add validation to warn if parser expects fields not in prompt
3. **Testing**: Need comprehensive testing that checks all generated fields, not just some
4. **Documentation**: Prompt templates should clearly document expected output structure

---

## Prevention

### Future Safeguards

1. **Add Prompt Validation Unit Test**:
   ```php
   public function test_hero_prompt_includes_all_required_fields() {
       $template = DefaultPrompts::getHeroTemplate();
       $user_prompt = $template['user'];

       // Verify prompt asks for all required fields
       $this->assertStringContainsString('"headline"', $user_prompt);
       $this->assertStringContainsString('"subheadline"', $user_prompt);
       $this->assertStringContainsString('"summary"', $user_prompt);
   }
   ```

2. **Create Parser-Prompt Contract Documentation**:
   ```php
   /**
    * Hero Block Output Contract
    *
    * Parser expects:
    * - headline (string, 6-10 words)
    * - subheadline (string, 15-20 words)
    * - summary (string, 60-80 words)
    *
    * @see BlockContentParser::parseHeroContent()
    * @see DefaultPrompts::getHeroTemplate()
    */
   ```

3. **Add Runtime Validation**:
   ```php
   private function parseHeroContent( string $content ): array {
       $data = $this->parseJSON( $content );

       // Log warning if expected fields are missing
       $required_fields = ['headline', 'subheadline', 'summary'];
       foreach ($required_fields as $field) {
           if (empty($data[$field])) {
               error_log("Warning: Hero content missing required field: {$field}");
           }
       }

       return array(
           'hero_title'    => $data['headline'] ?? '',
           'hero_subtitle' => $data['subheadline'] ?? '',
           'hero_summary'  => $data['summary'] ?? '',
       );
   }
   ```

---

## Summary of All 3 Bugs Fixed Today

1. **Context Key Mismatch** (Bug #1) - `title` vs `page_title`
2. **Response Format Mismatch** (Bug #2) - `response.data.content` vs `response.content`
3. **Missing Prompt Field** (Bug #3 - this bug) - AI prompt missing `summary` field

All three bugs have been fixed and documented.
