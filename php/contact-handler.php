<?php
/**
 * RAC Crew — Contact / service request form handler
 * Validates + sanitizes input server-side (never trust the browser),
 * emails the notification to the company inbox, then sends an
 * auto-reply confirmation to the customer.
 */

require_once __DIR__ . '/helpers.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.', 405);
}

// Honeypot — bots fill every field including hidden ones.
if (!empty($_POST['website'])) {
    json_response(true, 'Request submitted successfully.'); // pretend success, drop silently
}

if (!check_rate_limit()) {
    json_response(false, 'Too many requests. Please try again later or call/WhatsApp us directly.', 429);
}

// ---- Collect + sanitize ----
$name        = clean_input($_POST['name'] ?? '');
$phone       = clean_input($_POST['phone'] ?? '');
$email       = clean_input($_POST['email'] ?? '');
$service     = clean_input($_POST['service'] ?? '');
$acType      = clean_input($_POST['ac_type'] ?? '');
$prefDate    = clean_input($_POST['preferred_date'] ?? '');
$prefTime    = clean_input($_POST['preferred_time'] ?? '');
$address     = clean_input($_POST['address'] ?? '');
$message     = clean_textarea($_POST['message'] ?? '');

// ---- Server-side validation ----
$errors = [];
if ($name === '' || safe_strlen($name) < 2)          $errors[] = 'A valid name is required.';
if (!is_valid_phone($phone))                        $errors[] = 'A valid phone number is required.';
if (!is_valid_email($email))                        $errors[] = 'A valid email address is required.';
if ($service === '')                                $errors[] = 'Please select the service required.';
if ($address === '' || safe_strlen($address) < 5)     $errors[] = 'Please provide your address.';
if ($message === '' || safe_strlen($message) < 10)    $errors[] = 'Please describe your request (min. 10 characters).';

if (!empty($errors)) {
    json_response(false, implode(' ', $errors), 422);
}

$submittedAt = date('d M Y, h:i A');

// ---- Server-side backup (never lose a valid lead, even if email fails) ----
save_lead('contact', [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'service' => $service,
    'ac_type' => $acType,
    'preferred_date' => $prefDate,
    'preferred_time' => $prefTime,
    'address' => $address,
    'message' => $message,
]);

// ---- Notification email (to company) ----
$notifyBody = "
<h2 style='color:#0f2438;margin-top:0;'>New Service Request</h2>
<table role='presentation' width='100%' style='border-collapse:collapse;'>
  <tr><td style='padding:6px 0;color:#54626c;width:160px;'>Customer Name</td><td style='padding:6px 0;'><strong>{$name}</strong></td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Phone</td><td style='padding:6px 0;'>{$phone}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Email</td><td style='padding:6px 0;'>{$email}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Service</td><td style='padding:6px 0;'>{$service}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>AC Type</td><td style='padding:6px 0;'>" . ($acType ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Preferred Date</td><td style='padding:6px 0;'>" . ($prefDate ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Preferred Time</td><td style='padding:6px 0;'>" . ($prefTime ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Address</td><td style='padding:6px 0;'>{$address}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;vertical-align:top;'>Message</td><td style='padding:6px 0;'>" . nl2br($message) . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Submitted</td><td style='padding:6px 0;'>{$submittedAt}</td></tr>
</table>
";

$sentToCompany = send_mail(
    NOTIFY_EMAIL,
    COMPANY_BRAND,
    'New Service Request — ' . $name,
    email_shell('New Service Request', $notifyBody),
    '',
    ['email' => $email, 'name' => $name]
);

// ---- Auto-reply email (to customer) ----
$replyBody = "
<h2 style='color:#0f2438;margin-top:0;'>Thank you, {$name}.</h2>
<p>Thank you for contacting <strong>" . COMPANY_NAME . "</strong>.</p>
<p>We have received your service request successfully. Our team will review it and contact you shortly regarding the next steps.</p>
<p style='margin-top:20px;color:#54626c;font-size:13px;'>Reference submitted: {$submittedAt}</p>
";

$sentToCustomer = send_mail(
    $email,
    $name,
    'We received your request — ' . COMPANY_BRAND,
    email_shell('Request Received', $replyBody)
);

if ($sentToCompany) {
    json_response(true, 'Request submitted successfully. We will contact you shortly.');
}

// Notification failed (e.g. SMTP not configured yet) — tell the truth, give a fallback.
json_response(false, 'We could not send your request via email right now. Please call ' . COMPANY_PHONE . ' or message us on WhatsApp.', 502);
