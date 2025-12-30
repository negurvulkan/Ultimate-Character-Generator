<?php
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Template.php';
require_once __DIR__ . '/../lib/RuleEngine.php';
require_once __DIR__ . '/../lib/LegacyRunner.php';
require_once __DIR__ . '/../lib/Util.php';

use Ucg\Db;
use Ucg\Auth;
use Ucg\RuleEngine;
use Ucg\LegacyRunner;
use Ucg\Util;

$pdo = Db::connect();
$auth = new Auth($pdo);
$auth->ensure();
$user = $auth->user();

$species = $pdo->query('SELECT * FROM species')->fetchAll();
$genders = $pdo->query('SELECT * FROM gender')->fetchAll();
$lifeStages = $pdo->query('SELECT * FROM life_stage ORDER BY sort_order')->fetchAll();

$result = null;
$legacy = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Util::verifyCsrf($_POST['csrf'] ?? '');
        $ctx = [
            'species_id' => (int)($_POST['species_id'] ?? 0) ?: null,
            'gender_id' => (int)($_POST['gender_id'] ?? 0) ?: null,
            'gender_slug' => null,
            'life_stage_id' => (int)($_POST['life_stage_id'] ?? 0) ?: null,
            'age' => (int)($_POST['age'] ?? 25),
            'seed' => $_POST['seed'] === '' ? null : (int)$_POST['seed']
        ];
        // map gender slug
        if ($ctx['gender_id']) {
            foreach ($genders as $g) {
                if ($g['id'] == $ctx['gender_id']) {
                    $ctx['gender_slug'] = $g['slug'];
                }
            }
        }
        $engine = new RuleEngine($pdo, $ctx['seed']);
        $result = $engine->generate($ctx);
        $legacy = (new LegacyRunner())->generate($ctx);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$title = 'Preview';
include __DIR__ . '/../templates/admin_header.tpl.php';
?>
<h2 class="h5">Preview & Diff</h2>
<?php if ($error): ?><div class="alert alert-danger"><?= Util::esc($error) ?></div><?php endif; ?>
<form method="post" class="row g-2 mb-3">
    <input type="hidden" name="csrf" value="<?= Util::esc(Util::csrfToken()) ?>">
    <div class="col-md-3">
        <label class="form-label">Spezies</label>
        <select name="species_id" class="form-select">
            <?php foreach ($species as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= isset($_POST['species_id']) && $_POST['species_id'] == $s['id'] ? 'selected' : '' ?>><?= Util::esc($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Gender</label>
        <select name="gender_id" class="form-select">
            <?php foreach ($genders as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= isset($_POST['gender_id']) && $_POST['gender_id'] == $g['id'] ? 'selected' : '' ?>><?= Util::esc($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Lebensphase</label>
        <select name="life_stage_id" class="form-select">
            <option value="">--</option>
            <?php foreach ($lifeStages as $ls): ?>
                <option value="<?= (int)$ls['id'] ?>" <?= isset($_POST['life_stage_id']) && $_POST['life_stage_id'] == $ls['id'] ? 'selected' : '' ?>><?= Util::esc($ls['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Alter</label>
        <input type="number" name="age" class="form-control" value="<?= Util::esc($_POST['age'] ?? 25) ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label">Seed</label>
        <input type="number" name="seed" class="form-control" value="<?= Util::esc($_POST['seed'] ?? '') ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary">Generieren</button>
    </div>
</form>

<div class="row">
    <div class="col-md-6">
        <h3 class="h6">Aktuelle Engine</h3>
        <pre class="bg-white p-3 border" style="max-height:520px; overflow:auto;">
<?= $result ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' ?>
        </pre>
    </div>
    <div class="col-md-6">
        <h3 class="h6">Legacy (Placeholder)</h3>
        <pre class="bg-white p-3 border" style="max-height:520px; overflow:auto;">
<?= $legacy ? json_encode($legacy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Legacy runner noch nicht verbunden.' ?>
        </pre>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.tpl.php';
