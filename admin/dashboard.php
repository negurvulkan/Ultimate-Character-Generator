<?php
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Template.php';
require_once __DIR__ . '/../lib/Util.php';

use Ucg\Db;
use Ucg\Auth;
use Ucg\Template;
use Ucg\Util;

$pdo = Db::connect();
$auth = new Auth($pdo);
$auth->ensure();
$tpl = new Template();
$title = 'Dashboard';
$user = $auth->user();
include __DIR__ . '/../templates/admin_header.tpl.php';
?>
<div class="row">
    <div class="col">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3">Willkommen</h2>
                <p>Verwalte Taxonomie, Regeln und Basisprofile. Nutze Preview, um die Engine gegen aktuelle Seeds zu testen.</p>
                <ul>
                    <li><a href="/db/schema.sql">Schema</a></li>
                    <li><a href="/db/seed.php">Seed Script</a></li>
                    <li><a href="/api/generate.php" target="_blank">API Preview</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.tpl.php';
