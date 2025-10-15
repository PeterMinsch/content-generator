# Epic 1: Foundation Setup

**Timeline:** Weeks 1-2 (20-24 hours)
**Status:** Not Started
**Priority:** Critical

## Epic Goal

Establish the core WordPress plugin foundation including custom post types, taxonomies, ACF field groups, and basic admin infrastructure to support SEO content generation.

## Success Criteria

- Custom post type `seo-page` registered and functional
- Taxonomies `seo-topic` and `image_tag` created with default terms
- All 12 ACF field groups configured and saving data correctly
- Basic admin menu structure in place
- Settings page skeleton accessible and functional

---

## Story 1.1: Register Custom Post Type and Taxonomies

**As a** plugin developer
**I want** to register the `seo-page` custom post type and required taxonomies
**So that** users can create and categorize SEO content pages

### Acceptance Criteria

1. Custom post type `seo-page` is registered with correct configuration:
   - Supports: title, editor, thumbnail, revisions
   - Shows in REST API
   - Has correct labels and menu icon
   - Menu position 30
2. Taxonomy `seo-topic` is registered and assigned to `seo-page`:
   - Hierarchical: false
   - Shows in REST API
   - Default terms created: Engagement Rings, Wedding Bands, Men's Wedding Bands, Women's Wedding Bands, Education, Comparisons
3. Taxonomy `image_tag` is registered and assigned to attachments:
   - Hierarchical: false
   - Default tags created for: metals, types, gender, styles, finishes (as per PRD)
4. All registrations happen on `init` hook
5. Code follows WordPress coding standards

### Technical Requirements

- Use `register_post_type()` function
- Use `register_taxonomy()` function
- Create default terms on plugin activation
- File location: `includes/PostTypes/SEOPage.php` and `includes/Taxonomies/`
- Must be compatible with PHP 8.0+

---

## Story 1.2: Create ACF Field Groups for Content Blocks

**As a** content manager
**I want** ACF fields for all 12 content blocks
**So that** I can enter and store structured content data

### Acceptance Criteria

1. ACF field group "SEO Page Content Blocks" created with location rule: Post Type = `seo-page`
2. All 12 blocks configured with correct field types and settings:
   - Block 1: Hero Section (4 fields)
   - Block 2: SERP Answer (2 fields + repeater)
   - Block 3: Product Criteria (2 fields with repeater)
   - Block 4: Materials Explained (2 fields with repeater)
   - Block 5: Process (2 fields with repeater, max 4 steps)
   - Block 6: Comparison (5 fields with repeater)
   - Block 7: Product Showcase (3 fields with repeater)
   - Block 8: Size & Fit (3 fields)
   - Block 9: Care & Warranty (4 fields with repeater)
   - Block 10: Ethics & Origin (3 fields with repeater)
   - Block 11: FAQs (2 fields with repeater)
   - Block 12: CTA (6 fields)
3. SEO Meta Fields group created with 4 fields (focus_keyword, seo_title, seo_meta_description, seo_canonical)
4. Character limits enforced where specified in PRD
5. Field names follow snake_case convention matching PRD spec
6. ACF JSON sync enabled and files saved to `acf-json/` directory

### Technical Requirements

- Use ACF PHP API or ACF JSON export
- Field definitions must match PRD specification exactly
- Store in `acf-json/` for version control
- Validate max character counts on specified fields
- All fields use appropriate ACF field types (text, textarea, repeater, image, URL)

---

## Story 1.3: Set Up Admin Menu Structure

**As a** content manager
**I want** a dedicated admin menu for the Content Generator
**So that** I can easily access all plugin features

### Acceptance Criteria

1. Top-level admin menu "Content Generator" created:
   - Icon: dashicons-edit-large
   - Position: 30
   - Capability required: `edit_posts`
2. Sub-menu items added:
   - New Page (default, redirects to Add New SEO Page)
   - All SEO Pages (shows post type list)
   - Image Library Manager (placeholder page)
   - Settings (placeholder page)
   - Analytics (placeholder page)
3. Menu structure is accessible and links work
4. Proper capability checks in place for each menu item
5. Menu integrates with WordPress admin UI seamlessly

### Technical Requirements

- Use `add_menu_page()` and `add_submenu_page()` functions
- Implement in `includes/Admin/AdminMenu.php`
- Hook into `admin_menu` action
- Follow WordPress menu registration patterns
- Ensure proper escaping and sanitization

---

## Story 1.4: Create Settings Page Skeleton

**As a** site administrator
**I want** a settings page for plugin configuration
**So that** I can configure API keys and plugin behavior

### Acceptance Criteria

1. Settings page accessible from "Content Generator > Settings" menu
2. Tabbed interface created with 5 tabs (placeholders for now):
   - API Configuration
   - Default Content
   - Prompt Templates
   - Image Library
   - Limits & Tracking
3. Settings page uses WordPress Settings API
4. Basic form structure in place (no functional settings yet)
5. Settings page follows WordPress admin design patterns
6. Save button present with nonce verification

### Technical Requirements

- Use WordPress Settings API (`register_setting()`, `add_settings_section()`, `add_settings_field()`)
- Implement in `includes/Admin/SettingsPage.php`
- Template file: `templates/admin/settings.php`
- Use WordPress admin UI components (`.nav-tab`, `.form-table`)
- Implement proper nonce verification for form submission
- Store settings in `wp_options` table with prefix `seo_generator_`

---

## Story 1.5: Initialize Plugin Core Architecture

**As a** plugin developer
**I want** a properly structured plugin initialization system
**So that** the plugin follows WordPress best practices and is maintainable

### Acceptance Criteria

1. Main plugin file (`content-generator.php`) created with:
   - Plugin header comments
   - License information
   - Version constant
   - Activation/deactivation hooks
2. Plugin class created at `includes/Plugin.php` with:
   - Singleton pattern implementation
   - Dependency injection container setup
   - Hook registration system
   - Component initialization
3. Activation hook creates:
   - Default taxonomy terms
   - Custom database table (`wp_seo_generation_log`)
   - Default plugin options
4. Deactivation hook performs cleanup (does not delete data)
5. Autoloader implemented for PSR-4 class loading
6. Plugin structure follows WordPress plugin development handbook

### Technical Requirements

- Namespace: `SEOGenerator\`
- Use Composer for autoloading
- Implement singleton pattern for Plugin class
- Create database table on activation using `dbDelta()`
- Store plugin version in options table
- File: `content-generator.php` (main plugin file)
- File: `includes/Plugin.php` (main plugin class)
- File: `includes/Container.php` (DI container)

---

## Epic Dependencies

None (this is the foundation epic)

## Risks & Mitigations

**Risk:** ACF Free plugin not installed/activated
**Mitigation:** Add admin notice on activation if ACF not detected, provide installation instructions

**Risk:** PHP version incompatibility
**Mitigation:** Check PHP version on activation, show error if < 8.0

**Risk:** Database table creation fails
**Mitigation:** Log errors, provide manual SQL in documentation
