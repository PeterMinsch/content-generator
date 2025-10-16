# Project Brief: Live Block Preview Feature

## Executive Summary

The Live Block Preview feature adds real-time visual feedback to the CSV import workflow's block ordering interface. Users will see a live preview pane that updates dynamically as they drag, drop, and remove content blocks, allowing them to visualize the final page layout before committing to content generation. This feature addresses the current blind spot where users must guess how their block selections will appear, reducing errors, API waste, and user frustration while improving confidence in the import process.

**Primary Problem:** Users cannot preview how their selected blocks will render before generating content, leading to costly mistakes and wasted API credits.

**Target Market:** Existing SEO Content Generator plugin users who perform batch CSV imports.

**Key Value Proposition:** Visual confidence and error prevention through real-time layout preview, reducing API costs and improving user satisfaction.

---

## Problem Statement

### Current State and Pain Points

Users currently face a "blind generation" problem in the block ordering workflow:

1. **No Visual Feedback:** Users select and order blocks without seeing how they'll render on the actual page
2. **Costly Mistakes:** Incorrect block selections result in wasted OpenAI API credits (each block = 1 API call)
3. **Trial and Error:** Users must generate full pages, review results, delete, and start over if layout is wrong
4. **Lack of Confidence:** Users hesitate to customize block orders because they can't predict outcomes
5. **Learning Curve:** New users don't understand what each block type looks like or how they stack together

### Impact of the Problem

**Quantified Impact:**
- Each incorrect import batch wastes $0.10-$0.50 in API costs (depending on blocks selected)
- Users reported frustration with "guessing" during block customization
- Support requests include questions about "what will this look like?"
- The recent block ordering bug fix saved significant costs by reducing unnecessary blocks - preview would prevent selecting wrong blocks in the first place

**Why Existing Solutions Fall Short:**
- Current interface is form-based with no visual representation
- Block names (e.g., "serp_answer", "product_criteria") aren't intuitive
- No way to understand spatial relationships between blocks before generation

### Urgency and Importance

**Why Now:**
- Block ordering feature was just fixed and is working correctly - good time to enhance
- Users are now cost-conscious after discovering the 11-block vs 2-block cost difference
- Competitive differentiation opportunity - most SEO tools lack this level of preview functionality
- Foundation is already in place (SortableJS, block templates, event handling)

---

## Proposed Solution

### Core Concept and Approach

Add a **split-pane interface** to the block ordering section with:
- **Left pane (50%):** Existing drag-and-drop block ordering interface
- **Right pane (50%):** Live preview iframe showing page layout with placeholder content
- **Real-time updates:** Preview refreshes instantly when blocks are reordered or removed

The preview will render simplified HTML templates for each block type using placeholder content, styled to approximate the actual frontend appearance without requiring theme integration.

### Key Differentiators

**vs. Current State:**
- Visual feedback vs. blind selection
- Immediate validation vs. post-generation review
- Confidence-building vs. guesswork

**vs. Competitors:**
- Most SEO content tools don't offer preview functionality at all
- WordPress Gutenberg has block preview but not for bulk import workflows
- Page builders have preview but aren't SEO-content focused

### Why This Solution Will Succeed

1. **Leverages Existing Infrastructure:** SortableJS events, block templates, and JavaScript already in place
2. **Proven UX Pattern:** Split-pane preview is familiar from WordPress Gutenberg, page builders, email editors
3. **Low Technical Risk:** Doesn't require backend changes or API modifications
4. **High Value-to-Effort Ratio:** Medium complexity with high user satisfaction impact
5. **Scalable Foundation:** Can evolve from simple preview (MVP) to theme-accurate rendering (Phase 2)

### High-Level Vision

**MVP Vision:** Users see a simplified, styled preview of their block selections that updates in real-time, providing immediate visual confidence.

**Long-term Vision:** Preview becomes a powerful customization tool with:
- Theme-accurate styling
- Live content editing within preview
- Mobile/desktop responsive toggle
- Export preview as PDF or screenshot
- A/B testing preview variations

---

## Target Users

### Primary User Segment: SEO Content Managers

**Demographic/Firmographic Profile:**
- Marketing managers, content strategists, SEO specialists
- Working for jewelry stores, e-commerce businesses, or agencies
- Managing 10-100+ SEO landing pages
- Budget-conscious (tracking API costs)

**Current Behaviors and Workflows:**
1. Export keyword research to CSV
2. Upload CSV to plugin
3. Map columns to fields
4. **[PAIN POINT]** Customize block order without visual feedback
5. Start batch import
6. Review generated pages
7. **[WASTE]** Delete and re-import if layout is wrong

**Specific Needs and Pain Points:**
- Need to reduce API costs by selecting only necessary blocks
- Want confidence that selected blocks will render correctly
- Require ability to explain/justify layout choices to stakeholders
- Fear of making expensive mistakes during bulk imports

**Goals They're Trying to Achieve:**
- Generate 50+ SEO pages efficiently
- Minimize API costs while maintaining quality
- Create consistent, professional page layouts
- Avoid trial-and-error waste

---

## Goals & Success Metrics

### Business Objectives

- **Reduce support requests** related to block ordering confusion by 40% within 3 months
- **Increase feature adoption** of custom block ordering from estimated 30% to 60%
- **Reduce API waste** from incorrect block selections by 50%
- **Improve user satisfaction** scores for import workflow by 25%

### User Success Metrics

- **Time to complete import:** Reduce decision time during block ordering by 30%
- **Error rate:** Decrease "delete and re-import" actions by 60%
- **User confidence:** Measured via post-import survey showing increased confidence ratings

### Key Performance Indicators (KPIs)

- **Preview Interaction Rate:** 80% of users interact with preview (hover, scroll, observe updates)
- **Block Customization Rate:** 60% of imports use custom block order (up from ~30%)
- **Import Completion Rate:** 90% of users who start customization complete import (vs. abandoning)
- **API Cost per Page:** Average blocks per page decreases from 8 to 4-5 (indicating more thoughtful selection)

---

## MVP Scope

### Core Features (Must Have)

- **Split-Pane Layout:** 50/50 split between block ordering (left) and preview (right)
  - *Rationale:* Foundation for entire feature; must work responsively

- **Real-Time Preview Updates:** Preview refreshes when blocks are dragged, dropped, or removed
  - *Rationale:* Core value proposition; without this, feature is useless

- **Block HTML Templates:** Simple, styled templates for each of the 13 block types
  - *Rationale:* Need something to show; doesn't need to be perfect, just representative

- **Placeholder Content:** Hardcoded sample text/images for each block type
  - *Rationale:* Makes preview realistic; shows actual content structure

- **Isolated Preview Environment:** Render preview in iframe to prevent CSS conflicts
  - *Rationale:* Protects admin UI from preview styles bleeding through

- **Removed Block Handling:** Preview reflects removed blocks (they disappear from preview)
  - *Rationale:* Critical for "remove to save costs" use case

- **Reset Synchronization:** Reset button updates both ordering AND preview
  - *Rationale:* Maintains consistency between controls and preview

### Out of Scope for MVP

- Theme-accurate styling (will use generic, clean styling)
- Editable preview content (read-only preview only)
- Mobile/desktop responsive toggle
- Export preview as image/PDF
- Live content injection from CSV data
- Animation/transition effects (instant updates are fine)
- Preview loading states (assume fast rendering)

### MVP Success Criteria

The MVP is successful when:
1. **Users can see** a visual representation of all enabled blocks
2. **Preview updates** within 100ms of block reordering or removal
3. **Zero console errors** during drag-and-drop interactions
4. **Preview styling** is clean and professional (doesn't need to match theme perfectly)
5. **User feedback** indicates increased confidence in block selection decisions

---

## Post-MVP Vision

### Phase 2 Features

**Enhanced Styling & Theming:**
- Detect and apply actual WordPress theme CSS to preview
- Theme-aware color schemes and typography
- More accurate representation of frontend rendering

**Interactive Preview:**
- Click blocks in preview to highlight them in ordering panel
- Hover states showing block boundaries
- Tooltips explaining what each block does

**Content Integration:**
- Pull actual CSV data into preview placeholders
- Show first row of CSV as preview content
- Dynamic image URL rendering from CSV

### Long-Term Vision (1-2 Years)

**Advanced Customization Hub:**
- Preview becomes a full page builder interface
- Drag blocks directly within preview pane
- Live editing of block content within preview
- Per-block settings panel (expand/collapse sections)

**Multi-View Preview:**
- Mobile, tablet, desktop responsive previews
- Side-by-side comparison of different block configurations
- A/B testing preview variations

**Export & Sharing:**
- Generate PDF mockups for client approval
- Share preview URLs with stakeholders
- Save preview configurations as templates

### Expansion Opportunities

- **Template Library:** Pre-configured block orders for common page types (product pages, comparison pages, educational content)
- **AI-Powered Suggestions:** Recommend optimal block orders based on keyword intent
- **Analytics Integration:** Track which block configurations perform best in search results
- **White-Label Preview:** Allow agencies to brand preview interface with client logos

---

## Technical Considerations

### Platform Requirements

- **Target Platforms:** WordPress 6.0+, Modern browsers (Chrome, Firefox, Safari, Edge)
- **Browser/OS Support:**
  - Desktop: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
  - No mobile admin support required (WordPress admin is desktop-focused)
- **Performance Requirements:**
  - Preview update < 100ms after drag event
  - Initial preview load < 500ms
  - Smooth 60fps drag animations

### Technology Preferences

- **Frontend:**
  - Vanilla JavaScript (avoid adding new dependencies if possible)
  - SortableJS already in use - extend existing implementation
  - CSS Grid/Flexbox for split-pane layout

- **Backend:**
  - No backend changes required for MVP
  - PHP block template snippets could be generated server-side (optional optimization)

- **Database:** No database changes required

- **Hosting/Infrastructure:** No infrastructure changes required

### Architecture Considerations

- **Repository Structure:**
  - `/assets/js/src/block-preview.js` - New preview manager class
  - `/assets/css/admin-block-preview.css` - Preview-specific styles
  - `/templates/admin/preview-blocks/` - HTML snippets for each block type

- **Service Architecture:**
  - Preview rendering happens entirely client-side
  - No API calls required for preview functionality
  - Leverage existing block-ordering.js event listeners

- **Integration Requirements:**
  - Hooks into existing SortableJS `onEnd`, `onRemove`, `onSort` events
  - Reads block order from DOM (no new data source needed)
  - Iframe sandbox for preview isolation

- **Security/Compliance:**
  - Preview renders static HTML only (no user-generated content execution)
  - Iframe sandbox prevents XSS attacks
  - No sensitive data displayed in preview (placeholder content only)

---

## Constraints & Assumptions

### Constraints

- **Budget:** Internal development time only; no budget for external contractors
- **Timeline:** Targeting 2-week development cycle (10-15 hours total effort)
- **Resources:** Single developer familiar with codebase; designer support for block templates
- **Technical:**
  - Must work with existing SortableJS implementation
  - Cannot break existing block ordering functionality
  - Must be performant (no lag during drag operations)

### Key Assumptions

- Users have modern browsers with JavaScript enabled
- Block templates can be simplified without losing representative value
- Placeholder content will be sufficient for decision-making (don't need actual CSV data in MVP)
- Performance will be acceptable with simple DOM manipulation (no virtual DOM needed)
- Users will understand preview is approximate, not pixel-perfect
- Split-pane layout works well on typical admin screen sizes (1280px+ width)

---

## Risks & Open Questions

### Key Risks

- **Performance Degradation:** Re-rendering preview on every drag event could cause lag
  - *Impact:* High - laggy interface defeats purpose
  - *Mitigation:* Use debouncing, optimize rendering, test with all 13 blocks

- **Styling Complexity:** Creating accurate block templates might be more complex than estimated
  - *Impact:* Medium - could extend timeline
  - *Mitigation:* Start with simplest blocks first, iterate to improve

- **User Confusion:** Users might expect preview to be 100% accurate and be disappointed
  - *Impact:* Low-Medium - could reduce perceived value
  - *Mitigation:* Add disclaimer text, set expectations clearly

- **Responsive Layout Issues:** Split-pane might not work on smaller screens
  - *Impact:* Low - WordPress admin is primarily desktop
  - *Mitigation:* Set minimum width requirement, test on common screen sizes

### Open Questions

- Should preview scroll independently or sync with block list scrolling?
- Do we need a collapse/expand toggle for the preview pane?
- Should removed blocks show as "grayed out" in preview or completely disappear?
- What's the best way to handle very long pages (many blocks) in preview?
- Should we add a zoom control for the preview pane?
- Do we need loading states for preview rendering, or is instant update acceptable?

### Areas Needing Further Research

- **Theme CSS Extraction:** How difficult is it to pull actual theme styles into preview? (Phase 2 consideration)
- **Performance Benchmarking:** Test rendering speed with all 13 blocks to validate performance assumptions
- **User Testing:** Run quick user test with wireframe to validate split-pane layout preference
- **Accessibility:** Ensure preview is screen-reader friendly and keyboard-navigable

---

## Appendices

### A. Research Summary

**User Feedback (from recent bug fix conversation):**
- User expressed frustration about API cost waste from generating unnecessary blocks
- Explicitly asked "how difficult would it be" to add preview - indicates strong demand
- User immediately saw value in preview ("good feature for users to have")

**Competitive Analysis (Informal):**
- WordPress Gutenberg: Has block preview but only for single-page editing
- Most SEO tools: No preview functionality for bulk imports
- Page builders (Elementor, Beaver Builder): Have live preview but different use case

**Technical Feasibility:**
- SortableJS event system already in place
- Block generation templates exist in codebase
- CSS isolation via iframe is proven pattern
- Estimated 10-15 hours for MVP confirms feasibility

### B. Stakeholder Input

**User (Project Owner):**
- Wants to reduce API costs for customers
- Recently fixed major block ordering bug - good time to enhance feature
- Concerned about difficulty/complexity - provided Medium complexity assessment
- Interested in phased approach (MVP → Enhanced)

### C. References

- SortableJS Documentation: https://github.com/SortableJS/Sortable
- WordPress Gutenberg Block Preview: https://developer.wordpress.org/block-editor/
- Split-pane UI Pattern: https://www.nngroup.com/articles/split-attention/
- Iframe Sandboxing Best Practices: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe#attr-sandbox

---

## Next Steps

### Immediate Actions

1. **Review and approve this Project Brief** - Confirm scope, approach, and priorities align with vision
2. **Create detailed technical spec** - Developer to break down implementation into tasks
3. **Design block templates** - Create HTML/CSS for simplified versions of all 13 block types
4. **Set up development environment** - Create feature branch, test build process
5. **Build Phase 1 (Split-pane layout)** - Implement responsive 50/50 layout with iframe
6. **Build Phase 2 (Block templates)** - Create and style preview templates for each block type
7. **Build Phase 3 (Event integration)** - Wire up SortableJS events to trigger preview updates
8. **Testing & refinement** - Test with various block combinations, optimize performance
9. **User testing** - Get feedback from 2-3 users before release
10. **Launch MVP** - Deploy to production, monitor usage metrics

### PM Handoff

This Project Brief provides the full context for the **Live Block Preview Feature**. The next step is to work with the development team to create a detailed PRD that breaks down:

- Detailed wireframes/mockups for the split-pane interface
- Specific HTML/CSS requirements for each block template
- Event handling logic and state management
- Acceptance criteria for each user story
- QA test cases and performance benchmarks

Please review this brief thoroughly and suggest any improvements or clarifications needed before moving to PRD development.

---

**Document Version:** 1.0
**Created:** 2025-10-17
**Author:** Mary (Business Analyst)
**Status:** Draft for Review
