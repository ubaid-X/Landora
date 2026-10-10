

---
# Frontend Overview

---

## 1. FOLDER STRUCTURE

```
Property Dealing/
│
├── frontend/
│   │
│   ├── shared/
│   │   ├── css/
│   │   │   └── style.css              (design system + all pages)
│   │   ├── js/
│   │   │   ├── api.js                 (fetch helpers, formatters)
│   │   │   └── partials.js            (injects navbar + footer)
│   │   └── partials/
│   │       ├── navbar-public.html
│   │       ├── navbar-dealer.html
│   │       ├── navbar-admin.html
│   │       └── footer.html
│   │
│   ├── public/                        (public pages — no login)
│   │   ├── index.html                 (home + property listing)
│   │   ├── property-details.html      (single property)
│   │   ├── dealer-register.html       (dealer signup)
│   │   ├── terms.html                 (Terms & Conditions)
│   │   └── privacy.html               (Privacy Policy)
│   │
│   ├── dealer/                        (dealer pages — login required)
│   │   ├── login.html                 (login + forgot password modal)
│   │   ├── dashboard.html             (overview + recent properties)
│   │   ├── profile.html               (account info)
│   │   ├── my-properties.html         (own listings management)
│   │   ├── add-property.html          (create new listing + map picker)
│   │   ├── edit-property.html         (edit listing + image manager)
│   │   └── change-password.html       (change own password)
│   │
│   └── admin/                         (admin pages — login required)
│       ├── login.html                 (private admin login)
│       ├── dashboard.html             (stats + quick actions)
│       ├── profile.html               (account info)
│       ├── dealers.html               (manage dealers)
│       ├── properties.html            (manage properties)
│       └── change-password.html       (change own password)
│
└── BACKEND.md / FRONTEND.md
```

---

## 2. HOW THE FRONTEND WORKS (MAP)

```
Browser loads a page (e.g. /frontend/dealer/dashboard.html)
    │
    ├── <head> loads:
    │     1. Bootstrap 5.3 CSS (from CDN)
    │     2. Font Awesome 6 CSS (from CDN)
    │     3. style.css (our design system)  ← MUST be last
    │
    ├── <body> loads in order:
    │     1. <div id="navbar" data-navbar="dealer">    ← empty slot
    │     2. <main>...page content...</main>
    │     3. <div id="footer">                         ← empty slot
    │
    └── <script> loads at the bottom:
          1. Bootstrap JS (bundle with Popper)
          2. Leaflet JS (only on map pages)
          3. api.js      ← API helpers
          4. partials.js ← injects navbar + footer
          5. <script>    ← page-specific logic
```

**Runtime flow for a protected page (e.g. dealer dashboard):**

```
1. Page loads
   ↓
2. partials.js fetches navbar-dealer.html + footer.html
   and injects them into #navbar and #footer
   ↓
3. Page script calls API.requireAuth('dealer')
   ↓
4. api.js sends GET /backend/api/dealer/my-profile.php with credentials
   ↓
5a. If 401 → API redirects to /frontend/dealer/login.html, execution stops
5b. If 200 → script continues
   ↓
6. Page script fetches data (properties, profile, etc.)
   ↓
7. Renders DOM: HTML strings via innerHTML, uses API.escapeHTML() for safety
   ↓
8. User interacts → clicks save / toggle / delete
   ↓
9. Page script sends POST with JSON → backend
   ↓
10. Response shown as alert or toast; DOM updated; user redirected if needed
```

**Runtime flow for a public page (e.g. home listing):**

```
1. Page loads
   ↓
2. partials.js injects public navbar + footer
   ↓
3. Page script calls API.get('/public/list-properties.php')
   ↓
4. Renders property cards grid
   ↓
5. Also calls API.get('/public/list-locations.php') to fill <datalist>
   ↓
6. User types search → reloads page with ?q=&type=&status=
   ↓
7. Page re-renders with filtered results
```

---

## 3. FILE-BY-FILE PURPOSE

### 3.1 Shared — CSS

| File | Purpose |
|---|---|
| **style.css** | Single stylesheet for the whole platform. Contains: design tokens (CSS variables), typography, buttons, badges, forms, cards, skeleton loaders, alerts, navbars, footer, page surface, tables, trust strip, image fallback, utilities, Bootstrap overrides, all page-specific blocks (hero, property detail, dealer dashboard, admin dashboard, forms, auth, modals, etc.) |

### 3.2 Shared — JS

| File | Purpose |
|---|---|
| **api.js** | Global `API` object with helpers: `base()`, `get()`, `post()`, `postForm()`, `getQuery()`, `redirect()`, `escapeHTML()`, `formatPrice()`, `imageUrl()`, `renderImageFallback()`, `showToast()`, `requireAuth(role)`, `logout(role)`, `setFieldError()`, `clearFieldError()`, `clearFormErrors()`, and `PAGES` constants. |
| **partials.js** | Fetches navbar + footer HTML partials and injects them into `#navbar` and `#footer` slots on page load. Also highlights the active nav link and wires the Logout button. |

### 3.3 Shared — Partials

| File | Purpose |
|---|---|
| **navbar-public.html** | Top navbar for public pages: logo + Home + Properties + phone + Dealer Login + Register as Dealer button |
| **navbar-dealer.html** | Top navbar for dealer pages: logo + Dashboard + My Properties + Profile + Logout |
| **navbar-admin.html** | Top navbar for admin pages: logo with ADMIN badge + Dashboard + Dealers + Properties + Profile + Logout |
| **footer.html** | Shared footer: brand + quick links (Home, Properties, Register as Dealer, Terms, Privacy) + contact (phone, email) + copyright |

### 3.4 Public Pages

| File | Purpose |
|---|---|
| **index.html** | Home page. Hero with search bar, filter dropdowns, latest properties grid, trust strip, footer. Handles loading/empty/error states. Reads `?q=&type=&status=` from URL. |
| **property-details.html** | Single property page. Gallery with thumbnails + lightbox, price card with contact + call + WhatsApp + share buttons, details table, dealer block. Reads `?property_id=X`. Sends `?count_view=1` once per browser tab. |
| **dealer-register.html** | Dealer signup form (11 fields). Shows validation errors in red. On success, shows "pending approval" message and redirects to dealer login. |
| **terms.html** | Static Terms & Conditions page. |
| **privacy.html** | Static Privacy Policy page. |

### 3.5 Dealer Pages

| File | Purpose |
|---|---|
| **login.html** | Dealer login form with show/hide password toggle. Handles 5 response states: `approved` (redirect), `pending` (amber card), `rejected` (red card), `invalid_credentials` (inline error), `not_registered` (register prompt). Has "Forgot Password?" link opening a contact modal. |
| **dashboard.html** | Welcome banner, 3 stat cards (Total / Sold / Unsold), Quick Actions, Recent Properties (up to 3 as compact cards). |
| **profile.html** | Business info card (username, email, business name, owner, phone, WhatsApp, address, province, city) + verification status card with "Change Password" button. |
| **my-properties.html** | List of dealer's own properties. Desktop: table with image/title/type/price/area/location/views/status/actions. Mobile: stacked cards. Actions: Edit, Toggle Sold/Unsold. No delete (business rule). |
| **add-property.html** | 3-section form: Property Info, Location (with Leaflet map picker), Images (drag-drop + previews). Submits property first, then uploads images one by one. Shows progress on the button. |
| **edit-property.html** | Same form structure as Add, prefilled. Shows existing images with X to remove (immediate backend delete) and a queue for new images. |
| **change-password.html** | Form to change own password. Requires current password. Red feedback for wrong current password, weak new password, mismatch. |

### 3.6 Admin Pages

| File | Purpose |
|---|---|
| **login.html** | Private admin login. Centered card with shield icon. No public navbar. |
| **dashboard.html** | 5 stat cards (Total/Pending/Approved/Rejected Dealers + Total Properties) + 2 quick action cards. |
| **profile.html** | Admin account info card + "Change Password" button. |
| **dealers.html** | Manage dealers. Filter tabs (All / Pending / Approved / Rejected) + table + pagination. Actions per row: Approve, Reject, Reset Password, Delete. Modals for reset + delete confirmation. |
| **properties.html** | Manage properties. Filter tabs (All / Unsold / Sold). Table with expandable rows revealing admin-only fields. Delete confirmation modal. |
| **change-password.html** | Same as dealer's but for admin session. |

---

## 4. KEY TERMS — SIMPLE EXPLANATIONS

| Term | Meaning |
|---|---|
| **HTML** | The structure of a page. Defines headings, forms, buttons, divs, etc. |
| **CSS** | The styling of the page. Colors, fonts, spacing, layout. |
| **JavaScript (JS)** | The behavior of the page. Fetches data, updates the page, handles clicks. |
| **Bootstrap 5.3** | A free CSS framework. Provides grid system, buttons, forms, modals, tabs, and utilities. Loaded from CDN. |
| **Font Awesome 6** | A free icon library. Provides all icons used (house, user, phone, etc.). Loaded from CDN. |
| **CDN** | Content Delivery Network. A remote server that hosts popular libraries so you don't have to bundle them. |
| **DOM** | Document Object Model. The browser's representation of the HTML as a tree of objects. JS modifies the DOM to update the page. |
| **`document.getElementById()`** | JS method to find an element by its `id` attribute. |
| **`document.querySelector()` / `querySelectorAll()`** | JS methods to find elements by CSS selector. |
| **`innerHTML`** | Property that sets or reads the HTML content of an element. Used to inject rendered HTML. |
| **`addEventListener()`** | JS method that attaches a function to run when an event (click, submit, input) happens. |
| **`fetch()`** | Built-in browser function that sends an HTTP request to a URL and returns a Promise. |
| **Promise** | A future value. `fetch()` returns one; `await` waits for it. |
| **`async` / `await`** | JS syntax that lets you write asynchronous code like synchronous. |
| **`fetch(url, { credentials: 'include' })`** | Sends the session cookie along with the request. Required for protected endpoints. |
| **FormData** | JS object for sending form fields + files as a single multipart request. Used for image uploads. |
| **Session storage** | Browser-side storage per tab. Used here to deduplicate view counts. Cleared when the tab closes. |
| **URLSearchParams** | JS class for building/reading URL query strings. |
| **Template literal** | JS strings using backticks + `${}` for interpolation. Not used here (we use string concatenation for readability). |
| **Escape HTML** | Converting `<`, `>`, `"`, `&` to safe entities before injecting into HTML. Prevents XSS. |
| **XSS** | Cross-Site Scripting. A security attack where malicious input runs as JS. Prevented by `API.escapeHTML()`. |
| **Design token** | A CSS variable holding a design value. Example: `--brand-primary: #006AFF`. |
| **CSS variable** | Custom property defined with `--name: value;` and read with `var(--name)`. |
| **Skeleton loader** | A placeholder grey block shown while data loads. Provides perceived speed. |
| **Empty state** | What the user sees when there is no data. Example: "No properties yet." |
| **Error state** | What the user sees when something fails. Example: red alert with API message. |
| **Form validation** | Client-side checks before submission. Example: "Title is required." |
| **Toast** | Small popup message at the top-right. Auto-dismisses after 3 seconds. |
| **Modal** | A dialog that appears on top of the page. Used for confirmations, reset password, map picker. |
| **Lightbox** | A full-screen image viewer with navigation. Powered by GLightbox (external library). |
| **Responsive** | Layout adapts to screen size. We support 3 breakpoints: 375px, 768px, 1440px. |
| **Breakpoint** | A width where the layout changes. Bootstrap uses: sm 576, md 768, lg 992, xl 1200, xxl 1400. |
| **Leaflet** | Free open-source JS library for interactive maps. Loaded from CDN. |
| **OpenStreetMap** | Free map tile provider. Supplies the map imagery shown by Leaflet. |
| **Nominatim** | Free OpenStreetMap geocoding service. Converts addresses to coordinates and coordinates to addresses. |
| **Reverse geocoding** | Coordinate → address. Used when the user clicks a spot on the map. |
| **GLightbox** | Free open-source lightbox library. Handles image galleries, keyboard, and touch. |
| **`data-*` attribute** | Custom data attached to an HTML element. Example: `data-property-id="3"`. |
| **Partial** | A reusable HTML fragment loaded into a page. Used here for navbar and footer. |
| **CDN link order** | In `<head>`, load Bootstrap → Font Awesome → our style.css last so ours wins. |
| **Script order** | At end of `<body>`, load Bootstrap JS → Leaflet (if needed) → api.js → partials.js → page script. |
| **Primary key** (from user's view) | Not visible. But pages use `property_id`, `dealer_id` from URLs and API responses to identify items. |
| **Query string** | Parameters added to a URL after `?`. Example: `?property_id=3`. |

---

## 5. IMPORTANT CONCEPTS

### 5.1 Design System — Why Everything Looks Consistent

Every page uses the same CSS variables from `style.css`:

```
Colors      → --brand-primary, --brand-surface, --status-success, etc.
Spacing     → multiples of 8px (--space-sm, --space-md, --space-lg)
Radius      → --radius-btn (8px), --radius-card (12px), --radius-pill
Typography  → same font, same sizes, same weights everywhere
Buttons     → 4 variants: primary, outline, danger, ghost
Badges      → 5 variants: approved, pending, rejected, sold, unsold
```

Because every page reads from the same tokens, changing one variable updates the whole platform.

### 5.2 Partials Injection — Why It Exists

Instead of copy-pasting the navbar into 14 files, we have:

- One `navbar-public.html`
- One `navbar-dealer.html`
- One `navbar-admin.html`
- One `footer.html`

Each page declares which navbar it wants:

```html
<div id="navbar" data-navbar="dealer"></div>
```

`partials.js` fetches the correct file and injects it. Change the navbar once → all 7 dealer pages update.

### 5.3 Authentication — How Protected Pages Stay Safe

Every protected page calls `API.requireAuth('dealer')` (or `'admin'`) on load. This does one thing:

```
GET /backend/api/dealer/my-profile.php (with credentials)
   ↓
If 401 → API.redirect('/frontend/dealer/login.html')
If 200 → page continues
```

**Why this matters:** the frontend check is a UX convenience, not security. The real security is the middleware on the backend. Even if someone bypassed the frontend redirect, every protected endpoint would still return 401.

### 5.4 Form Validation — Two Layers

```
Layer 1 (frontend):  Prevent bad submissions. Show red errors.
Layer 2 (backend):   Independent check. Refuses invalid data regardless of frontend.
```

Never trust the frontend alone. The backend validates every field again. Frontend validation is UX; backend validation is security.

### 5.5 Price Formatting

Backend sends: `"12000000.00"` (a string)
Frontend shows: `Rs. 1,20,00,000` (Pakistani lakh/crore system)

`API.formatPrice()` handles this. Uses `Intl.NumberFormat('en-IN')`. Strips the `.00`.

### 5.6 Image Fallback

When a property has no images:

```
<div class="img-fallback">
   <i class="fa-regular fa-image"></i>
   <span>No image available</span>
</div>
```

Rendered by `API.renderImageFallback(container, type)` or inline in card builders. Maintains 16:9 aspect ratio so layout stays stable.

### 5.7 Skeleton Loading

While data is being fetched, we show grey pulsing blocks in place of content. When data arrives, we swap them out. Why this matters: perceived performance. The user sees structure immediately instead of a blank page.

### 5.8 Empty States vs Error States

| Situation | What we show |
|---|---|
| Data loaded, but count = 0 | "No properties yet." (friendly, expected) |
| Search returned 0 | "No properties found for your search. Try a different city or clear filters." + Clear Filters button |
| API returned error | Red alert with `res.message` |
| User not logged in | Redirect to login page |

Each state has its own UI treatment. Users always know what happened.

### 5.9 Map Picker (Add/Edit Property) — Free Stack

```
Leaflet     → interactive map library
OpenStreetMap → free map tiles (the actual images)
Nominatim   → search + reverse geocoding (free)
```

The user can:
- Search a location → map flies there, pin drops
- Click anywhere on the map → pin drops
- Drag the pin → address updates
- Click Confirm → Google Maps URL saved to the input field

No API key, no billing account. Works forever free.

### 5.10 Image Upload Flow (Add/Edit Property)

```
1. Dealer picks files (or drags them)
2. Frontend validates: type (jpg/png/webp) and size (≤ 5 MB each)
3. Previews shown
4. On submit:
   a. First: property fields saved via POST /dealer/add-property.php
   b. Then: loop through each file and POST to /dealer/upload-property-image.php
   c. Backend caps at 10 images total per property
5. Success: redirect to My Properties
```

If the property save fails, images are never uploaded. This prevents orphan files.

### 5.11 View Counting — Why It's Tricky

Naive approach: increment on every `get-property.php` call. Problem: reloads and browser prefetch inflate the count.

Our approach:

```
Frontend:
   sessionStorage flag "viewed_property_X" → if absent, send ?count_view=1
Backend:
   increments only when ?count_view=1 is present
```

Result: one view per browser tab per property. Reloads don't inflate. Closing the tab clears the flag, so a genuine new visit counts.

### 5.12 Responsive Breakpoints

| Width | What changes |
|---|---|
| **1440px** | Full desktop. 4-column property grid. Full navbars. Two-column layouts |
| **768px** | Tablet. 2-column grid. Hamburger menu. Tables hide some columns |
| **375px** | Mobile. 1-column grid. Hamburger menu. Stacked cards. Full-width buttons |

Every page is designed at all three widths.

### 5.13 Escape HTML Before Injecting

Every place we render user-provided text (title, description, business name), we call:

```js
API.escapeHTML(p.title)
```

This converts `<script>` to `&lt;script&gt;`. Prevents XSS attacks. Every card, table row, and modal does this.

### 5.14 CDN vs Local Files

We load Bootstrap, Font Awesome, Leaflet, and GLightbox from CDNs. Reasons:

- Faster (browsers may have them cached from other sites)
- No files to bundle or update
- Zero hosting cost

Our own `style.css` and `api.js` are local because they change with the project.

### 5.15 Console Errors — Where to Look When Something Breaks

When a page misbehaves:

1. Open DevTools (F12)
2. Go to **Console** tab → look for red errors
3. Go to **Network** tab → look for failed requests (red status codes)
4. Click a failed request → **Response** tab → see the JSON error message

Most frontend bugs are either: (a) a wrong API URL, (b) a 401 redirect loop, or (c) a JavaScript error in the console.

---

## 6. QUICK REFERENCE — SHARED FILES

| File | Loaded by | Contains |
|---|---|---|
| `/frontend/shared/css/style.css` | Every page | Design system + all page styles |
| `/frontend/shared/js/api.js` | Every page | `API` object with all helpers |
| `/frontend/shared/js/partials.js` | Every page | Navbar + footer injector |
| `/frontend/shared/partials/navbar-public.html` | Public pages | Public navbar markup |
| `/frontend/shared/partials/navbar-dealer.html` | Dealer pages | Dealer navbar markup |
| `/frontend/shared/partials/navbar-admin.html` | Admin pages | Admin navbar markup |
| `/frontend/shared/partials/footer.html` | Every page | Footer markup |

**CDN libraries loaded per page:**

| Library | Version | Used for |
|---|---|---|
| Bootstrap CSS | 5.3.3 | Grid, forms, buttons, modals |
| Bootstrap JS | 5.3.3 | Navbar toggle, modals, dropdowns |
| Font Awesome | 6.5.2 | All icons |
| Leaflet CSS + JS | 1.9.4 | Map picker (Add/Edit property) |
| GLightbox CSS + JS | latest | Image lightbox (Property details) |

---

## 7. PAGE INVENTORY — 14 PAGES

### 7.1 Public (5)

| Page | Auth | Loads API |
|---|---|---|
| `public/index.html` | None | `list-properties.php`, `list-locations.php` |
| `public/property-details.html` | None | `get-property.php` (with `?count_view=1`) |
| `public/dealer-register.html` | None | `register-dealer.php` (POST) |
| `public/terms.html` | None | None (static) |
| `public/privacy.html` | None | None (static) |

### 7.2 Dealer (7)

| Page | Auth | Loads API |
|---|---|---|
| `dealer/login.html` | None | `login-dealer.php` (POST) |
| `dealer/dashboard.html` | Dealer | `my-profile.php`, `list-my-properties.php` |
| `dealer/profile.html` | Dealer | `my-profile.php` |
| `dealer/my-properties.html` | Dealer | `list-my-properties.php`, `toggle-property-status.php` |
| `dealer/add-property.html` | Dealer | `add-property.php`, `upload-property-image.php` |
| `dealer/edit-property.html` | Dealer | `list-my-properties.php`, `edit-property.php`, `upload-property-image.php`, `delete-property-image.php` |
| `dealer/change-password.html` | Dealer | `change-password.php` |

### 7.3 Admin (6)

| Page | Auth | Loads API |
|---|---|---|
| `admin/login.html` | None | `login-admin.php` (POST) |
| `admin/dashboard.html` | Admin | `my-profile.php`, `list-dealers.php`, `list-properties.php` |
| `admin/profile.html` | Admin | `my-profile.php` |
| `admin/dealers.html` | Admin | `list-dealers.php`, `update-dealer-status.php`, `reset-dealer-password.php`, `delete-dealer.php` |
| `admin/properties.html` | Admin | `list-properties.php`, `delete-property.php` |
| `admin/change-password.html` | Admin | `change-password.php` |

---

## 8. API.JS — PUBLIC INTERFACE

The `API` object is global. Every page uses it.

| Method | Purpose |
|---|---|
| `API.get(path)` | GET request with credentials. Returns parsed JSON. |
| `API.post(path, body)` | POST JSON request with credentials. |
| `API.postForm(path, formData)` | POST multipart (file upload). |
| `API.getQuery(name)` | Read `?param=` from current URL. |
| `API.redirect(path)` | Navigate the browser. |
| `API.escapeHTML(str)` | Sanitize text before HTML injection. |
| `API.formatPrice(value)` | `"12000000.00"` → `"Rs. 1,20,00,000"` |
| `API.imageUrl(path)` | `"uploads/..."` → `"/uploads/..."` |
| `API.renderImageFallback(el, type)` | Draw placeholder into a container. |
| `API.showToast(msg, type)` | Popup feedback (`success`, `error`, `warning`, `info`). |
| `API.requireAuth(role)` | Check session. Redirect to login if invalid. Returns profile JSON. |
| `API.logout(role)` | POST `/auth/logout.php` then redirect. |
| `API.setFieldError(el, msg)` | Add `.is-invalid` + message below input. |
| `API.clearFieldError(el)` | Remove error state. |
| `API.clearFormErrors(form)` | Reset all errors in a form. |
| `API.PAGES` | Constants: `PUBLIC_HOME`, `DEALER_LOGIN`, `ADMIN_LOGIN`. |

---

## 9. STYLE.CSS — WHAT'S INSIDE

The file is organized into numbered sections. If you need to find where a style lives:

| Section | Contains |
|---|---|
| 1 | Design tokens (CSS variables) |
| 2 | Base reset + body |
| 3 | Typography (`.hero-display`, `.page-title`, etc.) |
| 4 | Buttons (`.btn-primary`, `.btn-outline-primary`, `.btn-danger`, `.btn-ghost`) |
| 5 | Badges (`.badge-approved`, `.badge-pending`, `.badge-rejected`, `.badge-sold`, `.badge-unsold`) |
| 6 | Forms (Bootstrap overrides, `.invalid-feedback`, `.form-text`) |
| 7 | Cards (`.card-custom`) |
| 8 | Property card (`.property-card`) |
| 9 | Stat card (`.stat-card`) |
| 10 | Skeleton loading (`.skeleton`, `.skeleton-card`) |
| 11 | Alerts (`.alert-success`, `.alert-warning`, `.alert-danger`, `.alert-info`) |
| 12 | Navbars (`.site-navbar`, `.navbar-brand-custom`, `.admin-indicator`) |
| 13 | Footer (`.site-footer`) |
| 14 | Page surface (`.page-surface`, `.page-header`) |
| 15 | Tables (`.table-clean`, `.table-responsive-cards`) |
| 16 | Trust strip (`.trust-strip`, `.trust-item`) |
| 17 | Image fallback (`.img-fallback`) |
| 18 | Utilities (`.bg-surface`, `.divider`, `.empty-state`, `.container-1320`) |
| 19 | Bootstrap overrides |
| 20 | Hero (home page) |
| 21 | Navbar alignment fix |
| 22 | Property detail page |
| 23 | Registration / auth form pages |
| 24 | Auth pages (login) |
| 25 | Dealer dashboard |
| 26 | Profile page |
| 27 | Dealer my properties |
| 28 | Add / Edit property form |
| 29 | Location picker (Leaflet modal) |
| 30 | Edit property extras |
| 31 | Password show/hide toggle |
| 32 | Admin login page |
| 33 | Admin dashboard |
| 34 | Admin profile |
| 35 | Admin manage dealers |
| 36 | Admin manage properties |
| 37 | Legal pages (Terms, Privacy) |
| 38 | Admin reset password button + modal |

---

## 10. RESPONSIVE DESIGN RULES

**Bootstrap grid classes used throughout:**

| Class | Effect |
|---|---|
| `col-12` | Full width on all screens |
| `col-6` | Half width on all screens |
| `col-12 col-md-6` | Full width on mobile, half width on tablet+ |
| `col-12 col-md-4` | Full on mobile, one-third on tablet+ |
| `col-12 col-md-6 col-lg-4 col-xxl-3` | Property card: 1 → 2 → 3 → 4 columns |

**Utility classes used:**

| Class | Effect |
|---|---|
| `d-none d-md-block` | Hidden on mobile, visible on tablet+ |
| `d-block d-md-none` | Visible on mobile only |
| `d-none d-lg-table-cell` | Table cell hidden below 992px |
| `order-1 order-lg-2` | Reorder columns on larger screens |
| `g-4` | Grid gutter of 24px |
| `w-100` | Full width |
| `mt-*`, `mb-*`, `p-*` | Margins and paddings (Bootstrap 0-5 scale) |

**Every page tested at:**
- 375px (mobile)
- 768px (tablet)
- 1440px (desktop)

---

## 11. FORM VALIDATION — HOW IT WORKS

Two patterns used across the site:

### Pattern 1 — `API.setFieldError(el, msg)` helper

Used in the login pages:

```js
API.setFieldError(usernameInput, "Username is required.");
```

Adds `.is-invalid` class + writes the message into the `.invalid-feedback` element below the input.

### Pattern 2 — Page-local `setError(key, message)`

Used in longer forms (registration, add property, edit property):

```js
function setError(key, message) {
   const el = fields[key];
   el.classList.add("is-invalid");
   const fb = el.parentElement.querySelector(".invalid-feedback");
   if (fb) fb.textContent = message;
}
```

Every page that uses forms has this pattern.

### What the user sees

```
Input with default state:
   ┌──────────────────────────────┐
   │ Enter username               │
   └──────────────────────────────┘

Input with error state:
   ┌──────────────────────────────┐
   │                              │  ← red border
   └──────────────────────────────┘
   Username is required.             ← red text below
```

---

## 12. WHAT'S NOT IN THE FRONTEND

To keep scope clear:

- **No React, Vue, or Angular.** Plain HTML + vanilla JavaScript.
- **No build tools** (no npm, webpack, Vite). Files are served as-is.
- **No server-side rendering.** All data comes from `fetch()` after page load.
- **No state management library.** Each page manages its own state via local variables.
- **No CSS-in-JS.** All styles are in one `style.css`.
- **No service worker / PWA.** Not needed for MVP.
- **No offline support.**
- **No analytics / tracking pixels.**
- **No TypeScript.** Plain JS for simplicity.

---

## 13. DEBUGGING — QUICK GUIDE

When something doesn't work:

| Symptom | Where to look |
|---|---|
| Page loads blank | Browser Console (F12) → red JS error |
| Content stuck on skeleton | Network tab → API call failed or hung |
| 401 errors | User not logged in. Check session cookie |
| 404 on an API | Wrong URL path. Check `API.base()` |
| Form doesn't submit | Console for JS error, or validation red border |
| "Unable to connect" toast | Backend server not running (`php -S localhost:8000`) |
| Map doesn't load | Leaflet CDN blocked? Network tab → 404 on leaflet.js |
| Images broken | Check `/uploads/property_images/...` path exists on disk |
| Modal doesn't open | Bootstrap JS not loaded? Console for error |

**Always check the browser Console first.** 90% of frontend bugs are visible there immediately.

---

## 14. HOW EVERY PAGE STARTS

Every page follows the same skeleton:

```html
<!doctype html>
<html lang="en">
<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1" />
   <title>Page Title — Property Dealing</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
   <link href="/frontend/shared/css/style.css" rel="stylesheet" />
</head>
<body>

   <div id="navbar" data-navbar="public"></div>

   <main>
      <!-- page content -->
   </main>

   <div id="footer"></div>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   <script src="/frontend/shared/js/api.js"></script>
   <script src="/frontend/shared/js/partials.js"></script>

   <script>
      // page-specific logic
   </script>

</body>
</html>
```

Learn this skeleton once → every page makes sense.

---
```

---

# What this document covers

| Section | Covers |
|---|---|
| Folder structure | All 14 pages + shared files + design system |
| How it works | Two runtime flow diagrams (public + protected page) |
| File-by-file purpose | CSS, JS, partials, and all 20 HTML files |
| Key terms | 45+ terms explained in one line each |
| Important concepts | Design system, partials, auth, validation, price formatting, image fallback, skeleton loading, empty/error states, map picker, upload flow, view counting, responsive, escaping, CDNs, debugging |
| Quick reference | Shared files + CDN libraries |
| Page inventory | All 14 pages with API dependencies |
| API.JS interface | Full method list |
| Style.css sections | 38 numbered sections |
| Responsive rules | Grid classes + utilities used |
| Form validation | Two patterns |
| What's NOT in the frontend | Explicit exclusions |
| Debugging guide | Common symptoms → where to look |
| Page skeleton | The standard page template |