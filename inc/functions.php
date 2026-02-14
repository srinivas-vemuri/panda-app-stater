<?php
// inc/functions.php

// Start session once
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';

/**
 * Check login flag in session.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_logged_in']);
}

/**
 * Check if current user is super admin (type 2)
 */
function is_super_admin(): bool {
    return ($_SESSION['user_type'] ?? 0) == 2;
}

/**
 * Check if current user is admin (type 1 or 2)
 */
function is_admin(): bool {
    $user_type = $_SESSION['user_type'] ?? 0;
    return $user_type == 1 || $user_type == 2;
}

function require_admin_or_redirect(): void {
    if (!is_admin()) {
        redirect_to('index.php');
    }
}


/**
 * Check if current user is developer (super admin only)
 */
function is_developer(): bool {
    return is_super_admin();
}

/**
 * Get current user type
 */
function get_current_user_type(): int {
    return $_SESSION['user_type'] ?? 0;
}


/**
 * Get current user ID.
 */
function get_current_user_id(): ?string {
    return $_SESSION['user_id'] ?? null;
}

 /* Get all admin email addresses for notifications
 */
function get_admin_emails(): array {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT user_email FROM users WHERE user_type = 1 AND user_status = 1");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Attempt login with username/email and password.
 */
function do_login(string $username, string $password): bool {
    $pdo = get_db_connection();
    
    $sql = "SELECT user_id, user_username, user_email, user_password, user_type, user_status 
            FROM users 
            WHERE (user_username = ? OR user_email = ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Check if password is correct
        if (md5($password) === $user['user_password']) {
            // Check if user is active
            if ($user['user_status'] == 1) {
                session_regenerate_id(true);
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_username'] = $user['user_username'];
                return true;
            } else {
                // User exists and password is correct, but account is inactive
                $_SESSION['login_error'] = 'inactive';
                return false;
            }
        }
    }
    
    // If we get here, either user doesn't exist or password is wrong
    $_SESSION['login_error'] = 'invalid';
    return false;
}

/**
 * Guard: if not super admin, redirect to index.php
 */
function require_super_admin_or_redirect(): void {
    require_login_or_redirect();
    if (!is_super_admin()) {
        redirect_to('index.php');
    }
}



/**
 * Destroy session and logout.
 */
function do_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Redirect helper and exit.
 */
function redirect_to(string $relative): void {
    header('Location: ' . $relative);
    exit;
}

/**
 * Guard: if not logged in, redirect to login.php
 */
function require_login_or_redirect(): void {
    if (!is_logged_in()) {
        redirect_to('login.php');
    }
}



/**
 * FUNCTIONS FOR settings.php
 */

/**
 * Reads and returns settings from /inc/settings.json.
 * If file does not exist, auto-creates an empty structure.
 * Optionally returns a single group (e.g., 'basic', 'branding').
 */
function getSettings($group = null) {
    $settingsFile = __DIR__ . '/settings.json';

    // Auto-create file if missing
    if (!file_exists($settingsFile)) {
        $defaultSettings = ['settings' => []];
        file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
    }

    $json = json_decode(file_get_contents($settingsFile), true);
    if (!$json) return [];

    if ($group && isset($json['settings'][$group])) {
        return $json['settings'][$group];
    }
    return $json['settings'];
}

/**
 * Retrieves a single setting value by field name across all groups.
 * Returns null if the field is not found.
 */
function getSettingValue($field) {
    $all = getSettings();
    foreach ($all as $group => $fields) {
        if (isset($fields[$field]['value'])) {
            return $fields[$field]['value'];
        }
    }
    return null;
}


/**
 * Updates a specific settings group with new form data.
 * Automatically sanitizes values, merges them with existing JSON,
 * and saves atomically to prevent corruption.
 */
function updateSettingsGroup($group, $data) {
    $settingsFile = __DIR__ . '/settings.json';
    $json = json_decode(file_get_contents($settingsFile), true);
    if (!$json) $json = ['settings' => []];

    if (!isset($json['settings'][$group])) {
        $json['settings'][$group] = [];
    }

    foreach ($data as $key => $value) {
        if ($key === 'form_name') continue;
        if (!isset($json['settings'][$group][$key])) {
            $json['settings'][$group][$key] = [];
        }

        // allow HTML only for footer text
        if ($key === 'appfootertext') {
            $clean = trim($value);  // don’t encode HTML
        } else {
            $clean = htmlspecialchars(trim($value));
        }

        $json['settings'][$group][$key]['value'] = $clean;
    }

    // Save atomically
    file_put_contents($settingsFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return true;
}


/**
 * Low-level helper to update multiple individual settings fields.
 * Used internally by processAppLogo() or other bulk update operations.
 */
function updateSettings($updates) {
    $settingsFile = __DIR__ . '/settings.json';
    $json = json_decode(file_get_contents($settingsFile), true);
    if (!$json) $json = ['settings' => []];

    foreach ($updates as $key => $value) {
        foreach ($json['settings'] as $group => $fields) {
            if (isset($fields[$key])) {
                $json['settings'][$group][$key]['value'] = $value;
                continue 2;
            }
        }
    }

    file_put_contents($settingsFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}


/**
 * Handles upload, validation, and regeneration of the main 512x512 app logo.
 * Automatically creates necessary folders, validates PNG format and size,
 * deletes old logo variants, resizes to required dimensions, and updates JSON.
 */
function processAppLogo($file) {
    $uploadDir = __DIR__ . '/../assets/logos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Validate file
    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['error' => 'Invalid image file.'];
    }
    if ($info[2] !== IMAGETYPE_PNG) {
        return ['error' => 'Only PNG format allowed.'];
    }
    if ($info[0] !== 512 || $info[1] !== 512) {
        return ['error' => 'Logo must be exactly 512x512 pixels.'];
    }

    // Clean existing logos
    foreach (glob($uploadDir . 'app_logo_*.png') as $old) unlink($old);
    @unlink($uploadDir . 'favicon.png');

    // Store new logo
    $mainPath = $uploadDir . 'app_logo_512.png';
    move_uploaded_file($file['tmp_name'], $mainPath);

    // Create resized versions
    $sizes = [
        ['192', 'app_logo_192.png'],
        
        ['32', 'favicon.png']
    ];
    createResizedLogos($mainPath, $uploadDir, $sizes);

    // Update JSON
    $updates = [
       
        'appfevicon'     => 'favicon.png',
        'appicon192'     => 'app_logo_192.png',
        'appicon512'     => 'app_logo_512.png'
    ];
    updateSettings($updates);

    return ['success' => true];
}


/**
 * Resizes a given PNG source into multiple specified dimensions.
 * Maintains transparency, outputs to /assets/logos/, and overwrites existing files.
 * Utilizes PHP's GD library for efficient resampling.
 */
function createResizedLogos($source, $uploadDir, $sizes) {
    $src = imagecreatefrompng($source);
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    foreach ($sizes as $size) {
        list($dim, $filename) = $size;

        if (strpos($dim, 'x') !== false) {
            list($w, $h) = explode('x', $dim);
        } else {
            $w = $h = (int)$dim;
        }

        $dst = imagecreatetruecolor($w, $h);
        imagesavealpha($dst, true);
        $transColor = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transColor);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $srcW, $srcH);
        imagepng($dst, $uploadDir . $filename, 9);
        imagedestroy($dst);
    }

    imagedestroy($src);
}

/**
 * Renders the Branding & Design settings form.
 * Includes two upload sections:
 *  - Square Logo (512×512 PNG) → generates app icons & favicon
 *  - Rectangular Logo (PNG, ~350×150) → generates header variant automatically
 */
function renderBrandingSettingsForm() {
    $settings = getSettings('branding') ?? [];

    // Existing values from JSON
    $appregularlogo = $settings['appregularlogo']['value'] ?? '';
    $appheaderlogo  = $settings['appheaderlogo']['value'] ?? '';
    $appfevicon     = $settings['appfevicon']['value'] ?? '';
    $appthemecolor  = $settings['appthemecolor']['value'] ?? '#0d6efd';
    $appheadercolor = $settings['appheadercolor']['value'] ?? '#343a40';
    $appfootercolor = $settings['appfootercolor']['value'] ?? '#f8f9fa';
    $appicon512     = $settings['appicon512']['value'] ?? '';
    $appicon192     = $settings['appicon192']['value'] ?? '';

    $logosUrl = 'assets/logos/';

    ob_start();
?>
<form method="post" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="form_name" value="branding">

  <!-- ============================== -->
  <!-- 1️⃣ Square Logo Upload Section -->
  <!-- ============================== -->
  <div class="mb-4 p-3 border rounded bg-light">
    <h5 class="mb-3">🟦 Square Logo (512×512 PNG)</h5>
    <p class="small text-muted">
      Used for app icons, PWA, and favicon generation.<br>
      <strong>Requirements:</strong> Must be a perfect square (512×512) in PNG format.
    </p>

    <div class="mb-3">
      <label class="form-label">Current Square Logo</label><br>
      <?php if ($appicon512 && file_exists(__DIR__ . '/../assets/logos/' . $appicon512)): ?>
        <img src="<?= htmlspecialchars($logosUrl . $appicon512) ?>" alt="Square Logo"
             style="max-width:150px; border:1px solid #ccc; background:#fff; padding:4px;">
      <?php else: ?>
        <div class="border rounded p-3 text-muted">No square logo uploaded yet.</div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="square_logo" class="form-label">Upload Square Logo (512×512 PNG)</label>
      <input id="square_logo" name="square_logo" type="file" accept="image/png" class="form-control">
      <div class="form-text">
        Must be exactly 512×512 pixels. The system will generate:
        <ul class="small mb-0">
          <li>app_icon_512.png</li>
          <li>app_icon_192.png</li>
          <li>favicon.png</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- ================================ -->
  <!-- 2️⃣ Rectangular Logo Upload Section -->
  <!-- ================================ -->
  <div class="mb-4 p-3 border rounded bg-light">
    <h5 class="mb-3">🟩 Rectangular Logo (PNG, around 350×150)</h5>
    <p class="small text-muted">
      Used for general branding, login screens, and page headers.<br>
      <strong>Requirements:</strong> Must be wider than tall (recommended ~350×150) in PNG format.
    </p>

    <div class="mb-3">
      <label class="form-label">Current Rectangular Logo</label><br>
      <?php if ($appregularlogo && file_exists(__DIR__ . '/../assets/logos/' . $appregularlogo)): ?>
        <img src="<?= htmlspecialchars($logosUrl . $appregularlogo) ?>" alt="Rectangular Logo"
             style="max-width:350px; border:1px solid #ccc; background:#fff; padding:4px;">
      <?php else: ?>
        <div class="border rounded p-3 text-muted">No rectangular logo uploaded yet.</div>
      <?php endif; ?>
    </div>

    <div class="mb-3">
      <label for="rect_logo" class="form-label">Upload Rectangular Logo (PNG)</label>
      <input id="rect_logo" name="rect_logo" type="file" accept="image/png" class="form-control">
      <div class="form-text">
        Upload a wide PNG logo (recommended 350×150). The system will generate a smaller header version automatically.<br>
        Files generated:
        <ul class="small mb-0">
          <li>app_regular_logo.png (max-width: 350px via CSS)</li>
          <li>app_logo.png (max-width: 190px via CSS)</li>
        </ul>
      </div>
    </div>
  </div>

  <hr>

  <!-- ================================ -->
  <!-- 3️⃣ Theme & Color Configuration -->
  <!-- ================================ -->
  <div class="row">
    <div class="col-md-4 mb-3">
      <label for="appthemecolor" class="form-label">Theme Color</label>
      <input id="appthemecolor" name="appthemecolor" type="color" class="form-control form-control-color"
             value="<?= htmlspecialchars($appthemecolor) ?>">
      <div class="form-text">Primary brand color.</div>
    </div>

    <div class="col-md-4 mb-3">
      <label for="appheadercolor" class="form-label">Header Color</label>
      <input id="appheadercolor" name="appheadercolor" type="color" class="form-control form-control-color"
             value="<?= htmlspecialchars($appheadercolor) ?>">
    </div>

    <div class="col-md-4 mb-3">
      <label for="appfootercolor" class="form-label">Footer Color</label>
      <input id="appfootercolor" name="appfootercolor" type="color" class="form-control form-control-color"
             value="<?= htmlspecialchars($appfootercolor) ?>">
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center pt-2">
    <button type="submit" class="btn btn-primary">Save Branding Settings</button>
    <a href="?tab=branding" class="text-muted small">Reset</a>
  </div>
</form>
<?php
    echo ob_get_clean();
}


if (!function_exists('renderBasicSettingsForm')) {
    function renderBasicSettingsForm() {
        echo '<div class="alert alert-secondary">Basic settings form not implemented yet.</div>';
    }
}
if (!function_exists('renderPWASettingsForm')) {
    function renderPWASettingsForm() {
        echo '<div class="alert alert-secondary">PWA settings form not implemented yet.</div>';
    }
}
if (!function_exists('renderEmailSettingsForm')) {
    function renderEmailSettingsForm() {
        echo '<div class="alert alert-secondary">Email settings form not implemented yet.</div>';
    }
}


/**
 * Handles upload of the 512×512 square logo.
 * Generates:
 *  - app_icon_512.png (main)
 *  - app_icon_192.png
 *  - favicon.png
 */
function processSquareLogo($file) {
    $uploadDir = __DIR__ . '/../assets/logos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Validate
    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['error' => 'Invalid image file.'];
    }
    if ($info[2] !== IMAGETYPE_PNG) {
        return ['error' => 'Only PNG images are allowed.'];
    }
    if ($info[0] !== 512 || $info[1] !== 512) {
        return ['error' => 'Image must be exactly 512×512 pixels.'];
    }

    // Cleanup existing icon family
    @unlink($uploadDir . 'app_icon_512.png');
    @unlink($uploadDir . 'app_icon_192.png');
    @unlink($uploadDir . 'favicon.png');

    // Move uploaded master
    $mainPath = $uploadDir . 'app_icon_512.png';
    move_uploaded_file($file['tmp_name'], $mainPath);

    // Generate resized variants
    $sizes = [
        ['192', 'app_icon_192.png'],
        ['32', 'favicon.png']
    ];
    createResizedLogos($mainPath, $uploadDir, $sizes);

    // Update JSON references
  updateSettingsGroup('branding', [
    'appicon512' => 'app_icon_512.png',
    'appicon192' => 'app_icon_192.png',
    'appfevicon' => 'favicon.png'
]);


    return ['success' => true];
}

/**
 * Handles upload of the rectangular branding logo (PNG).
 * Generates two scaled variants:
 *  - app_regular_logo.png  → up to 350px wide
 *  - app_logo.png          → up to 250px wide (header version)
 */
function processRectangularLogo($file) {
    $uploadDir = __DIR__ . '/../assets/logos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Validate uploaded file
    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['error' => 'Invalid image file.'];
    }
    if ($info[2] !== IMAGETYPE_PNG) {
        return ['error' => 'Only PNG images are allowed.'];
    }
    if ($info[0] <= $info[1]) {
        return ['error' => 'Image must be wider than it is tall (rectangular).'];
    }
    if ($info[0] < 300 || $info[1] < 100) {
        return ['error' => 'Image too small. Recommended minimum 350×150 pixels.'];
    }

    // Clean old logo variants
    @unlink($uploadDir . 'app_regular_logo.png');
    @unlink($uploadDir . 'app_logo.png');

    // Temporary filename for processing
    $tempPath = $uploadDir . 'temp_rect_logo.png';
    move_uploaded_file($file['tmp_name'], $tempPath);

    // Generate scaled variants
    resizeRectangularLogo($tempPath, $uploadDir . 'app_regular_logo.png', 350);
    resizeRectangularLogo($tempPath, $uploadDir . 'app_logo.png', 250);

    // Remove temp file
    @unlink($tempPath);

    // Update JSON settings under branding group
    updateSettingsGroup('branding', [
        'appregularlogo' => 'app_regular_logo.png',
        'appheaderlogo'  => 'app_logo.png'
    ]);

    return ['success' => true];
}


/**
 * Resizes a rectangular PNG proportionally to a target max width.
 * - Maintains aspect ratio
 * - Preserves transparency
 * - Does not upscale if smaller than target width
 */
function resizeRectangularLogo($source, $dest, $maxWidth) {
    $src = imagecreatefrompng($source);
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    // Skip resize if already smaller
    if ($srcW <= $maxWidth) {
        copy($source, $dest);
        imagedestroy($src);
        return;
    }

    // Maintain aspect ratio
    $scale = $maxWidth / $srcW;
    $newW = (int)($srcW * $scale);
    $newH = (int)($srcH * $scale);

    // Create destination canvas with alpha
    $dst = imagecreatetruecolor($newW, $newH);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);

    // Resample proportionally
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

    // Save PNG with compression
    imagepng($dst, $dest, 9);

    // Cleanup
    imagedestroy($dst);
    imagedestroy($src);
}


/**
 * Resizes an image proportionally to fit within target dimensions.
 * Keeps aspect ratio, preserves transparency.
 */
function createResizedLogoProportionally($source, $dest, $targetW, $targetH) {
    $src = imagecreatefrompng($source);
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    // Compute new dimensions preserving aspect ratio
    $scale = min($targetW / $srcW, $targetH / $srcH);
    $newW = (int)($srcW * $scale);
    $newH = (int)($srcH * $scale);

    $dst = imagecreatetruecolor($newW, $newH);
    imagesavealpha($dst, true);
    $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $trans);

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    imagepng($dst, $dest, 9);

    imagedestroy($dst);
    imagedestroy($src);
}


/**
 * Renders the Basic Settings form.
 * Includes core application identity fields.
 * Enforces 12-character limit on appshortname (client + server).
 */
function renderBasicSettingsForm() {
    $settings = getSettings('basic') ?? [];

    // Current values
    $appname        = $settings['appname']['value'] ?? '';
    $appshortname   = $settings['appshortname']['value'] ?? '';
    $appurl         = $settings['appurl']['value'] ?? '';
    $appadminemail  = $settings['appadminemail']['value'] ?? '';
    $appfootertext  = $settings['appfootertext']['value'] ?? '';
    $appindexpage   = $settings['appindexpage']['value'] ?? '';

    ob_start(); ?>
    <form method="post" novalidate>
        <input type="hidden" name="form_name" value="basicsettings">

        <div class="mb-3">
            <label for="appname" class="form-label">Application Name</label>
            <input id="appname" name="appname" type="text" class="form-control"
                   value="<?= htmlspecialchars($appname) ?>" required>
            <div class="form-text">
                The full name of your application, shown in titles and browser tabs.
            </div>
        </div>

        <div class="mb-3">
            <label for="appshortname" class="form-label">Short Name (max 12 characters)</label>
            <input id="appshortname" name="appshortname" type="text" maxlength="12" class="form-control"
                   value="<?= htmlspecialchars($appshortname) ?>" required>
            <div id="shortnameHelp" class="form-text text-muted">
                Used in PWA manifest and mobile homescreens.
            </div>
        </div>

        <div class="mb-3">
            <label for="appurl" class="form-label">Application URL</label>
            <input id="appurl" name="appurl" type="url" class="form-control"
                   value="<?= htmlspecialchars($appurl) ?>" placeholder="https://example.com" required>
            <div class="form-text">
                Base URL of your application. Must include protocol (http/https).
            </div>
        </div>

        <div class="mb-3">
            <label for="appadminemail" class="form-label">Admin Email</label>
            <input id="appadminemail" name="appadminemail" type="email" class="form-control"
                   value="<?= htmlspecialchars($appadminemail) ?>" required>
            <div class="form-text">
                Used for system notifications and password resets.
            </div>
        </div>

        <div class="mb-3">
            <label for="appfootertext" class="form-label">Footer Text</label>
            <input id="appfootertext" name="appfootertext" type="text" class="form-control"
                   value="<?= htmlspecialchars($appfootertext) ?>">
            <div class="form-text">
                Appears in the application footer (e.g., copyright, disclaimer).
            </div>
        </div>

        <div class="mb-3">
            <label for="appindexpage" class="form-label">Index Page</label>
            <input id="appindexpage" name="appindexpage" type="text" class="form-control"
                   value="<?= htmlspecialchars($appindexpage) ?>" placeholder="index.php">
            <div class="form-text">
                Default page shown after login.
            </div>
        </div>

            <div class="form-check mb-3">
            <input type="hidden" name="RegistrationActive" value="0">
            <input class="form-check-input" type="checkbox" id="RegistrationActive" name="RegistrationActive" value="1"
            <?= !empty($settings['RegistrationActive']['value']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="RegistrationActive">Enable Registration</label>
            <div class="form-text">Allow users to register new accounts.</div>
            </div>

            <div class="form-check mb-3">
            <input type="hidden" name="StickyHeader" value="0">
            <input class="form-check-input" type="checkbox" id="StickyHeader" name="StickyHeader" value="1"
            <?= !empty($settings['StickyHeader']['value']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="StickyHeader">Sticky Header</label>
            <div class="form-text">Keep navigation bar fixed to the top of the screen.</div>
            </div>


        <div class="d-flex justify-content-between align-items-center pt-2">
            <button type="submit" class="btn btn-primary">Save Basic Settings</button>
            <a href="?tab=basic" class="text-muted small">Reset</a>
        </div>
    </form>

    <script>
      const shortInput = document.getElementById('appshortname');
      const shortHelp = document.getElementById('shortnameHelp');
      shortInput.addEventListener('input', function() {
          const len = shortInput.value.length;
          if (len > 12) {
              shortInput.classList.add('is-invalid');
              shortHelp.classList.add('text-danger');
              shortHelp.textContent = `Too long! ${len}/12 characters.`;
          } else {
              shortInput.classList.remove('is-invalid');
              shortHelp.classList.remove('text-danger');
              shortHelp.textContent = `Used in PWA manifest and mobile homescreens (${len}/12).`;
          }
      });
    </script>
    <?php
    echo ob_get_clean();
}



function isRegistrationActive(): bool {
    $basic = getSettings('basic');
    return !empty($basic['RegistrationActive']['value']);
}

function isStickyHeaderEnabled(): bool {
    $basic = getSettings('basic');
    return !empty($basic['StickyHeader']['value']);
}



/**
 * Renders the Email Configuration form.
 * Handles SMTP details and system email signature.
 */
function renderEmailSettingsForm() {
    $settings = getSettings('email') ?? [];

    $appsmtphost      = $settings['appsmtphost']['value'] ?? '';
    $appsmtpport      = $settings['appsmtpport']['value'] ?? '';
    $appsmtpusername  = $settings['appsmtpusername']['value'] ?? '';
    $appsmtppassword  = $settings['appsmtppassword']['value'] ?? '';
    $appemailsignature = $settings['appemailsignature']['value'] ?? '';

    ob_start(); ?>
    <form method="post" novalidate>
        <input type="hidden" name="form_name" value="emailsettings">

        <div class="mb-3">
            <label for="appsmtphost" class="form-label">SMTP Host</label>
            <input id="appsmtphost" name="appsmtphost" type="text" class="form-control"
                   value="<?= htmlspecialchars($appsmtphost) ?>" required>
            <div class="form-text">Your mail server (e.g., smtp.gmail.com).</div>
        </div>

        <div class="mb-3">
            <label for="appsmtpport" class="form-label">SMTP Port</label>
            <input id="appsmtpport" name="appsmtpport" type="number" class="form-control"
                   value="<?= htmlspecialchars($appsmtpport) ?>" required>
            <div class="form-text">Typical ports: 465 (SSL), 587 (TLS).</div>
        </div>

        <div class="mb-3">
            <label for="appsmtpusername" class="form-label">SMTP Username</label>
            <input id="appsmtpusername" name="appsmtpusername" type="text" class="form-control"
                   value="<?= htmlspecialchars($appsmtpusername) ?>">
        </div>

        <div class="mb-3">
            <label for="appsmtppassword" class="form-label">SMTP Password</label>
            <input id="appsmtppassword" name="appsmtppassword" type="password" class="form-control"
                   value="<?= $appsmtppassword ? '••••••' : '' ?>" autocomplete="off">
            <div class="form-text">Stored securely. Enter a new password to update.</div>
        </div>

        <div class="mb-3">
            <label for="appemailsignature" class="form-label">Email Signature</label>
            <textarea id="appemailsignature" name="appemailsignature" rows="3" class="form-control"><?= htmlspecialchars($appemailsignature) ?></textarea>
            <div class="form-text">This signature is appended to all system-generated emails.</div>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-2">
            <button type="submit" class="btn btn-primary">Save Email Settings</button>
            <a href="?tab=email" class="text-muted small">Reset</a>
        </div>
    </form>
    <?php
    echo ob_get_clean();
}

/**
 * Renders navigation
 */

function getNavigationItems(): array {
    $nav = getSettings('navigation');
    return $nav['items'] ?? [];
}


function saveNavigationItems(array $items): bool {
    $settingsPath = __DIR__ . '/settings.json';
    $json = json_decode(file_get_contents($settingsPath), true);
    if (!isset($json['settings']['navigation'])) {
        $json['settings']['navigation'] = ['items' => []];
    }
    $json['settings']['navigation']['items'] = $items;
    return (bool)file_put_contents($settingsPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function getNextNavigationId(array $items): int {
    $ids = array_column($items, 'id');
    return empty($ids) ? 1 : max($ids) + 1;
}


function renderMobileNavigation() {
    $items = getNavigationItems();
    $userType = $_SESSION['user_type'] ?? 0;

    // Sort by 'order'
    usort($items, function($a, $b) {
        return $a['order'] <=> $b['order'];
    });

   // echo '<ul class="list-group list-group-flush">';

    foreach ($items as $item) {
        // Skip inactive or hidden-for-mobile items
        if ($item['status'] != 1 || empty($item['showinmobile'])) continue;
        // Skip admin-only links for regular users
        if (!empty($item['foradmin']) && $userType < 1) continue;

        $title  = htmlspecialchars($item['title']);
        $link   = htmlspecialchars($item['link']);
        $target = htmlspecialchars($item['target']);

        echo "<li><a href='{$link}' target='{$target}' class='list-group-item list-group-item-action'>{$title}</a></li>";
    }

  // echo '</ul>';
}






function renderDesktopNavigation() {
    $items = getNavigationItems();
    $userType = $_SESSION['user_type'] ?? 0;

    // Sort by order
    usort($items, function($a, $b) {
        return $a['order'] <=> $b['order'];
    });

   // echo '<div class="d-flex flex-wrap justify-content-center gap-3 my-3">';

    foreach ($items as $item) {
        if ($item['status'] != 1) continue; // skip inactive
        if (!empty($item['foradmin']) && $userType < 1) continue; // admin-only filter

        $title  = htmlspecialchars($item['title']);
        $link   = htmlspecialchars($item['link']);
        $target = htmlspecialchars($item['target']);

        echo "<a href='{$link}' target='{$target}' class='btn btn-outline-secondary text-truncate' style='min-width:320px;max-width:320px;'>{$title}</a>";
    }

   //echo '</div>';
}


function renderNavigationSettingsForm() {
    $items = getNavigationItems();
    usort($items, function($a, $b) {
        return $a['order'] <=> $b['order'];
    });

    ob_start(); ?>
    
    <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Link</th>
                <th>Target</th>
                <th>Mobile</th>
                <th>Admin</th>
                <th>Order</th>
                <th>Status</th>
                <th width="160">Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
            <form method="post" class="m-0">
                <input type="hidden" name="form_name" value="navigation_update">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                <tr>
                    <td><?= (int)$item['id'] ?></td>
                    <td><input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" class="form-control" required></td>
                    <td><input type="text" name="link" value="<?= htmlspecialchars($item['link']) ?>" class="form-control" required></td>
                    <td>
                        <select name="target" class="form-select">
                            <option value="_self" <?= $item['target'] === '_self' ? 'selected' : '' ?>>Same Tab</option>
                            <option value="_blank" <?= $item['target'] === '_blank' ? 'selected' : '' ?>>New Tab</option>
                        </select>
                    </td>
                    <td>
                        <input type="checkbox" name="showinmobile" value="1" <?= !empty($item['showinmobile']) ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <input type="checkbox" name="foradmin" value="1" <?= !empty($item['foradmin']) ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <input type="number" name="order" value="<?= (int)$item['order'] ?>" class="form-control" style="width:80px;">
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" name="status" value="1" <?= ($item['status'] == 1) ? 'checked' : '' ?>>
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="submit" class="btn btn-sm btn-success">Update</button>
                        <button type="submit" name="delete" value="1" class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
            </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Add New Row -->
        <form method="post" class="m-0">
            <input type="hidden" name="form_name" value="navigation_add">
            <tr class="table-info">
                <td>+</td>
                <td><input type="text" name="title" class="form-control" placeholder="New title" required></td>
                <td><input type="text" name="link" class="form-control" placeholder="New link" required></td>
                <td>
                    <select name="target" class="form-select">
                        <option value="_self">Same Tab</option>
                        <option value="_blank">New Tab</option>
                    </select>
                </td>
                <td><input type="checkbox" name="showinmobile" value="1" checked></td>
                <td><input type="checkbox" name="foradmin" value="1"></td>
                <td><input type="number" name="order" class="form-control" style="width:80px;" placeholder="#"></td>
                <td>
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="status" value="1" checked>
                    </div>
                </td>
                <td class="text-center">
                    <button type="submit" class="btn btn-sm btn-primary">Add</button>
                </td>
            </tr>
        </form>

        </tbody>
    </table>
    </div>

    <?php
    echo ob_get_clean();
}



/**
 * Renders the PWA Settings section.
 * Shows state-aware UI: generate button, missing fields, or success message.
 */
function renderPWASettingsForm() {
    $pwa = getSettings('pwa') ?? [];
    $basic = getSettings('basic') ?? [];
    $branding = getSettings('branding') ?? [];

    $fields = [
        'appispwa'     => $pwa['appispwa']['value'] ?? false,
        'appicon192'   => $branding['appicon192']['value'] ?? '',
        'appicon512'   => $branding['appicon512']['value'] ?? '',
        'appfevicon'   => $branding['appfevicon']['value'] ?? '',
        'appdisplay'   => $pwa['appdisplay']['value'] ?? 'standalone',
        'appname'      => $basic['appname']['value'] ?? '',
        'appshortname' => $basic['appshortname']['value'] ?? '',
        'appurl'       => $basic['appurl']['value'] ?? '',
    ];

    $generated = $pwa['app_pwa_generated']['value'] ?? false;

    $required = ['appicon192', 'appicon512', 'appdisplay', 'appname', 'appshortname', 'appurl'];
    $missing = [];
    foreach ($required as $key) {
        if (empty($fields[$key])) $missing[] = $key;
    }

    ob_start(); ?>
    <form method="post" novalidate>
      <input type="hidden" name="form_name" value="pwa">

      <div class="mb-4 p-3 border rounded bg-light">
        <h5 class="mb-3">Progressive Web App (PWA) Settings</h5>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="appispwa" name="appispwa" value="1"
                 <?= $fields['appispwa'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="appispwa">Enable PWA Features</label>
        </div>

        <div class="mb-3">
          <label for="appdisplay" class="form-label">Display Mode</label>
          <select id="appdisplay" name="appdisplay" class="form-select">
            <?php
              $options = ['standalone', 'minimal-ui', 'browser'];
              foreach ($options as $opt) {
                  $sel = ($fields['appdisplay'] === $opt) ? 'selected' : '';
                  echo "<option value='$opt' $sel>$opt</option>";
              }
            ?>
          </select>
        </div>
      </div>

      <!-- Always show a Save button -->
      <div class="d-flex justify-content-between mb-3">
        <button type="submit" class="btn btn-primary">Save PWA Settings</button>
      </div>

      <?php if (!$fields['appispwa']): ?>
        <div class="alert alert-secondary">
          PWA mode is currently disabled. Toggle the switch above and click “Save” to enable it.
        </div>

      <?php elseif (!empty($missing)): ?>
        <div class="alert alert-danger">
          <strong>Missing required fields:</strong>
          <ul class="mb-0">
            <?php foreach ($missing as $m): ?>
              <li><?= htmlspecialchars($m) ?></li>
            <?php endforeach; ?>
          </ul>
          <p class="mb-0 small">Please complete these settings before generating PWA files.</p>
        </div>

      <?php elseif (!$generated): ?>
        <div class="alert alert-info">
          All required fields are set. You can now generate PWA files.
        </div>
        <button type="submit" name="generate_pwa" value="1" class="btn btn-success">Generate PWA Files</button>

      <?php else: ?>
        <div class="alert alert-success">
          🎉 Congratulations! Your app is live as a PWA.
        </div>
        <button type="submit" name="generate_pwa" value="1" class="btn btn-warning">Regenerate PWA Files</button>
      <?php endif; ?>
    </form>
    <?php
    echo ob_get_clean();
}


/**
 * Generates or regenerates manifest.json and service-worker.js for PWA.
 * Returns ['success'=>true] or ['error'=>msg]
 */
function generatePWAFiles() {
    $branding = getSettings('branding');
    $basic = getSettings('basic');
    $pwa = getSettings('pwa');

    $appName = $basic['appname']['value'] ?? 'Panda PHP App';
    $appShort = $basic['appshortname']['value'] ?? 'Panda';
    $appUrl = rtrim($basic['appurl']['value'] ?? 'http://localhost', '/');
    $icon192 = $branding['appicon192']['value'] ?? '';
    $icon512 = $branding['appicon512']['value'] ?? '';
    $display = $pwa['appdisplay']['value'] ?? 'standalone';

    if (!$icon192 || !$icon512) {
        return ['error' => 'Missing required PWA icons (192 or 512).'];
    }

    $manifest = [
        'name' => $appName,
        'short_name' => $appShort,
        'start_url' => $appUrl . '/',
        'display' => $display,
        'icons' => [
            ['src' => "assets/logos/$icon192", 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => "assets/logos/$icon512", 'sizes' => '512x512', 'type' => 'image/png']
        ],
        'background_color' => '#ffffff',
        'theme_color' => $branding['appthemecolor']['value'] ?? '#ffffff',
    ];

    // Write manifest.json
    $manifestPath = __DIR__ . '/../manifest.json';
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Basic service worker
    $swPath = __DIR__ . '/../service-worker.js';
    $swScript = "self.addEventListener('install',e=>{e.waitUntil(caches.open('panda-v1').then(c=>c.addAll(['/'])))});\n".
                "self.addEventListener('fetch',e=>{e.respondWith(caches.match(e.request).then(r=>r||fetch(e.request)))})";
    file_put_contents($swPath, $swScript);

    // Mark generated
    updateSettingsGroup('pwa', ['app_pwa_generated' => true]);

    return ['success' => true];
}
/**
 * Includes manifest, favicon, service worker registration, and dynamic title.
 * All URLs are relative to the configured appurl in settings.json.
 */
function includePWAAssets() {
    $pwa   = getSettings('pwa');
    $brand = getSettings('branding');
    $basic = getSettings('basic');

    $isOn        = !empty($pwa['appispwa']['value']);
    $isGenerated = !empty($pwa['app_pwa_generated']['value']);
    $themeColor  = $brand['appthemecolor']['value'] ?? '#ffffff';
    $favicon     = $brand['appfevicon']['value'] ?? '';
    $appShort    = $basic['appshortname']['value'] ?? 'Panda PHP';
    $appURL      = rtrim($basic['appurl']['value'] ?? '', '/');

    // Compute relative paths safely
    $manifestURL = $appURL . '/manifest.json';
    $swURL       = $appURL . '/service-worker.js';
    $faviconURL  = $favicon ? $appURL . '/assets/logos/' . $favicon : '';

    // Title (only change if in public header)
    echo '<title>' . htmlspecialchars($appShort) . '</title>' . "\n";

    // Always link favicon if available
    if ($faviconURL) {
        echo '<link rel="icon" type="image/png" href="' . htmlspecialchars($faviconURL) . '">' . "\n";
    }

    // Inject manifest + service worker only if PWA is active and generated
    if ($isOn && $isGenerated) {
        echo "\n<!-- PWA integration -->\n";
        echo '<link rel="manifest" href="' . htmlspecialchars($manifestURL) . '">' . "\n";
        echo '<meta name="theme-color" content="' . htmlspecialchars($themeColor) . '">' . "\n";
        echo "<script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('" . addslashes($swURL) . "')
                  .catch(err => console.warn('Service Worker registration failed:', err));
            }
        </script>\n";

        if ($isOn && $isGenerated) {
    echo "\n<!-- PWA integration -->\n";
    echo '<link rel="manifest" href="' . htmlspecialchars($manifestURL) . '">' . "\n";
    echo '<meta name="theme-color" content="' . htmlspecialchars($themeColor) . '">' . "\n";
    echo "<script>
      if ('serviceWorker' in navigator) {
          navigator.serviceWorker.register('" . addslashes($swURL) . "')
            .catch(err => console.warn('Service Worker registration failed:', err));
      }

      // Handle PWA install prompt
      let deferredPrompt;
      window.addEventListener('beforeinstallprompt', (e) => {
          e.preventDefault();
          deferredPrompt = e;
          const banner = document.createElement('div');
          banner.id = 'pwa-install-banner';
          banner.innerHTML = `
            <div style=\"position:fixed;top:0;left:0;right:0;max-width:450px;background:#0d6efd;color:#fff;
                        padding:10px;display:flex;justify-content:space-between;
                        align-items:center;z-index:10000;font-family:sans-serif;\">
              <span>📲 Install this app for faster access!</span>
              <button id=\"installBtn\" style=\"background:#fff;color:#0d6efd;
                        border:none;padding:6px 12px;border-radius:4px;font-weight:bold;\">
                Install
              </button>
            </div>`;
          document.body.prepend(banner);
          document.getElementById('installBtn').addEventListener('click', async () => {
              banner.remove();
              deferredPrompt.prompt();
              const { outcome } = await deferredPrompt.userChoice;
              if (outcome === 'accepted') {
                  console.log('User installed the app');
              } else {
                  console.log('User dismissed install prompt');
              }
              deferredPrompt = null;
          });
      });

      // Optional: remove banner if app is already installed
      window.addEventListener('appinstalled', () => {
          const b = document.getElementById('pwa-install-banner');
          if (b) b.remove();
          console.log('App installed successfully');
      });
    </script>\n";
}

    }
}

/**
 * Outputs the centered header logo (or fallback text) for external/public pages.
 */
function renderHeaderLogo() {
    $branding = getSettings('branding');
    $basic    = getSettings('basic');
    $appName = getSettingValue('appname') ?: 'Your App Name';

    $logoFile = $branding['appregularlogo']['value'] ?? '';
    $appName  = $basic['appname']['value'] ?? 'Your App Name';
    $logoPath = $logoFile ? 'assets/logos/' . $logoFile : '';

    echo '<div style="text-align:center; margin-top:20px;">';
    if ($logoFile && file_exists(__DIR__ . '/../' . $logoPath)) {
        echo '<img src="' . htmlspecialchars($logoPath) . '" alt="' . htmlspecialchars($appName) . '" style="max-width:300px;height:auto;">';
    } else {
        echo '<h2 style="margin:0;font-weight:600;">' . htmlspecialchars($appName ?: 'Your App Name') . '</h2>';
    }
    echo '</div>';
    echo '<div style="text-align:center;font-weight:600;font-size:1.1rem;margin-bottom:10px;">' . htmlspecialchars($appName) . '</div>';
}


/**
 * Outputs footer text (HTML allowed) for external/public pages.
 */
function renderFooterText() {
    $basic = getSettings('basic');
    $footerHTML = $basic['appfootertext']['value'] ?? '';

    if (!empty(trim($footerHTML))) {
        echo '<div style="text-align:center; padding:15px; font-size:0.9rem; color:#666;">';
        echo $footerHTML; // allow HTML
        echo '</div>';
    }
}


/**
 * Renders a small logo for navbar (internal header).
 * Fallbacks:
 *  - app_logo.png → branding header version
 *  - app_regular_logo.png → rectangular logo
 *  - appname text → if no image found
 */
function renderSmallHeaderLogo() {
    $branding = getSettings('branding');
    $basic = getSettings('basic');

    $headerLogo = $branding['appheaderlogo']['value'] ?? ''; // app_logo.png
    $regularLogo = $branding['appregularlogo']['value'] ?? ''; // app_regular_logo.png
    $appName = $basic['appname']['value'] ?? 'Your App Name';

    $logoToUse = '';
    if ($headerLogo && file_exists(__DIR__ . '/../assets/logos/' . $headerLogo)) {
        $logoToUse = 'assets/logos/' . $headerLogo;
    } elseif ($regularLogo && file_exists(__DIR__ . '/../assets/logos/' . $regularLogo)) {
        $logoToUse = 'assets/logos/' . $regularLogo;
    }

    if ($logoToUse) {
        echo '<img src="' . htmlspecialchars($logoToUse) . '" alt="' . htmlspecialchars($appName) . '" style="max-width:180px; max-height:60px; height:auto;">';
    } else {
        echo '<span style="font-weight:600; font-size:1.1rem;">' . htmlspecialchars($appName) . '</span>';
    }
}
/**
 * Returns the proper class + style attributes for the <nav> element.
 * - Uses Branding's appheadercolor if available
 * - Falls back to 'navbar-softgray' if not
 */
function renderNavClassStyle() {
    $branding = getSettings('branding');
    $basic = getSettings('basic');
    $headerColor = trim($branding['appheadercolor']['value'] ?? '');

    $baseClasses = 'navbar navbar-dark shadow-sm';
    $styleParts = [];

    // Header color
    if (!empty($headerColor)) {
        $styleParts[] = 'background-color:' . htmlspecialchars($headerColor);
    } else {
        $baseClasses .= ' navbar-softgray';
    }

    // Sticky header
    if (!empty($basic['StickyHeader']['value'])) {
        $styleParts[] = 'position:sticky';
        $styleParts[] = 'top:0';
        $styleParts[] = 'z-index:1030';
    }

    $style = !empty($styleParts) ? ' style="' . implode(';', $styleParts) . ';"' : '';
    return 'class="' . $baseClasses . '"' . $style;
}


/**
 * Returns or echoes the current user's role label.
 *
 * @param bool $echo Whether to print (true) or just return (false)
 * @return string The formatted role label
 */
function renderUserRoleLabel(bool $echo = true): string {
    $user_type = $_SESSION['user_type'] ?? 0;

    switch ($user_type) {
        case 2:
            $roleLabel = 'Super User (Admin + Developer)';
            break;
        case 1:
            $roleLabel = 'Administrator';
            break;
        default:
            $roleLabel = 'Regular User';
    }

    //$output = '<p>Role: ' . htmlspecialchars($roleLabel) . '</p>';
    $output = htmlspecialchars($roleLabel) ;

    if ($echo) {
        echo $output;
    }

    return $output;
}

/**
 * Renders a template file with supplied data.
 * Usage: $html = renderTemplate('email_welcome', ['name'=>'John']);
 */
function renderTemplate(string $template, array $data = []): string {
    $templatePath = __DIR__ . "/templates/{$template}.php";
    if (!file_exists($templatePath)) {
        return "<p>Template not found: {$template}</p>";
    }

    // Extract variables into local scope
    extract($data, EXTR_SKIP);

    // Capture output
    ob_start();
    include $templatePath;
    return ob_get_clean();
}

/**
 * Wraps given HTML inside a styled email container.
 */
function wrapEmailHtml(string $content, string $title = ''): string {
    $appName = getSettingValue('appname') ?: 'Panda PHP App';
    $themeColor = getSettingValue('appthemecolor') ?: '#336699';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{$title}</title>
</head>
<body style="margin:0;padding:20px;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;margin:auto;">
    <tr>
      <td style="padding:20px;background:#ffffff;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);">
        <div style="border-bottom:3px solid {$themeColor};margin-bottom:15px;padding-bottom:10px;font-size:18px;font-weight:bold;color:{$themeColor};">
          {$appName}
        </div>
        {$content}
        <div style="margin-top:20px;font-size:12px;color:#888;text-align:center;">
          This message was sent by {$appName}. Please do not reply directly.
        </div>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}



/**
 * Redirects user to the configured app index page.
 * If user is logged in, this acts as a post-login redirect.
 * If user is not logged in, it can be used to send to the app's public start page.
 */
function redirectToAppStart(): void {
    $baseUrl = rtrim(getSettingValue('appurl') ?: '', '/');
    $indexPage = ltrim(getSettingValue('appindexpage') ?: 'index.php', '/');
    $target = $baseUrl . '/' . $indexPage;
    header("Location: {$target}");
    exit;
}

?>