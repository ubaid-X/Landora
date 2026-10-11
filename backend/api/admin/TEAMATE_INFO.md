> # Landora — Info for Team Members

A non-developer's guide to understanding the project, answering questions, and speaking confidently about it.

**For:** team members who are Computer Science students but are not building the code.
**Purpose:** so you can explain the project, its purpose, and its technology when asked.

---

## 1. What is Landora?

Landora is a **verified property marketplace** for Swat, Khyber Pakhtunkhwa, and eventually across Pakistan.

In simple terms: it is a website where people can browse houses, plots, shops, and other properties listed by **verified local dealers**. But it is not just a listing site — it is a **lead-generation and connection platform**. When a buyer is interested in a property, they contact Landora's team (not the dealer directly). Landora arranges the meeting between buyer and dealer. If the deal closes, the dealer pays Landora an agreed commission.

**The key idea:** Landora stands in the middle of every transaction, verifies both sides, and gets paid only when a deal succeeds.

---

## 2. The Business Model

### How Landora makes money

```
Dealer lists property on Landora
        ↓
Public user finds it, clicks "Contact Us"
        ↓
Landora team calls the user, understands their need
        ↓
Landora calls the dealer, arranges a meeting
        ↓
If the deal closes → dealer pays Landora 1%–5% commission
```

No subscription. No listing fee. Landora only earns when a deal closes. This is the **commission model**.

### Why this model

- **Zero risk for dealers** — they pay nothing upfront
- **Aligned incentives** — Landora only succeeds when dealers succeed
- **Data collection** — Landora learns what buyers want, which dealers close, which properties sell
- **Future SaaS path** — once enough data exists, Landora can charge monthly subscriptions instead

### The differentiator

Most Pakistani property sites (OLX, Zameen) are just **classified listings** — anyone can post anything, no verification. Landora is different because:

1. **Every dealer is verified** — physically visited, documents checked
2. **Every property comes from an approved dealer** — no scams, no fake listings
3. **The public never sees dealer contact info** — Landora controls the flow, keeps the deal in the loop
4. **Local focus** — starts in Swat/KP instead of trying to cover all of Pakistan on day one

---

## 3. Who Uses Landora?

There are **three types of users**, each with different permissions:

### Public Users (no login)
- Browse approved properties
- Search by city, province, or property type
- View full details and image galleries
- Contact Landora by phone or email
- Share listings on WhatsApp

They **never** see the dealer's phone number, WhatsApp, or full property address. They only see what Landora chooses to show.

### Dealers (login required)
- Register with their business details → status = "pending"
- Wait for admin approval
- Once approved:
  - Add, edit, and manage their own property listings
  - Upload up to 10 images per property
  - Pick a map location for each property
  - Mark properties as "Sold" or "Unsold"
  - See how many views each property has
  - Change their own password
- **Cannot** delete properties or their own account (that's a business rule)

### Admins (private login)
- Approve, reject, or delete dealers
- Reset dealer passwords
- View and delete any property
- See all hidden fields (full address, dealer phone, dealer WhatsApp)
- Access the admin panel — not linked anywhere publicly
- The admin is the **only** role that sees everything

---

## 4. The Tech Stack — Explained Simply

If a classmate or teacher asks "what did you build this with?" here's the short version:

| Layer | What it means | What we used |
|---|---|---|
| **Frontend** | What users see in the browser | HTML, CSS, JavaScript, Bootstrap 5 |
| **Backend** | The logic that runs on the server | PHP 8.5 |
| **Database** | Where data is stored permanently | MySQL 8 |
| **API style** | How frontend and backend talk | REST-style JSON |
| **Authentication** | How users log in | PHP sessions (cookie-based) |
| **Image storage** | Where property photos live | ImgBB (free image host) or local disk |
| **Map picker** | For choosing property location | Leaflet + OpenStreetMap (free) |
| **Hosting** | Where the site runs | InfinityFree (free) |

### Why these choices

- **PHP + MySQL** — mature, widely used for web apps, free, and runs on almost any host
- **Bootstrap 5** — for consistent, responsive design without writing everything from scratch
- **Vanilla JavaScript** — no React/Vue, so no build tools needed
- **Leaflet instead of Google Maps** — Google Maps charges money once you exceed the free tier; Leaflet + OpenStreetMap is free forever
- **ImgBB for image hosting** — free, simple, works on most free hosts

---

## 5. How It Works — At a High Level

### What happens when someone visits the site

```
User opens landora.infinityfree.io
        ↓
Browser requests the HTML page + CSS + JavaScript
        ↓
JavaScript calls backend APIs (e.g., "give me all approved properties")
        ↓
Backend queries MySQL
        ↓
Backend returns JSON data
        ↓
JavaScript renders the property cards
        ↓
User clicks a property → sees full details + contact options
```

### What happens when a dealer adds a property

```
Dealer logs in (session created)
        ↓
Fills the Add Property form
        ↓
Selects location on interactive map
        ↓
Uploads images
        ↓
Clicks Save → frontend sends data to backend
        ↓
Backend verifies: is this really a dealer? is the data valid?
        ↓
Backend saves to database
        ↓
Images uploaded to ImgBB (or local disk)
        ↓
Frontend redirects to "My Properties"
```

### What happens when the admin approves a dealer

```
Admin clicks "Approve" on a pending dealer
        ↓
Frontend sends POST to backend
        ↓
Backend verifies: is this really an admin?
        ↓
Backend updates dealer's status to "approved"
        ↓
Dealer can now log in and add properties
```

---

## 6. Quick Reference — Key Terms

If someone uses these terms and you're unsure, here's what they mean:

| Term | Plain meaning |
|---|---|
| **API** | A way for the frontend to ask the backend for data |
| **Endpoint** | One specific API URL (like `/api/list-properties.php`) |
| **JSON** | The text format for sending data back and forth. Looks like `{"success": true}` |
| **REST** | A style of API where URLs represent resources |
| **Database** | The permanent storage (MySQL, in our case) |
| **Table** | A grid of data, like a spreadsheet |
| **Row** | One entry in a table (one dealer, one property) |
| **Primary key** | A unique ID for each row |
| **Foreign key** | A link from one table to another |
| **Cascade delete** | When you delete a parent, children are deleted too |
| **PDO** | The safe way PHP talks to MySQL |
| **SQL injection** | A security attack where malicious input runs as SQL |
| **Session** | Server-side memory that knows who's logged in |
| **Cookie** | Small data the browser keeps and sends back |
| **Middleware** | A check that runs before an endpoint executes |
| **Password hashing** | Turning a password into a one-way code so it can't be read |
| **CRUD** | Create, Read, Update, Delete — the four basic data actions |
| **Responsive** | Layout adapts to different screen sizes |

---

## 7. Common Questions You Might Be Asked

Practice these answers. They're short, clear, and technically accurate.

### Q: What does your project do?
**A:** It's a verified property marketplace for Swat and KP. Buyers browse properties, contact our platform, and we arrange a meeting with the verified dealer. If a deal closes, the dealer pays us a commission.

### Q: What tech did you use?
**A:** PHP 8.5 on the backend, MySQL for the database, and HTML/CSS/JavaScript with Bootstrap on the frontend. The frontend and backend talk over a REST-style JSON API.

### Q: Why PHP and not Node.js or Python?
**A:** PHP is built for web applications. It has everything we needed — sessions, database access, file uploads — without needing extra tools. It also runs on almost any cheap hosting, which matters for a real business.

### Q: How is this different from OLX or Zameen?
**A:** OLX and Zameen are open classified sites — anyone can post, no verification. Landora verifies every dealer physically and checks documents before approving them. Also, we don't expose dealer contact details publicly; we control the buyer-dealer connection because that's how we earn commission.

### Q: How do you make money?
**A:** Commission on closed deals. Dealers pay us 1%–5% when a sale completes through a lead we generated.

### Q: How do you ensure the dealer is real?
**A:** Every dealer goes through admin approval. In the full version, we plan to visit them physically and check their business documents. For now, the admin manually approves them.

### Q: What happens if a dealer tries to bypass you?
**A:** They can't easily — the public never sees their contact information. Only Landora's phone number appears on listings. Users must call us first.

### Q: How do you handle security?
**A:** Passwords are hashed with `password_hash()` — never stored in plain text. All database queries use PDO prepared statements, which prevents SQL injection. Session cookies are HttpOnly and SameSite to prevent cross-site attacks. Protected endpoints have middleware that blocks unauthorized access. Admin routes are not linked anywhere on the public site.

### Q: What about scalability — will it handle many users?
**A:** For the current MVP (a demo for one city), yes. If we scale, we'd move images to Cloudflare R2, move the database to a managed service, and possibly use a CDN for static files. The architecture separates frontend, backend, and database, so scaling each layer independently is possible.

### Q: Why do you hide the dealer contact info?
**A:** Because our business depends on being the intermediary. If buyers contact dealers directly, they'd cut us out and we'd earn nothing.

### Q: Can a dealer see another dealer's properties?
**A:** No. Each dealer only sees their own listings. The backend checks ownership on every request.

### Q: Can a public user see the full property address?
**A:** No. Public users see only the city and province. The full address and Google Maps location are visible only to admins, and to the dealer on their own listings.

### Q: What did you learn building this?
**A:** How to structure a real web application — separating concerns (API layer, classes, middleware), how authentication and authorization differ, how to use sessions securely, how to handle file uploads, how to prevent SQL injection with prepared statements, and how to design a database with proper relationships and cascade deletes.

### Q: How long did it take?
**A:** (Say the honest number. If asked "is this a real product?" — say "It's an MVP with real business potential that we plan to test with actual local dealers.")

### Q: Is it deployed?
**A:** Yes. It's live at `landora.infinityfree.io`. You can visit the public pages, register as a dealer, and log in as admin (demo credentials can be provided).

---

## 8. What's Live vs What's Next

### Live now (feature complete)
- Public property listings with search
- Dealer registration + admin approval
- Dealer dashboard with property management
- Image uploads (up to 10 per property)
- Interactive map picker
- Admin dashboard with stats
- Change password, reset password
- Terms & Privacy pages
- View counter per property
- WhatsApp share button
- Responsive design (320px–1440px)

### Planned next (not yet built)
- **Inquiry tracking** — the platform logs every buyer call, tracks deal progress, measures conversion
- **Dealer paper verification** — upload and verify CNIC, NTN, registration certificates
- **Property paper verification** — Fard, Registry, Mutation documents
- **Featured listings** — dealers pay to boost listings
- **Monthly subscription tier** — replaces commission for high-volume dealers
- **Email notifications** — when a new inquiry arrives, when a listing is approved

### Long-term vision
- Multi-city expansion (Islamabad, Peshawar, Lahore)
- Dealer SaaS — full inventory management software for dealers
- Mobile app for buyers and dealers

---

## 9. Where to Find More Information

If someone asks a technical question you can't answer, don't guess — point them to the right document.

| Document | What it covers |
|---|---|
| `README.md` | Project overview, setup, deployment |
| `BACKEND.md` | Every API endpoint, database schema, request/response flow, full glossary |
| `FRONTEND.md` | Every page, design system, API helper reference, debugging guide |
| `TEAMMATE_INFO.md` | This file — high-level explanation for non-developers |

---

## 10. How to Talk About Landora in a Presentation

If you're presenting the project (or answering questions in class), use this structure:

### 1. Start with the problem
> "Property buyers in Swat and KP have no reliable way to find verified dealers. Fake listings, hidden scams, and no accountability are everywhere."

### 2. Introduce the solution
> "Landora is a verified property marketplace that only lists properties from dealers we've checked. Buyers browse, contact our team, and we handle the connection."

### 3. Explain the business model
> "We earn commission when a deal closes. That means we only win when dealers and buyers win."

### 4. Show the tech
> "PHP backend with MySQL, vanilla JavaScript frontend. We use PDO for secure database access, session-based authentication, and a REST-style API between frontend and backend."

### 5. Show the demo
- Open the live site
- Register a dealer → approve from admin → log in as dealer → add a property
- Show the public listing
- Point out the privacy rules (no dealer contact shown publicly)

### 6. Close with the vision
> "This is an MVP. Next steps are inquiry tracking, dealer verification documents, and eventually a subscription SaaS model for dealers."

---

## 11. Rules for Answering Questions

1. **Don't pretend to know what you don't know.** Say "I'm not the developer on that part, but I can tell you the high-level idea" or "Let me point you to the technical docs."

2. **Stay at a high level unless asked.** If they want technical details, refer them to the developer.

3. **Use the business angle** — most questions will be about *why* rather than *how*. The business model is your strongest answer.

4. **Be honest about what's not built yet.** Saying "that's planned for phase 2" is better than pretending.

5. **If asked about a specific technology term you don't know** — "I know we use it, but I'd have to check the docs to explain exactly how. Let me get back to you."

---

## 12. Quick Cheat Sheet — One-Liners

Memorize these. They cover 90% of casual questions.

- **What is it?** → A verified property marketplace for Swat and KP.
- **Who uses it?** → Public buyers, verified dealers, and admins.
- **How does it make money?** → Commission on closed deals (1%–5%).
- **What's unique?** → Every dealer is verified; dealers' contact info is never shown publicly.
- **What tech?** → PHP + MySQL backend, HTML/CSS/JS frontend, Bootstrap, REST API.
- **Is it live?** → Yes — `landora.infinityfree.io`.
- **What's next?** → Inquiry tracking, dealer verification, subscription SaaS.

---

*Last updated: 11 October 2026*
```

---