> # Backend Overview

---

## 1. FOLDER STRUCTURE

```
Property Dealing/
│
├── backend/
│   ├── api/
│   │   ├── auth/
│   │   │   ├── register-dealer.php
│   │   │   ├── login-dealer.php
│   │   │   ├── login-admin.php
│   │   │   └── logout.php
│   │   ├── dealer/
│   │   │   ├── my-profile.php
│   │   │   ├── change-password.php
│   │   │   ├── list-my-properties.php
│   │   │   ├── add-property.php
│   │   │   ├── edit-property.php
│   │   │   ├── toggle-property-status.php
│   │   │   ├── upload-property-image.php
│   │   │   └── delete-property-image.php
│   │   ├── admin/
│   │   │   ├── my-profile.php
│   │   │   ├── change-password.php
│   │   │   ├── list-dealers.php
│   │   │   ├── update-dealer-status.php
│   │   │   ├── delete-dealer.php
│   │   │   ├── reset-dealer-password.php
│   │   │   ├── list-properties.php
│   │   │   └── delete-property.php
│   │   └── public/
│   │       ├── list-properties.php
│   │       ├── get-property.php
│   │       └── list-locations.php
│   │
│   ├── classes/
│   │   ├── Database.php
│   │   ├── Dealer.php
│   │   ├── Admin.php
│   │   ├── Auth.php
│   │   └── Property.php
│   │
│   ├── middleware/
│   │   ├── require-admin.php
│   │   └── require-dealer.php
│   │
│   ├── config/
│   │   └── config.php
│   │
│   ├── setup/
│   │   └── (empty — used once to create first admin, then deleted)
│   │
│   ├── index.php
│   └── (test-*.php files for local testing only)
│
├── database/
│   └── schema.sql
│
├── frontend/
│   ├── shared/
│   │   ├── css/
│   │   │   └── style.css
│   │   ├── js/
│   │   │   ├── api.js
│   │   │   └── partials.js
│   │   └── partials/
│   │       ├── navbar-public.html
│   │       ├── navbar-dealer.html
│   │       ├── navbar-admin.html
│   │       └── footer.html
│   ├── public/
│   │   ├── index.html
│   │   ├── property-details.html
│   │   ├── dealer-register.html
│   │   ├── terms.html
│   │   └── privacy.html
│   ├── dealer/
│   │   ├── login.html
│   │   ├── dashboard.html
│   │   ├── profile.html
│   │   ├── my-properties.html
│   │   ├── add-property.html
│   │   ├── edit-property.html
│   │   └── change-password.html
│   └── admin/
│       ├── login.html
│       ├── dashboard.html
│       ├── profile.html
│       ├── dealers.html
│       ├── properties.html
│       └── change-password.html
│
├── uploads/
│   └── property_images/
│       └── {property_id}/
│           └── xxxx.jpg
│
└── BACKEND.md
```

---

## 2. HOW THE BACKEND WORKS (MAP)

```
FRONTEND (browser)
    │
    │  fetch('/backend/api/...')
    │  with credentials: 'include'
    ▼
┌──────────────────────────────────────────────────┐
│  MIDDLEWARE (for protected endpoints)            │
│  require-admin.php  →  is role 'admin'?          │
│  require-dealer.php →  is role 'dealer'?         │
│         │                                        │
│         │  If unauthorized → 401 JSON, stop.     │
└─────────┼────────────────────────────────────────┘
          ▼
┌──────────────────────────────────────────────────┐
│  API LAYER (backend/api/)                        │
│  Reads request → validates input → calls class   │
│  → returns JSON response                         │
└─────────┬────────────────────────────────────────┘
          ▼
┌──────────────────────────────────────────────────┐
│  OOP CLASSES (backend/classes/)                  │
│  Database  → PDO connection                      │
│  Dealer    → dealer operations                   │
│  Admin     → admin operations                    │
│  Auth      → login / password verify             │
│  Property  → property CRUD                       │
└─────────┬────────────────────────────────────────┘
          ▼
┌──────────────────────────────────────────────────┐
│  MySQL DATABASE                                  │
│  property_marketplace                            │
│  Tables: admins, dealers, properties,            │
│          property_images                         │
└──────────────────────────────────────────────────┘
```

**Request flow (example: dealer adds a property)**

```
Dealer fills form in browser
    ↓
JavaScript fetch() → POST /backend/api/dealer/add-property.php
    ↓
require-dealer.php checks session → role = 'dealer' ✓
    ↓
add-property.php reads JSON input, validates fields
    ↓
Property::create() runs INSERT query
    ↓
MySQL saves row → returns property_id
    ↓
JSON response sent back to browser
    ↓
Frontend shows success message
```

---

## 3. FILE-BY-FILE PURPOSE

### 3.1 Classes (the core logic)

| File | Purpose |
|---|---|
| **Database.php** | Creates the PDO connection to MySQL. Reused by every other class. |
| **Dealer.php** | Handles dealer records: `usernameExists()`, `emailExists()`, `create()`. |
| **Admin.php** | Handles admin records: `findByUsername()`, `verifyPassword()`. |
| **Auth.php** | Handles dealer authentication: `findDealerByUsername()`, `verifyPassword()`. |
| **Property.php** | Handles properties: create, find, update, toggle status, delete, list with dealer info. |

### 3.2 Middleware (session guards)

| File | Purpose |
|---|---|
| **require-admin.php** | Include at top of admin-only endpoints. Returns 401 if not logged in as admin. |
| **require-dealer.php** | Include at top of dealer-only endpoints. Returns 401 if not logged in as dealer. |

### 3.3 Config

| File | Purpose |
|---|---|
| **config.php** | Stores constants: `ADMIN_CONTACT_NUMBER`, `ADMIN_CONTACT_EMAIL`. Read by public APIs to show contact info. |

### 3.4 API Endpoints

#### Auth (no login required)

| File | Method | Purpose |
|---|---|---|
| **register-dealer.php** | POST | New dealer registration. Status set to `pending`. |
| **login-dealer.php** | POST | Dealer login. Checks password, then status. |
| **login-admin.php** | POST | Admin login. Creates session. |
| **logout.php** | POST | Destroys session. |

#### Dealer (requires dealer session)

| File | Method | Purpose |
|---|---|---|
| **my-profile.php** | GET | Returns logged-in dealer's profile. |
| **change-password.php** | POST | Change dealer's own password. Requires current password. |
| **list-my-properties.php** | GET | Returns only this dealer's properties (with images). |
| **add-property.php** | POST | Creates a new property. Status = `unsold`. |
| **edit-property.php** | POST | Updates a property (must belong to this dealer). |
| **toggle-property-status.php** | POST | Marks a property sold or unsold. |
| **upload-property-image.php** | POST (multipart) | Saves an image file and inserts a row in `property_images`. Max 10 per property. |
| **delete-property-image.php** | POST | Deletes one image (DB row + file) belonging to this dealer. |

#### Admin (requires admin session)

| File | Method | Purpose |
|---|---|---|
| **my-profile.php** | GET | Returns logged-in admin's profile. |
| **change-password.php** | POST | Change admin's own password. Requires current password. |
| **list-dealers.php** | GET | Returns all dealers. |
| **update-dealer-status.php** | POST | Approve / reject a dealer. |
| **reset-dealer-password.php** | POST | Admin sets a new password for any dealer. |
| **delete-dealer.php** | POST | Delete dealer + cascade-delete their properties and images. |
| **list-properties.php** | GET | Returns all properties with dealer info (admin sees private fields). |
| **delete-property.php** | POST | Delete a single property + its images (cascade). |

#### Public (no auth)

| File | Method | Purpose |
|---|---|---|
| **list-properties.php** | GET | Lists approved properties. Supports `?q=`, `?type=`, `?status=`. |
| **get-property.php** | GET | Returns one approved property by `?property_id=`. Increments view counter once per session (`?count_view=1`). |
| **list-locations.php** | GET | Returns distinct provinces and cities for the search datalist. |

### 3.5 Database

| File | Purpose |
|---|---|
| **database/schema.sql** | Full database definition. Creates the DB and all 4 tables with relationships and indexes. Run once in MySQL Workbench to set up the project. |

**Tables created by schema.sql:**

| Table | Holds |
|---|---|
| `admins` | Admin login accounts |
| `dealers` | Dealer accounts with business info, province, city, verification status |
| `properties` | Property listings tied to a dealer, with province, city, views count |
| `property_images` | Multiple image paths per property (max 10 per property) |

**Column changes since v1:**

| Table | Change |
|---|---|
| `dealers` | `city_area` was **split** into `province` + `city`. `whatsapp` column is nullable. |
| `properties` | `city_area` was **split** into `province` + `city`. Added `views INT UNSIGNED DEFAULT 0`. |

### 3.6 Other

| File | Purpose |
|---|---|
| **backend/index.php** | Placeholder. Shows "PHP is working". Not used by the app. |
| **backend/test-*.php** | Local test files. Can be deleted in production. |

---

## 4. KEY TERMS — SIMPLE EXPLANATIONS

| Term | Meaning |
|---|---|
| **API** | A URL that the frontend calls to get or send data. Example: `/backend/api/public/list-properties.php`. |
| **REST API** | An API style where URLs represent resources and HTTP methods (GET, POST) describe the action. GET = read, POST = create/change. |
| **Endpoint** | One specific API URL. Each file in `backend/api/` is one endpoint. |
| **JSON** | Text format for sending/receiving data. Example: `{"success": true, "count": 5}`. Every API returns JSON. |
| **Request** | The call from frontend to backend. Contains method, URL, headers, and optional body. |
| **Response** | The reply from backend. Contains HTTP status code and JSON body. |
| **HTTP Status** | Number telling the result. `200` = OK, `201` = created, `400` = bad input, `401` = not logged in, `403` = forbidden, `404` = not found, `409` = conflict, `500` = server error. |
| **Database** | The permanent storage. Here: MySQL. |
| **Schema** | The structure of the database — which tables exist and what columns each has. |
| **Table** | A grid of data with rows and columns. Example: `dealers` table. |
| **Row / Record** | One entry in a table. One dealer = one row. |
| **Primary Key** | Unique ID for each row. Example: `dealer_id`. Generated automatically. |
| **Foreign Key** | A column that points to a primary key in another table. Example: `properties.dealer_id` points to `dealers.dealer_id`. |
| **Cascade Delete** | If a parent row is deleted, related child rows are deleted too. Example: deleting a dealer also deletes their properties and images. |
| **PDO** | PHP Data Objects. The safe way to talk to MySQL from PHP. Prevents SQL injection. |
| **SQL Injection** | An attack where malicious input tries to run SQL commands. Prevented by PDO prepared statements. |
| **Prepared Statement** | A safe SQL template where values are sent separately. Example: `WHERE username = :username`. |
| **Session** | Server-side memory tied to a browser cookie. Stores who is logged in. `$_SESSION['role']`. |
| **Cookie** | Small piece of data the browser keeps and sends on every request. Identifies the session. |
| **Middleware** | A file included at the top of an endpoint that checks access before the endpoint runs. |
| **Role** | The user type stored in session: `admin`, `dealer`, or none (public). |
| **Authentication** | Verifying "who are you?" — done by login. |
| **Authorization** | Verifying "what are you allowed to do?" — done by middleware and role checks. |
| **Password Hashing** | Storing passwords as one-way hash (never plain text). Done with `password_hash()`. Verified with `password_verify()`. |
| **OOP** | Object-Oriented Programming. Code organized into classes with methods. Example: `Property::create()`. |
| **Class** | A blueprint for an object. Example: `Database`, `Property`. |
| **Method** | A function inside a class. Example: `findById()`. |
| **Constructor** | Special method that runs when a class is created. Example: `__construct(PDO $db)`. |
| **CRUD** | Create, Read, Update, Delete. The four basic data operations. |
| **Relative Path** | A URL or file path that starts from the current location. Example: `/backend/api/...` instead of `http://localhost:8000/backend/api/...`. |
| **CORS** | Cross-Origin Resource Sharing. Rules about whether a browser can call APIs on a different domain. Not needed here because frontend and backend share one domain. |
| **Multipart Form** | The format used for file uploads. Sent with `FormData` in JavaScript. |
| **Timestamp** | Date + time stored by MySQL. Example: `2026-10-02 16:35:03`. |
| **Alias (in search)** | A short form mapped to a longer value. Example: `kpk` → `Khyber Pakhtunkhwa`. |
| **Rate Limiting** | Blocking excessive requests from one source. Not implemented in MVP. |
| **View Counter (deduplicated)** | Counts unique page views per session, not every request. Prevents reload inflation. |
| **Reverse Geocoding** | Converting GPS coordinates (lat/lng) into a human-readable address. Done here via Nominatim. |
| **Nominatim** | Free OpenStreetMap geocoding service. Used for the map picker's search and address lookup. |
| **Leaflet** | Free open-source JavaScript library for interactive maps. Used in the location picker. |
| **OpenStreetMap** | Free map data provider. Supplies the tiles displayed by Leaflet. |

---

## 5. IMPORTANT CONCEPTS

### Authentication vs Authorization

```
Authentication  →  Login: "Are you who you say you are?"
Authorization   →  Middleware: "Are you allowed to do this?"
```

Both are needed. Login alone is not enough.

### Why Sessions

- Admin and dealer cannot be logged in at the same time in one browser.
- Logging in as one replaces the other session.
- This is expected and correct.
- Test each role in its own browser or after logging out.

### Why Public Never Sees Dealer Contact

The platform earns commission. If users contact the dealer directly, the platform loses the deal. So public API responses never include `dealer_phone`, `dealer_whatsapp`, `full_address`, or `google_map_url`.

### Why Two Location Fields

- **province** and **city** → shown publicly and used for search.
- **address** (full) → private, admin only.
- Splitting them lets users search "kpk" and match every property in that province.

### Why Cascade Delete

- Deleting a dealer must delete their properties (no orphan rows).
- Deleting a property must delete its images (no orphan files or rows).
- Handled by MySQL foreign keys with `ON DELETE CASCADE`.

### Why View Counter Uses `?count_view=1`

Browsers sometimes send two requests for the same page (prefetch, reload). To avoid counting each as a new view, the **frontend** signals a "new view" only once per browser tab using `sessionStorage`. The backend increments the counter only when it receives that flag.

### Why the Image Upload Has a Cap

- Prevents abuse — a single property cannot host 200 images.
- Keeps pages fast — gallery stays manageable.
- Standard limit: **10 images per property**.

### Why Map Location Is Required

- Enforces quality — every listing has a verified location pin.
- Helps admin and buyers understand exactly where the property is.
- The frontend rejects submission early; backend also validates as a safety net.

### Why Admin Reset Password Exists (even though self-service is preferred)

- The dealer-facing "Forgot Password" flow requires sending email, which many free hosts block.
- Admin reset is a **rescue tool** for the rare case where a dealer is locked out.
- It uses the same `password_hash()` system and does not weaken security.
- It is not the primary flow — dealers still change their own passwords.

---

## 6. QUICK REFERENCE — ALL ENDPOINTS

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| POST | `/backend/api/auth/register-dealer.php` | Public | Register dealer |
| POST | `/backend/api/auth/login-dealer.php` | Public | Dealer login |
| POST | `/backend/api/auth/login-admin.php` | Public | Admin login |
| POST | `/backend/api/auth/logout.php` | Any | Logout |
| GET | `/backend/api/public/list-properties.php` | Public | List approved properties (supports `?q=`, `?type=`, `?status=`) |
| GET | `/backend/api/public/get-property.php?property_id=X` | Public | Single property (with view counting via `?count_view=1`) |
| GET | `/backend/api/public/list-locations.php` | Public | Province + city list for datalist |
| GET | `/backend/api/dealer/my-profile.php` | Dealer | Dealer profile |
| POST | `/backend/api/dealer/change-password.php` | Dealer | Change own password |
| GET | `/backend/api/dealer/list-my-properties.php` | Dealer | Dealer's properties (with images) |
| POST | `/backend/api/dealer/add-property.php` | Dealer | Add property |
| POST | `/backend/api/dealer/edit-property.php` | Dealer | Edit property |
| POST | `/backend/api/dealer/toggle-property-status.php` | Dealer | Sold / Unsold |
| POST | `/backend/api/dealer/upload-property-image.php` | Dealer | Upload image (max 10 per property) |
| POST | `/backend/api/dealer/delete-property-image.php` | Dealer | Delete one image |
| GET | `/backend/api/admin/my-profile.php` | Admin | Admin profile |
| POST | `/backend/api/admin/change-password.php` | Admin | Change own password |
| GET | `/backend/api/admin/list-dealers.php` | Admin | List dealers |
| POST | `/backend/api/admin/update-dealer-status.php` | Admin | Approve / Reject |
| POST | `/backend/api/admin/reset-dealer-password.php` | Admin | Reset a dealer's password |
| POST | `/backend/api/admin/delete-dealer.php` | Admin | Delete dealer (cascade) |
| GET | `/backend/api/admin/list-properties.php` | Admin | List all properties (with images + private fields) |
| POST | `/backend/api/admin/delete-property.php` | Admin | Delete property (cascade) |

---

## 7. FRONTEND PAGES (14 total)

### Public (5)
| File | Purpose |
|---|---|
| `frontend/public/index.html` | Home / property listing |
| `frontend/public/property-details.html` | Single property detail |
| `frontend/public/dealer-register.html` | Dealer signup form |
| `frontend/public/terms.html` | Terms & Conditions |
| `frontend/public/privacy.html` | Privacy Policy |

### Dealer (7)
| File | Purpose |
|---|---|
| `frontend/dealer/login.html` | Dealer login (with forgot-password modal) |
| `frontend/dealer/dashboard.html` | Dealer overview + recent properties |
| `frontend/dealer/profile.html` | Dealer profile info |
| `frontend/dealer/my-properties.html` | Manage own listings (view counts, toggle sold/unsold) |
| `frontend/dealer/add-property.html` | Add new property (with map picker + multi-image upload) |
| `frontend/dealer/edit-property.html` | Edit existing property |
| `frontend/dealer/change-password.html` | Change own password |

### Admin (6)
| File | Purpose |
|---|---|
| `frontend/admin/login.html` | Private admin login |
| `frontend/admin/dashboard.html` | Admin overview + stats |
| `frontend/admin/profile.html` | Admin profile info |
| `frontend/admin/dealers.html` | Manage dealers (approve/reject/reset/delete) |
| `frontend/admin/properties.html` | Manage properties (view/delete, private fields) |
| `frontend/admin/change-password.html` | Change own password |

### Shared
| File | Purpose |
|---|---|
| `frontend/shared/css/style.css` | Design system tokens + all page styles |
| `frontend/shared/js/api.js` | Fetch helpers, formatters, auth utilities |
| `frontend/shared/js/partials.js` | Injects navbar + footer into every page |
| `frontend/shared/partials/*.html` | Navbar templates + footer |

---
```

---

# What changed vs the old version

| Section | Change |
|---|---|
| Folder structure | Added `change-password.php` (dealer + admin), `reset-dealer-password.php`, `delete-property-image.php`, `terms.html`, `privacy.html`, `change-password.html` (dealer + admin), the two partial JS files, and the four navbar/footer partials |
| Dealer endpoints table | Added `change-password.php` and `delete-property-image.php`; noted image cap of 10 |
| Admin endpoints table | Added `change-password.php` and `reset-dealer-password.php` |
| Public endpoints | Noted `?count_view=1` flag on `get-property.php` |
| Database tables | Noted `province` + `city` split and new `views` column |
| Key terms | Added **Rate Limiting**, **View Counter (deduplicated)**, **Reverse Geocoding**, **Nominatim**, **Leaflet**, **OpenStreetMap**; renamed "Timezone / UTC" to "Timestamp" and clarified |
| Important concepts | Added sections on view counter deduplication, image cap, map location requirement, admin reset password rationale |
| Quick reference | Updated with all new endpoints |
| New section 7 | Full inventory of the 14 frontend pages + shared files |
