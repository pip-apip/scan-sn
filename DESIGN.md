---
name: Field Asset Scanner
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#464554'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#777586'
  outline-variant: '#c7c4d7'
  surface-tint: '#5148d7'
  primary: '#2a14b4'
  on-primary: '#ffffff'
  primary-container: '#4338ca'
  on-primary-container: '#c1beff'
  inverse-primary: '#c3c0ff'
  secondary: '#0058be'
  on-secondary: '#ffffff'
  secondary-container: '#2170e4'
  on-secondary-container: '#fefcff'
  tertiary: '#00442d'
  on-tertiary: '#ffffff'
  tertiary-container: '#005e40'
  on-tertiary-container: '#66daa8'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e3dfff'
  primary-fixed-dim: '#c3c0ff'
  on-primary-fixed: '#100069'
  on-primary-fixed-variant: '#372abf'
  secondary-fixed: '#d8e2ff'
  secondary-fixed-dim: '#adc6ff'
  on-secondary-fixed: '#001a42'
  on-secondary-fixed-variant: '#004395'
  tertiary-fixed: '#85f8c4'
  tertiary-fixed-dim: '#68dba9'
  on-tertiary-fixed: '#002114'
  on-tertiary-fixed-variant: '#005137'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  display:
    fontFamily: Space Grotesk
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.02em
  display-mobile:
    fontFamily: Space Grotesk
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Space Grotesk
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Space Grotesk
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Space Grotesk
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  headline-sm:
    fontFamily: Space Grotesk
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  label-code:
    fontFamily: JetBrains Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
    letterSpacing: 0.02em
  label-code-lg:
    fontFamily: JetBrains Mono
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 22px
    letterSpacing: 0.04em
  label-badge:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  touch-min: 48px
  gutter-mobile: 12px
  gutter-tablet: 16px
  gutter-desktop: 24px
  margin-mobile: 16px
  margin-tablet: 24px
  margin-desktop: 32px
  card-gap: 16px
  stack-compact: 8px
  stack-standard: 12px
  stack-spacious: 20px
---

## Brand & Style
This design system is tailored for logistics operators, supply chain auditors, and field technicians operating under harsh, unpredictable physical environments—ranging from sun-drenched tarmac loading docks to dimly lit warehouse aisles. The emotional signature must project uncompromised precision, rapid cognitive parsing, and tactical efficiency.

The visual direction merges **Precision Industrialism** with **Modern Enterprise Clarity**:
- Ultra-sharp spatial separation, high contrast edges, and zero visual ambiguity.
- Eliminates decorative blur, delicate pastels, or soft aesthetic gradients that wash out under direct solar glare.
- High physical affordance: large tactile touch targets engineered for single-handed use or gloved operation.
- Purpose-driven status signals delivering instant split-second recognition during barcode scanning and real-time inventory telemetry.

## Colors
The color hierarchy is optimized for daylight outdoor contrast, meeting strict WCAG AAA guidelines across core textual and telemetry displays.

### Primary & Accent Roles
- **Primary Brand Accent (`#4338ca`)**: Deep Indigo. Governs primary navigation states, active scanner viewfinders, dominant action triggers, and focus boundaries.
- **Secondary Action Accent (`#3b82f6`)**: Vibrant Royal Blue. Powers interactive highlights, secondary confirmations, scan beam telemetry, and selected filters.
- **Canvas Base (`#f8fafc`)**: Soft Slate. Anti-glare backdrop preventing eye fatigue caused by raw `#ffffff` under direct sunlight.
- **Surface Elevation (`#ffffff`)**: Pristine White. Reserved for active cards, input containers, modal sheets, and data tables to build crisp physical separation against the canvas.
- **Structural Borders (`#e2e8f0` default, `#cbd5e1` heavy)**: Clean structural outlines framing content without visual weight.

### High-Contrast Typography
- **Dominant Text / Titles (`#0f172a`)**: Deep Dark Slate delivering stark visual contrast against white cards and slate backgrounds.
- **Secondary / Supporting Copy (`#334155`)**: Slate Dark for metadata labels, serial numbers, timestamps, and supporting breadcrumbs.
- **Muted / Inactive Text (`#64748b`)**: Subtle slate used strictly for placeholder states and disabled indicators.

### Tactical Semantic Status
- **Verified / Available (`#059669`)**: Emerald Green. Paired with background `#ecfdf5` and border `#a7f3d0`. Applied to confirmed barcodes, accurate inventory matches, and online reader status.
- **In-Progress / Team Action (`#d97706`)**: Amber/Orange. Paired with background `#fffbeb` and border `#fde68a`. Denotes pending synch cycles, partial scans, and handoff tasks.
- **Lock Alerts / Discrepancy (`#e11d48`)**: Rose Red. Paired with background `#fff1f2` and border `#fecdd3`. Signals missing assets, locked cages, mismatch alarms, and battery criticals.

## Typography
The typographic architecture is split deliberately into three distinct functional roles:

1. **Space Grotesk (Display & Headlines)**: Brings an authoritative, geometric edge to module titles, shelf counts, and scanner headers. Ensures swift scanability when holding ruggedized tablets at arm's length.
2. **Inter (Body & System Instructions)**: Supplies unmatched neutrality and optimal glyph legibility at small sizes for notes, field documentation, and multi-step checklists.
3. **JetBrains Mono (Telemetry & Asset Identifiers)**: Applied strictly to asset IDs, RFID hex sequences, EAN/UPC barcodes, GPS coordinates, and bin locations. Ensures zero character ambiguity between `0` and `O`, or `1`, `I`, and `l`.

Text sizes scale automatically down below `768px` breakpoints, locking display scales to prevent accidental horizontal overflow during split-screen terminal workflows.

## Layout & Spacing
A responsive 12-column adaptive fluid grid anchors desktop and dock-mounted vehicle mounts, shifting to an 8-column layout on enterprise tablets and a robust single/dual column stack on handheld scanners (e.g., Zebra, Honeywell devices).

- **Touch-First Baseline**: Interactive targets (action bars, list entries, scan triggers) strictly enforce a minimum height and width of `48px` to guarantee zero miss-taps while wearing industrial work gloves.
- **Rhythm Model**: Built on a solid `4px`/`8px` geometric base. Card internals maintain `16px` of internal padding, stepping up to `24px` on desktop widescreen dashboards.
- **Screen Form-Factors**:
  - **Handheld / Phone (`< 640px`)**: Single column flow with pinned lower action docks (`56px` height) keeping scanner triggers in immediate thumb reach.
  - **Rugged Tablet (`640px - 1024px`)**: Split 2-pane view. Left pane (40%) renders scan feed/item list; right pane (60%) shows real-time detail maps and bin verification.
  - **Docked Desktop (`> 1024px`)**: Full 12-column analytical layout featuring persistent shelf matrices, bulk status meters, and live map telemetry.

## Elevation & Depth
This design system rejects ambient floating drop shadows in favor of **Crisp Outlined Surfaces** and **Tonal Stratification**. Diffuse multi-stop shadows degrade in outdoor light; precise structural borders retain absolute edge definition.

- **Level 0 (Background Canvas)**: Plain `#f8fafc`. Receded backdrop for raw workspace layout.
- **Level 1 (Card / Container Surface)**: Crisp `#ffffff` fill framed by a 1px structural line of `#e2e8f0`.
- **Level 2 (Active Focus / Grouping)**: `#ffffff` surface with a 1px perimeter of `#cbd5e1` and a hyper-subtle directional tactile key-shadow (`0 1px 2px rgba(15, 23, 42, 0.06)`).
- **Level 3 (Modals / Alert Sheets / Flyouts)**: Elevated white layer with a prominent perimeter border (`#cbd5e1`) and a high-definition structural offset shadow (`0 8px 16px -2px rgba(15, 23, 42, 0.12), 0 4px 6px -2px rgba(15, 23, 42, 0.04)`).
- **Level 4 (Scan Overlay Viewfinder)**: Transparent overlay with solid `#4338ca` laser bounding ticks and high-contrast `#ffffff` reticles.

## Shapes
A controlled, industrial **Soft (Level 1)** geometric curve is applied uniformly. Round pill geometries are rejected to maximize usable visual real estate within compact data-dense matrices.

- **Base Radius (`0.25rem` / `4px`)**: Applied to badges, metric chips, input fields, and inline code tags.
- **Container Radius (`0.5rem` / `8px`)**: Applied to standard inventory cards, shelf units, inspection sheets, and control panels.
- **Prominent Surfaces (`0.75rem` / `12px`)**: Restricted to primary scanning viewports, dialog sheets, and floating persistent action ribbons.
- **Border Hierarchy**: Standard surfaces leverage `1px` crisp boundaries. Active, selected, or focused modules step up to `2px` with zero perimeter feathering.

## Components

### Buttons
- **Primary Action**: Background `#4338ca`, label `#ffffff` in `Space Grotesk` Medium. Flat, high-contrast, minimum height `48px`. Hover: `#3730a3`. Active state displays a crisp inner keyline.
- **Secondary Action**: Background `#ffffff`, 1.5px border `#cbd5e1`, label `#0f172a`. Hover: `#f1f5f9`.
- **Destructive / Lock**: Background `#e11d48`, label `#ffffff`. Hover: `#be123c`.

### Status Badges & Chips
- **Verified / Available**: Background `#ecfdf5`, border `1px solid #a7f3d0`, text `#059669`. Prefixed by a filled 6px emerald circle.
- **In-Progress / Team**: Background `#fffbeb`, border `1px solid #fde68a`, text `#d97706`.
- **Discrepancy / Lock**: Background `#fff1f2`, border `1px solid #fecdd3`, text `#e11d48`.
- **Barcode / Hex Chip**: Monospace badge (`JetBrains Mono`), background `#f1f5f9`, border `#e2e8f0`, text `#0f172a`.

### Asset Cards
- White `#ffffff` surface, bounded by `#e2e8f0`.
- Top header: Monospace asset serial code accompanied by the appropriate semantic badge.
- Body: 2-column or 3-column micro-grid detailing SKU, aisle assignment, and scan timestamp.
- Bottom footer: High-contrast actionable button bar (e.g., "Verify Asset", "Flag Lock").

### Input & Scan Fields
- Background `#ffffff`, border `1.5px solid #cbd5e1`, text `#0f172a`, height `48px`.
- Focus state: Border switches directly to `#4338ca` with a solid `2px` outline ring in `#c7d2fe`.
- Barcode Scan Bar: Embedded right-aligned scan trigger icon in `#3b82f6` with haptic feedback confirmation.

### Selection Controls (Checkboxes & Radios)
- Heavy `2px` structural border `#94a3b8` on white ground.
- Checked: `#4338ca` solid fill with bright white `#ffffff` check glyph.
- Size: Explicitly `20px × 20px` with a wrapped `48px` invisible tap buffer.

### Telemetry Reticle (Scanner Viewfinder)
- Frame: 4-corner bracket overlays in `#4338ca` (`3px` line weight).
- Active targeting laser: Horizontal center pulse line in `#3b82f6`.
- Verified match feedback: Flash transition to solid `#059669` border.