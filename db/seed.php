<?php
// Seed data extracted from chargen2.html to populate DB-first rule engine.
// Usage: php db/seed.php (expects DB_DSN, DB_USER, DB_PASS env vars)

require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../lib/Util.php';

use Ucg\Db;
use Ucg\Util;

$pdo = Db::connect();
$pdo->beginTransaction();

function insert($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

// Clear existing data to allow repeatable seeding
$tables = ['audit_log','rule_dependency','rule_condition','rule','base_profile','computed_key','stage_modifier','life_stage','gender','ancestry_group','species','admin_user'];
foreach ($tables as $table) {
    $pdo->exec("DELETE FROM {$table}");
}

// Taxonomy
$speciesId = insert($pdo, "INSERT INTO species (slug, name) VALUES (?, ?)", ['human', 'Mensch']);

$ancestries = [
    ['slug' => 'generic', 'name' => 'Allgemein', 'parent_id' => null],
];
foreach ($ancestries as $a) {
    insert($pdo, "INSERT INTO ancestry_group (species_id, slug, name, parent_id) VALUES (?,?,?,?)", [$speciesId, $a['slug'], $a['name'], $a['parent_id']]);
}

$genderIds = [
    'male' => insert($pdo, "INSERT INTO gender (slug, name) VALUES (?, ?)", ['male', 'Männlich']),
    'female' => insert($pdo, "INSERT INTO gender (slug, name) VALUES (?, ?)", ['female', 'Weiblich'])
];

$lifeStages = [
    ['slug' => 'child', 'name' => 'Kind', 'sort' => 10],
    ['slug' => 'puberty_early', 'name' => 'Pubertät Früh', 'sort' => 20],
    ['slug' => 'puberty_mid', 'name' => 'Pubertät Mitte', 'sort' => 30],
    ['slug' => 'puberty_late', 'name' => 'Pubertät Spät', 'sort' => 40],
    ['slug' => 'adult', 'name' => 'Erwachsen', 'sort' => 50],
];
$lifeStageIds = [];
foreach ($lifeStages as $ls) {
    $lifeStageIds[$ls['slug']] = insert($pdo, "INSERT INTO life_stage (slug, name, sort_order) VALUES (?,?,?)", [$ls['slug'], $ls['name'], $ls['sort']]);
}

// Computed keys (subset grouped by output sections)
$computedKeys = [
    ['key_path' => 'meta.name', 'label' => 'Name', 'category' => 'meta'],
    ['key_path' => 'meta.gender', 'label' => 'Geschlecht', 'category' => 'meta'],
    ['key_path' => 'meta.age', 'label' => 'Alter', 'category' => 'meta'],
    ['key_path' => 'meta.buildFactor', 'label' => 'Körperbau-Faktor', 'category' => 'meta'],
    ['key_path' => 'meta.buildLabel', 'label' => 'Körperbau-Typ', 'category' => 'meta'],
    ['key_path' => 'meta.hairColor', 'label' => 'Haarfarbe', 'category' => 'meta'],
    ['key_path' => 'meta.eyeColor', 'label' => 'Augenfarbe', 'category' => 'meta'],
    ['key_path' => 'meta.tanner.primary', 'label' => 'Tanner Primär', 'category' => 'meta'],
    ['key_path' => 'meta.tanner.pubic', 'label' => 'Tanner Pubic', 'category' => 'meta'],
    ['key_path' => 'meta.tanner.summary', 'label' => 'Tanner Zusammenfassung', 'category' => 'meta'],

    ['key_path' => 'body.height', 'label' => 'Körpergröße', 'unit' => 'cm', 'category' => 'body'],
    ['key_path' => 'body.scale', 'label' => 'Skalierung', 'category' => 'body'],
    ['key_path' => 'body.bmi', 'label' => 'BMI', 'category' => 'body'],
    ['key_path' => 'body.weight', 'label' => 'Gewicht', 'unit' => 'kg', 'category' => 'body'],
    ['key_path' => 'body.headsTall', 'label' => 'Kopfanzahl', 'category' => 'body'],
    ['key_path' => 'body.headHeight', 'label' => 'Kopfhöhe', 'unit' => 'cm', 'category' => 'body'],
    ['key_path' => 'body.headCircum', 'label' => 'Kopfumfang', 'unit' => 'cm', 'category' => 'body'],
    ['key_path' => 'body.neckLen', 'label' => 'Halslänge', 'unit' => 'cm', 'category' => 'body'],

    ['key_path' => 'face.length', 'label' => 'Gesichtslänge', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.width', 'label' => 'Gesichtsbreite', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.eyeDist', 'label' => 'Augenabstand', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.noseLen', 'label' => 'Nasenscheitel-Länge', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.noseWidth', 'label' => 'Nasenbreite', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.mouthWidth', 'label' => 'Mundbreite', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.earLen', 'label' => 'Ohrenlänge', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.earProt', 'label' => 'Ohrenabstand', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.foreheadH', 'label' => 'Stirnhöhe', 'unit' => 'cm', 'category' => 'face'],
    ['key_path' => 'face.foreheadW', 'label' => 'Stirnbreite', 'unit' => 'cm', 'category' => 'face'],

    ['key_path' => 'legs.total', 'label' => 'Beinlänge Gesamt', 'unit' => 'cm', 'category' => 'legs'],
    ['key_path' => 'legs.inseam', 'label' => 'Innenbeinlänge', 'unit' => 'cm', 'category' => 'legs'],
    ['key_path' => 'legs.thighLen', 'label' => 'Oberschenkellänge', 'unit' => 'cm', 'category' => 'legs'],
    ['key_path' => 'legs.shankLen', 'label' => 'Unterschenkellänge', 'unit' => 'cm', 'category' => 'legs'],
    ['key_path' => 'legs.thighCircum', 'label' => 'Oberschenkelumfang', 'unit' => 'cm', 'category' => 'legs'],
    ['key_path' => 'legs.calfCircum', 'label' => 'Wadenumfang', 'unit' => 'cm', 'category' => 'legs'],
    ['key_path' => 'legs.ankleCircum', 'label' => 'Knöchelumfang', 'unit' => 'cm', 'category' => 'legs'],

    ['key_path' => 'limbs.handLen', 'label' => 'Handlänge', 'unit' => 'cm', 'category' => 'limbs'],
    ['key_path' => 'limbs.handW', 'label' => 'Handbreite', 'unit' => 'cm', 'category' => 'limbs'],
    ['key_path' => 'limbs.fingerLen', 'label' => 'Fingerlänge', 'unit' => 'cm', 'category' => 'limbs'],
    ['key_path' => 'limbs.footLen', 'label' => 'Fußlänge', 'unit' => 'cm', 'category' => 'limbs'],

    ['key_path' => 'measurements.chest', 'label' => 'Brustumfang', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.underbust', 'label' => 'Unterbrustumfang', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.cup', 'label' => 'Körbchengröße', 'category' => 'measurements'],
    ['key_path' => 'measurements.chestDepth', 'label' => 'Brustkorbtiefe', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.nippleDist', 'label' => 'Brustwarzenabstand', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.waist', 'label' => 'Taille', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.hips', 'label' => 'Hüfte', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.gluteCircum', 'label' => 'Gesäßumfang', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.shoulderWidth', 'label' => 'Schulterbreite', 'unit' => 'cm', 'category' => 'measurements'],
    ['key_path' => 'measurements.armLength', 'label' => 'Armlänge', 'unit' => 'cm', 'category' => 'measurements'],

    ['key_path' => 'clothing.pants', 'label' => 'Hosenweite', 'category' => 'clothing'],
    ['key_path' => 'clothing.shirt', 'label' => 'Oberteil', 'category' => 'clothing'],
    ['key_path' => 'clothing.dress', 'label' => 'Kleid', 'category' => 'clothing'],
    ['key_path' => 'clothing.bra', 'label' => 'BH', 'category' => 'clothing']
];

$computedKeyIds = [];
foreach ($computedKeys as $ck) {
    $computedKeyIds[$ck['key_path']] = insert(
        $pdo,
        "INSERT INTO computed_key (key_path, label, unit, category, description) VALUES (?,?,?,?,?)",
        [$ck['key_path'], $ck['label'], $ck['unit'] ?? null, $ck['category'] ?? null, $ck['description'] ?? null]
    );
}

// Base profiles
$baseProfiles = [
    [
        'gender' => 'male',
        'profile' => [
            'height' => 175,
            'waist' => 85,
            'hips' => 95,
            'shoulder' => 46,
            'hand' => 19,
            'foot' => 26.5,
            'baseChestDepth' => 26
        ]
    ],
    [
        'gender' => 'female',
        'profile' => [
            'height' => 162,
            'waist' => 72,
            'hips' => 96,
            'shoulder' => 39,
            'hand' => 17.5,
            'foot' => 24,
            'baseChestDepth' => 24
        ]
    ]
];
foreach ($baseProfiles as $bp) {
    insert(
        $pdo,
        "INSERT INTO base_profile (species_id, gender_id, priority, profile_json) VALUES (?,?,?,?)",
        [$speciesId, $genderIds[$bp['gender']], 50, json_encode($bp['profile'])]
    );
}

// Helper to attach rule, conditions, dependencies
function addRule($pdo, $computedKeyIds, $key, $name, $distribution, $params, $priority = 100, $conditions = [], $deps = [], $notes = null) {
    $rid = insert($pdo, "INSERT INTO rule (computed_key_id, name, priority, distribution, params_json, notes) VALUES (?,?,?,?,?,?)", [
        $computedKeyIds[$key],
        $name,
        $priority,
        $distribution,
        json_encode($params),
        $notes
    ]);
    foreach ($conditions as $cond) {
        insert($pdo, "INSERT INTO rule_condition (rule_id, species_id, ancestry_group_id, include_children, gender_id, life_stage_id, min_age, max_age) VALUES (?,?,?,?,?,?,?,?)", [
            $rid,
            $cond['species_id'] ?? null,
            $cond['ancestry_group_id'] ?? null,
            $cond['include_children'] ?? 1,
            $cond['gender_id'] ?? null,
            $cond['life_stage_id'] ?? null,
            $cond['min_age'] ?? null,
            $cond['max_age'] ?? null
        ]);
    }
    foreach ($deps as $dep) {
        insert($pdo, "INSERT INTO rule_dependency (rule_id, depends_on_computed_key_id) VALUES (?,?)", [
            $rid,
            $computedKeyIds[$dep]
        ]);
    }
    return $rid;
}

// Random helper distributions derived from original JS
addRule($pdo, $computedKeyIds, 'meta.name', 'Weighted name', 'choice', [
    'options' => [
        ['value' => 'male', 'values' => ["Alexander","Maximilian","Paul","Luca","Felix","Leon","Lukas","Jonas","Tim","Finn","Noah","Elias","Julian","Luis","Ben","Henry","Jakob","David","Moritz","Niklas"]],
        ['value' => 'female', 'values' => ["Sophie","Marie","Sophia","Emma","Mia","Hannah","Emilia","Anna","Lea","Lina","Mila","Ella","Leni","Leonie","Lilly","Emily","Clara","Laura","Lara","Johanna"]]
    ],
    'combine_with_lastnames' => ["Müller","Schmidt","Schneider","Fischer","Weber","Meyer","Wagner","Becker","Schulz","Hoffmann","Schäfer","Koch","Bauer","Richter","Klein","Wolf","Schröder"],
    'selector_key' => 'meta.gender'
]);

addRule($pdo, $computedKeyIds, 'meta.gender', 'Gender passthrough', 'choice', [
    'options' => [
        ['value' => 'male', 'weight' => 0.5],
        ['value' => 'female', 'weight' => 0.5]
    ],
    'respect_context' => true
], 1);

addRule($pdo, $computedKeyIds, 'meta.age', 'Age passthrough', 'uniform', [
    'min' => ['context' => 'age'],
    'max' => ['context' => 'age'],
    'round' => 0
], 1);

addRule($pdo, $computedKeyIds, 'meta.buildFactor', 'Build factor', 'gaussian', [
    'mean' => 1.0,
    'stddev' => 0.06,
    'clamp' => ['min' => 0.92, 'max' => 1.08],
    'round' => 2
]);

addRule($pdo, $computedKeyIds, 'meta.buildLabel', 'Build label', 'piecewise', [
    'input_key' => 'meta.buildFactor',
    'cases' => [
        ['max' => 0.95, 'value' => 'Ektomorph (schlank)'],
        ['min' => 1.05, 'value' => 'Endomorph (weich/rundlich)'],
        ['value' => 'Mesomorph (athletisch)']
    ]
], 50, [], ['meta.buildFactor']);

// Hair and eye color weighted by age
addRule($pdo, $computedKeyIds, 'meta.hairColor', 'Hair color', 'choice', [
    'options' => [
        ['value' => 'schwarz', 'weight' => 0.05, 'age_weight_overrides' => [['min_age' => 50, 'weight' => 0.03]]],
        ['value' => 'dunkelbraun', 'weight' => 0.25, 'age_weight_overrides' => [['min_age' => 50, 'weight' => 0.15]]],
        ['value' => 'mittelbraun', 'weight' => 0.30, 'age_weight_overrides' => [['min_age' => 50, 'weight' => 0.25]]],
        ['value' => 'hellbraun', 'weight' => 0.20],
        ['value' => 'dunkelblond', 'weight' => 0.10, 'age_weight_overrides' => [['min_age' => 50, 'weight' => 0.15]]],
        ['value' => 'blond', 'weight' => 0.08, 'age_weight_overrides' => [['min_age' => 50, 'weight' => 0.10]]],
        ['value' => 'rot', 'weight' => 0.02],
        ['value' => 'grau/weiss', 'weight' => 0.00, 'age_weight_overrides' => [['min_age' => 50, 'weight' => 0.10]]]
    ]
]);

addRule($pdo, $computedKeyIds, 'meta.eyeColor', 'Eye color', 'choice', [
    'options' => [
        ['value' => 'braun', 'weight' => 0.50],
        ['value' => 'blau', 'weight' => 0.30],
        ['value' => 'grün', 'weight' => 0.12],
        ['value' => 'grau', 'weight' => 0.05],
        ['value' => 'hasel', 'weight' => 0.03]
    ]
]);

// Tanner staging inspired by calcTanner
addRule($pdo, $computedKeyIds, 'meta.tanner.primary', 'Tanner primary', 'piecewise', [
    'input_key' => 'meta.age',
    'cases' => [
        ['max' => 9, 'distribution' => ['type' => 'gaussian', 'mean' => 1, 'stddev' => 0.2, 'round' => 0, 'clamp' => ['min' => 1, 'max' => 2]]],
        ['min' => 10, 'max' => 11, 'distribution' => ['type' => 'gaussian', 'mean' => 2, 'stddev' => 0.6, 'round' => 0, 'clamp' => ['min' => 1, 'max' => 3]]],
        ['min' => 12, 'max' => 13, 'distribution' => ['type' => 'gaussian', 'mean' => 3, 'stddev' => 0.6, 'round' => 0, 'clamp' => ['min' => 1, 'max' => 4]]],
        ['min' => 14, 'max' => 15, 'distribution' => ['type' => 'gaussian', 'mean' => 4, 'stddev' => 0.6, 'round' => 0, 'clamp' => ['min' => 2, 'max' => 5]]],
        ['min' => 16, 'distribution' => ['type' => 'gaussian', 'mean' => 5, 'stddev' => 0.4, 'round' => 0, 'clamp' => ['min' => 4, 'max' => 5]]]
    ],
    'gender_bias' => ['female' => 0.3, 'male' => -0.2]
], 100, [], ['meta.age', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'meta.tanner.pubic', 'Tanner pubic', 'piecewise', [
    'input_key' => 'meta.age',
    'cases' => [
        ['max' => 8, 'distribution' => ['type' => 'gaussian', 'mean' => 1, 'stddev' => 0.2, 'round' => 0, 'clamp' => ['min' => 1, 'max' => 2]]],
        ['min' => 9, 'max' => 10, 'distribution' => ['type' => 'gaussian', 'mean' => 2, 'stddev' => 0.7, 'round' => 0, 'clamp' => ['min' => 1, 'max' => 3]]],
        ['min' => 11, 'max' => 12, 'distribution' => ['type' => 'gaussian', 'mean' => 3, 'stddev' => 0.7, 'round' => 0, 'clamp' => ['min' => 2, 'max' => 4]]],
        ['min' => 13, 'max' => 14, 'distribution' => ['type' => 'gaussian', 'mean' => 4, 'stddev' => 0.7, 'round' => 0, 'clamp' => ['min' => 3, 'max' => 5]]],
        ['min' => 15, 'distribution' => ['type' => 'gaussian', 'mean' => 5, 'stddev' => 0.4, 'round' => 0, 'clamp' => ['min' => 4, 'max' => 5]]]
    ]
], 100, [], ['meta.age']);

addRule($pdo, $computedKeyIds, 'meta.tanner.summary', 'Tanner summary', 'piecewise', [
    'input_key' => 'meta.age',
    'cases' => [
        ['min' => 18, 'value' => 'Erwachsen (Tanner-Stadium 5)'],
        ['value' => 'Jugendlich']
    ]
], 200, [], ['meta.age']);

// Height
addRule($pdo, $computedKeyIds, 'body.height', 'Height by age', 'piecewise', [
    'input_key' => 'meta.age',
    'cases' => [
        ['max' => 1, 'distribution' => ['type' => 'gaussian', 'mean' => 75, 'stddev' => 5, 'round' => 0]],
        ['min' => 2, 'max' => 2, 'distribution' => ['type' => 'gaussian', 'mean' => 87, 'stddev' => 5, 'round' => 0]],
        ['min' => 3, 'max' => 3, 'distribution' => ['type' => 'gaussian', 'mean' => 95, 'stddev' => 5, 'round' => 0]],
        ['min' => 4, 'max' => 4, 'distribution' => ['type' => 'gaussian', 'mean' => 103, 'stddev' => 5, 'round' => 0]],
        ['min' => 5, 'max' => 5, 'distribution' => ['type' => 'gaussian', 'mean' => 110, 'stddev' => 5, 'round' => 0]],
        ['min' => 6, 'max' => 6, 'distribution' => ['type' => 'gaussian', 'mean' => 116, 'stddev' => 5, 'round' => 0]],
        ['min' => 7, 'max' => 7, 'distribution' => ['type' => 'gaussian', 'mean' => 122, 'stddev' => 7, 'round' => 0]],
        ['min' => 8, 'max' => 8, 'distribution' => ['type' => 'gaussian', 'mean' => 128, 'stddev' => 7, 'round' => 0]],
        ['min' => 9, 'max' => 9, 'distribution' => ['type' => 'gaussian', 'mean' => 134, 'stddev' => 7, 'round' => 0]],
        ['min' => 10, 'max' => 10, 'distribution' => ['type' => 'gaussian', 'mean' => 140, 'stddev' => 7, 'round' => 0]],
        ['min' => 11, 'max' => 11, 'distribution' => ['type' => 'gaussian', 'mean' => 146, 'stddev' => 7, 'round' => 0]],
        ['min' => 12, 'max' => 12, 'distribution' => ['type' => 'gaussian', 'mean' => 153, 'stddev' => 7, 'round' => 0]],
        ['min' => 13, 'max' => 13, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 156.5, 'female' => 157], 'stddev' => 8, 'round' => 0]],
        ['min' => 14, 'max' => 14, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 159.7, 'female' => 159.0], 'stddev' => 8, 'round' => 0]],
        ['min' => 15, 'max' => 15, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 164.0, 'female' => 160.4], 'stddev' => 8, 'round' => 0]],
        ['min' => 16, 'max' => 16, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 168.3, 'female' => 161.2], 'stddev' => 8, 'round' => 0]],
        ['min' => 17, 'max' => 17, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 171.5, 'female' => 161.7], 'stddev' => 8, 'round' => 0]],
        ['min' => 18, 'max' => 59, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 175, 'female' => 162], 'stddev' => 7, 'round' => 0]],
        ['min' => 60, 'distribution' => ['type' => 'gaussian', 'mean' => ['male' => 175, 'female' => 162], 'stddev' => 7, 'round' => 0, 'decline_after' => ['age' => 60, 'rate' => 0.2]]]
    ]
], 100, [], ['meta.age', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'body.scale', 'Scale vs adult base', 'ratio', [
    'input_key' => 'body.height',
    'factor_per_gender' => ['male' => 1/175, 'female' => 1/162],
    'round' => 3
], 100, [], ['body.height', 'meta.gender']);

// BMI and weight
addRule($pdo, $computedKeyIds, 'body.bmi', 'BMI by age', 'piecewise', [
    'input_key' => 'meta.age',
    'cases' => [
        ['max' => 5, 'distribution' => ['type' => 'gaussian', 'mean' => 15.5, 'stddev' => 1.0, 'round' => 1]],
        ['min' => 6, 'max' => 10, 'distribution' => ['type' => 'gaussian', 'mean_formula' => ['start' => 16, 'slope' => 0.4], 'stddev' => 1.0, 'round' => 1]],
        ['min' => 11, 'max' => 15, 'distribution' => ['type' => 'gaussian', 'mean_formula' => ['start' => 18, 'slope' => 0.6], 'stddev' => 1.5, 'round' => 1]],
        ['min' => 16, 'max' => 20, 'distribution' => ['type' => 'gaussian', 'mean_formula' => ['start' => 21, 'slope' => 0.4], 'stddev' => 2.0, 'round' => 1]],
        ['min' => 21, 'max' => 40, 'distribution' => ['type' => 'gaussian', 'mean' => 24.5, 'stddev' => 2.5, 'round' => 1]],
        ['min' => 41, 'max' => 60, 'distribution' => ['type' => 'gaussian', 'mean' => 26.0, 'stddev' => 2.5, 'round' => 1]],
        ['min' => 61, 'distribution' => ['type' => 'gaussian', 'mean' => 25.0, 'stddev' => 2.0, 'round' => 1]]
    ],
    'inputs' => [['key' => 'meta.buildFactor', 'op' => 'mul']]
], 100, [], ['meta.age', 'meta.buildFactor']);

addRule($pdo, $computedKeyIds, 'body.weight', 'Weight from BMI', 'ratio', [
    'input_key' => 'body.bmi',
    'factor_key' => 'body.height',
    'power' => 2,
    'factor' => 0.0001,
    'round' => 1
], 100, [], ['body.bmi', 'body.height']);

addRule($pdo, $computedKeyIds, 'body.headsTall', 'Heads tall', 'gaussian', [
    'mean' => ['child' => 4.8, 'preteen' => 6.0, 'teen' => 7.1, 'adult' => 8.0],
    'stddev' => 0.3,
    'round' => 1,
    'age_breaks' => [5,10,15]
], 100, [], ['meta.age']);

addRule($pdo, $computedKeyIds, 'body.headHeight', 'Head height', 'ratio', [
    'input_key' => 'body.height',
    'factor_key' => 'body.headsTall',
    'op' => 'div',
    'round' => 0
], 100, [], ['body.height', 'body.headsTall']);

addRule($pdo, $computedKeyIds, 'body.headCircum', 'Head circumference', 'gaussian', [
    'mean' => ['male' => 58, 'female' => 56],
    'stddev' => 2,
    'inputs' => [
        ['key' => 'body.headHeight', 'op' => 'mul', 'factor' => 1/22]
    ],
    'round' => 0
], 100, [], ['body.headHeight', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'body.neckLen', 'Neck length', 'gaussian', [
    'mean' => ['male' => 13, 'female' => 11],
    'stddev' => 1.5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 1
], 100, [], ['body.scale', 'meta.buildFactor', 'meta.gender']);

// Face metrics
$faceRules = [
    ['face.length', ['male' => 19.5, 'female' => 18.8], 1.2],
    ['face.width', ['male' => 15.2, 'female' => 14.0], 1.0],
    ['face.eyeDist', ['male' => 3.2, 'female' => 3.2], 0.3],
    ['face.noseLen', ['male' => 19.5 * 0.32, 'female' => 18.8 * 0.32], 1.2 * 0.32],
    ['face.noseWidth', ['male' => 3.8, 'female' => 3.4], 0.4],
    ['face.mouthWidth', ['male' => 5.1, 'female' => 4.8], 0.5],
    ['face.earLen', ['male' => 6.3, 'female' => 6.3], 0.5],
    ['face.earProt', ['male' => 1.8, 'female' => 1.8], 0.4]
];
foreach ($faceRules as $rule) {
    addRule($pdo, $computedKeyIds, $rule[0], 'Face metric', 'gaussian', [
        'mean' => $rule[1],
        'stddev' => $rule[2],
        'inputs' => [['key' => 'body.headHeight', 'op' => 'mul', 'factor' => 1/22]],
        'round' => 1
    ], 100, [], ['body.headHeight', 'meta.gender']);
}

addRule($pdo, $computedKeyIds, 'face.foreheadH', 'Forehead height', 'ratio', [
    'input_key' => 'face.length',
    'factor' => 0.33,
    'round' => 1
], 120, [], ['face.length']);

addRule($pdo, $computedKeyIds, 'face.foreheadW', 'Forehead width', 'ratio', [
    'input_key' => 'face.width',
    'factor' => 0.85,
    'round' => 1
], 120, [], ['face.width']);

// Legs & measurements
addRule($pdo, $computedKeyIds, 'legs.inseam', 'Inseam', 'gaussian', [
    'mean' => ['male' => 0.45, 'female' => 0.46],
    'stddev' => 0.018,
    'inputs' => [
        ['key' => 'body.height', 'op' => 'mul'],
        ['key' => 'body.scale', 'op' => 'mul', 'weight' => 0.01],
        ['key' => 'meta.buildFactor', 'op' => 'mul', 'weight' => 0.005],
        ['key' => 'meta.age', 'op' => 'range_add', 'ranges' => [
            ['min' => 12, 'max' => 18, 'delta' => 0.02]
        ]]
    ],
    'round' => 0
], 100, [], ['body.height', 'body.scale', 'meta.buildFactor', 'meta.age', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'legs.total', 'Leg total', 'ratio', [
    'input_key' => 'legs.inseam',
    'factor_key' => null,
    'offset_input' => ['key' => 'body.height', 'factor' => 0.1],
    'round' => 0
], 110, [], ['legs.inseam', 'body.height']);

addRule($pdo, $computedKeyIds, 'legs.thighLen', 'Thigh length', 'gaussian', [
    'mean' => 0.52,
    'stddev' => 0.03,
    'inputs' => [
        ['key' => 'legs.total', 'op' => 'mul']
    ],
    'round' => 0
], 120, [], ['legs.total']);

addRule($pdo, $computedKeyIds, 'legs.shankLen', 'Shank length', 'ratio', [
    'input_key' => 'legs.total',
    'offset_input' => ['key' => 'legs.thighLen', 'op' => 'sub'],
    'round' => 0
], 120, [], ['legs.total', 'legs.thighLen']);

addRule($pdo, $computedKeyIds, 'legs.thighCircum', 'Thigh circumference', 'gaussian', [
    'mean' => ['male' => 56, 'female' => 54],
    'stddev' => 5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 130, [], ['body.scale', 'meta.buildFactor', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'legs.calfCircum', 'Calf circumference', 'gaussian', [
    'mean' => ['male' => 37, 'female' => 35],
    'stddev' => 3,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 130, [], ['body.scale', 'meta.buildFactor', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'legs.ankleCircum', 'Ankle circumference', 'gaussian', [
    'mean' => ['male' => 23, 'female' => 21.5],
    'stddev' => 1.5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 130, [], ['body.scale', 'meta.buildFactor', 'meta.gender']);

// Limbs
addRule($pdo, $computedKeyIds, 'limbs.handLen', 'Hand length', 'gaussian', [
    'mean_key' => 'base.hand',
    'stddev' => 1,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul']
    ],
    'round' => 0
], 100, [], ['body.scale']);

addRule($pdo, $computedKeyIds, 'limbs.handW', 'Hand width', 'gaussian', [
    'mean' => 0.45,
    'stddev' => 0.03,
    'inputs' => [
        ['key' => 'limbs.handLen', 'op' => 'mul']
    ],
    'round' => 1
], 110, [], ['limbs.handLen']);

addRule($pdo, $computedKeyIds, 'limbs.fingerLen', 'Finger length', 'ratio', [
    'input_key' => 'limbs.handLen',
    'factor' => 0.45,
    'round' => 1
], 110, [], ['limbs.handLen']);

addRule($pdo, $computedKeyIds, 'limbs.footLen', 'Foot length', 'gaussian', [
    'mean_key' => 'base.foot',
    'stddev' => 1.5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul']
    ],
    'round' => 1
], 100, [], ['body.scale']);

// Measurements
addRule($pdo, $computedKeyIds, 'measurements.waist', 'Waist', 'gaussian', [
    'mean_key' => 'base.waist',
    'stddev' => 6,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 100, [], ['body.scale', 'meta.buildFactor']);

addRule($pdo, $computedKeyIds, 'measurements.hips', 'Hips', 'gaussian', [
    'mean_key' => 'base.hips',
    'stddev' => 5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 100, [], ['body.scale', 'meta.buildFactor']);

addRule($pdo, $computedKeyIds, 'measurements.shoulderWidth', 'Shoulder width', 'gaussian', [
    'mean' => ['male' => 41.5, 'female' => 36.5],
    'stddev' => 2.5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 100, [], ['body.scale', 'meta.buildFactor', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'measurements.armLength', 'Arm length', 'gaussian', [
    'mean' => ['male' => 60, 'female' => 55],
    'stddev' => 3,
    'inputs' => [['key' => 'body.scale', 'op' => 'mul']],
    'round' => 0
], 100, [], ['body.scale', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'measurements.chestDepth', 'Chest depth', 'gaussian', [
    'mean_key' => 'base.baseChestDepth',
    'stddev' => 2,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 100, [], ['body.scale', 'meta.buildFactor']);

addRule($pdo, $computedKeyIds, 'measurements.chest', 'Chest circumference', 'gaussian', [
    'mean' => ['male' => 100, 'female' => 76],
    'stddev' => 6,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 100, [], ['body.scale', 'meta.buildFactor', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'measurements.underbust', 'Underbust circumference', 'gaussian', [
    'mean' => ['male' => 0, 'female' => 76],
    'stddev' => 4,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 0
], 100, [
    ['gender_id' => $genderIds['female']]
], ['body.scale', 'meta.buildFactor', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'measurements.nippleDist', 'Nipple distance', 'gaussian', [
    'mean' => 22,
    'stddev' => 2.5,
    'inputs' => [
        ['key' => 'body.scale', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul']
    ],
    'round' => 1,
    'min_stage' => 3
], 120, [], ['body.scale', 'meta.buildFactor']);

addRule($pdo, $computedKeyIds, 'measurements.gluteCircum', 'Glute circumference', 'gaussian', [
    'mean' => ['male' => 0.98, 'female' => 1.05],
    'stddev' => 0.04,
    'inputs' => [
        ['key' => 'measurements.hips', 'op' => 'mul'],
        ['key' => 'meta.buildFactor', 'op' => 'mul', 'weight' => 0.05]
    ],
    'round' => 0
], 120, [], ['measurements.hips', 'meta.buildFactor', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'measurements.hips', 'Hips clamp', 'piecewise', [
    'input_key' => 'measurements.gluteCircum',
    'cases' => [
        ['value_from' => 'measurements.hips', 'prefer_min' => true]
    ]
], 200, [], ['measurements.gluteCircum']);

addRule($pdo, $computedKeyIds, 'measurements.cup', 'Cup size', 'linear', [
    'base' => 0,
    'inputs' => [
        ['key' => 'measurements.chest', 'op' => 'sub', 'factor' => 1, 'ref_key' => 'measurements.underbust']
    ],
    'cup_map' => [[10,'AA'],[12,'A'],[14,'B'],[16,'C'],[18,'D'],[20,'E'],[22,'F']],
    'round' => 0
], 200, [], ['measurements.chest', 'measurements.underbust']);

// Clothing
addRule($pdo, $computedKeyIds, 'clothing.pants', 'Pants size', 'piecewise', [
    'input_key' => 'meta.age',
    'cases' => [
        ['max' => 14, 'value' => 'Kindergröße'],
        ['value_from_formula' => 'pants']
    ]
], 200, [], ['meta.age', 'measurements.waist', 'legs.inseam', 'meta.gender', 'measurements.hips']);

addRule($pdo, $computedKeyIds, 'clothing.shirt', 'Shirt size', 'piecewise', [
    'cases' => [['value_from_formula' => 'shirt']]
], 200, [], ['measurements.chest']);

addRule($pdo, $computedKeyIds, 'clothing.dress', 'Dress size', 'piecewise', [
    'cases' => [['value_from_formula' => 'dress']]
], 200, [], ['measurements.hips', 'meta.gender']);

addRule($pdo, $computedKeyIds, 'clothing.bra', 'Bra size', 'piecewise', [
    'cases' => [['value_from_formula' => 'bra']]
], 200, [
    ['gender_id' => $genderIds['female']]
], ['measurements.underbust', 'measurements.chest']);

$pdo->commit();

echo "Seed complete\n";
