# Epic 6: CSV Import & Bulk Processing

**Timeline:** Week 9 (6-8 hours)
**Status:** Not Started
**Priority:** Medium
**Dependencies:** Epic 1 (Foundation), Epic 2 (Core Generation)

## Epic Goal

Enable bulk page creation from CSV files exported from SEMrush or similar SEO tools, with column mapping, draft creation, optional auto-generation via background queue, and progress tracking.

## Success Criteria

- CSV upload and parsing functional
- Column mapping interface allows flexible field assignment
- Preview shows first 3 rows before import
- Pages created as drafts with mapped data
- Optional auto-generation queues background jobs
- Import handles 100+ rows without timeout
- Progress feedback during import
- Error handling for malformed CSV data

---

## Story 6.1: Create CSV Import Admin Page

**As a** site administrator
**I want** an admin page to import keywords from CSV
**So that** I can create multiple pages at once from SEO research

### Acceptance Criteria

1. Admin page created: "Content Generator > Import Keywords"
2. Page layout includes:
   - File upload field (accepts .csv files only)
   - "Upload & Map" button
   - Instructions/help text explaining CSV format
3. After upload, page displays:
   - Column mapping interface (see Story 6.2)
   - Preview of first 3 rows
   - Import options
   - "Import" button
4. Progress indicator shown during import
5. Completion summary after import:
   - Total rows processed
   - Pages created successfully
   - Any errors encountered

### Technical Requirements

- Admin page: `includes/Admin/ImportPage.php`
- Template: `templates/admin/import.php`
- File validation: check extension is `.csv`
- Max file size check against server limits
- Store uploaded file temporarily in WordPress uploads directory
- Source: PRD "CSV Import Feature" section

---

## Story 6.2: Implement Column Mapping Interface

**As a** site administrator
**I want** to map CSV columns to page fields
**So that** data imports correctly regardless of CSV format

### Acceptance Criteria

1. Interface shows CSV column headers
2. For each CSV column, dropdown to select mapping:
   - Page Title (required)
   - Focus Keyword
   - Topic Category
   - Image URL (optional)
   - Skip (don't import this column)
3. System detects likely mappings automatically:
   - Column named "keyword" maps to Page Title
   - Column named "intent" or "category" maps to Topic Category
   - Column named "search_volume" can be skipped
4. At least one column must map to "Page Title" (validation)
5. Preview table shows 3 sample rows with mapped data
6. Mapping saved temporarily (session or transient) for this import

### Technical Requirements

- Parse CSV headers: `fgetcsv()` first row
- Auto-detection: case-insensitive match on common column names
- Store mapping: `set_transient('import_mapping_' . $user_id, $mapping, HOUR_IN_SECONDS)`
- Validation: ensure Page Title mapped before allowing import
- Display preview: format mapped data in table
- Source: PRD "Import Interface" section

---

## Story 6.3: Build CSV Parser and Validator

**As a** plugin developer
**I want** robust CSV parsing with error handling
**So that** malformed data doesn't break the import

### Acceptance Criteria

1. CSV parser class created: `CSVParser` with method `parse($file_path)`
2. Parser handles:
   - Various encodings (UTF-8, ISO-8859-1)
   - Different delimiters (comma, semicolon, tab)
   - Quoted fields with embedded commas
   - Empty rows (skipped)
   - BOM (byte order mark) removal
3. Validation checks:
   - File exists and is readable
   - File has at least 2 rows (header + data)
   - Required mapped columns have values
   - Row count doesn't exceed limit (max 1000 rows per import)
4. Parser returns structured array:
   ```php
   [
     'headers' => ['keyword', 'intent', 'search_volume'],
     'rows' => [
       ['platinum wedding bands', 'commercial', '1000'],
       ...
     ],
     'errors' => ['Row 15: Missing keyword value']
   ]
   ```
5. Errors logged but import continues for valid rows

### Technical Requirements

- File: `includes/Services/CSVParser.php`
- Use `fgetcsv()` with auto-delimiter detection
- Encoding conversion: `mb_convert_encoding()` if needed
- BOM removal: check first bytes for UTF-8 BOM (`\xEF\xBB\xBF`)
- Row limit: configurable, default 1000
- Source: PRD "Import Processing" section

---

## Story 6.4: Implement Batch Page Creation

**As a** site administrator
**I want** pages created from CSV rows
**So that** I can bulk-create content for generation

### Acceptance Criteria

1. Import service creates draft post for each CSV row:
   - Post type: `seo-page`
   - Post status: `draft`
   - Post title: from mapped "Page Title" column
   - Post name (slug): auto-generated from title
2. Mapped data saved:
   - Focus Keyword: saved to ACF field `seo_focus_keyword`
   - Topic Category: assign term from `seo-topic` taxonomy (create if doesn't exist)
   - Image URL: download image and attach (if provided)
3. Import processes rows in batches (10 at a time) to avoid memory issues
4. Progress updates via AJAX every batch
5. Import completes without timeout (uses chunked processing)
6. Duplicate detection: skip row if page with same title already exists (optional setting)

### Technical Requirements

- Service: `includes/Services/ImportService.php`
- Use `wp_insert_post()` for page creation
- Slug generation: `sanitize_title($title)`
- Taxonomy assignment: `wp_set_object_terms($post_id, $topic, 'seo-topic')`
- Intent to topic mapping: helper function maps common intents to topics
- Batch size: 10 posts per iteration
- Progress: use `update_option()` to track current row
- Source: PRD "Import Processing" workflow

---

## Story 6.5: Add Background Generation Queue

**As a** site administrator
**I want** imported pages auto-generated in the background
**So that** I don't have to manually trigger generation for each page

### Acceptance Criteria

1. Import options include radio buttons:
   - "Create drafts only" (manual generation later)
   - "Auto-generate content" (queue background jobs)
2. If "Auto-generate content" selected:
   - Each imported page queued for generation
   - Jobs scheduled 3 minutes apart to avoid rate limits
   - Generation happens via WordPress Cron
3. Cron job `seo_generate_queued_page` processes one page:
   - Calls `generateAllBlocks()` for the post
   - Updates post status to "pending review" after successful generation
   - Logs any errors
4. Admin can view queue status:
   - Pending jobs count
   - Currently processing job
   - Estimated completion time
5. Queue can be cleared/paused by admin

### Technical Requirements

- Cron hook: `seo_generate_queued_page`
- Schedule job: `wp_schedule_single_event(time() + ($index * 180), 'seo_generate_queued_page', [$post_id])`
- Action callback: calls `GenerationService::generateAllBlocks()`
- Queue status: stored in transient or custom option
- Cron relies on WordPress Cron (not real cron) - document limitations
- Source: PRD "Import Processing" background processing

---

## Story 6.6: Handle Image Downloads from URLs

**As a** plugin developer
**I want** to download images from URLs in CSV
**So that** pages have hero images assigned automatically

### Acceptance Criteria

1. If CSV has "Image URL" column mapped and row has URL:
   - Download image from URL using `media_sideload_image()`
   - Save to WordPress Media Library
   - Attach to post
   - Assign to `hero_image` ACF field
2. Image download errors handled gracefully:
   - Log error (invalid URL, 404, timeout)
   - Continue import (don't fail entire row)
   - Leave `hero_image` field empty if download fails
3. Downloaded images marked with meta `_seo_imported_image = 1`
4. Duplicate images detected (same URL) and reused
5. Timeout set to 30 seconds per image download

### Technical Requirements

- Function: `media_sideload_image($url, $post_id, $desc, 'id')`
- Require WordPress media functions: `require_once(ABSPATH . 'wp-admin/includes/media.php')`
- URL validation: `filter_var($url, FILTER_VALIDATE_URL)`
- Error handling: try/catch or check for `WP_Error`
- Duplicate check: query by meta `_source_url = $url`
- Source: PRD "Import Processing" image handling

---

## Story 6.7: Add Import History and Logging

**As a** site administrator
**I want** to see history of past imports
**So that** I can track what was imported and troubleshoot issues

### Acceptance Criteria

1. Import history table on Import page shows:
   - Import date/time
   - Filename
   - Total rows
   - Success count
   - Error count
   - User who imported
   - View details link
2. Details view shows:
   - Full import log
   - List of created post IDs and titles
   - List of errors encountered
   - Download error log as .txt file
3. Import data stored in custom table or post meta
4. History paginated (10 imports per page)
5. Old import records (90+ days) auto-deleted

### Technical Requirements

- Option 1: Store in `wp_options` as serialized array (simple, low volume)
- Option 2: Custom table `wp_seo_import_log` (scalable, high volume)
- Log structure: `{timestamp, filename, total, success, errors, user_id, error_log}`
- Cleanup: WP Cron daily job deletes old records
- Source: Not explicitly in PRD, but good practice for bulk operations

---

## Epic Dependencies

- Epic 1: Foundation Setup (post types, taxonomies, ACF fields)
- Epic 2: Core Generation (for auto-generation queue)

## Risks & Mitigations

**Risk:** Large CSV files cause memory exhaustion
**Mitigation:** Chunked processing, batch imports, memory limit checks

**Risk:** WordPress Cron unreliable for background generation
**Mitigation:** Document Cron limitations, recommend server cron for production

**Risk:** Image downloads slow or fail
**Mitigation:** Timeouts, error handling, continue on failure

**Risk:** Malformed CSV data breaks import
**Mitigation:** Robust parsing, validation, skip invalid rows with logging
