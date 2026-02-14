<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // A date in the past
// settings.php
// Admin-only settings page (admins user_type 1 and super_admins user_type 2)

// Enable all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        echo "<pre style='color:red;background:#fff;padding:10px;'>";
        print_r($error);
        echo "</pre>";
    }
});

// Load core includes & auth
require_once __DIR__ . '/inc/header.php'; // header.php already calls require_login_or_redirect()

// Extra guard: ensure only user_type 1 or 2 can access
require_admin_or_redirect();

$form = '';        // define it early to prevent notices
$errors = [];
$success = '';


$allowedTabs = ['basic', 'branding', 'pwa', 'email', 'navigation'];

// tab from GET overrides session, defaults to 'basic'
if (isset($_GET['tab']) && in_array($_GET['tab'], $allowedTabs, true)) {
    $_SESSION['active_tab'] = $_GET['tab'];
}

$activeTab = $_SESSION['active_tab'] ?? 'basic';



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form_name'] ?? '';

    if ($form === 'branding') {
        // Process branding form
        if (!empty($_FILES['square_logo']['name'])) {
        $result = processSquareLogo($_FILES['square_logo']);
        if (!empty($result['error'])) $errors[] = $result['error'];
        else $success = 'Square logo and icon variants uploaded successfully.';
        }

        if (!empty($_FILES['rect_logo']['name'])) {
        $result = processRectangularLogo($_FILES['rect_logo']);
        if (!empty($result['error'])) $errors[] = $result['error'];
        else $success = 'Rectangular logo and header variant uploaded successfully.';
        }


        // 2) Update other branding fields via updateSettingsGroup
        // (fields will be sanitized in updateSettingsGroup)
        $brandingPost = [
            // include only expected fields - prevents accidental injection
            'appthemecolor'  => $_POST['appthemecolor'] ?? '',
            'appheadercolor' => $_POST['appheadercolor'] ?? '',
            'appfootercolor' => $_POST['appfootercolor'] ?? '',
            // If you want to allow custom header logo file name or override, add here
        ];
        updateSettingsGroup('branding', $brandingPost);

        // If no file upload error and no other errors, show success
        if (empty($errors) && empty($success)) {
            $success = 'Branding settings updated.';
        } elseif (!empty($errors) && empty($success)) {
            // nothing
        }
    }

        
        if ($form === 'basicsettings') {
            if (strlen($_POST['appshortname'] ?? '') > 12) {
                $errors[] = 'Short name must be 12 characters or fewer.';
            } else {
                updateSettingsGroup('basic', $_POST);
                $success = 'Basic settings updated successfully.';
            }
        }

        if ($form === 'emailsettings') {
            updateSettingsGroup('email', $_POST);
            $success = 'Email settings updated successfully.';
        }


        if ($form === 'pwa') {
            // Save checkbox + display mode state
            $pwaPost = [
                'appispwa' => isset($_POST['appispwa']) ? 1 : 0,
                'appdisplay' => $_POST['appdisplay'] ?? 'standalone',
            ];
            updateSettingsGroup('pwa', $pwaPost);
            $success = 'PWA settings updated.';

            // If user clicked generate/regenerate
            if (isset($_POST['generate_pwa'])) {
                $result = generatePWAFiles();
                if (!empty($result['error'])) {
                    $errors[] = $result['error'];
                } else {
                    $success = '🎉 PWA files generated successfully!';
                }
            }
        }

// --- NAVIGATION ADD ---
if ($form === 'navigation_add') {
    $items = getNavigationItems();
    $newItem = [
        'id' => getNextNavigationId($items),
        'title' => trim($_POST['title'] ?? ''),
        'link' => trim($_POST['link'] ?? ''),
        'target' => $_POST['target'] ?? '_self',
        'showinmobile' => isset($_POST['showinmobile']) ? 1 : 0,
        'foradmin' => isset($_POST['foradmin']) ? 1 : 0,
        'order' => (int)($_POST['order'] ?? (count($items) + 1)),
        'status' => isset($_POST['status']) ? 1 : 2
    ];

    if ($newItem['title'] && $newItem['link']) {
        $items[] = $newItem;
        saveNavigationItems($items);
        $success = 'Navigation item added successfully.';
    } else {
        $errors[] = 'Title and link are required.';
    }
}


    // --- NAVIGATION UPDATE / DELETE ---
    if ($form === 'navigation_update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $items = getNavigationItems();

        if (isset($_POST['delete'])) {
            // DELETE
            $items = array_filter($items, function($i) use ($id) {
            return $i['id'] != $id;
            });
            saveNavigationItems(array_values($items));
            $success = 'Navigation item deleted.';
        } else {
            // UPDATE
            foreach ($items as &$i) {
                if ($i['id'] == $id) {
                    $i['title'] = trim($_POST['title'] ?? '');
                    $i['link'] = trim($_POST['link'] ?? '');
                    $i['target'] = $_POST['target'] ?? '_self';
                    $i['showinmobile'] = isset($_POST['showinmobile']) ? 1 : 0;
                    $i['foradmin'] = isset($_POST['foradmin']) ? 1 : 0;
                    $i['order'] = (int)($_POST['order'] ?? $i['order']);
                    $i['status'] = isset($_POST['status']) ? 1 : 2;
                    break;
                }
            }
            unset($i);
            saveNavigationItems($items);
            $success = 'Navigation item updated.';
        }

        // Always refresh tab
        $_SESSION['active_tab'] = 'navigation';
       // header("Location: settings.php?tab=navigation");
        //exit;
    }


}




?>


    <h3 class="mb-3">System Settings</h3>

  <!-- Flash messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>


<div class="d-flex flex-wrap gap-2 mb-4">
<a href="?tab=basic" class="btn <?= $activeTab === 'basic' ? 'btn-primary' : 'btn-outline-secondary' ?>">Basic</a>
<a href="?tab=branding" class="btn <?= $activeTab === 'branding' ? 'btn-primary' : 'btn-outline-secondary' ?>">Branding</a>
<a href="?tab=pwa" class="btn <?= $activeTab === 'pwa' ? 'btn-primary' : 'btn-outline-secondary' ?>">PWA</a>
<a href="?tab=email" class="btn <?= $activeTab === 'email' ? 'btn-primary' : 'btn-outline-secondary' ?>">Email</a>
<a href="?tab=navigation" class="btn <?= $activeTab === 'navigation' ? 'btn-primary' : 'btn-outline-secondary' ?>">Navigation</a>
</div>

  

<?php if ($activeTab === 'basic'): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header">Basic Settings</div>
    <div class="card-body"><?php renderBasicSettingsForm(); ?></div>
  </div>
<?php elseif ($activeTab === 'branding'): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header">Branding & Design</div>
    <div class="card-body"><?php renderBrandingSettingsForm(); ?></div>
  </div>
<?php elseif ($activeTab === 'pwa'): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header">PWA Settings</div>
    <div class="card-body"><?php renderPWASettingsForm(); ?></div>
  </div>
<?php elseif ($activeTab === 'email'): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header">Email Configuration</div>
    <div class="card-body"><?php renderEmailSettingsForm(); ?></div>
  </div>

<?php elseif ($activeTab === 'navigation'): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header">Navigation Management</div>
    <div class="card-body"><?php renderNavigationSettingsForm(); ?></div>
  </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/inc/footer.php';
