<?php
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Template.php';
require_once __DIR__ . '/../lib/Util.php';

use Ucg\Db;
use Ucg\Auth;
use Ucg\Util;

$pdo = Db::connect();
$auth = new Auth($pdo);
$auth->ensure();
$user = $auth->user();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Util::verifyCsrf($_POST['csrf'] ?? '');
        if (isset($_POST['delete'])) {
            $stmt = $pdo->prepare('DELETE FROM computed_key WHERE id = ?');
            $stmt->execute([(int)$_POST['delete']]);
            $message = 'Key gelöscht';
        } else {
            $stmt = $pdo->prepare('INSERT INTO computed_key (key_path, label, unit, category, description, is_active) VALUES (?,?,?,?,?,1)');
            $stmt->execute([$_POST['key_path'], $_POST['label'], $_POST['unit'] ?: null, $_POST['category'] ?: null, $_POST['description'] ?: null]);
            $message = 'Key gespeichert';
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

$rows = $pdo->query('SELECT * FROM computed_key ORDER BY category, key_path')->fetchAll();
$title = 'Computed Keys';
include __DIR__ . '/../templates/admin_header.tpl.php';
?>
<h2 class="h5">Computed Keys</h2>
<?php if ($message): ?><div class="alert alert-info"><?= Util::esc($message) ?></div><?php endif; ?>
<table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Key</th><th>Label</th><th>Unit</th><th>Category</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= Util::esc($row['key_path']) ?></td>
            <td><?= Util::esc($row['label']) ?></td>
            <td><?= Util::esc($row['unit']) ?></td>
            <td><?= Util::esc($row['category']) ?></td>
            <td>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf" value="<?= Util::esc(Util::csrfToken()) ?>">
                    <button class="btn btn-sm btn-outline-danger" name="delete" value="<?= (int)$row['id'] ?>">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="card mt-3">
    <div class="card-body">
        <h3 class="h6">Neu anlegen</h3>
        <form method="post" class="row g-2">
            <input type="hidden" name="csrf" value="<?= Util::esc(Util::csrfToken()) ?>">
            <div class="col-md-3"><input class="form-control" name="key_path" placeholder="key path" required></div>
            <div class="col-md-3"><input class="form-control" name="label" placeholder="Label" required></div>
            <div class="col-md-2"><input class="form-control" name="unit" placeholder="Unit"></div>
            <div class="col-md-2"><input class="form-control" name="category" placeholder="Category"></div>
            <div class="col-md-12"><textarea class="form-control" name="description" placeholder="Beschreibung"></textarea></div>
            <div class="col-md-12 mt-2"><button class="btn btn-primary">Speichern</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.tpl.php';
