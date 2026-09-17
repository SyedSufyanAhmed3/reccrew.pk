# RAC Crew — Website

RacCrew Service Maintenance Engineering | AC & HVAC services in Karachi, Pakistan.

A multi-page HTML/CSS/JS site with a PHP + PHPMailer backend for the contact and quote forms.
No frameworks required beyond PHPMailer — deploys on any standard cPanel/shared hosting or XAMPP.

---

## 1. Project structure

```
raccrew-website/
├── index.html, about.html, services.html, contact.html, quote.html, gallery.html,
│   projects.html, privacy-policy.html, terms.html, 404.html
├── ac-installation.html, ac-repair.html, ac-maintenance.html, ac-gas-charging.html,
│   ac-cleaning.html, ac-troubleshooting.html, split-ac-services.html,
│   inverter-ac-services.html, commercial-hvac-services.html, emergency-ac-service.html
├── assets/
│   ├── css/style.css, responsive.css
│   ├── js/main.js, animations.js, gallery.js, form.js
│   ├── images/{logo,hero,technicians,services,gallery,projects}/
│   └── videos/hvac-technician.mp4
├── php/
│   ├── config.php        (SMTP + company settings — EDIT THIS)
│   ├── helpers.php       (shared sanitization / mailer / rate-limit helpers)
│   ├── contact-handler.php
│   ├── quote-handler.php
│   └── mailer/README.txt (PHPMailer install instructions)
├── vendor/phpmailer/      (place PHPMailer here if not using Composer)
├── uploads/               (used for rate-limit tracking files)
├── .htaccess, robots.txt, sitemap.xml
└── README.md
```

Every service sub-page (`ac-installation.html`, etc.) follows the same template. To add another
service page not already included, copy one of the existing `*-services.html` files and update
its heading, intro text, and image reference — the header, footer, forms and scripts stay identical.

---

## 2. XAMPP setup (local testing)

1. Copy the `raccrew-website` folder into `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac).
2. Start **Apache** from the XAMPP control panel (MySQL is not required unless you add a database later).
3. Visit `http://localhost/raccrew-website/` in your browser.
4. PHP forms will run through XAMPP's built-in PHP — no extra setup needed beyond PHPMailer (see §4).

---

## 3. General PHP setup

- Requires **PHP 8.0+**.
- No database is required for the current contact/quote forms (they email directly). If you later
  want to store submissions in MySQL, add a `php/db.php` with a PDO connection and insert a row in
  `contact-handler.php` / `quote-handler.php` before sending mail.
- `php/config.php` holds all editable settings — SMTP credentials and company details.

---

## 4. PHPMailer setup

**Option A — Composer (recommended):**
```bash
cd raccrew-website
composer require phpmailer/phpmailer
```
This creates `vendor/autoload.php`, which `php/helpers.php` detects automatically.

**Option B — Manual (no Composer/SSH on shared hosting):**
1. Download PHPMailer from https://github.com/PHPMailer/PHPMailer/releases
2. Copy its `src/` folder into `vendor/phpmailer/src/`, so you have:
   - `vendor/phpmailer/src/PHPMailer.php`
   - `vendor/phpmailer/src/SMTP.php`
   - `vendor/phpmailer/src/Exception.php`

Full details are in `php/mailer/README.txt`.

---

## 5. SMTP configuration

Edit `php/config.php`:

```php
define('SMTP_HOST', 'ENTER_SMTP_HOST');
define('SMTP_USERNAME', 'ENTER_SMTP_USERNAME');
define('SMTP_PASSWORD', 'ENTER_SMTP_APP_PASSWORD');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
```

**For Gmail / Google Workspace:** `SMTP_HOST` = `smtp.gmail.com`, port `587`, encryption `tls`.
You must use a 16-character **App Password** (Google Account → Security → 2-Step Verification →
App Passwords) — your normal Gmail password will not work for SMTP once 2FA is enabled.

Never commit real credentials to a public repository. `.htaccess` already blocks direct web
access to `config.php` and `helpers.php`.

---

## 6. Testing the contact form

1. Complete SMTP setup (§4–5).
2. Open `contact.html` (or `quote.html`) in a browser served by PHP (XAMPP/Apache — not `file://`).
3. Submit the form. On success you'll see the "Request Submitted Successfully" modal.
4. Check the inbox at the address in `NOTIFY_EMAIL` (defaults to the company email) for the lead
   notification, and the submitted email address for the auto-reply.
5. If sending fails, the form shows an error message with Call/WhatsApp fallback — check your
   PHP error log for the specific SMTP error (`error_log` calls are in `helpers.php`).

---

## 7. Deploying to cPanel / shared hosting

1. Upload the entire `raccrew-website` folder contents to `public_html/` (or a subfolder) via
   File Manager or FTP.
2. Set folder permissions: `755` for directories, `644` for files. `uploads/` needs to be writable
   (`755` or `775`) since it stores lightweight rate-limit tracking files.
3. Install PHPMailer (§4) — Composer via cPanel's Terminal if available, otherwise manual install.
4. Edit `php/config.php` with real SMTP credentials.
5. Confirm PHP version in cPanel → MultiPHP Manager is set to 8.0+.
6. Update `robots.txt` and `sitemap.xml` if your domain differs from `raccre.pk`.
7. Test both forms live (§6) before announcing the site.

---

## 8. Replacing images

All images live under `assets/images/` and are currently **generated placeholders** clearly
labeled as such (e.g. "Professional HVAC Technician", "AC Installation"). To replace them:

- Keep the same filename and folder so no HTML needs editing, **or**
- Update the `src`/`srcset` in the relevant `.html` file if you rename a file.
- Recommended sizes: hero images 1600×900+, technician photos 800×1000 (portrait), gallery/project
  images 900×900 or 1200×750, service page images 1200×900.
- Prefer `.webp` for production; `.jpg`/`.png` also work.
- Do not present stock technician photos as real employees — keep the "Professional HVAC
  Technician" labeling until real team photos are available (see About/Home sections).

---

## 9. Replacing the background video

The current `assets/videos/hvac-technician.mp4` is a **generated placeholder** (solid color with
on-screen text) — not real footage. To replace it:

1. Export your real footage as H.264 MP4, ideally under 15MB and 1080p or lower for mobile performance.
2. Save it as `assets/videos/hvac-technician.mp4` (same filename — no HTML changes needed), or
3. Update the `<source src="...">` paths in `index.html` if you use a different filename.
4. Also replace `assets/images/hero/hvac-poster.webp` with a real still frame from the video —
   it's shown while the video loads and to visitors on data-saving mode.

---

## 10. Changing company information

Company details are centralized in two places:

- **`php/config.php`** — used inside emails (company name, phone, email, address).
- **`build.py` constants** (if you regenerate pages) or directly inside each `.html` file's
  header/footer if editing manually — phone (`tel:+923124986998`), WhatsApp number
  (`923124986998` in `assets/js/main.js`), email, and address appear in the nav, footer, contact
  page, and structured data (`schema.org` JSON-LD block in every page's `<head>`).

Search-and-replace across all `.html` files is the fastest way to update contact details site-wide
if you're editing manually rather than through the Python generator.

---

## 11. Security checklist

- [x] Server-side validation on both forms (never trust client-side JS alone)
- [x] Input sanitized (`strip_tags`, newline stripping to prevent header injection)
- [x] Honeypot field (`name="website"`) on both forms to catch basic bots
- [x] Basic file-based rate limiting (5 submissions / 10 minutes per IP)
- [x] SMTP credentials kept server-side only, in `php/config.php`
- [x] `.htaccess` blocks direct access to `config.php`, `helpers.php`, and `.log`/`.sql`/`.env` files
- [x] Directory listing disabled (`Options -Indexes`)
- [x] Security headers set (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- [ ] Enable HTTPS redirect in `.htaccess` once an SSL certificate is active (commented block provided)
- [ ] Consider adding a CAPTCHA (e.g. hCaptcha/Turnstile) if spam volume increases beyond what the honeypot/rate-limit stop

---

## 12. SEO checklist

- [x] Unique `<title>` and meta description per page
- [x] Canonical URL per page
- [x] Open Graph + Twitter Card metadata per page
- [x] `HVACBusiness` (Schema.org) structured data with name, phone, email, address on every page
- [x] Semantic heading hierarchy (`h1` per page, `h2`/`h3` for sections)
- [x] Descriptive `alt` text on all images
- [x] `robots.txt` and `sitemap.xml` included
- [x] Clean, descriptive URLs (`ac-installation.html`, `commercial-hvac-services.html`, etc.)
- [ ] Update `sitemap.xml` if you add/rename pages
- [ ] Replace placeholder images with real, compressed photos before launch (page speed affects SEO)
- [ ] Register the site with Google Search Console and submit `sitemap.xml` after going live

---

## Notes on content accuracy

This site intentionally avoids inventing certifications, years of experience, customer counts, or
testimonials that weren't supplied. Where such content is commonly expected (e.g. an About page
"why choose us" section), the copy is written around what's actually known (services offered,
owner name, coverage area) rather than fabricated claims. Update these sections with real facts
as they become available.
#   r e c c r e w . p k  
 