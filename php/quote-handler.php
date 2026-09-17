<?php
/**
 * RAC Crew — Quote request form handler
 * Same pattern as contact-handler.php with quote-specific fields.
 */

require_once __DIR__ . '/helpers.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.', 405);
}

if (!empty($_POST['website'])) {
    json_response(true, 'Request submitted successfully.');
}

if (!check_rate_limit()) {
    json_response(false, 'Too many requests. Please try again later or call/WhatsApp us directly.', 429);
}

$name        = clean_input($_POST['name'] ?? '');
$phone       = clean_input($_POST['phone'] ?? '');
$email       = clean_input($_POST['email'] ?? '');
$propertyType= clean_input($_POST['property_type'] ?? '');
$service     = clean_input($_POST['service'] ?? '');
$acType      = clean_input($_POST['ac_type'] ?? '');
$unitsCount  = clean_input($_POST['quantity'] ?? '');
$address     = clean_input($_POST['address'] ?? '');
$message     = clean_textarea($_POST['message'] ?? '');

$errors = [];
if ($name === '' || safe_strlen($name) < 2)          $errors[] = 'A valid name is required.';
if (!is_valid_phone($phone))                        $errors[] = 'A valid phone number is required.';
if (!is_valid_email($email))                        $errors[] = 'A valid email address is required.';
if ($service === '')                                $errors[] = 'Please select the service you need a quote for.';
if ($address === '' || safe_strlen($address) < 5)     $errors[] = 'Please provide the property address.';

if (!empty($errors)) {
    json_response(false, implode(' ', $errors), 422);
}

$submittedAt = date('d M Y, h:i A');

// ---- Server-side backup (never lose a valid lead, even if email fails) ----
save_lead('quote', [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'property_type' => $propertyType,
    'service' => $service,
    'ac_type' => $acType,
    'units_count' => $unitsCount,
    'address' => $address,
    'message' => $message,
]);

$notifyBody = "
<h2 style='color:#0f2438;margin-top:0;'>New Quote Request</h2>
<table role='presentation' width='100%' style='border-collapse:collapse;'>
  <tr><td style='padding:6px 0;color:#54626c;width:160px;'>Customer Name</td><td style='padding:6px 0;'><strong>{$name}</strong></td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Phone</td><td style='padding:6px 0;'>{$phone}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Email</td><td style='padding:6px 0;'>{$email}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Property Type</td><td style='padding:6px 0;'>" . ($propertyType ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Service Needed</td><td style='padding:6px 0;'>{$service}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>AC Type</td><td style='padding:6px 0;'>" . ($acType ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Number of Units</td><td style='padding:6px 0;'>" . ($unitsCount ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Address</td><td style='padding:6px 0;'>{$address}</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;vertical-align:top;'>Notes</td><td style='padding:6px 0;'>" . nl2br($message ?: '—') . "</td></tr>
  <tr><td style='padding:6px 0;color:#54626c;'>Submitted</td><td style='padding:6px 0;'>{$submittedAt}</td></tr>
</table>
";

$sentToCompany = send_mail(
    NOTIFY_EMAIL,
    COMPANY_BRAND,
    'New Quote Request — ' . $name,
    email_shell('New Quote Request', $notifyBody),
    '',
    ['email' => $email, 'name' => $name]
);

$replyBody = "
<h2 style='color:#0f2438;margin-top:0;'>Thank you, {$name}.</h2>
<p>Thank you for requesting a quote from <strong>" . COMPANY_NAME . "</strong>.</p>
<p>We have received the details of your project and will get back to you with a quote shortly.</p>
<p style='margin-top:20px;color:#54626c;font-size:13px;'>Reference submitted: {$submittedAt}</p>
";

send_mail(
    $email,
    $name,
    'Your quote request — ' . COMPANY_BRAND,
    email_shell('Quote Request Received', $replyBody)
);

if ($sentToCompany) {
    json_response(true, 'Quote request submitted successfully. We will get back to you shortly.');
}

json_response(false, 'We could not send your request via email right now. Please call ' . COMPANY_PHONE . ' or message us on WhatsApp.', 502);
