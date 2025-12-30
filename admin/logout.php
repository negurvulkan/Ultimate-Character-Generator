<?php
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Auth.php';

use Ucg\Db;
use Ucg\Auth;

$auth = new Auth(Db::connect());
$auth->logout();
header('Location: /admin/login.php');
exit;
