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

$species = $pdo->query('SELECT * FROM species')->fetchAll();
$genders = $pdo->query('SELECT * FROM gender')->fetchAll();
$lifeStages = $pdo->query('SELECT * FROM life_stage ORDER BY sort_order')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Util::verifyCsrf($_POST['csrf'] ?? '');
        $profile = Util::jsonDecode($_POST['profile_json'] ?? '{}');
        $stmt = $pdo->prepare('INSERT INTO base_profile (species_id, gender_id, life_stage_id, priority, profile_json) VALUES (?,?,?,?,?)');
        $stmt->execute([
            $_POST['species_id'] ?: null,
            $_POST['gender_id'] ?: null,
            $_POST['life_stage_id'] ?: null,
            (int)($_POST['priority'] ?? 100),
            json_encode($profile)
        ]);
        $message = 'Profil gespeichert';
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

$rows = $pdo->query('SELECT bp.*, s.name AS species_name, g.name AS gender_name, ls.name AS life_stage_name FROM base_profile bp
LEFT JOIN species s ON s.id = bp.species_id
LEFT JOIN gender g ON g.id = bp.gender_id
LEFT JOIN life_stage ls ON ls.id = bp.life_stage_id
ORDER BY bp.priority')->fetchAll();
$title = 'Basisprofile';
include __DIR__ . '/../templates/admin_header.tpl.php';
?>
<h2 class="h5">Basisprofile</h2>
<?php if ($message): ?><div class="alert alert-info"><?= Util::esc($message) ?></div><?php endif; ?>
<table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Species</th><th>Gender</th><th>Stage</th><th>Priority</th><th>JSON</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= Util::esc($row['species_name']) ?></td>
            <td><?= Util::esc($row['gender_name']) ?></td>
            <td><?= Util::esc($row['life_stage_name']) ?></td>
            <td><?= (int)$row['priority'] ?></td>
            <td><code><?= Util::esc($row['profile_json']) ?></code></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="card mt-3">
    <div class="card-body">
        <h3 class="h6">Neues Profil</h3>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= Util::esc(Util::csrfToken()) ?>">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Species</label>
                    <select name="species_id" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($species as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= Util::esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender_id" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($genders as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= Util::esc($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lebensphase</label>
                    <select name="life_stage_id" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($lifeStages as $ls): ?>
                            <option value="<?= (int)$ls['id'] ?>"><?= Util::esc($ls['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priorität</label>
                    <input type="number" name="priority" class="form-control" value="100">
                </div>
                <div class="col-12">
                    <label class="form-label">JSON (Profilwerte)</label>
                    <textarea class="form-control" name="profile_json" rows="4">{"example": 123}</textarea>
                </div>
                <div class="col-12 mt-2"><button class="btn btn-primary">Speichern</button></div>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.tpl.php';
