# Landora

> Verified property marketplace for Swat, Khyber Pakhtunkhwa, and across Pakistan.

Landora connects property buyers with verified local dealers. Public users browse approved listings and contact the platform team; the platform arranges a meeting with the dealer and earns an agreed commission on a closed deal.

**Status:** MVP — feature-complete, pre-deployment.

---

## Table of Contents

- [What It Does](#what-it-does)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Configuration](#configuration)
- [User Roles](#user-roles)
- [API Overview](#api-overview)
- [Data Privacy Rules](#data-privacy-rules)
- [Documentation](#documentation)
- [Deployment Notes](#deployment-notes)
- [License](#license)

---

## What It Does

### Public Users (no login)
- Browse approved property listings
- Search by city, province, or property type
- View full property details with an image gallery and lightbox
- Share listings via WhatsApp
- Contact the platform by phone or email

### Dealers (login required)
- Register with business details (pending admin approval)
- Once approved:
  - Add, edit, and manage their own property listings
  - Upload up to 10 images per property (stored on ImgBB)
  - Pick a map location with an interactive Leaflet picker
  - Toggle listings between Sold and Unsold
  - See view counts per property
  - Change their own password
- Cannot delete properties or their account (business rule)

### Admins (private login)
- Approve, reject, or delete dealers
- Reset dealer passwords
- View and delete any property (with cascade to images)
- See all private fields: full address, Google Maps URL, dealer phone/WhatsApp
- Change their own password

---

## Tech Stack

**Backend**
- PHP 8.5 (NTS, Windows local dev)
- MySQL 8 (via MySQL Workbench)
- PDO with prepared statements
- PHP sessions for authentication

**Frontend**
- HTML5, CSS3, vanilla JavaScript
- Bootstrap 5.3 (via CDN)
- Custom design system in `frontend/shared/css/style.css`
- Partials injection for navbar + footer

**Third-party services (all free, no API keys required unless noted)**
- [Font Awesome 6](https://fontawesome.com/) — icons
- [Leaflet](https://leafletjs.com/) — interactive map picker
- [OpenStreetMap](https://www.openstreetmap.org/) — map tiles
- [Nominatim](https://nominatim.org/) — address search + reverse geocoding
- [GLightbox](https://biati-digital.github.io/glightbox/) — image lightbox
- [ImgBB](https://api.imgbb.com/) — image storage (free API key required)

---

## Architecture
