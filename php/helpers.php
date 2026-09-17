<?php
/**
 * RAC Crew — shared backend helpers
 * Sanitization, basic rate limiting, JSON responses, and a thin
 * wrapper around PHPMailer so both handlers share one code path.
 */

require_once __DIR__ . '/config.php';

/** Send a JSON response and stop execution. */
function json_response(bool $success, string $message, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

/** Trim, strip tags, and normalize whitespace on a raw POST value. */
function clean_input(string $value): string
{
    $value = trim($value);
    $value = strip_tags($value);
    $value = str_replace(["\r", "\n"], ' ', $value); // prevent header injection in single-line fields
    return $value;
}

/** Same as clean_input but preserves newlines, for message/textarea fields. */
function clean_textarea(string $value): string
{
    $value = trim(strip_tags($value));
    return $value;
}

/** Character count that works even if the mbstring extension is disabled. */
function safe_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

/** Basic email format check. */
function is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Basic phone format check — digits, spaces, +, -, 7 to 15 digits. */
function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[+]?[0-9\s-]{7,15}$/', $phone);
}

/**
 * Very small file-based rate limiter keyed by IP address.
 * Not a replacement for a proper WAF, but stops naive spam bots
 * on shared hosting where nothing else is available.
 */
function check_rate_limit(): bool
{
    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0755, true);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = RATE_LIMIT_DIR . '/' . md5($ip) . '.json';
    $now = time();
    $attempts = [];

    if (is_file($file)) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data)) {
            $attempts = array_filter($data, function ($ts) use ($now) {
                return ($now - $ts) < RATE_LIMIT_WINDOW_SECONDS;
            });
        }
    }

    if (count($attempts) >= RATE_LIMIT_MAX) {
        return false; // too many attempts
    }

    $attempts[] = $now;
    @file_put_contents($file, json_encode(array_values($attempts)));
    return true;
}

/**
 * Append a valid lead to a server-side CSV backup, keyed by form type.
 * This runs BEFORE email is attempted so a submission is preserved even
 * if SMTP fails, is misconfigured, or the host is temporarily down.
 * The uploads/ directory is blocked from direct browser access via
 * uploads/.htaccess (Require all denied) — never expose this file
 * through a public URL or a download endpoint.
 */
function save_lead(string $type, array $fields): bool
{
    if (!is_dir(LEADS_DIR)) {
        @mkdir(LEADS_DIR, 0755, true);
    }

    $safeType = preg_replace('/[^a-z0-9_-]/i', '', $type) ?: 'lead';
    $file = LEADS_DIR . '/' . $safeType . '-leads.csv';
    $isNew = !is_file($file);

    $fp = @fopen($file, 'a');
    if (!$fp) {
        error_log('RAC Crew: unable to open lead backup file: ' . $file);
        return false;
    }

    if (flock($fp, LOCK_EX)) {
        if ($isNew) {
            fputcsv($fp, array_merge(['submitted_at', 'ip'], array_keys($fields)));
        }
        fputcsv($fp, array_merge(
            [date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            array_values($fields)
        ));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return true;
}

/**
 * Send an email via PHPMailer over SMTP.
 * Requires vendor/autoload.php (installed via Composer) OR the
 * manually-downloaded PHPMailer source in vendor/phpmailer/src/.
 * See php/mailer/README.txt for setup instructions.
 */
function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = '', array $replyTo = []): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    $manual   = __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';

    if (is_file($autoload)) {
        require_once $autoload;
    } elseif (is_file($manual)) {
        require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
    } else {
        error_log('PHPMailer not installed. See php/mailer/README.txt');
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        if (!empty($replyTo['email']) && is_valid_email($replyTo['email'])) {
            $mail->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('RAC Crew mailer error: ' . $e->getMessage());
        return false;
    }
}

/** Wraps a body table in RAC Crew's shared HTML email shell. */
function email_shell(string $title, string $innerHtml): string
{
    $brand = COMPANY_BRAND;
    $company = COMPANY_NAME;
    $phone = COMPANY_PHONE;
    $email = COMPANY_EMAIL;
    $website = COMPANY_WEBSITE;

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>{$title}</title></head>
<body style="margin:0;padding:0;background:#eef2f4;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f4;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border-radius:10px;overflow:hidden;">
        <tr>
          <td style="background:#0f2438;padding:22px 28px;">
            <span style="color:#ffffff;font-size:20px;font-weight:bold;">{$brand}</span><br>
            <span style="color:#8fb2c4;font-size:12px;">{$company}</span>
          </td>
        </tr>
        <tr>
          <td style="padding:28px;color:#2a3742;font-size:15px;line-height:1.6;">
            {$innerHtml}
          </td>
        </tr>
        <tr>
          <td style="background:#f7f9fa;padding:18px 28px;font-size:12px;color:#54626c;">
            {$company}<br>
            Phone: {$phone} &nbsp;|&nbsp; Email: {$email} &nbsp;|&nbsp; {$website}
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
