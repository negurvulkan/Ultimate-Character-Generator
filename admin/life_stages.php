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
            $stmt = $pdo->prepare('DELETE FROM life_stage WHERE id = ?');
            $stmt->execute([(int)$_POST['delete']]);
            $message = 'Lebensphase gelöscht';
        } else {
            $stmt = $pdo->prepare('INSERT INTO life_stage (slug, name, sort_order, is_active) VALUES (?,?,?,1)');
            $stmt->execute([$_POST['slug'], $_POST['name'], (int)($_POST['sort_order'] ?? 0)]);
            $message = 'Lebensphase gespeichert';
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

$rows = $pdo->query('SELECT * FROM life_stage ORDER BY sort_order')->fetchAll();
$title = 'Lebensphasen';
include __DIR__ . '/../templates/admin_header.tpl.php';
?>
<h2 class="h5">Lebensphasen</h2>
<?php if ($message): ?><div class="alert alert-info"><?= Util::esc($message) ?></div><?php endif; ?>
<table class="table table-bordered">
    <thead><tr><th>ID</th><th>Slug</th><th>Name</th><th>Sort</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= Util::esc($row['slug']) ?></td>
            <td><?= Util::esc($row['name']) ?></td>
            <td><?= (int)$row['sort_order'] ?></td>
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
            <div class="col-md-3"><input class="form-control" name="slug" placeholder="slug" required></div>
            <div class="col-md-5"><input class="form-control" name="name" placeholder="Name" required></div>
            <div class="col-md-2"><input class="form-control" type="number" name="sort_order" placeholder="Sort"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Speichern</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.tpl.php';
