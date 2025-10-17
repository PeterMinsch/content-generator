# Epic 10: Live Block Preview

**Timeline:** Week 13-14 (10-15 hours)
**Status:** Not Started
**Priority:** High
**Dependencies:** Epic 6 (CSV Import - block ordering interface)

## Epic Goal

Add a real-time visual preview to the CSV import block ordering interface that updates dynamically as users drag, drop, and remove content blocks. Users will see a split-pane layout with the existing block ordering controls on the left and a live preview of the page layout on the right, allowing them to visualize the final page before committing to content generation.

## Success Criteria

- Split-pane layout (50/50) with block ordering (left) and preview (right)
- Preview updates in real-time (<100ms) when blocks are reordered or removed
- Preview renders simplified HTML templates for all 13 block types with placeholder content
- Preview isolated in iframe to prevent CSS conflicts
- Reset button synchronizes both ordering panel and preview
- Zero console errors during drag-and-drop interactions
- Clean, professional preview styling (generic, not theme-specific for MVP)
- User feedback indicates increased confidence in block selection decisions

---

## Story 10.1: Implement Split-Pane Layout and Preview Infrastructure

**As a** content manager
**I want** a split-pane interface with my block ordering controls on the left and a preview pane on the right
**So that** I can see both the controls and the visual preview simultaneously

### Acceptance Criteria

1. **Split-Pane Layout:**
   - Left pane (50% width): Existing block ordering interface
   - Right pane (50% width): New preview container
   - Responsive layout that works on screens 1280px+ width
   - Vertical split using CSS Grid or Flexbox
   - Clean visual separation between panes

2. **Preview Container Structure:**
   - Container div with ID `block-preview-container`
   - Header section with title "Page Preview" and optional subtitle
   - Iframe element with ID `block-preview-iframe` for isolated rendering
   - Iframe sized to fit container with appropriate scrolling

3. **Iframe Isolation:**
   - Sandbox attribute for security
   - Initial HTML structure injected into iframe
   - Basic CSS framework injected for preview styling
   - No bleed of admin CSS into preview
   - No bleed of preview CSS into admin

4. **Accessibility:**
   - Preview pane has appropriate ARIA labels
   - Keyboard navigation doesn't break
   - Screen reader announces preview updates

5. **Responsive Behavior:**
   - Minimum width requirement (1280px) documented
   - Graceful degradation on smaller screens (preview stacks below)
   - Mobile/tablet compatibility not required for MVP

### Technical Requirements

**File Structure:**
- Modify: `assets/css/admin-import-settings.css` - Add split-pane layout styles
- Create: `assets/css/admin-block-preview.css` - Preview-specific styles
- Create: `assets/js/src/block-preview.js` - Preview manager class

**HTML Structure (to be injected into existing import settings page):**
```html
<div class="block-ordering-split-layout">
    <div class="block-ordering-pane">
        <!-- Existing block ordering interface -->
    </div>
    <div class="block-preview-pane">
        <div class="block-preview-header">
            <h3>Page Preview</h3>
            <p class="preview-disclaimer">This is a simplified preview. Actual styling may vary based on your theme.</p>
        </div>
        <div id="block-preview-container">
            <iframe id="block-preview-iframe" sandbox="allow-same-origin"></iframe>
        </div>
    </div>
</div>
```

**CSS Requirements:**
- Split-pane using CSS Grid: `grid-template-columns: 1fr 1fr;`
- Gap between panes: 20px
- Preview iframe: 100% width, min-height 600px, border, scrolling
- Preview container: clean card-style design matching WordPress admin UI

**JavaScript Class Structure:**
```javascript
class BlockPreviewManager {
    constructor(iframeElement) {
        this.iframe = iframeElement;
        this.iframeDoc = null;
        this.init();
    }

    init() {
        // Initialize iframe document
        // Inject base HTML structure
        // Inject base CSS
    }

    updatePreview(blockOrder) {
        // Render preview based on block order array
    }

    clearPreview() {
        // Clear preview content
    }
}
```

**Integration Point:**
- Modify admin template: `templates/admin/import-settings.php`
- Enqueue new CSS and JS files on import settings page only
- Initialize `BlockPreviewManager` after DOM ready

### Definition of Done

- [ ] Split-pane layout renders correctly on import settings page
- [ ] Preview pane and block ordering pane are 50/50 width
- [ ] Iframe initializes with basic HTML/CSS structure
- [ ] No CSS conflicts between admin and preview
- [ ] Preview container has clean, professional appearance
- [ ] Layout works on 1280px+ screens
- [ ] No console errors on page load
- [ ] Code documented with JSDoc and PHPDoc

---

## Story 10.2: Create Block HTML Templates for Preview

**As a** content manager
**I want** to see simplified visual representations of each block type
**So that** I understand what each block looks like and how they stack together

### Acceptance Criteria

1. **Template System:**
   - HTML template created for all 13 block types
   - Each template uses placeholder content (hardcoded sample text/images)
   - Templates styled with generic, clean CSS (not theme-specific)
   - Templates approximate the structure of actual blocks

2. **13 Block Templates Created:**
   - `hero_section` - Hero image with headline and subheadline
   - `serp_answer` - Featured snippet style box
   - `introduction` - Text paragraph with heading
   - `product_criteria` - Bulleted list with criteria
   - `product_display` - Product grid/cards layout
   - `faq_section` - Accordion-style Q&A
   - `buying_guide` - Numbered steps or sections
   - `comparison_table` - Simple comparison table
   - `customer_reviews` - Review cards with ratings
   - `cta_section` - Call-to-action button/box
   - `image_gallery` - Image grid
   - `video_section` - Video embed placeholder
   - `conclusion` - Closing paragraph

3. **Placeholder Content Standards:**
   - Sample text: Jewelry/SEO-related placeholder text
   - Sample images: Use placeholder image service (via.placeholder.com or similar)
   - Consistent tone and style across all blocks
   - Representative content length (not too short, not too long)

4. **Template Organization:**
   - Templates stored as JavaScript template literals
   - Function `getBlockTemplate(blockType)` returns HTML string
   - Templates easy to modify and maintain

5. **Styling Requirements:**
   - Clean, modern design
   - Consistent spacing and typography
   - Responsive within preview iframe
   - Colors: neutral palette (grays, blues, professional)
   - Fonts: system font stack (no external font loading)

### Technical Requirements

**File Structure:**
- Create: `assets/js/src/block-templates.js` - Template definitions

**Template Function:**
```javascript
function getBlockTemplate(blockType) {
    const templates = {
        'hero_section': `
            <div class="preview-block hero-block">
                <div class="hero-image" style="background-image: url('https://via.placeholder.com/1200x400')"></div>
                <h1>Discover the Perfect Engagement Ring</h1>
                <p class="hero-subtitle">Handcrafted with precision and elegance</p>
            </div>
        `,
        'serp_answer': `
            <div class="preview-block serp-block">
                <div class="serp-box">
                    <h3>What makes a quality engagement ring?</h3>
                    <p>A quality engagement ring combines expert craftsmanship, certified diamonds, and timeless design...</p>
                </div>
            </div>
        `,
        // ... other templates
    };

    return templates[blockType] || `<div class="preview-block unknown-block">Block: ${blockType}</div>`;
}
```

**CSS for Templates:**
- Create: `assets/js/src/block-preview-styles.css` (injected into iframe)
- Base styles for all `.preview-block` elements
- Specific styles for each block type class
- Responsive grid/flexbox layouts
- Consistent spacing using CSS variables

**Placeholder Content Strategy:**
- Hero: "Discover the Perfect Engagement Ring" + sample image
- SERP Answer: "What makes a quality engagement ring?" + answer text
- Introduction: 2-3 paragraphs about jewelry selection
- Product Criteria: Bulleted list of ring features
- FAQ: 3-4 sample jewelry questions with answers
- Reviews: 2-3 fake customer reviews with 5-star ratings
- CTA: "Browse Our Collection" button
- Etc.

### Definition of Done

- [ ] All 13 block templates created
- [ ] Each template has appropriate placeholder content
- [ ] Templates render correctly in preview iframe
- [ ] Styling is clean and professional
- [ ] Templates are responsive within iframe
- [ ] `getBlockTemplate()` function returns correct HTML
- [ ] CSS for all templates documented
- [ ] Templates visually distinct from each other

---

## Story 10.3: Integrate Real-Time Preview Updates with Block Ordering

**As a** content manager
**I want** the preview to update automatically when I reorder or remove blocks
**So that** I see immediate visual feedback on my layout changes

### Acceptance Criteria

1. **Event Integration:**
   - Preview updates on SortableJS `onEnd` event (after drag-drop)
   - Preview updates on block removal (click X button)
   - Preview updates on block addition (if blocks can be re-added)
   - Preview updates on reset button click

2. **Update Performance:**
   - Preview updates within 100ms of user action
   - No visible lag or flickering
   - Smooth rendering (no flash of empty content)
   - Debouncing if necessary for rapid changes

3. **Block Order Synchronization:**
   - Preview reflects exact order of blocks in ordering panel
   - Removed blocks disappear from preview immediately
   - Re-added blocks appear in preview immediately
   - Reset synchronizes both panels to default state

4. **Initial Load:**
   - Preview renders initial block order on page load
   - Default block order displayed if no customization yet
   - Preview ready state indicated (no loading spinner needed for MVP)

5. **Edge Cases:**
   - Preview handles zero blocks (shows empty state message)
   - Preview handles all 13 blocks enabled
   - Preview handles rapid reordering (debounced if needed)

### Technical Requirements

**Integration with Existing block-ordering.js:**
- Modify: `assets/js/src/block-ordering.js`
- Hook into existing SortableJS event handlers:
  ```javascript
  sortable.options.onEnd = function(evt) {
      // Existing code...

      // NEW: Update preview
      blockPreviewManager.updatePreview(getCurrentBlockOrder());
  };
  ```

**Block Order Detection:**
```javascript
function getCurrentBlockOrder() {
    const blocks = [];
    document.querySelectorAll('.block-item:not(.removed)').forEach(item => {
        blocks.push(item.dataset.blockType);
    });
    return blocks;
}
```

**Preview Update Method:**
```javascript
updatePreview(blockOrder) {
    if (!blockOrder || blockOrder.length === 0) {
        this.showEmptyState();
        return;
    }

    const htmlContent = blockOrder.map(blockType => {
        return getBlockTemplate(blockType);
    }).join('');

    this.iframeDoc.body.innerHTML = htmlContent;
}

showEmptyState() {
    this.iframeDoc.body.innerHTML = `
        <div class="preview-empty-state">
            <p>No blocks selected. Add blocks to see preview.</p>
        </div>
    `;
}
```

**Reset Button Integration:**
- Hook into existing reset button click handler
- Call `blockPreviewManager.updatePreview()` with default block order
- Ensure synchronization between reset and preview

**Performance Optimization:**
- Use `requestAnimationFrame` if needed for smooth updates
- Debounce rapid updates (100ms threshold)
- Avoid full iframe reload (use innerHTML update only)

### Definition of Done

- [ ] Preview updates immediately on drag-drop
- [ ] Preview updates immediately on block removal
- [ ] Preview updates immediately on reset
- [ ] Update performance < 100ms
- [ ] No console errors during updates
- [ ] Empty state shows when no blocks selected
- [ ] All 13 blocks can be previewed
- [ ] Rapid reordering handled gracefully

---

## Story 10.4: Polish Preview UX and Handle Edge Cases

**As a** content manager
**I want** a polished preview experience with helpful guidance
**So that** I understand how to use the preview and what it represents

### Acceptance Criteria

1. **Visual Polish:**
   - Preview disclaimer text clearly visible
   - Preview header styled consistently with WordPress admin
   - Preview iframe has subtle border/shadow for depth
   - Scrolling behavior smooth and intuitive
   - Preview content well-padded and readable

2. **User Guidance:**
   - Disclaimer: "This is a simplified preview. Actual styling may vary based on your theme."
   - Empty state message: "No blocks selected. Drag blocks from the left to see preview."
   - Tooltip or help icon explaining preview purpose (optional)

3. **Edge Case Handling:**
   - Preview handles missing block types gracefully
   - Preview handles malformed block data
   - Preview recovers from rendering errors
   - Preview maintains scroll position during updates (or resets to top)

4. **Browser Compatibility:**
   - Works in Chrome 90+
   - Works in Firefox 88+
   - Works in Safari 14+
   - Works in Edge 90+
   - Graceful degradation message for unsupported browsers (if needed)

5. **Accessibility Refinements:**
   - Preview updates announced to screen readers
   - Focus management doesn't break during updates
   - Keyboard navigation works between panes
   - Color contrast meets WCAG AA standards

6. **Performance Validation:**
   - Test with all 13 blocks enabled (no lag)
   - Test with rapid reordering (smooth updates)
   - Test with slow network (preview still renders locally)
   - No memory leaks on repeated updates

### Technical Requirements

**Error Handling:**
```javascript
updatePreview(blockOrder) {
    try {
        if (!blockOrder || blockOrder.length === 0) {
            this.showEmptyState();
            return;
        }

        const htmlContent = blockOrder.map(blockType => {
            return getBlockTemplate(blockType);
        }).join('');

        this.iframeDoc.body.innerHTML = htmlContent;

        // Announce to screen readers
        this.announceUpdate(blockOrder.length);

    } catch (error) {
        console.error('[Block Preview] Update failed:', error);
        this.showErrorState();
    }
}

showErrorState() {
    this.iframeDoc.body.innerHTML = `
        <div class="preview-error-state">
            <p>Preview could not be rendered. Please refresh the page.</p>
        </div>
    `;
}

announceUpdate(blockCount) {
    // ARIA live region update for screen readers
    const announcement = `Preview updated with ${blockCount} block${blockCount !== 1 ? 's' : ''}`;
    // Implement ARIA live announcement
}
```

**Browser Compatibility Check:**
```javascript
function checkBrowserSupport() {
    const isSupported = 'content' in document.createElement('template') &&
                       'grid' in document.createElement('div').style;

    if (!isSupported) {
        console.warn('[Block Preview] Browser may not fully support preview features');
    }

    return isSupported;
}
```

**CSS Polish:**
- Smooth scrolling: `scroll-behavior: smooth;`
- Subtle animations on update (optional for MVP)
- Consistent spacing using CSS variables
- Professional color palette
- Proper z-index management

**Testing Checklist:**
- [ ] Test with 0 blocks
- [ ] Test with 1 block
- [ ] Test with all 13 blocks
- [ ] Test rapid drag-drop (10+ times quickly)
- [ ] Test in Chrome, Firefox, Safari, Edge
- [ ] Test with keyboard navigation only
- [ ] Test with screen reader (NVDA/JAWS)
- [ ] Test on 1280px, 1440px, 1920px screens

### Definition of Done

- [ ] Preview has polished, professional appearance
- [ ] Disclaimer text clearly visible and helpful
- [ ] Empty state provides clear guidance
- [ ] Error handling prevents crashes
- [ ] Works in all target browsers
- [ ] Accessibility requirements met
- [ ] Performance validated with all edge cases
- [ ] No console errors under any scenario
- [ ] Code documented and commented

---

## Epic Dependencies

- Epic 6: CSV Import (block ordering interface must exist and work correctly)
- SortableJS library (already in use for block ordering)
- WordPress admin CSS framework (for consistent styling)

## Risks & Mitigations

**Risk:** Preview updates cause lag during drag operations
**Mitigation:** Use debouncing, optimize rendering, test with all 13 blocks early

**Risk:** Creating accurate block templates takes longer than estimated
**Mitigation:** Start with simplest blocks first, iterate to improve quality

**Risk:** Users expect pixel-perfect theme matching and are disappointed
**Mitigation:** Clear disclaimer text, set expectations as "simplified preview"

**Risk:** Iframe isolation causes unexpected CSS issues
**Mitigation:** Inject complete CSS framework into iframe, test early

**Risk:** Split-pane layout doesn't work on smaller screens
**Mitigation:** Document minimum width requirement (1280px), acceptable for admin-only feature

## Technical Notes

### MVP Simplifications

This epic focuses on a **functional MVP** with these simplifications:
- ✅ Generic preview styling (not theme-specific)
- ✅ Placeholder content only (no CSV data injection)
- ✅ Static templates (no interactive elements in preview)
- ✅ Desktop-only support (1280px+ screens)
- ❌ No theme CSS extraction (Phase 2)
- ❌ No editable preview content (Phase 2)
- ❌ No mobile/tablet responsive toggle (Phase 2)
- ❌ No export/screenshot functionality (Phase 2)

### Performance Considerations

- Preview updates happen entirely client-side (no API calls)
- Templates are static HTML strings (fast rendering)
- Iframe isolation prevents CSS calculation overhead
- Single DOM update per reorder event (batch changes)
- No external resource loading in preview (images use placeholder service)

### Post-MVP Enhancements (Out of Scope for Epic 10)

**Phase 2 - Enhanced Styling:**
- Detect and apply actual WordPress theme CSS
- Theme-aware color schemes and typography
- More accurate frontend representation

**Phase 3 - Interactive Preview:**
- Click blocks in preview to highlight in ordering panel
- Hover states showing block boundaries
- Tooltips explaining block purpose

**Phase 4 - Content Integration:**
- Pull actual CSV data into preview placeholders
- Show first row of CSV as preview content
- Dynamic image URL rendering from CSV

**Phase 5 - Advanced Features:**
- Mobile/desktop responsive toggle
- Export preview as PDF or screenshot
- Save preview configurations as templates
- A/B testing preview variations

### User Value Proposition

This feature addresses the "blind generation" problem by:
1. **Reducing API Waste:** Users see layout before generating, avoiding costly mistakes
2. **Increasing Confidence:** Visual feedback reduces hesitation about customization
3. **Improving Learning Curve:** New users understand block types through visual examples
4. **Preventing Errors:** Users catch layout issues before committing to generation
5. **Enabling Better Decisions:** Visual comparison helps users select optimal block combinations

**Expected Impact:**
- 40% reduction in support requests about block ordering
- 50% reduction in API waste from incorrect block selections
- 30% faster decision-making during import workflow
- 60% increase in custom block ordering adoption (from ~30% to ~60%)

---

**Epic Status:** Ready for Development
**Estimated Total Effort:** 10-15 hours
**Recommended Approach:** Build stories sequentially (10.1 → 10.2 → 10.3 → 10.4)
