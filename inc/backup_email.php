<?php
// -----------------------------------------------------------------------------
// inc/email.php
// Universal email sender — automatically switches between SMTP and PHP mail()
// -----------------------------------------------------------------------------

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
        require_once __DIR__ . '/PHPMailer/PHPMailerAutoload.php';
    

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->Port       = $smtpPort;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;

        // Secure connection based on port
        if (in_array($smtpPort, [465, 587])) {
            $mail->SMTPSecure = $smtpPort == 465 ? 'ssl' : 'tls';
        }

        $mail->setFrom($fromEmail, $appName);
        $mail->addAddress($to);
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        try {
            $result = $mail->send();
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../logs/mail_debug.log', 
                "[" . date('c') . "] SMTP error: {$e->getMessage()}\n", FILE_APPEND);
            return false;
        }

        // Log result
        file_put_contents(__DIR__ . '/../logs/mail_debug.log',
            "[" . date('c') . "] SMTP sent OK to {$to}\n", FILE_APPEND);

        return $result;
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
