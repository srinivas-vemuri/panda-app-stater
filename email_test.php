<?php


require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/email.php';

// --- CONFIG ---
$to = 'vpsrinivas32@gmail.com'; // replace with your test address
$subject = '📬 Test Email from Panda PHP xxx
';

// --- Prepare data for template ---
$data = [
    'name' => 'John Doe',
    'username' => 'john123',
    'code' => 'ABC123XYZ',
    'appName' => getSettingValue('appname'),
    'organisation_signature' => getSettingValue('appemailsignature')
];

// --- Render template ---
$templateHtml = renderTemplate('email_test', $data);

// --- Wrap it in email design ---
$finalHtml = wrapEmailHtml($templateHtml, $subject);


// Send it (assuming sendmail() expects HTML)
sendmail($to, $subject, $finalHtml, null, true);

// --- Optional: send or preview ---
if (isset($_GET['preview'])) {
    // Preview in browser
    header('Content-Type: text/html; charset=UTF-8');
    echo $finalHtml;
    exit;
}



echo "✅ Test email sent to {$to}. Use ?preview=1 to view in browser.";
