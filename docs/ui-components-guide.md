# SEO Generator Plugin - UI Components Guide

**macOS-Inspired Design System**
*Version 1.0.0*

This guide documents all CSS classes, variables, and JavaScript APIs for the macOS-inspired UI components.

---

## Table of Contents

1. [Design System](#design-system)
2. [Components](#components)
3. [JavaScript APIs](#javascript-apis)
4. [Usage Examples](#usage-examples)
5. [Accessibility](#accessibility)

---

## Design System

### CSS Custom Properties

All design tokens are defined in `assets/css/design-system.css` as CSS custom properties.

#### Brand Colors

```css
--gold: #CA9652;           /* Primary brand - accents, CTAs, highlights */
--gold-light: #E5C697;     /* Hover states, subtle highlights */
--gold-dark: #A67835;      /* Active states, shadows */
--charcoal: #272521;       /* Primary text, dark backgrounds */
--charcoal-soft: #3D3935;  /* Secondary backgrounds */
```

#### System Grays

```css
--gray-50: #FAFAF9;        /* Page backgrounds */
--gray-100: #F5F5F4;       /* Card backgrounds */
--gray-200: #E7E5E4;       /* Borders, dividers */
--gray-300: #D6D3D1;       /* Disabled states */
--gray-700: #44403C;       /* Secondary text */
--gray-900: #1C1917;       /* Headings */
```

#### Semantic Colors

```css
--success: #34C759;        /* macOS green */
--warning: #FF9500;        /* macOS orange */
--error: #FF3B30;          /* macOS red */
--info: #007AFF;           /* macOS blue */
```

#### Typography

```css
/* Font Stacks */
--font-display: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
--font-text: 'SF Pro Text', -apple-system, BlinkMacSystemFont, sans-serif;
--font-mono: 'SF Mono', 'Menlo', 'Monaco', monospace;

/* Type Scale */
--text-xs: 11px;     /* Helper text, captions */
--text-sm: 13px;     /* Body small, labels */
--text-base: 15px;   /* Primary body text */
--text-lg: 17px;     /* Large body, subheadings */
--text-xl: 22px;     /* Section headings */
--text-2xl: 28px;    /* Page titles */
--text-3xl: 34px;    /* Hero headings */
```

#### Spacing

```css
--space-1: 4px;
--space-2: 8px;
--space-3: 12px;
--space-4: 16px;
--space-5: 20px;
--space-6: 24px;
--space-8: 32px;
--space-10: 40px;
--space-12: 48px;
--space-16: 64px;
```

---

## Components

### File Drop Zone

**Class:** `.seo-drop-zone`

Drag-and-drop file upload area.

```html
<div class="seo-drop-zone">
  <span class="seo-drop-zone__icon">📄</span>
  <p class="seo-drop-zone__text">Drop CSV file here or click to browse</p>
  <p class="seo-drop-zone__hint">Supported format: .csv (max 50MB)</p>
  <input type="file" accept=".csv" />
</div>
```

**States:**
- `.drag-over` - Applied when file is dragged over zone

### Buttons

#### Primary Button

**Class:** `.seo-btn-primary`

Gold gradient button for primary actions.

```html
<button class="seo-btn-primary">
  Import CSV ✨
</button>
```

#### Secondary Button

**Class:** `.seo-btn-secondary`

Outlined button for secondary actions.

```html
<button class="seo-btn-secondary">
  Cancel
</button>
```

#### Danger Button

**Class:** `.seo-btn-danger`

Red button for destructive actions.

```html
<button class="seo-btn-danger">
  Delete
</button>
```

### Cards

**Class:** `.seo-card`

Container for grouped content.

```html
<div class="seo-card">
  <h3 class="seo-card__title">
    ⚙️ Settings
  </h3>
  <div class="seo-card__content">
    <!-- Card content here -->
  </div>
</div>
```

### Radio Buttons

**Class:** `.seo-radio`

macOS-style radio buttons with gold accent.

```html
<div class="seo-radio">
  <label class="seo-radio__option">
    <input type="radio" name="mode" class="seo-radio__input" value="draft" />
    <span class="seo-radio__label">Drafts Only</span>
  </label>
  <label class="seo-radio__option">
    <input type="radio" name="mode" class="seo-radio__input" value="auto" checked />
    <span class="seo-radio__label">Auto-Generate Content ⭐</span>
  </label>
</div>
```

### Checkboxes

**Class:** `.seo-checkbox`

macOS-style checkboxes with gold accent.

```html
<div class="seo-checkbox">
  <label class="seo-checkbox__option">
    <input type="checkbox" class="seo-checkbox__input" checked />
    <span class="seo-checkbox__label">Check for duplicates</span>
  </label>
</div>
```

### Search Bar

**Class:** `.seo-search`

Pill-shaped search input with glassmorphic background.

```html
<div class="seo-search">
  <input type="search" class="seo-search__input" placeholder="Search..." />
  <span class="seo-search__icon">🔍</span>
</div>
```

### Filter Dropdowns

**Class:** `.seo-filter`

macOS-style dropdown menus.

```html
<div class="seo-filter">
  <button class="seo-filter__button" data-filter-type="status">
    Status
    <span class="seo-filter__badge">3</span>
  </button>
</div>
```

### List Rows

**Class:** `.seo-list-row`

Card-based list item.

```html
<div class="seo-list-row" data-item-id="123">
  <div class="seo-list-row__content">
    <h4 class="seo-list-row__title">
      ✨ Diamond Engagement Rings Guide
    </h4>
    <div class="seo-list-row__meta">
      <span class="seo-list-row__meta-item">
        <span class="seo-badge seo-badge--published">Published</span>
      </span>
      <span class="seo-list-row__meta-item">Updated 1 hour ago</span>
      <span class="seo-list-row__meta-item">15 images</span>
    </div>
  </div>
  <div class="seo-action-menu">
    <button class="seo-action-menu__trigger">⋯</button>
  </div>
</div>
```

### Status Badges

**Classes:**
- `.seo-badge--published` (green)
- `.seo-badge--pending` (orange)
- `.seo-badge--draft` (gray)
- `.seo-badge--failed` (red)

```html
<span class="seo-badge seo-badge--published">Published</span>
<span class="seo-badge seo-badge--pending">Pending</span>
<span class="seo-badge seo-badge--draft">Draft</span>
<span class="seo-badge seo-badge--failed">Failed</span>
```

### Sidebar Navigation

**Class:** `.seo-sidebar`

Fixed sidebar with navigation items.

```html
<div class="seo-sidebar">
  <div class="seo-sidebar__header">
    <span class="seo-sidebar__logo">📊</span>
    <h2 class="seo-sidebar__title">SEO Generator</h2>
  </div>
  <nav class="seo-sidebar__nav">
    <a href="#" class="seo-sidebar__item seo-sidebar__item--active">
      <span class="seo-sidebar__icon">●</span>
      Dashboard
    </a>
    <a href="#" class="seo-sidebar__item">
      <span class="seo-sidebar__icon">📄</span>
      CSV Import
    </a>
  </nav>
</div>
```

### Progress Card

**Class:** `.seo-progress-card`

Glassmorphic card showing AI generation progress.

```html
<div class="seo-progress-card">
  <div class="seo-progress-card__header">
    🤖 Generating Content...
  </div>
  <div class="seo-progress-bar">
    <div class="seo-progress-bar__fill" style="width: 60%"></div>
  </div>
  <div class="seo-progress-list">
    <div class="seo-progress-item seo-progress-item--completed">
      <span class="seo-progress-item__icon">✓</span>
      <span class="seo-progress-item__label">Hero Section</span>
    </div>
    <div class="seo-progress-item seo-progress-item--generating">
      <span class="seo-progress-item__icon">⟳</span>
      <span class="seo-progress-item__label">SERP Answer</span>
    </div>
    <div class="seo-progress-item seo-progress-item--queued">
      <span class="seo-progress-item__icon">⋯</span>
      <span class="seo-progress-item__label">Materials</span>
    </div>
  </div>
  <div class="seo-progress-card__footer">
    Est. completion: 2 min 30 sec
  </div>
</div>
```

### Upload Progress

**Class:** `.seo-upload-progress`

File upload progress indicator.

```html
<div class="seo-upload-progress">
  <div class="seo-upload-progress__info">
    <span class="seo-upload-progress__name">products.csv</span>
    <span class="seo-upload-progress__size">2.5 MB</span>
  </div>
  <div class="seo-upload-progress__bar">
    <div class="seo-upload-progress__fill" style="width: 45%"></div>
  </div>
</div>
```

---

## JavaScript APIs

### Progress Tracker

Track multi-step operations with progress updates.

```javascript
// Get progress card element
const card = document.querySelector('.seo-progress-card');
const tracker = new window.SEOProgressTracker(card);

// Add items
tracker.addItem('hero', 'Hero Section', 'queued');
tracker.addItem('serp', 'SERP Answer', 'queued');
tracker.addItem('materials', 'Materials', 'queued');

// Update progress
tracker.updateProgress(33);

// Update item status
tracker.updateItemStatus('hero', 'completed');
tracker.updateItemStatus('serp', 'generating');

// Complete
tracker.complete();
```

### Upload Progress

Track file upload progress.

```javascript
// Get upload element
const upload = document.querySelector('.seo-upload-progress');
const tracker = new window.SEOUploadProgress(upload);

// Start upload
tracker.start('products.csv', 2621440); // fileName, fileSize in bytes

// Update progress
tracker.updateProgress(45);

// Complete upload
tracker.complete();

// Handle error
tracker.error('Upload failed: Network error');
```

### Progress Polling

Poll server for real-time updates.

```javascript
const poller = new window.SEOProgressPoller('/api/status', 2000); // URL, interval

poller.start((data) => {
  // Handle update
  console.log('Progress:', data.progress);

  // Update UI
  tracker.updateProgress(data.progress);
});

// Stop polling
poller.stop();
```

### Custom Events

Listen for component events:

```javascript
// File selected
document.addEventListener('seo-file-selected', (e) => {
  console.log('Files:', e.detail.files);
});

// Search
document.addEventListener('seo-search', (e) => {
  console.log('Search term:', e.detail.searchTerm);
});

// Filter change
document.addEventListener('seo-filter-change', (e) => {
  console.log('Filters:', e.detail.filters);
});

// Action menu click
document.addEventListener('seo-action-menu-click', (e) => {
  console.log('Action:', e.detail.action, 'Item:', e.detail.itemId);
});

// Progress complete
card.addEventListener('seo-progress-complete', () => {
  console.log('Generation complete!');
});
```

---

## Usage Examples

### Complete CSV Import Page

```html
<div class="seo-generator-page">
  <h1>CSV Import</h1>

  <!-- Drop Zone -->
  <div class="seo-drop-zone">
    <span class="seo-drop-zone__icon">📄</span>
    <p class="seo-drop-zone__text">Drop CSV file here or click to browse</p>
    <p class="seo-drop-zone__hint">Supported format: .csv (max 50MB)</p>
    <input type="file" accept=".csv" />
  </div>

  <!-- Settings Card -->
  <div class="seo-card">
    <h3 class="seo-card__title">⚙️ Generation Mode</h3>
    <div class="seo-card__content">
      <div class="seo-radio">
        <label class="seo-radio__option">
          <input type="radio" name="mode" class="seo-radio__input" value="draft" />
          <span class="seo-radio__label">Drafts Only</span>
        </label>
        <label class="seo-radio__option">
          <input type="radio" name="mode" class="seo-radio__input" value="auto" checked />
          <span class="seo-radio__label">Auto-Generate Content ⭐</span>
        </label>
      </div>

      <div class="seo-checkbox">
        <label class="seo-checkbox__option">
          <input type="checkbox" class="seo-checkbox__input" checked />
          <span class="seo-checkbox__label">Check for duplicates</span>
        </label>
        <label class="seo-checkbox__option">
          <input type="checkbox" class="seo-checkbox__input" checked />
          <span class="seo-checkbox__label">Download images from URLs</span>
        </label>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="seo-btn-group">
    <button class="seo-btn-secondary">Cancel</button>
    <button class="seo-btn-primary">Import CSV ✨</button>
  </div>
</div>
```

### SEO Pages List

```html
<div class="seo-generator-page">
  <!-- Toolbar -->
  <div class="seo-toolbar">
    <div class="seo-search">
      <input type="search" class="seo-search__input" placeholder="Search pages..." />
      <span class="seo-search__icon">🔍</span>
    </div>

    <div class="seo-filters">
      <div class="seo-filter">
        <button class="seo-filter__button">All</button>
      </div>
      <div class="seo-filter">
        <button class="seo-filter__button">Status</button>
      </div>
    </div>

    <button class="seo-btn-primary">+ New Page</button>
  </div>

  <!-- List -->
  <div class="seo-list">
    <div class="seo-list-row">
      <div class="seo-list-row__content">
        <h4 class="seo-list-row__title">✨ Diamond Engagement Rings</h4>
        <div class="seo-list-row__meta">
          <span class="seo-badge seo-badge--published">Published</span>
          <span>Updated 1 hour ago</span>
          <span>15 images</span>
        </div>
      </div>
      <div class="seo-action-menu">
        <button class="seo-action-menu__trigger">⋯</button>
      </div>
    </div>
  </div>
</div>
```

---

## Accessibility

### Keyboard Navigation

All interactive components support keyboard navigation:

- **Tab** - Navigate between elements
- **Enter/Space** - Activate buttons and links
- **Arrow keys** - Navigate dropdowns and menus
- **Escape** - Close modals and dropdowns

### Screen Reader Support

All components include proper ARIA attributes:

- `aria-label` - Descriptive labels
- `aria-expanded` - Dropdown state
- `aria-current` - Current page indicator
- `aria-live` - Live region updates
- `role` - Semantic roles

### Focus Indicators

All interactive elements show a 2px gold outline on focus:

```css
:focus-visible {
  outline: 2px solid var(--gold);
  outline-offset: 2px;
}
```

### Color Contrast

All text meets WCAG 2.1 AA contrast requirements (4.5:1 minimum).

### Reduced Motion

Animations are disabled when user prefers reduced motion:

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

Graceful degradation for older browsers.

---

## File Structure

```
/assets/
  /css/
    design-system.css      # CSS custom properties, base styles
    components.css         # Component styles
    animations.css         # Keyframes and transitions
    responsive.css         # Mobile/tablet/desktop breakpoints
    accessibility.css      # WCAG compliance, focus management
  /js/
    interactions.js        # UI component behaviors
    progress-tracking.js   # Progress indicators
```

---

## Need Help?

- Design Spec: `docs/design-spec-macos-ui.md`
- User Story: `docs/user-story-macos-ui-redesign.md`
- Dev Story: `docs/stories/story-001-macos-ui-redesign.md`

---

*Built with ❤️ using macOS Ventura/Sonoma design principles*
