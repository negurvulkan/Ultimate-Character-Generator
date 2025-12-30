<?php
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Util.php';
require_once __DIR__ . '/../lib/Template.php';

use Ucg\Db;
use Ucg\Auth;
use Ucg\Util;
use Ucg\Template;

$pdo = Db::connect();
$auth = new Auth($pdo);
$tpl = new Template();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Util::verifyCsrf($_POST['csrf'] ?? '');
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($auth->login($email, $password)) {
            header('Location: /admin/dashboard.php');
            exit;
        }
        $error = 'Login fehlgeschlagen';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$tpl->assign('csrf', Util::csrfToken());
$tpl->assign('error', $error);
$tpl->display('login.tpl.php');
