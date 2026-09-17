<?php
/**
 * RAC Crew — Site configuration
 * -------------------------------------------------------------
 * Fill in your real SMTP credentials before going live.
 * NEVER commit real credentials to a public repository.
 * For Gmail / Google Workspace you must use an "App Password",
 * not your normal account password (Google Account > Security >
 * 2-Step Verification > App Passwords).
 * -------------------------------------------------------------
 */

// ---- SMTP SETTINGS (edit these) ----
define('SMTP_HOST', 'ENTER_SMTP_HOST');          // e.g. smtp.gmail.com
define('SMTP_USERNAME', 'ENTER_SMTP_USERNAME');  // e.g. raccrewsmenengineering@gmail.com
define('SMTP_PASSWORD', 'ENTER_SMTP_APP_PASSWORD'); // Gmail App Password, NOT your login password
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');                // 'tls' or 'ssl'

// ---- COMPANY DETAILS (used inside emails) ----
define('COMPANY_NAME', 'RacCrew Service Maintenance Engineering');
define('COMPANY_BRAND', 'RAC Crew');
define('COMPANY_OWNER', 'Kashif Khan Fayyaz');
define('COMPANY_PHONE', '+92 312 4986998');
define('COMPANY_WHATSAPP', '923124986998');
define('COMPANY_EMAIL', 'raccrewsmenengineering@gmail.com');
define('COMPANY_WEBSITE', 'https://raccre.pk');
define('COMPANY_ADDRESS', 'Ghani Chowrangi, SITE Area, Karachi, Pakistan, 75700');

// The address that receives new lead notification emails
define('NOTIFY_EMAIL', COMPANY_EMAIL);

// The "From" address used to send mail. Many SMTP providers require this
// to match the authenticated SMTP_USERNAME.
define('MAIL_FROM_EMAIL', SMTP_USERNAME);
define('MAIL_FROM_NAME', COMPANY_BRAND . ' Website');

// ---- SECURITY ----
// Simple file-based rate limiting: max submissions per IP per window.
define('RATE_LIMIT_MAX', 5);
define('RATE_LIMIT_WINDOW_SECONDS', 600); // 10 minutes
define('RATE_LIMIT_DIR', __DIR__ . '/../uploads/.ratelimit');

// Server-side lead backup: every valid submission is appended here as a
// CSV row BEFORE email is attempted, so a lead is never lost even if
// SMTP is unreachable or temporarily misconfigured. This folder is
// blocked from direct browser access by uploads/.htaccess.
define('LEADS_DIR', __DIR__ . '/../uploads/leads');

// Timezone used for "submission date/time" stamps in emails.
date_default_timezone_set('Asia/Karachi');
