<?php
// database_admin.php — admin only database management
require_once __DIR__ . '/inc/header.php';

// Only admins can access this page
require_admin_or_redirect();

$pdo = get_db_connection();
$error = '';
$success = '';
$results = [];
$query = '';

// Handle SQL query execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    
    if (!empty($query)) {
        try {
            // Check if it's a SELECT query or other
            if (stripos($query, 'SELECT') === 0) {
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $success = 'Query executed successfully. ' . count($results) . ' rows returned.';
            } else {
                // INSERT, UPDATE, DELETE
                $affected = $pdo->exec($query);
                $success = 'Query executed successfully. ' . $affected . ' rows affected.';
            }
        } catch (Exception $e) {
            $error = 'Query error: ' . $e->getMessage();
        }
    }
}

// Get table list
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Database Administration</h5>
        <span class="badge bg-danger">Admin Only</span>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Database Tables</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($tables as $table): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($table) ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('query').value = 'SELECT * FROM <?= htmlspecialchars($table) ?> LIMIT 10'">Select</button>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-info btn-sm" onclick="document.getElementById('query').value = 'SELECT * FROM users'">View Users</button>
                            <button class="btn btn-outline-warning btn-sm" onclick="document.getElementById('query').value = 'VACUUM'">Optimize DB</button>
                            <a href="usermanagement.php" class="btn btn-outline-primary btn-sm">User Management</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">SQL Query</h6>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="query" class="form-label">SQL Query</label>
                                <textarea id="query" name="query" class="form-control" rows="4" placeholder="SELECT * FROM users"><?= htmlspecialchars($query) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Execute Query</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('query').value = ''">Clear</button>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($results)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Results (<?= count($results) ?> rows)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <?php if (!empty($results)): ?>
                                            <?php foreach (array_keys($results[0]) as $column): ?>
                                                <th><?= htmlspecialchars($column) ?></th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-warning mt-3">
    <strong>Warning:</strong> This is a powerful tool. Be careful with SQL queries as they can modify or delete data.
</div>

<?php

require_once __DIR__ . '/inc/footer.php';
?>