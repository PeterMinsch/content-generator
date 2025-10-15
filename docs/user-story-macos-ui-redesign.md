# User Story: macOS-Inspired UI Redesign

## Epic
**Redesign SEO Generator Plugin with macOS Ventura/Sonoma Aesthetic**

---

## Story 1: Implement macOS-Style Design System

### As a
Plugin administrator

### I want
The SEO Generator plugin to have a modern, macOS-inspired interface with our brand colors

### So that
The plugin feels professional, intuitive, and delightful to use, matching the quality of the content it generates

---

## Acceptance Criteria

### 1. Design System Foundation
- [ ] Implement CSS custom properties for the complete color palette
  - Brand colors: Gold (#CA9652) and Charcoal (#272521) with variations
  - macOS system grays (50-900 scale)
  - Semantic colors (success, warning, error, info)
  - Glassmorphism variables (backdrop blur, translucent backgrounds)

- [ ] Set up SF Pro font stack with fallbacks
  - SF Pro Display for headings
  - SF Pro Text for body copy
  - SF Mono for code/technical elements
  - Fallback to system fonts if SF Pro unavailable

- [ ] Define type scale and weights
  - 7 text sizes (xs to 3xl)
  - 4 font weights (regular to bold)
  - Consistent line heights and letter spacing

### 2. Component Library - Phase 1 Priority

#### CSV Import Page
- [ ] **File Drop Zone**
  - 200px height, dashed border with 12px border radius
  - Drag-over state: solid gold border with glow effect
  - Smooth 200ms transitions on all states
  - Center-aligned icon (48px) and helper text
  - Click to browse functionality

- [ ] **Settings Cards**
  - White background with subtle shadow
  - 12px border radius, 24px padding
  - Hover effect: lift 2px with enhanced shadow
  - Radio buttons and checkboxes with gold accent color
  - 16px gap between cards

- [ ] **Primary Action Button (Import CSV)**
  - Gold gradient background (light to dark)
  - White text, semibold weight
  - 12px vertical, 24px horizontal padding
  - 8px border radius
  - Hover: lift 2px with gold shadow
  - Active: scale 0.98
  - Includes sparkle icon (✨)

- [ ] **Column Mapping Interface**
  - Two-column layout showing CSV → Plugin field mapping
  - Custom dropdowns with macOS-style appearance
  - Auto-detect common column names
  - Visual indicators for required vs optional fields

#### SEO Pages List
- [ ] **Toolbar with Search**
  - Pill-shaped search input (18px border radius)
  - Glassmorphic background with backdrop blur
  - Magnifying glass icon positioned left
  - Gold focus ring (3px offset)
  - 300px width, 36px height

- [ ] **Filter Dropdowns**
  - macOS-style dropdown menus
  - Options: All/Status/Topic
  - Show active filter count
  - Clear filters option

- [ ] **List Rows**
  - Card-based layout (not traditional table)
  - 10px border radius, white background
  - 16px vertical padding, 20px horizontal
  - 12px gap between rows
  - Hover: slight lift with background change
  - 120ms transition timing

- [ ] **Status Badges**
  - Color-coded by status (Published/Pending/Draft/Failed)
  - 6px border radius, inline-flex display
  - Dot indicator before text
  - Xs size, semibold weight
  - Published: Green (#34C759)
  - Pending: Orange (#FF9500)
  - Draft: Gray
  - Failed: Red (#FF3B30)

- [ ] **Action Menu (⋯)**
  - 32×32px clickable area
  - Hover: gray background
  - Dropdown menu with blur effect
  - Options: Edit, Duplicate, Regenerate, Delete
  - Smooth slide-down animation (250ms)

### 3. Sidebar Navigation
- [ ] **macOS-Style Sidebar**
  - Fixed 260px width
  - Light gray background (#FAFAF9)
  - Plugin logo and name at top
  - Navigation items with icons (16px)
  - Active state: gold left border (3px) + glass background
  - Hover state: light gray background
  - 12px vertical padding between items
  - 8px border radius on hover/active states

- [ ] **Navigation Items**
  - Dashboard
  - CSV Import
  - SEO Pages
  - Settings
  - Diagnostics (with 🔗 icon)

### 4. Progress Indicators
- [ ] **AI Generation Progress Card**
  - Glassmorphic card with backdrop blur
  - 12px border radius, 20px padding
  - Progress bar: 8px height, gold fill
  - Block list showing:
    - ✓ Completed (green checkmark)
    - ⟳ Generating... (gold spinner)
    - ⋯ Queued (gray)
  - Estimated completion time
  - Smooth animations (pulse on active items)

- [ ] **Upload Progress**
  - Similar styling to generation progress
  - Show file name, size, upload percentage
  - Success state with ripple animation

### 5. Animations & Micro-interactions
- [ ] Define keyframe animations:
  - `lift`: translateY(-2px) on hover
  - `ripple`: scale and fade for success states
  - `spin`: loading spinners
  - `fadeIn`: element entrance

- [ ] Set transition timing:
  - Instant feedback: 80ms
  - Standard: 150ms
  - Panels: 250ms
  - Pages: 300ms
  - Easing: cubic-bezier(0.4, 0.0, 0.2, 1)

- [ ] Respect `prefers-reduced-motion` for accessibility

### 6. Responsive Design
- [ ] Mobile breakpoint (≤640px):
  - Sidebar collapses to hamburger menu
  - Cards stack vertically
  - Touch targets minimum 44px
  - Search bar full width

- [ ] Tablet breakpoint (≤768px):
  - Sidebar toggleable
  - 2-column card layouts where applicable

- [ ] Desktop (≥1024px):
  - Full sidebar visible
  - Multi-column layouts
  - Optimal use of horizontal space

### 7. Accessibility
- [ ] WCAG 2.1 AA compliance
- [ ] Color contrast minimum 4.5:1 for all text
- [ ] 2px gold focus indicators with 2px offset
- [ ] Full keyboard navigation support
- [ ] ARIA labels on all interactive elements
- [ ] Screen reader tested with NVDA/JAWS

---

## Technical Implementation Notes

### File Structure
```
/assets/
  /css/
    design-system.css       # CSS custom properties
    components.css          # Component styles
    animations.css          # Keyframes and transitions
  /js/
    interactions.js         # Micro-interactions
    progress-tracking.js    # Progress indicators
```

### Technology Stack
- **CSS Variables** for theming
- **Vanilla CSS** or **Tailwind CSS** (developer preference)
- **WordPress admin enqueue** for proper loading
- **Progressive enhancement** for older browsers

### Browser Support
- Modern evergreen browsers (Chrome, Firefox, Safari, Edge)
- Graceful degradation for older browsers
- Tested on macOS, Windows, Linux

---

## Definition of Done

- [ ] All acceptance criteria met
- [ ] Responsive on mobile, tablet, desktop
- [ ] Accessibility audit passed
- [ ] Cross-browser tested
- [ ] Performance: Page load < 2s, interactions < 100ms
- [ ] Code review completed
- [ ] Design review with stakeholder approval
- [ ] Documentation updated with component usage

---

## Design Assets

### Reference Files
- Front-end specification (see: `docs/design-spec-macos-ui.md`)
- Color palette with hex codes
- Typography scale and weights
- Component spacing and sizing

### Resources Needed
- SF Pro font files (or system font fallbacks)
- Icon set (16px, 24px, 48px sizes)
- Example screenshots from macOS Ventura/Sonoma for reference

---

## Estimated Effort
- **Phase 1 (CSV Import):** 2-3 days
- **Phase 2 (SEO Pages List):** 2-3 days
- **Phase 3 (Global Components):** 1-2 days
- **Total:** 5-8 days for full implementation

---

## Notes for Developer

1. **Start with Design System** - Implement all CSS variables first, then components will be easy
2. **Component-First Approach** - Build reusable components (buttons, cards, badges) that can be used throughout
3. **Test Incrementally** - Deploy CSV Import page first, gather feedback, then iterate
4. **Reference macOS** - Keep macOS System Preferences or Settings open for color/spacing inspiration
5. **Gold Sparingly** - Use gold for primary actions and accents only, charcoal for text/UI structure

---

## Questions or Clarifications?

Contact the UX team for design clarification or additional mockups.
