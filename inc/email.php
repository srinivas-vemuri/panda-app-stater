<?php
// -----------------------------------------------------------------------------
// inc/email.php
// Universal email sender — automatically switches between SMTP and PHP mail()
// -----------------------------------------------------------------------------
// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer files manually
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendmail($to, $subject, $message, $from = null, $isHtml = true): bool
{
    // Load settings
    $appName          = getSettingValue('appname') ?: 'Panda PHP App';
    $defaultFrom      = getSettingValue('appadminemail') ?: '';
    $smtpHost         = getSettingValue('appsmtphost') ?: '';
    $smtpPort         = (int)(getSettingValue('appsmtpport') ?: 0);
    $smtpUser         = getSettingValue('appsmtpusername') ?: '';
    $smtpPass         = getSettingValue('appsmtppassword') ?: '';

    // Determine sender email
    $fromEmail = $from ?: $defaultFrom;
    if (empty($fromEmail)) {
        $host = preg_replace('/^www\./', '', $_SERVER['SERVER_NAME']);
        $fromEmail = "no-reply@{$host}";
    }

    // Check if SMTP mode should be used
    $useSMTP = !empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass) && $smtpPort > 0;

    // -----------------------------------------------------------------------------
    // SMTP MODE (using PHPMailer)
    // -----------------------------------------------------------------------------
    if ($useSMTP) {

        $mail = new PHPMailer(true); // Enable exceptions

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->Port       = $smtpPort;
            $mail->SMTPDebug  = 0; // Set to 2 for debugging

            // Secure connection based on port
            if (in_array($smtpPort, [465, 587])) {
                $mail->SMTPSecure = $smtpPort == 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Enable verbose debug output if needed
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            // Recipients
            $mail->setFrom($fromEmail, $appName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);
            
            // Optional: Add DKIM signing if you have it configured
            // $mail->DKIM_domain = 'yourdomain.com';
            // $mail->DKIM_private = '/path/to/private/key';
            // $mail->DKIM_selector = 'default';

            $result = $mail->send();
            
            // Log success
            file_put_contents(__DIR__ . '/../logs/mail_debug.log',
                "[" . date('c') . "] SMTP sent OK to {$to}\n", FILE_APPEND);

            return $result;

        } catch (Exception $e) {
            // Log detailed error information
            $errorMsg = "SMTP error to {$to}: {$e->getMessage()}";
            file_put_contents(__DIR__ . '/../logs/mail_debug.log', 
                "[" . date('c') . "] {$errorMsg}\n", FILE_APPEND);
            return false;
        }
    }

    // -----------------------------------------------------------------------------
    // FALLBACK: PHP MAIL()
    // -----------------------------------------------------------------------------
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "From: {$appName} <{$fromEmail}>";
    $headers[] = "Reply-To: {$fromEmail}";
    $headers[] = $isHtml
        ? "Content-Type: text/html; charset=UTF-8"
        : "Content-Type: text/plain; charset=UTF-8";
    $headers = implode("\r\n", $headers);

    $result = @mail($to, $subject, $message, $headers, "-f{$fromEmail}");

    file_put_contents(__DIR__ . '/../logs/mail_debug.log',
        "[" . date('c') . "] PHP mail() to {$to} => " . ($result ? "OK" : "FAIL") . "\n",
        FILE_APPEND);

    return $result;
}
?>