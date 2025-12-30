<?php
require_once __DIR__ . '/lib/Db.php';
require_once __DIR__ . '/lib/Util.php';

use Ucg\Db;
use Ucg\Util;

$pdo = Db::connect();

$schema = file_get_contents(__DIR__ . '/db/schema.sql');
$pdo->exec($schema);

echo "Schema imported\n";

// optional seed
if (php_sapi_name() === 'cli') {
    include __DIR__ . '/db/seed.php';
}
