# The Wave Men's Salon

Production-ready salon management system — PHP 8.2 MVC, MySQL 8, Bootstrap 5, OTP auth, Google Cloud deployment.

## Stack

- **Backend:** PHP 8.2+ MVC, PDO/MySQL 8.0
- **Frontend:** Bootstrap 5, jQuery, AJAX, Chart.js, Cormorant Garamond + Outfit
- **Theme:** Luxury black `#121212` / gold `#D4AF37`, glassmorphism, collapsible sidebar
- **Auth:** Phone OTP (Twilio / Vonage) with email/log fallback, 5-min expiry, 3 attempts / 10 min rate limit
- **Cloud:** Cloud Run, Cloud SQL, Cloud Storage, Secret Manager, Cloud Build, Cloud Scheduler

## Quick start (XAMPP)

1. Ensure Apache + MySQL are running in XAMPP.
2. Document root already points at `C:\xampp\htdocs` — open:

   `http://localhost/wave`

3. Create DB & seed:

```bash
C:\xampp\php\php.exe scripts\migrate.php
```

Or import `database/database.sql` via phpMyAdmin.

4. (Optional) Composer packages for DomPDF / Stripe / Twilio:

```bash
composer install
```

The app boots without Composer using `config/bootstrap.php`.

## Demo accounts

| Role | Phone | Notes |
|------|-------|-------|
| Admin | `+919999000001` | OTP printed in `storage/logs/app.log` when `SMS_PROVIDER=log` |
| Customer | `+919876543210` | Same OTP log flow |
| Staff | `+919999000002` | Rahul Sharma |

Default password hash in seed is Laravel's `password` demo hash (`password`) for optional password fields — primary auth is OTP.

## Features

### Customer
- Home, About, Services (filter), Pricing, Gallery, Staff, Book, Reviews, Contact (Maps embed)
- OTP register/login with auto-advancing inputs + resend
- Profile (name, email, phone re-verify, password, avatar upload)
- Booking with real-time slot conflict detection
- Booking ID + QR, appointment history, cancel
- Loyalty tiers: Standard / Gold / Platinum / Diamond
- Invoices (HTML/PDF when DomPDF installed)

### Admin
- Stats cards + Chart.js daily/weekly/monthly/yearly revenue
- CRUD: customers, staff, services, appointments, invoices, offers, gallery, reviews, attendance, salaries, expenses, inventory, settings
- CSV export of appointments/payments

### Integrations (env-driven)
- Twilio / Vonage SMS
- Stripe / Razorpay payments + webhook
- Google Calendar event sync
- Google Cloud Storage for avatars/gallery
- Firebase Cloud Messaging notifications
- Google Charts QR (SVG fallback)

## Docker

```bash
docker compose up --build
```

App: `http://localhost:8080` · MySQL: `3307` · Redis included.

## Google Cloud one-click

```bash
export PROJECT_ID=your-gcp-project
chmod +x scripts/deploy-gcp.sh
./scripts/deploy-gcp.sh
```

Creates Artifact Registry, Cloud SQL (automated backups), Storage bucket, Secret Manager secrets, Scheduler job, and submits Cloud Build → Cloud Run.

Configure IAM: Cloud Run SA needs **Cloud SQL Client**, **Secret Manager Secret Accessor**, **Storage Object Admin**.

## Environment

Copy `.env.example` → `.env`. Key switches:

| Variable | Purpose |
|----------|---------|
| `SMS_PROVIDER` | `log` / `twilio` / `vonage` |
| `PAYMENT_GATEWAY` | `offline` / `stripe` / `razorpay` |
| `USE_REDIS` | Rate-limit cache |
| `USE_CLOUD_STORAGE` | GCS uploads |
| `FCM_ENABLED` | Push notifications |

## GDPR notes

- Phone/email stored for auth & booking only
- Profile deletion can be handled via admin customer tools
- Prefer Secret Manager for API keys; never commit `.env`
- Enable Cloud SQL automated backups and SSL

## Project layout

```
app/Controllers|Models|Views|Services|Middleware|Core
config/   database/   public/   routes/   scripts/   docker/
```

## License

Proprietary — The Wave Men's Salon.
