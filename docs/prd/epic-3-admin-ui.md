# Epic 3: Admin User Interface

**Timeline:** Weeks 5-6 (16-20 hours)
**Status:** Not Started
**Priority:** High
**Dependencies:** Epic 1 (Foundation), Epic 2 (Core Generation)

## Epic Goal

Build a modern, responsive React-based admin interface for content creation and management, featuring real-time generation status, progress tracking, form validation, and an intuitive user experience.

## Success Criteria

- React admin UI loads on "New Page" screen
- All 12 content blocks displayed as collapsible sections
- Status indicators show generation state for each block
- Progress modal displays during bulk generation with live updates
- Character counters enforce field limits
- Form validation provides immediate feedback
- Interface works on screens 1024px and above
- Zero JavaScript errors in browser console

---

## Story 3.1: Set Up React Build Environment

**As a** plugin developer
**I want** a React development environment with hot reload
**So that** I can efficiently build the admin UI

### Acceptance Criteria

1. Webpack configured using `@wordpress/scripts`
2. Build process compiles React JSX to browser-compatible JavaScript
3. Development server supports hot module replacement
4. Production build minifies and optimizes assets
5. WordPress dependencies properly externalized (React, wp-api-fetch, wp-components)
6. Source maps generated for debugging
7. Build outputs to `assets/js/build/` directory
8. npm scripts configured:
   - `npm run start` - development mode with watch
   - `npm run build` - production build
   - `npm run lint:js` - ESLint checking

### Technical Requirements

- Package: `@wordpress/scripts` (handles Webpack config)
- Entry point: `assets/js/src/index.js`
- Output: `assets/js/build/index.js` and `index.asset.php`
- Use WordPress dependency extraction plugin
- ESLint config: `@wordpress/eslint-plugin`
- Source: Architecture "Tech Stack" and "Development Workflow" sections

---

## Story 3.2: Create Page Editor React Component Structure

**As a** content manager
**I want** a React interface for editing SEO pages
**So that** I have a modern, responsive editing experience

### Acceptance Criteria

1. Main `PageEditor` component created and renders on "New SEO Page" admin screen
2. Component structure organized:
   ```
   components/
   ├── PageEditor/
   │   ├── index.js
   │   ├── BasicInfo.js
   │   ├── BlockList.js
   │   ├── BlockItem.js
   │   └── GenerationControls.js
   ```
3. Basic Info section displays:
   - Page Title (required field)
   - URL Slug (auto-generated from title, editable)
   - Topic (dropdown populated from `seo-topic` taxonomy)
   - Focus Keyword (text input)
4. Component correctly enqueued on edit screen:
   - Script enqueued with dependencies
   - React root div exists in DOM
   - Script localized with nonce and API URLs
5. Initial page data loaded from WordPress post

### Technical Requirements

- Enqueue script in `includes/Admin/PageEditor.php`
- Use `wp_enqueue_script()` with dependencies from `.asset.php`
- Localize script with `wp_localize_script()` providing: nonce, ajaxUrl, restUrl, postId
- React root div ID: `seo-generator-page-editor`
- Use functional components with hooks
- Source: Architecture "Frontend Components" section

---

## Story 3.3: Implement Content Block UI Components

**As a** content manager
**I want** collapsible sections for each of the 12 content blocks
**So that** I can focus on one block at a time

### Acceptance Criteria

1. 12 block components created (one per content block):
   - HeroBlock, SerpAnswerBlock, ProductCriteriaBlock, MaterialsBlock, ProcessBlock, ComparisonBlock, ProductShowcaseBlock, SizeFitBlock, CareWarrantyBlock, EthicsBlock, FAQsBlock, CTABlock
2. Each block displays:
   - Block header with name
   - Status indicator (color-coded circle/icon)
   - Generate button
   - Collapsible content area (collapsed by default except Hero)
3. Block content areas show appropriate form fields matching ACF structure:
   - Text inputs for text fields
   - Textareas for textarea fields
   - Repeaters for repeatable fields
   - Image pickers for image fields
4. Blocks can be expanded/collapsed via click on header
5. Form fields are disabled when block is in "generating" state
6. Character counters displayed on fields with limits

### Technical Requirements

- Component location: `assets/js/src/components/blocks/`
- Use `@wordpress/components` for UI elements (TextControl, TextareaControl, Button)
- Status indicators: CSS classes `.status-not-generated`, `.status-generating`, `.status-generated`, `.status-failed`, `.status-edited`
- Track expanded/collapsed state with React useState
- Source: PRD "New Page Interface Layout" and "Content Structure" sections

---

## Story 3.4: Add Status Indicators and Generation Buttons

**As a** content manager
**I want** visual feedback on generation status for each block
**So that** I know which blocks are ready and which need generation

### Acceptance Criteria

1. Status indicator system implemented with 5 states:
   - **Not Generated:** Gray circle (○), empty fields
   - **Generating:** Spinner animation (⏳), disabled fields
   - **Generated:** Green checkmark (✓), populated fields
   - **Failed:** Red X (✗), error message shown
   - **Edited:** Blue pencil (✏️), user modified content
2. Each block header shows current status
3. "Generate" button behavior:
   - Disabled during generation
   - Shows "Generating..." text when active
   - Returns to "Generate" when complete
   - Shows "Regenerate" after initial generation
4. Clicking "Generate" triggers API call to single block endpoint
5. Status updates in real-time as generation progresses
6. Error state displays error message below block header

### Technical Requirements

- Status state managed in React Context or component state
- Use `apiFetch` from `@wordpress/api-fetch` for API calls
- Loading state prevents multiple simultaneous generations
- Icons: use Dashicons or custom SVG
- Update ACF field values after successful generation
- Source: PRD "Admin Interface" and "Block States" sections

---

## Story 3.5: Build Bulk Generation Progress Modal

**As a** content manager
**I want** a progress modal during "Generate All Blocks" operation
**So that** I can see real-time progress and estimated completion time

### Acceptance Criteria

1. "Generate All Blocks" button displayed above block list
2. Clicking button opens modal overlay with:
   - Title: "Generating Content..."
   - Current progress: "Block 4 of 12"
   - Progress bar showing percentage complete
   - List of all blocks with status icons
   - Estimated time remaining
   - Cancel button
3. Modal updates in real-time as each block completes:
   - Progress bar advances
   - Block status icon changes
   - Time remaining recalculates
4. Completion summary shown when finished:
   - Total blocks generated
   - Total cost
   - Any failed blocks listed
   - "Close" button
5. Cancel button stops generation after current block
6. Modal cannot be dismissed by clicking overlay during generation

### Technical Requirements

- Component: `components/generation/BulkGenerationModal.js`
- Use `@wordpress/components` Modal component
- Sequential API calls to single block endpoint
- Calculate estimated time based on average time per block
- Prevent modal close with `isDismissible={false}` during generation
- Progress bar: CSS width percentage animation
- Source: PRD "Progress Modal" section

---

## Story 3.6: Implement Form Validation and Character Counters

**As a** content manager
**I want** real-time validation and character count feedback
**So that** I know my content meets requirements before saving

### Acceptance Criteria

1. Character counters displayed on all fields with limits:
   - Shows "X / Y characters" below field
   - Updates in real-time as user types
   - Turns red when limit exceeded
2. Required field validation:
   - Page title required before generation
   - Focus keyword required for generation
   - Visual indicator (red border) on empty required fields
3. URL validation on URL fields:
   - Validates format is valid URL
   - Shows error message if invalid
4. Save button disabled when:
   - Required fields empty
   - Character limits exceeded
   - Validation errors present
5. Validation messages clear and actionable
6. Validation runs on blur and before save

### Technical Requirements

- Use controlled components with onChange handlers
- Character count function: `field.value.length`
- URL validation: regex or built-in URL validation
- Error messages: display below field with `.error` class
- Disable save button: `disabled` prop based on validation state
- Source: PRD "Admin Interface" character counter requirements

---

## Story 3.7: Integrate with WordPress Media Library

**As a** content manager
**I want** to select images from the WordPress Media Library for image fields
**So that** I can assign images to blocks without leaving the page

### Acceptance Criteria

1. Image fields display:
   - "Select Image" button when no image selected
   - Image preview thumbnail when image selected
   - "Change Image" and "Remove Image" buttons when image selected
2. Clicking "Select Image" opens WordPress Media Library modal
3. User can select existing image or upload new image
4. Selected image saves to ACF field (image ID)
5. Image preview shows after selection
6. "Remove Image" clears the field
7. Integration works for all image fields: hero_image, step_image, size_chart_image

### Technical Requirements

- Use `wp.media` JavaScript API
- Store image ID in state, not URL
- Media frame options: `title`, `button text`, `multiple: false`
- Update ACF field value via REST API on selection
- Image preview: use WordPress image size (thumbnail or medium)
- Source: PRD "Admin Interface" and WordPress Media Library documentation

---

## Epic Dependencies

- Epic 1: Foundation Setup (ACF fields, admin menu)
- Epic 2: Core Generation (REST API endpoints for generation)

## Risks & Mitigations

**Risk:** React version conflicts with other plugins
**Mitigation:** Use WordPress-provided React (`@wordpress/element`), externalize dependencies

**Risk:** Large bundle size impacting load time
**Mitigation:** Code splitting, lazy loading, use WordPress components to reduce bundle

**Risk:** Browser compatibility issues
**Mitigation:** Transpile with Babel for browser support (defined in browserslist), test on target browsers

**Risk:** Accessibility issues
**Mitigation:** Use WordPress components (already accessible), add ARIA labels, keyboard navigation support
