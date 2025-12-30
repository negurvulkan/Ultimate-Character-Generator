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

$computed = $pdo->query('SELECT * FROM computed_key ORDER BY key_path')->fetchAll();
$genders = $pdo->query('SELECT * FROM gender')->fetchAll();
$species = $pdo->query('SELECT * FROM species')->fetchAll();
$lifeStages = $pdo->query('SELECT * FROM life_stage')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Util::verifyCsrf($_POST['csrf'] ?? '');
        $params = Util::jsonDecode($_POST['params_json'] ?? '{}');
        $stmt = $pdo->prepare('INSERT INTO rule (computed_key_id, name, priority, distribution, params_json, is_active) VALUES (?,?,?,?,?,1)');
        $stmt->execute([
            $_POST['computed_key_id'],
            $_POST['name'],
            (int)$_POST['priority'],
            $_POST['distribution'],
            json_encode($params)
        ]);
        $ruleId = $pdo->lastInsertId();
        // condition optional
        if (!empty($_POST['gender_id']) || !empty($_POST['species_id']) || !empty($_POST['life_stage_id']) || $_POST['min_age'] !== '' || $_POST['max_age'] !== '') {
            $stmt = $pdo->prepare('INSERT INTO rule_condition (rule_id, species_id, gender_id, life_stage_id, min_age, max_age) VALUES (?,?,?,?,?,?)');
            $stmt->execute([
                $ruleId,
                $_POST['species_id'] ?: null,
                $_POST['gender_id'] ?: null,
                $_POST['life_stage_id'] ?: null,
                $_POST['min_age'] !== '' ? (int)$_POST['min_age'] : null,
                $_POST['max_age'] !== '' ? (int)$_POST['max_age'] : null
            ]);
        }
        if (!empty($_POST['dependencies'])) {
            $deps = array_map('trim', explode(',', $_POST['dependencies']));
            foreach ($deps as $depKey) {
                $depId = null;
                foreach ($computed as $c) {
                    if ($c['key_path'] === $depKey) {
                        $depId = $c['id'];
                        break;
                    }
                }
                if ($depId) {
                    $stmt = $pdo->prepare('INSERT INTO rule_dependency (rule_id, depends_on_computed_key_id) VALUES (?,?)');
                    $stmt->execute([$ruleId, $depId]);
                }
            }
        }
        $message = 'Regel gespeichert';
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

$rules = $pdo->query('SELECT r.*, ck.key_path FROM rule r JOIN computed_key ck ON ck.id = r.computed_key_id ORDER BY ck.key_path, r.priority')->fetchAll();
$title = 'Regeln';
include __DIR__ . '/../templates/admin_header.tpl.php';
?>
<h2 class="h5">Regeln</h2>
<?php if ($message): ?><div class="alert alert-info"><?= Util::esc($message) ?></div><?php endif; ?>
<table class="table table-sm table-striped">
    <thead><tr><th>Key</th><th>Name</th><th>Prio</th><th>Distribution</th><th>Params</th></tr></thead>
    <tbody>
    <?php foreach ($rules as $r): ?>
        <tr>
            <td><?= Util::esc($r['key_path']) ?></td>
            <td><?= Util::esc($r['name']) ?></td>
            <td><?= (int)$r['priority'] ?></td>
            <td><?= Util::esc($r['distribution']) ?></td>
            <td><code><?= Util::esc($r['params_json']) ?></code></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="card mt-3">
    <div class="card-body">
        <h3 class="h6">Neue Regel</h3>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= Util::esc(Util::csrfToken()) ?>">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Key</label>
                    <select name="computed_key_id" class="form-select" required>
                        <?php foreach ($computed as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= Util::esc($c['key_path']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input class="form-control" name="name" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prio</label>
                    <input type="number" class="form-control" name="priority" value="100">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Distribution</label>
                    <select class="form-select" name="distribution">
                        <option>gaussian</option>
                        <option>uniform</option>
                        <option>linear</option>
                        <option>ratio</option>
                        <option>piecewise</option>
                        <option>sigmoid</option>
                        <option>choice</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Params (JSON)</label>
                    <textarea class="form-control" name="params_json" rows="6">{"mean":0}</textarea>
                </div>
                <div class="col-12"><h4 class="h6 mt-3">Bedingung (optional)</h4></div>
                <div class="col-md-3"><select name="species_id" class="form-select"><option value="">Species</option><?php foreach ($species as $s): ?><option value="<?= (int)$s['id'] ?>"><?= Util::esc($s['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><select name="gender_id" class="form-select"><option value="">Gender</option><?php foreach ($genders as $g): ?><option value="<?= (int)$g['id'] ?>"><?= Util::esc($g['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><select name="life_stage_id" class="form-select"><option value="">Lebensphase</option><?php foreach ($lifeStages as $ls): ?><option value="<?= (int)$ls['id'] ?>"><?= Util::esc($ls['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3">
                    <div class="input-group">
                        <input class="form-control" type="number" name="min_age" placeholder="min age">
                        <input class="form-control" type="number" name="max_age" placeholder="max age">
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Dependencies (key paths, comma)</label>
                    <input class="form-control" name="dependencies" placeholder="body.height,body.scale">
                </div>
                <div class="col-12 mt-3"><button class="btn btn-primary">Speichern</button></div>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.tpl.php';
