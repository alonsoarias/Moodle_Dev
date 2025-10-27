# RESUMEN VISUAL - COURSEINDEX STYLES

## Matriz de Selectores por Componente

```
COURSE INDEX DRAWER STRUCTURE
============================

.course-index-section
├─ Background: White (#FFFFFF)
├─ Border: 1px solid (light gray)
├─ Border-radius: 6px
├─ Margin: 0 auto 0.75rem
├─ Width: 95%
│
├─ HEADER: .course-index-header
│  ├─ Background: Primary Blue (#365ba3)
│  ├─ Display: Flex
│  ├─ Height: Auto
│  ├─ Border-bottom: 1px solid rgba(white, 0.1)
│  │
│  ├─ LINK: .course-index-link
│  │  ├─ Color: White
│  │  ├─ Padding: 14px 18px
│  │  ├─ Font-size: 0.95rem
│  │  ├─ Font-weight: 500
│  │  ├─ Flex-grow: 1
│  │  ├─ Transition: all 0.2s ease
│  │  └─ Hover BG: rgba(white, 0.08)
│  │
│  └─ TOGGLE: .course-index-toggle
│     ├─ Background: Transparent
│     ├─ Border: None
│     ├─ Color: rgba(white, 0.95)
│     ├─ Min-width: 46px
│     ├─ Cursor: Pointer
│     ├─ Display: Flex
│     │
│     └─ ICON: i
│        ├─ Font-size: 0.875rem
│        ├─ Transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)
│        ├─ Rotate: 0deg (collapsed) → 180deg (expanded)
│
└─ CONTENT: .course-index-content
   ├─ Background: White
   ├─ Display: flex/none (toggle)
   │
   └─ ITEMS: .course-index-item
      ├─ Width: 95%
      ├─ Margin: 2px auto
      │
      └─ LINK: a
         ├─ Display: Flex
         ├─ Padding: 10px 16px
         ├─ Color: Text Gray (#5e5e5e)
         ├─ Border-left: 3px solid transparent
         ├─ Border-radius: 0 4px 4px 0
         ├─ Transition: all 0.2s ease
         │
         ├─ STATES:
         │  ├─ Default: Gray text
         │  ├─ :hover → Red text (#e21144), Yellow border (#ffb000)
         │  ├─ .active → Red text, Yellow border, Font-weight 500
         │  └─ .completed → Dimmed text, Blue icon
         │
         └─ ICON: i
            ├─ Font-size: 1rem
            ├─ Width: 20px
            ├─ Margin-right: 12px
            ├─ Color: rgba(text-gray, 0.7)
            └─ Transition: all 0.2s ease
```

---

## COLOR REFERENCE CARD

```
PRIMARY COLORS
==============
Primary Blue:      #365ba3  [Header, Nav, Icons Completed]
Primary Red:       #e21144  [Hover, Active, Actions]
Yellow:            #ffb000  [Active Border, Highlights]
White:             #FFFFFF  [Background, Text Light]
Text Gray:         #5e5e5e  [Default Text]

PROGRESS BAR SPECIFIC
====================
Title/Percentage:  #001f40  [Dark Blue]
Details Text:      #666666  [Medium Gray]
Progress BG:       rgba(102,102,102,0.15) [Subtle Gray]
Border Left:       #001f40  [Dark Blue]
Gradient:          linear-gradient(135deg, rgba(255,255,255,0.95), rgba(249,250,251,0.95))
```

---

## RESPONSIVE BREAKPOINTS

```
DESKTOP (≥769px)
================
.course-index-section:
  ├─ Width: 95%
  ├─ Margin-bottom: 0.75rem
  └─ Border-radius: 6px

.course-index-link:
  ├─ Padding: 14px 18px
  ├─ Font-size: 0.95rem

.course-index-item a:
  ├─ Padding: 10px 16px

.courseindex-progress-container:
  ├─ Padding: 1rem
  ├─ Margin: 0 0.5rem 1rem


TABLET/MOBILE (≤768px)
======================
.course-index-section:
  ├─ Width: 98%
  ├─ Margin-bottom: 0.5rem
  └─ Border-radius: 4px

.course-index-link:
  ├─ Padding: 12px 14px
  ├─ Font-size: 0.875rem

.course-index-toggle:
  ├─ Min-width: 40px
  ├─ Padding: 0 8px
  ├─ Margin-right: 4px

.course-index-item a:
  ├─ Padding: 10px 14px
  ├─ Font-size: 0.875rem

.courseindex-progress-container:
  ├─ Padding: 0.75rem
  ├─ Margin: 0 0.25rem 0.75rem

.progress-percentage:
  ├─ Font-size: 1.25rem (from 1.5rem)

.progress-title:
  ├─ Font-size: 0.8125rem (from 0.875rem)
```

---

## ANIMATION & TRANSITION TIMING

```
TRANSICIONES
============
Quick (0.2s):           Course Index Links & Items
Normal (0.25s):         Course Index Section
Smooth (0.3s):          Progress Container, Toggle Icon
Progress Bar (0.6s):    Width change (cubic-bezier)
Icon Rotation (0.3s):   Toggle icon rotation


ANIMACIONES
===========
Shimmer Effect:
  ├─ Duration: 2s infinite
  ├─ Applied to: .progress-bar::after
  ├─ Effect: translateX(-100% → 100%)
  └─ Background: linear-gradient (white shimmer)
```

---

## SHADOW & BORDER PATTERNS

```
ELEVACIONES (Box-Shadow)
=======================
Light Elevation:
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  
Medium Elevation:
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
  
High Elevation:
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);


BORDES
======
Default Section:         1px solid lighten($gray, 40%)
Active Section:          rgba($primary-blue, 0.3)
Header Border:           1px solid rgba(white, 0.1)
Item Left Border:        3px solid (transparent → yellow active)
Progress Container:      4px solid #001f40
```

---

## ICON STATES VISUAL

```
COURSE INDEX ITEMS
==================

DEFAULT STATE
└─ Icon Color: rgba(text-gray, 0.7)
   Background: White
   Text: Gray
   Border-left: Transparent

:HOVER STATE
└─ Icon Color: rgba(primary-red, 0.8)
   Background: rgb(226 17 68 / 0.05) [Red 5%]
   Text: primary-red (#e21144)
   Border-left: yellow (#ffb000)
   Transform: translateX(2px)

.ACTIVE STATE
└─ Icon Color: yellow (#ffb000)
   Background: rgba(primary-red, 0.08) [Red 8%]
   Text: primary-red (#e21144)
   Border-left: yellow (#ffb000)
   Font-weight: 500

.COMPLETED STATE
└─ Icon Color: rgba(primary-blue, 0.6)
   Text: rgba(text-gray, 0.75) [Dimmed]
   [Hover same as default but with dimmed start]
```

---

## TYPOGRAPHY SCALE

```
WEIGHTS & SIZES
===============
.progress-title:
  Font-weight: 700
  Font-size: 0.875rem
  Text-transform: uppercase
  Letter-spacing: 0.5px

.progress-percentage:
  Font-weight: 700
  Font-size: 1.5rem (responsive: 1.25rem mobile)

.course-index-link:
  Font-weight: 500
  Font-size: 0.95rem (responsive: 0.875rem mobile)
  Letter-spacing: 0.2px

.course-index-item a:
  Font-weight: 400 (default) → 500 (active)
  Font-size: Inherit (responsive: 0.875rem mobile)

Font Family: 'Roboto', sans-serif
```

---

## ACCESSIBILITY FEATURES

```
VISUAL INDICATORS
=================
✓ Color Contrast: WCAG AA compliant
✓ State Indicators: Border, Color, Icon changes
✓ Focus States: Included (same as :hover)
✓ Active States: Clear visual feedback

MOTION PREFERENCES
==================
@media (prefers-reduced-motion: reduce) {
  ✓ All transitions: none
  ✓ All transforms: none
  ✓ All animations: none
}

KEYBOARD NAVIGATION
===================
✓ Links: Natural tab order
✓ Buttons: Focus states
✓ Toggles: Keyboard accessible
```

---

## SPACING GRID

```
VERTICAL SPACING (rem)
======================
0.25rem:  Small item padding
0.5rem:   Gap between elements
0.75rem:  Section bottom margin (desktop)
1rem:     Container padding (progress bar)
2px:      Item margin/padding within lists

HORIZONTAL SPACING (px)
=======================
12px:     Icon space
16px:     Item link padding-right
18px:     Header link padding-right
6px:      Toggle margin-right (desktop)
8px:      Toggle padding (desktop)
```

---

## QUICK COPY-PASTE REFERENCE

```scss
// PRIMARY COLOR PALETTE
$primary-red: #e21144;
$secondary-red: #be0a37;
$primary-blue: #365ba3;
$yellow: #ffb000;
$white: #FFFFFF;
$gray: #5e5e5e;

// COURSE INDEX COLORS
$courseindex-header-bg: #365ba3;
$courseindex-active-text: #e21144;
$courseindex-active-border: #ffb000;
$courseindex-progress-dark: #001f40;

// TRANSITIONS
$transition-quick: all 0.2s ease;
$transition-normal: all 0.25s ease-in-out;
$transition-smooth: all 0.3s ease;
$transition-progress: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);

// SHADOW ELEVATION
$shadow-light: 0 2px 8px rgba(0, 0, 0, 0.08);
$shadow-medium: 0 3px 8px rgba(0, 0, 0, 0.05);
$shadow-high: 0 4px 12px rgba(0, 0, 0, 0.12);
```

---

## FILE LOCATIONS QUICK REFERENCE

```
Main Styles:
/home/user/Moodle_Dev/theme/compecer/scss/compecer.scss
  └─ Course Index: Lines 1441-1790
  └─ Nav Drawer: Lines 26-66, 429-488

Variables:
/home/user/Moodle_Dev/theme/compecer/scss/custom_variables.scss
  └─ Color Palette: Lines 1-28
  └─ Dimensions: Lines 36-39

Legacy Variables:
/home/user/Moodle_Dev/theme/compecer/scss/_variables.scss
  └─ Moove Theme Variables: Lines 1-104
```

