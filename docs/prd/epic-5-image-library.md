# Epic 5: Image Library System

**Timeline:** Week 8 (8-12 hours)
**Status:** Not Started
**Priority:** Medium
**Dependencies:** Epic 1 (Foundation - image_tag taxonomy)

## Epic Goal

Build an image library management system with bulk upload, tag-based organization, auto-assignment algorithm for matching images to content based on keywords, and admin interface for managing the library.

## Success Criteria

- Bulk upload interface allows uploading multiple images at once
- Images can be tagged with multiple taxonomy terms
- Tag-based matching algorithm successfully finds relevant images
- Auto-assignment works during content generation
- Admin can manage image tags via grid interface
- Default image fallback when no matches found

---

## Story 5.1: Create Image Library Manager Admin Page

**As a** site administrator
**I want** an admin page to manage the image library
**So that** I can organize and tag images for content generation

### Acceptance Criteria

1. Admin page created: "Content Generator > Image Library Manager"
2. Page displays grid view of all library images:
   - Thumbnail preview
   - Filename
   - Current tags (as removable badges)
   - "Edit Tags" button
   - Checkbox for bulk selection
3. Page includes:
   - Upload area (drag-drop or click to select files)
   - Bulk tag editor (select images, apply tags to all)
   - Filter by tag dropdown
   - Search by filename
4. Only images marked with meta `_seo_library_image = 1` shown
5. Pagination for large libraries (20 images per page)
6. Responsive grid (4 columns desktop, 2 mobile)

### Technical Requirements

- Admin page: `includes/Admin/ImageLibraryPage.php`
- Template: `templates/admin/image-library.php`
- Use WordPress media queries to filter images
- Meta query: `meta_key = '_seo_library_image', meta_value = '1'`
- Grid CSS: CSS Grid or Flexbox
- AJAX for tag updates (no page reload)
- Source: PRD "Image Library System" section

---

## Story 5.2: Implement Bulk Image Upload

**As a** site administrator
**I want** to upload multiple images at once
**So that** I can quickly populate the image library

### Acceptance Criteria

1. Upload interface supports:
   - Drag-and-drop files onto designated area
   - Click to open file picker
   - Multiple file selection
   - File type validation (jpg, jpeg, png, webp only)
2. Upload progress indicator:
   - Shows each file uploading
   - Progress bar per file
   - Success/error status per file
   - Total files uploaded count
3. Uploaded images automatically:
   - Added to WordPress Media Library
   - Marked with meta `_seo_library_image = 1`
   - Appear in library grid immediately
4. Large uploads handled gracefully (chunked upload for files >5MB)
5. Error handling for:
   - Invalid file types
   - File too large
   - Upload failures

### Technical Requirements

- Use `wp_handle_upload()` or Media Library REST API
- AJAX endpoint: `POST /wp-json/seo-generator/v1/images`
- Set meta on upload: `update_post_meta($attachment_id, '_seo_library_image', '1')`
- File validation: `wp_check_filetype()`
- Max file size: check `upload_max_filesize` and `post_max_size`
- Use HTML5 File API for drag-drop
- Source: PRD "Bulk Upload Interface" section

---

## Story 5.3: Build Tag Management System

**As a** site administrator
**I want** to add and manage tags on library images
**So that** images can be matched to content automatically

### Acceptance Criteria

1. Each image in grid shows:
   - Current tags as colored badges/chips
   - "X" button on each tag to remove
   - "+ Add Tag" button or input field
2. Adding tags:
   - Autocomplete suggests existing tags from `image_tag` taxonomy
   - Can create new tags on-the-fly
   - Tags save immediately (AJAX)
3. Bulk tag operations:
   - Select multiple images via checkboxes
   - "Bulk Actions" dropdown appears
   - Option: "Add Tags" - opens modal with tag input
   - Option: "Remove Tags" - opens modal with tag selection
   - Changes apply to all selected images
4. Tag suggestions based on filename:
   - Parse filename for keywords (e.g., "platinum-mens-ring.jpg" suggests "platinum", "mens")
   - Show suggestions when editing tags
5. Tag color coding by category (metals, types, gender, etc.)

### Technical Requirements

- Use `wp_set_object_terms()` to assign tags
- AJAX endpoint: `PUT /wp-json/seo-generator/v1/images/{id}/tags`
- Autocomplete: load all terms from `image_tag` taxonomy
- Tag suggestion regex: split filename by `-`, `_`, and match against known terms
- UI: use `@wordpress/components` FormTokenField or custom tag input
- Source: PRD "Tagging System" section

---

## Story 5.4: Implement Auto-Assignment Algorithm

**As a** plugin developer
**I want** an algorithm to automatically select relevant images
**So that** images are assigned to content blocks during generation

### Acceptance Criteria

1. Service class created: `ImageMatchingService` with method `findMatchingImage($context)`
2. Algorithm logic:
   - Extract keywords from context: focus_keyword, topic, category
   - Convert keywords to tag slugs
   - **First attempt:** Query images with ALL tags (AND operator)
   - **Second attempt (fallback):** Query images with first 2 tags
   - **Third attempt (fallback):** Query images with first 1 tag
   - **Final fallback:** Return default image ID (if configured) or null
3. If multiple images match, select random image from matches
4. Algorithm prioritizes images with more matching tags
5. Image selection logged to debug.log for troubleshooting
6. Function returns image ID or null

### Technical Requirements

- File: `includes/Services/ImageMatchingService.php`
- Method signature: `findMatchingImage(array $context): ?int`
- Use `WP_Query` with tax_query for tag matching
- Tax query operator: `AND` for strict matching, fallback to fewer tags
- Random selection: `array_rand($matches)`
- Log format: `[Image Matching] Context: {keywords} | Found: {count} images | Selected: {id}`
- Source: PRD "Auto-Assignment Algorithm" section

---

## Story 5.5: Integrate Auto-Assignment with Generation

**As a** content manager
**I want** images automatically assigned during content generation
**So that** I don't have to manually select images

### Acceptance Criteria

1. Auto-assignment setting in Settings > Image Library tab:
   - Checkbox: "Auto-assign images during generation" (default: enabled)
   - Dropdown: "Matching strategy" - Strict (3 tags) or Flexible (1-3 tags)
   - Image picker: "Default image" (used when no matches found)
2. During generation, if auto-assign enabled:
   - After generating hero block, run image matching algorithm
   - Assign selected image to `hero_image` field
   - If process block generated, assign images to step_image fields
3. Auto-assigned images can be changed manually after generation
4. Generation log includes note if image was auto-assigned
5. If no match and no default image, field left empty (no error)

### Technical Requirements

- Check setting before running auto-assignment
- Integrate in `ContentGenerationService::generateBlock()`
- For hero block: assign to `hero_image` ACF field
- For process block: assign to each `step_image` in repeater
- Update field: `update_field('hero_image', $image_id, $post_id)`
- Log: "Auto-assigned image {id} to hero_image"
- Source: PRD "Auto-Assignment Algorithm" integration notes

---

## Story 5.6: Create Default Image Settings

**As a** site administrator
**I want** to configure a default fallback image
**So that** content always has an image even if matching fails

### Acceptance Criteria

1. Settings page "Image Library" tab includes:
   - Image picker: "Default Hero Image"
   - Help text: "This image will be used when no matching images are found"
2. Default image can be:
   - Selected from WordPress Media Library
   - Cleared (set to none)
3. Default image used by matching algorithm when:
   - No images match tags
   - Matching is disabled
4. Default image preview shown in settings
5. Setting saves to options table

### Technical Requirements

- Setting option: `seo_generator_image_settings`
- Field: `default_image_id` (int or null)
- Use `wp.media` for image picker in admin
- Retrieve in matching algorithm: `get_option('seo_generator_image_settings')['default_image_id']`
- Source: PRD "Settings Page" tab 4

---

## Epic Dependencies

- Epic 1: Foundation Setup (`image_tag` taxonomy must exist)

## Risks & Mitigations

**Risk:** Poor image matching due to inconsistent tagging
**Mitigation:** Filename-based tag suggestions, bulk tagging tools, tag guidelines documentation

**Risk:** Large image uploads timeout on slow hosting
**Mitigation:** Chunked upload for large files, increase PHP limits in documentation

**Risk:** Image library grows very large, slowing admin page
**Mitigation:** Pagination, lazy loading thumbnails, database query optimization
