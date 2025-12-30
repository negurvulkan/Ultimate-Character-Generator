<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/RuleEngine.php';
require_once __DIR__ . '/../lib/Util.php';

use Ucg\Db;
use Ucg\RuleEngine;
use Ucg\Util;

try {
    $pdo = Db::connect();
    $input = $_POST + $_GET;
    $age = isset($input['age']) ? (int)$input['age'] : 25;
    $genderSlug = $input['gender'] ?? 'male';
    $seed = isset($input['seed']) && $input['seed'] !== '' ? (int)$input['seed'] : null;

    $genderStmt = $pdo->prepare('SELECT * FROM gender WHERE slug = ?');
    $genderStmt->execute([$genderSlug]);
    $genderRow = $genderStmt->fetch();
    if (!$genderRow) {
        throw new RuntimeException('Ungültiges Gender');
    }

    $ctx = [
        'species_id' => isset($input['species_id']) ? (int)$input['species_id'] : null,
        'ancestry_group_id' => isset($input['ancestry_group_id']) ? (int)$input['ancestry_group_id'] : null,
        'gender_id' => (int)$genderRow['id'],
        'gender_slug' => $genderSlug,
        'life_stage_id' => isset($input['life_stage_id']) ? (int)$input['life_stage_id'] : null,
        'age' => $age,
        'seed' => $seed
    ];

    $engine = new RuleEngine($pdo, $seed);
    $result = $engine->generate($ctx);
    $response = [
        'meta' => [
            'name' => $result['meta']['name'] ?? 'Unbekannt',
            'age' => $age,
            'gender' => $genderSlug,
            'build' => $result['meta']['buildLabel'] ?? 'N/A',
            'buildFactor' => $result['meta']['buildFactor'] ?? null,
            'tanner' => [
                'primary' => $result['meta']['tanner']['primary'] ?? null,
                'pubic' => $result['meta']['tanner']['pubic'] ?? null,
                'summary' => $result['meta']['tanner']['summary'] ?? null
            ],
            'hairColor' => $result['meta']['hairColor'] ?? null,
            'eyeColor' => $result['meta']['eyeColor'] ?? null
        ],
        'body' => $result['body'] ?? [],
        'face' => $result['face'] ?? [],
        'legs' => $result['legs'] ?? [],
        'limbs' => $result['limbs'] ?? [],
        'measurements' => $result['measurements'] ?? [],
        'clothing' => $result['clothing'] ?? []
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
