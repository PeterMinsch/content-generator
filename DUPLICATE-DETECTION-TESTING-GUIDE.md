# Duplicate Title Detection - Testing Guide

## Overview

This guide will help you test the duplicate title detection feature that was added to the Geographic Title Generator. This feature checks if generated titles already exist in WordPress and provides visual warnings.

---

## What Was Implemented

### Features Added:

1. **Backend Duplicate Checking** - Queries WordPress database for existing posts
2. **Visual Warnings** - Shows warning message with duplicate count
3. **Row Highlighting** - Duplicates highlighted in yellow with red border
4. **EXISTS Badge** - Red "⚠️ EXISTS" badge on duplicate titles
5. **Filter Checkbox** - Option to hide/show duplicate titles
6. **Smart Detection** - Checks both post slugs AND titles across all post types

### Files Modified:

- `includes/Admin/GeographicTitleGeneratorPage.php` - Backend duplicate checking
- `assets/js/src/geo-titles.js` - Frontend display and filtering
- Test files created for easy testing

---

## Testing Steps

### Step 1: Create Test Duplicate Posts

1. Open your browser and navigate to:
   ```
   http://contentgeneratorwpplugin.local/wp-content/plugins/content-generator-disabled/test-create-duplicate-posts.php
   ```

2. You should see a page that creates 5 test posts:
   - ✅ Diamond Rings In Carlsbad
   - ✅ Diamond Rings Near La Jolla
   - ✅ Wedding Bands In San Diego
   - ✅ Engagement Rings Within Encinitas
   - ✅ Gold Rings In Del Mar

3. These posts are created as **drafts** so they're easy to find and delete later.

4. **Expected Output:**
   ```
   ✅ Created: 5 test posts
   ⏭️ Skipped: 0 (already exist)
   ```

---

### Step 2: Test the Geographic Title Generator

1. Go to: **WordPress Admin → Content Generator → Geographic Title Generator**

2. **Upload the test CSV:**
   - Click "Choose File" in Step 1
   - Navigate to: `wp-content/plugins/content-generator-disabled/test-keywords-for-duplicates.csv`
   - Click "Upload Keywords"
   - Should see: "✓ Successfully uploaded 5 keywords"

3. **Generate titles:**
   - Click "Generate Title Variations" button
   - Wait for generation to complete (~1-2 seconds)

---

### Step 3: Expected Results ✅

If everything is working correctly, you should see:

#### A. Success Message with Warning

The status message should show:
```
✓ Generated 1,053 titles in XXms (AJAX: XXms)
⚠️ Warning: 5 titles already exist in your database
```

- Note the warning in **red text**
- The status notice should be **yellow** (warning color) instead of green

#### B. Duplicate Rows Highlighted

Look for titles like "Diamond Rings In Carlsbad" - they should have:

- **Yellow background** color (#fff3cd)
- **Red left border** (3px solid)
- **Red badge** next to title: "⚠️ EXISTS"
- **Tooltip** on hover: "This title already exists in your database"

**Example of what a duplicate row looks like:**
```
| # | Title                           | Slug                          | ...
|---|---------------------------------|-------------------------------|----
| 5 | Diamond Rings In Carlsbad ⚠️ EXISTS | diamond-rings-in-carlsbad | ...
     ↑ Yellow background with red left border
```

#### C. Filter Checkbox Appears

Below the search box, you should see:
```
☑ Hide duplicate titles (5)
```

- Checkbox should be **unchecked** by default
- The number in parentheses should match the duplicate count

#### D. Test the Filter

1. **Check the box** - The 5 duplicate rows should disappear from the table
2. **Uncheck the box** - The 5 duplicate rows should reappear
3. Pagination should update to reflect filtered count

---

### Step 4: Browser Console Check 🔍

Open Developer Tools (F12) → Console tab

**Check for:**

1. **No JavaScript errors** ❌
2. **Performance metrics logged:**
   ```
   === GEOGRAPHIC TITLES PERFORMANCE METRICS ===
   AJAX Round-trip Time: XXms
   Server Metrics: {check_duplicates: XX, ...}
   ```
3. **Duplicate information logged:**
   ```
   duplicateCount: 5
   duplicateSlugs: Array(5) ["diamond-rings-in-carlsbad", ...]
   ```

---

### Step 5: Network Tab Verification

Open Developer Tools (F12) → Network tab

1. Click "Generate Title Variations"
2. Find the AJAX request: `admin-ajax.php` with action `seo_generate_geo_titles`
3. Click on it → Preview tab
4. Check the response JSON:

```json
{
  "success": true,
  "data": {
    "titles": [...],
    "duplicateCount": 5,
    "duplicateSlugs": [
      "diamond-rings-in-carlsbad",
      "diamond-rings-near-la-jolla",
      "wedding-bands-in-san-diego",
      "engagement-rings-within-encinitas",
      "gold-rings-in-del-mar"
    ],
    "metrics": {
      "check_duplicates": 12.5,
      ...
    }
  }
}
```

---

### Step 6: Test Export/Send to Import

#### Test CSV Export:

1. Click **"Export as CSV"** button
2. Open the downloaded file in Excel/text editor
3. **Expected:** All titles including duplicates are in the export
4. The duplicates are included (we warn about them but still export)

#### Test Send to Import:

1. Click **"Send to Import Page"** button
2. Should redirect to Import page
3. Success banner should appear
4. All titles (including duplicates) should be ready to import
5. **Note:** Duplicates are flagged but not filtered during import - user decides

---

### Step 7: Cleanup After Testing 🧹

#### Option A: Use WordPress Admin

1. Go to: **SEO Pages → All SEO Pages**
2. Click the **"Draft"** filter at the top
3. Select all 5 test posts (checkboxes)
4. Choose **"Move to Trash"** from Bulk Actions dropdown
5. Click **Apply**

#### Option B: Use Direct Link

The test script provides a direct link to draft SEO pages:
```
http://yoursite.local/wp-admin/edit.php?post_type=seo-page&post_status=draft
```

#### Verify Cleanup:

Re-run the Geographic Title Generator with the same test CSV. You should see:
- **No warning message** (0 duplicates)
- **No highlighted rows**
- **No filter checkbox**

---

## Testing Checklist

| Feature | Expected Behavior | Status | Notes |
|---------|------------------|--------|-------|
| **Test posts created** | 5 draft posts created successfully | ⏳ | Check Drafts |
| **Duplicate detection runs** | Console shows `check_duplicates` metric | ⏳ | F12 Console |
| **Warning message** | "⚠️ Warning: 5 titles already exist" | ⏳ | In status area |
| **Status color** | Status notice is yellow/warning color | ⏳ | Visual check |
| **Yellow background** | Duplicate rows have #fff3cd background | ⏳ | Visual check |
| **Red left border** | 3px solid red border on left of row | ⏳ | Visual check |
| **EXISTS badge** | "⚠️ EXISTS" appears next to title | ⏳ | Visual check |
| **Tooltip works** | Hover shows "already exists" message | ⏳ | Hover test |
| **Checkbox appears** | "Hide duplicate titles (5)" visible | ⏳ | Below search |
| **Checkbox hidden when no dupes** | Checkbox hidden when 0 duplicates | ⏳ | After cleanup |
| **Filter works** | Checking box hides duplicate rows | ⏳ | Toggle test |
| **Filter resets page** | Goes to page 1 when filtering | ⏳ | Pagination |
| **Export includes dupes** | CSV export contains duplicates | ⏳ | Download test |
| **Send to Import works** | Redirects with all titles | ⏳ | Import page |
| **Performance acceptable** | `check_duplicates` < 100ms | ⏳ | Console metrics |

---

## Troubleshooting

### Issue: No warning appears

**Possible causes:**
- Test posts weren't created successfully
- JavaScript not rebuilt after code changes
- Browser cache issues

**Solutions:**
1. Verify test posts exist: Go to SEO Pages → Drafts
2. Rebuild JavaScript: `npm run build`
3. Hard refresh browser: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
4. Check browser console for errors

---

### Issue: Duplicates not highlighted

**Possible causes:**
- CSS not loading
- `is_duplicate` flag not set in response
- JavaScript error preventing rendering

**Solutions:**
1. Check Network tab → Verify response contains `is_duplicate: true`
2. Check Console tab → Look for JavaScript errors
3. Inspect element → Verify styles are applied
4. Clear browser cache completely

---

### Issue: Checkbox doesn't appear

**Possible causes:**
- `duplicateCount` is 0 or undefined
- CSS display property not being set
- jQuery selector not finding element

**Solutions:**
1. Check Network tab → Response → Verify `duplicateCount: 5`
2. Console: Type `$('#hide-duplicates-container')` → Should return element
3. Console: Type `duplicateCount` → Should show 5
4. Inspect element → Check inline styles

---

### Issue: Checkbox doesn't filter rows

**Possible causes:**
- Event handler not bound
- `hideDuplicates` variable not updating
- Filter logic not running

**Solutions:**
1. Console: Type `hideDuplicates` → Should be true when checked
2. Check console for errors when clicking checkbox
3. Add `console.log()` in checkbox change handler to debug
4. Verify `item.is_duplicate` is set in titles array

---

### Issue: Performance is slow

**Possible causes:**
- Large number of existing posts
- Database not optimized
- SQL query inefficient

**Solutions:**
1. Check `check_duplicates` metric in console → Should be < 100ms
2. Check total posts in database: **Posts → All Posts** (see count)
3. If > 10,000 posts, consider adding database index
4. Only runs on first page load, so not a major concern

---

### Issue: Wrong posts detected as duplicates

**Possible causes:**
- Slug normalization differences
- Case sensitivity issues
- Different post types not being checked

**Solutions:**
1. Check the SQL query in `checkDuplicateTitles()` method
2. Verify post types being checked: `'seo-page', 'post', 'page'`
3. Check post status being checked: `'publish', 'draft', 'pending', 'future'`
4. Add debug logging to see what WordPress is returning

---

## Technical Details

### Backend Implementation

**File:** `includes/Admin/GeographicTitleGeneratorPage.php`

**Method:** `checkDuplicateTitles()` (lines 1040-1088)
- Uses `$wpdb->prepare()` for safe SQL queries
- Checks both `post_name` (slug) and `post_title`
- Searches across multiple post types and statuses
- Returns array of duplicate slugs
- Efficient batch query using SQL `IN` clause

**Integration:** `handleTitleGeneration()` (lines 648-661)
- Only runs on first page load for performance
- Marks each title with `is_duplicate` flag
- Returns `duplicateCount` and `duplicateSlugs` to frontend

---

### Frontend Implementation

**File:** `assets/js/src/geo-titles.js`

**Variables Added:**
```javascript
let duplicateCount = 0;
let duplicateSlugs = [];
let hideDuplicates = false;
```

**Features:**
1. **Store duplicate data** (lines 315-327)
2. **Show warning** (lines 340-350)
3. **Highlight rows** (lines 452-474)
4. **Filter checkbox** (lines 205-210)
5. **Hide duplicates** (lines 453-455)

---

### Database Query

The duplicate detection uses this SQL query:

```sql
SELECT post_name, post_title
FROM wp_posts
WHERE post_status IN ('publish', 'draft', 'pending', 'future')
AND (post_name IN (...) OR post_title IN (...))
AND post_type IN ('seo-page', 'post', 'page')
```

**Performance:**
- Single batch query (not per-title)
- Uses indexed columns (`post_name`, `post_status`, `post_type`)
- Typically runs in 5-50ms depending on database size

---

## Test Files

### 1. test-create-duplicate-posts.php

**Location:** `/wp-content/plugins/content-generator-disabled/test-create-duplicate-posts.php`

**Purpose:** Creates 5 test posts that match geographic title patterns

**Usage:** Run once via browser, then test duplicate detection

**Cleanup:** Delete posts from SEO Pages → Drafts

---

### 2. test-keywords-for-duplicates.csv

**Location:** `/wp-content/plugins/content-generator-disabled/test-keywords-for-duplicates.csv`

**Contents:**
```csv
keyword
Diamond Rings
Wedding Bands
Engagement Rings
Gold Rings
Silver Rings
```

**Usage:** Upload this CSV in Geographic Title Generator

**Expected:** Generates ~1,053 titles with 5 duplicates detected

---

## Next Steps After Testing

Once testing is complete and verified:

1. **Delete test files** (optional):
   - `test-create-duplicate-posts.php`
   - `test-keywords-for-duplicates.csv`

2. **Delete this guide** (optional):
   - `DUPLICATE-DETECTION-TESTING-GUIDE.md`

3. **Production use:**
   - Feature is ready for real-world use
   - No additional configuration needed
   - Works automatically on all geographic title generation

4. **Share with team:**
   - Show boss the duplicate warnings
   - Explain how it prevents duplicate content
   - Demonstrate the filter checkbox

---

## Feature Benefits

### For Users:
✅ **Prevent duplicates** - See which titles already exist
✅ **Save time** - Don't manually check every title
✅ **Visual clarity** - Instantly spot duplicates with highlighting
✅ **Control** - Choose to hide or show duplicates
✅ **Confidence** - Know before you import

### For SEO:
✅ **Avoid duplicate content** - Prevent SEO penalties
✅ **Better organization** - Keep content unique
✅ **Cleaner database** - No unnecessary duplicate posts

### For Workflow:
✅ **Faster decisions** - Quick visual feedback
✅ **Smart filtering** - Focus on new content only
✅ **Production ready** - Works with existing import flow

---

## Questions or Issues?

If you encounter any problems during testing or have questions about the implementation, check:

1. **Browser Console** - For JavaScript errors
2. **Network Tab** - For AJAX response data
3. **WordPress Debug Log** - For PHP errors
4. **This guide** - Troubleshooting section above

---

**Last Updated:** January 2025
**Version:** 1.0
**Status:** Ready for Testing ✅
