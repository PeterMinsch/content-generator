# SEO Generator Plugin - macOS-Inspired UI Specification

## Design System Overview

### Visual Language
**Inspiration:** macOS Ventura/Sonoma - rounded corners, subtle glassmorphism, vibrant but elegant, smooth animations

**Core Principles:**
- **Clarity First** - Every element serves a purpose
- **Spatial Breathing** - Generous white space, never cramped
- **Delightful Feedback** - Smooth transitions, clear status
- **Brand Integration** - Luxury feel with gold accents on professional charcoal

---

## Color Palette

### Primary Colors
```css
--gold: #CA9652;           /* Primary brand - accents, CTAs, highlights */
--gold-light: #E5C697;     /* Hover states, subtle highlights */
--gold-dark: #A67835;      /* Active states, shadows */
--charcoal: #272521;       /* Primary text, dark backgrounds */
--charcoal-soft: #3D3935;  /* Secondary backgrounds */
```

### System Colors (macOS-inspired grays)
```css
--gray-50: #FAFAF9;        /* Page backgrounds */
--gray-100: #F5F5F4;       /* Card backgrounds */
--gray-200: #E7E5E4;       /* Borders, dividers */
--gray-300: #D6D3D1;       /* Disabled states */
--gray-700: #44403C;       /* Secondary text */
--gray-900: #1C1917;       /* Headings */
```

### Semantic Colors
```css
--success: #34C759;        /* macOS green - completed, success */
--warning: #FF9500;        /* macOS orange - pending, warnings */
--error: #FF3B30;          /* macOS red - errors, failures */
--info: #007AFF;           /* macOS blue - info, links */
```

### Glassmorphism
```css
--glass-bg: rgba(255, 255, 255, 0.7);
--glass-border: rgba(255, 255, 255, 0.18);
--glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
backdrop-filter: blur(10px);
```

---

## Typography

### Font Stack
```css
--font-display: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-text: 'SF Pro Text', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-mono: 'SF Mono', 'Menlo', 'Monaco', monospace;
```

### Type Scale
```css
--text-xs: 11px;     /* Helper text, captions */
--text-sm: 13px;     /* Body small, labels */
--text-base: 15px;   /* Primary body text */
--text-lg: 17px;     /* Large body, subheadings */
--text-xl: 22px;     /* Section headings */
--text-2xl: 28px;    /* Page titles */
--text-3xl: 34px;    /* Hero headings */

--weight-regular: 400;
--weight-medium: 500;
--weight-semibold: 600;
--weight-bold: 700;
```

---

## Spacing System

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

## Component Library

### 1. Sidebar Navigation (macOS-style)

**Visual:**
```
┌─────────────────────┐
│ 📊 SEO Generator    │  ← Logo + title
├─────────────────────┤
│                     │
│ ● Dashboard         │  ← Active state (gold dot)
│   CSV Import        │
│   SEO Pages         │
│   Settings          │
│   🔗 Diagnostics    │
│                     │
└─────────────────────┘
```

**Specs:**
- Width: 260px fixed
- Background: `--gray-50` with subtle texture
- Active item: `--glass-bg` with gold left border (3px)
- Hover: `--gray-100` with smooth 150ms transition
- Icons: 16px, `--charcoal` (inactive) / `--gold` (active)
- Text: `--text-base`, `--weight-medium`
- Padding: 12px vertical between items
- Border radius: 8px for hover/active states

---

### 2. CSV Import Page

#### Layout Structure
```
┌─────────────────────────────────────────────────────────┐
│  CSV Import                                    [? Help] │  ← Header
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  📄 Drop CSV file here or click to browse      │   │  ← Drop Zone
│  │                                                 │   │
│  │  Supported format: .csv (max 50MB)            │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  Import Settings                                        │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ⚙️  Generation Mode                             │   │  ← Card 1
│  │                                                 │   │
│  │  ○ Drafts Only                                 │   │
│  │  ● Auto-Generate Content  ⭐                   │   │
│  │                                                 │   │
│  │  ☑️ Check for duplicates                       │   │
│  │  ☑️ Download images from URLs                  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │ 🗂  Column Mapping                              │   │  ← Card 2
│  │                                                 │   │
│  │  CSV Column        →  Plugin Field             │   │
│  │  [page_title    ▾]  →  Page Title              │   │
│  │  [focus_keyword ▾]  →  Focus Keyword           │   │
│  │  [image_url     ▾]  →  Image URL               │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│             [Cancel]  [Import CSV ✨]                  │  ← Actions
└─────────────────────────────────────────────────────────┘
```

#### Component Specs:

**Drop Zone**
- Height: 200px
- Border: 2px dashed `--gray-300`
- Border radius: 12px
- Background: `--gray-50`
- Hover: Border → `--gold`, Background → `--glass-bg`
- Active drag: Border → `--gold` (solid), glow effect
- Icon: 48px, `--gray-400`
- Transition: all 200ms ease

**Cards**
- Background: white
- Border: 1px solid `--gray-200`
- Border radius: 12px
- Padding: 24px
- Shadow: `0 1px 3px rgba(0,0,0,0.05)`
- Hover: Shadow → `0 4px 12px rgba(0,0,0,0.08)`, lift 2px
- Gap between cards: 16px

**Primary Button (Import CSV)**
- Background: `linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%)`
- Color: white
- Padding: 12px 24px
- Border radius: 8px
- Font: `--text-base`, `--weight-semibold`
- Shadow: `0 2px 8px rgba(202, 150, 82, 0.3)`
- Hover: Lift 2px, shadow → `0 4px 12px rgba(202, 150, 82, 0.4)`
- Active: Scale 0.98
- Transition: all 150ms ease

**Secondary Button (Cancel)**
- Background: transparent
- Color: `--gray-700`
- Border: 1px solid `--gray-300`
- Hover: Background → `--gray-100`

---

### 3. SEO Pages List

#### Layout Structure
```
┌───────────────────────────────────────────────────────────────┐
│  SEO Pages                           🔍 [Search...] [+New]   │  ← Toolbar
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  Filters: [All ▾] [Status ▾] [Topic ▾]           142 pages  │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ ✨ Diamond Engagement Rings Guide          [⋯]         │ │  ← Row 1
│  │ ● Pending  │  Generated 2 min ago  │  15 images        │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ 💎 Wide Band Diamond Rings                  [⋯]         │ │  ← Row 2
│  │ ✓ Published  │  Updated 1 hour ago  │  8 images       │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │ 🔷 Vintage Diamond Bands                    [⋯]         │ │  ← Row 3
│  │ ⚠ Draft  │  Created 3 hours ago  │  No images         │ │
│  └─────────────────────────────────────────────────────────┘ │
│                                                               │
│                        ← 1 2 3 ... 15 →                      │
└───────────────────────────────────────────────────────────────┘
```

#### Component Specs:

**Search Bar**
- Width: 300px
- Height: 36px
- Background: `--glass-bg`
- Border: 1px solid `--glass-border`
- Border radius: 18px (pill shape)
- Padding: 0 16px 0 36px (room for icon)
- Icon: Magnifying glass, 16px, `--gray-500`, positioned left
- Placeholder: `--gray-400`
- Focus: Border → `--gold`, shadow → `0 0 0 3px rgba(202,150,82,0.1)`

**List Rows**
- Background: white
- Border: 1px solid `--gray-200`
- Border radius: 10px
- Padding: 16px 20px
- Gap: 12px between rows
- Hover: Background → `--gray-50`, lift 1px
- Cursor: pointer
- Transition: all 120ms ease

**Status Badges**
- Published: `--success` background, white text
- Pending: `--warning` background, white text
- Draft: `--gray-300` background, `--gray-700` text
- Failed: `--error` background, white text
- Padding: 4px 10px
- Border radius: 6px
- Font: `--text-xs`, `--weight-semibold`
- Display: inline-flex with dot indicator

**Action Menu (⋯)**
- Size: 32px × 32px
- Border radius: 6px
- Hover: Background → `--gray-100`
- Active: Background → `--gray-200`
- Menu dropdown: macOS-style with blur, shadow, smooth animation

---

### 4. Progress Indicators (AI Generation)

**Progress Card (during generation)**
```
┌─────────────────────────────────────────┐
│  🤖 Generating Content...               │
│                                         │
│  ▓▓▓▓▓▓▓▓▓▓░░░░░░░░  60%              │
│                                         │
│  Hero Section ✓                         │
│  SERP Answer ✓                          │
│  Product Criteria ⟳ Generating...      │
│  Materials ⋯ Queued                     │
│                                         │
│  Est. completion: 2 min 30 sec          │
└─────────────────────────────────────────┘
```

**Specs:**
- Background: `--glass-bg`
- Backdrop blur: 10px
- Border: 1px solid `--glass-border`
- Border radius: 12px
- Padding: 20px
- Shadow: `--glass-shadow`
- Progress bar: Height 8px, `--gold` fill, `--gray-200` background
- Animated pulse on active items
- Checkmarks: `--success` color
- Spinner: `--gold` color

---

### 5. Buttons

**Primary Button**
```css
background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
color: white;
padding: 12px 24px;
border-radius: 8px;
font-size: var(--text-base);
font-weight: var(--weight-semibold);
box-shadow: 0 2px 8px rgba(202, 150, 82, 0.3);
transition: all 150ms ease;

&:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(202, 150, 82, 0.4);
}

&:active {
  transform: scale(0.98);
}
```

**Secondary Button**
```css
background: transparent;
color: var(--gray-700);
border: 1px solid var(--gray-300);
padding: 12px 24px;
border-radius: 8px;
transition: all 150ms ease;

&:hover {
  background: var(--gray-100);
}
```

**Danger Button**
```css
background: var(--error);
color: white;
/* Same padding, radius, transitions as primary */
```

---

## Animation Specifications

### Micro-interactions
```css
/* Button hover lift */
@keyframes lift {
  from { transform: translateY(0); }
  to { transform: translateY(-2px); }
}

/* Success ripple */
@keyframes ripple {
  0% { transform: scale(0); opacity: 1; }
  100% { transform: scale(2); opacity: 0; }
}

/* Loading spinner */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Fade in */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Pulse (for active generation items) */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}
```

### Transition Timing
- Instant feedback: 80ms
- Standard interactions: 150ms
- Panel/modal open: 250ms
- Page transitions: 300ms
- Easing: `cubic-bezier(0.4, 0.0, 0.2, 1)` (macOS system)

---

## Responsive Breakpoints

```css
--mobile: 640px;
--tablet: 768px;
--desktop: 1024px;
--wide: 1280px;
```

**Mobile behavior:**
- Sidebar collapses to hamburger menu
- Cards stack vertically
- Tables become swipeable cards
- Touch targets minimum 44px

---

## Accessibility

- WCAG 2.1 AA compliance
- Color contrast ratios: Minimum 4.5:1 for text
- Focus indicators: 2px gold outline with 2px offset
- Keyboard navigation: Full support with visible focus
- Screen reader: ARIA labels on all interactive elements
- Reduced motion: Respect `prefers-reduced-motion`

---

## Implementation Priority

**Phase 1: CSV Import Page** (Week 1)
- Drop zone component
- Settings cards
- Primary action button
- Upload progress indicator

**Phase 2: SEO Pages List** (Week 2)
- List layout with cards
- Search and filters
- Status badges
- Action menus

**Phase 3: Global Components** (Week 3)
- Sidebar navigation
- Header toolbar
- Notification system
- Loading states

---

## Design Tokens (CSS Variables)

```css
:root {
  /* Brand Colors */
  --gold: #CA9652;
  --gold-light: #E5C697;
  --gold-dark: #A67835;
  --charcoal: #272521;
  --charcoal-soft: #3D3935;

  /* System Grays */
  --gray-50: #FAFAF9;
  --gray-100: #F5F5F4;
  --gray-200: #E7E5E4;
  --gray-300: #D6D3D1;
  --gray-700: #44403C;
  --gray-900: #1C1917;

  /* Semantic Colors */
  --success: #34C759;
  --warning: #FF9500;
  --error: #FF3B30;
  --info: #007AFF;

  /* Glassmorphism */
  --glass-bg: rgba(255, 255, 255, 0.7);
  --glass-border: rgba(255, 255, 255, 0.18);
  --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);

  /* Typography */
  --font-display: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-text: 'SF Pro Text', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-mono: 'SF Mono', 'Menlo', 'Monaco', monospace;

  --text-xs: 11px;
  --text-sm: 13px;
  --text-base: 15px;
  --text-lg: 17px;
  --text-xl: 22px;
  --text-2xl: 28px;
  --text-3xl: 34px;

  --weight-regular: 400;
  --weight-medium: 500;
  --weight-semibold: 600;
  --weight-bold: 700;

  /* Spacing */
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

  /* Borders */
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 18px;

  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
  --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
  --shadow-gold: 0 2px 8px rgba(202, 150, 82, 0.3);
  --shadow-gold-lg: 0 4px 12px rgba(202, 150, 82, 0.4);

  /* Transitions */
  --transition-fast: 80ms cubic-bezier(0.4, 0.0, 0.2, 1);
  --transition-base: 150ms cubic-bezier(0.4, 0.0, 0.2, 1);
  --transition-slow: 250ms cubic-bezier(0.4, 0.0, 0.2, 1);
  --transition-page: 300ms cubic-bezier(0.4, 0.0, 0.2, 1);
}
```

---

This spec gives you a complete macOS Ventura/Sonoma aesthetic with your luxury brand colors! 🎨
