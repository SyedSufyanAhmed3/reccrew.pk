RAC Crew — PHPMailer installation
==================================

This project uses PHPMailer to send SMTP email from php/contact-handler.php
and php/quote-handler.php via the shared php/helpers.php send_mail() function.

OPTION 1 — Composer (recommended, if your host supports SSH/Composer)
-----------------------------------------------------------------------
From the project root run:

    composer require phpmailer/phpmailer

This creates /vendor/autoload.php, which helpers.php automatically detects
and requires. No further code changes are needed.

OPTION 2 — Manual install (shared hosting without Composer/SSH)
-----------------------------------------------------------------------
1. Download the latest PHPMailer release from:
   https://github.com/PHPMailer/PHPMailer/releases
2. Extract it and copy the "src" folder into:
   vendor/phpmailer/src/
   so you end up with:
     vendor/phpmailer/src/PHPMailer.php
     vendor/phpmailer/src/SMTP.php
     vendor/phpmailer/src/Exception.php
3. No further code changes are needed — helpers.php automatically falls
   back to this manual path if vendor/autoload.php is not present.

CONFIGURE SMTP
-----------------------------------------------------------------------
Edit php/config.php and set:
  SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD, SMTP_PORT, SMTP_ENCRYPTION

For Gmail / Google Workspace:
  - SMTP_HOST = smtp.gmail.com
  - SMTP_PORT = 587
  - SMTP_ENCRYPTION = tls
  - SMTP_USERNAME = your full Gmail address
  - SMTP_PASSWORD = a 16-character "App Password" generated at
    Google Account > Security > 2-Step Verification > App Passwords
    (a normal account password will NOT work once 2FA is enabled,
    and Google recommends App Passwords/OAuth over regular passwords
    for third-party apps in general).

NEVER commit real SMTP credentials to a public git repository. On shared
hosting, keep config.php outside the public web root if your host allows
it, or restrict access to it via .htaccess (already included in this
project's root .htaccess).
