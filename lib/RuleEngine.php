<?php
namespace Ucg;

use PDO;
use RuntimeException;

class RuleEngine
{
    private PDO $pdo;
    private Rng $rng;
    private array $cache = [];

    public function __construct(PDO $pdo, ?int $seed = null)
    {
        $this->pdo = $pdo;
        $this->rng = new Rng();
        $this->rng->seed($seed);
    }

    public function generate(array $context): array
    {
        $computed = [];
        $keys = $this->loadComputedKeys();
        $rules = $this->loadRules();
        $deps = $this->buildDependencies($rules);
        $ordered = $this->toposort(array_keys($keys), $deps);

        // base profiles applied for convenience
        $baseProfile = $this->loadBaseProfile($context);

        foreach ($ordered as $keyPath) {
            $ruleSet = $rules[$keyPath] ?? [];
            $rule = $this->selectRule($ruleSet, $context);
            if (!$rule) {
                continue;
            }
            $value = $this->evaluateRule($rule, $computed, $context, $baseProfile);
            $this->setValue($computed, $keyPath, $value);
        }

        return $this->postProcess($computed, $context);
    }

    private function loadComputedKeys(): array
    {
        if (isset($this->cache['keys'])) {
            return $this->cache['keys'];
        }
        $rows = $this->pdo->query('SELECT * FROM computed_key WHERE is_active = 1')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['key_path']] = $row;
        }
        return $this->cache['keys'] = $map;
    }

    private function loadRules(): array
    {
        if (isset($this->cache['rules'])) {
            return $this->cache['rules'];
        }
        $sql = 'SELECT r.*, ck.key_path FROM rule r JOIN computed_key ck ON ck.id = r.computed_key_id WHERE r.is_active = 1 ORDER BY r.priority ASC';
        $rows = $this->pdo->query($sql)->fetchAll();
        $rules = [];
        foreach ($rows as $row) {
            $row['params'] = Util::jsonDecode($row['params_json']);
            $row['conditions'] = $this->loadConditions($row['id']);
            $row['dependencies'] = $this->loadDependencies($row['id']);
            $rules[$row['key_path']][] = $row;
        }
        return $this->cache['rules'] = $rules;
    }

    private function loadConditions(int $ruleId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rule_condition WHERE rule_id = ?');
        $stmt->execute([$ruleId]);
        return $stmt->fetchAll();
    }

    private function loadDependencies(int $ruleId): array
    {
        $stmt = $this->pdo->prepare('SELECT depends_on_computed_key_id, ck.key_path FROM rule_dependency rd JOIN computed_key ck ON ck.id = rd.depends_on_computed_key_id WHERE rd.rule_id = ?');
        $stmt->execute([$ruleId]);
        $deps = [];
        foreach ($stmt->fetchAll() as $row) {
            $deps[] = $row['key_path'];
        }
        return $deps;
    }

    private function buildDependencies(array $rules): array
    {
        $depMap = [];
        foreach ($rules as $key => $list) {
            foreach ($list as $rule) {
                foreach ($rule['dependencies'] as $dep) {
                    $depMap[$key][] = $dep;
                }
            }
        }
        return $depMap;
    }

    private function toposort(array $nodes, array $deps): array
    {
        $visited = [];
        $order = [];
        $visiting = [];

        $visit = function ($n) use (&$visit, &$visited, &$order, &$visiting, $deps) {
            if (isset($visited[$n])) {
                return;
            }
            if (isset($visiting[$n])) {
                throw new RuntimeException('Circular dependency at ' . $n);
            }
            $visiting[$n] = true;
            foreach ($deps[$n] ?? [] as $d) {
                $visit($d);
            }
            $visited[$n] = true;
            $order[] = $n;
        };

        foreach ($nodes as $n) {
            $visit($n);
        }

        return $order;
    }

    private function selectRule(array $rules, array $ctx): ?array
    {
        foreach ($rules as $rule) {
            if ($this->matches($rule['conditions'], $ctx)) {
                return $rule;
            }
        }
        return $rules[0] ?? null;
    }

    private function matches(array $conditions, array $ctx): bool
    {
        if (!$conditions) {
            return true;
        }
        foreach ($conditions as $cond) {
            if ($cond['species_id'] && ($ctx['species_id'] ?? null) != $cond['species_id']) {
                continue;
            }
            if ($cond['gender_id'] && ($ctx['gender_id'] ?? null) != $cond['gender_id']) {
                continue;
            }
            if ($cond['life_stage_id'] && ($ctx['life_stage_id'] ?? null) != $cond['life_stage_id']) {
                continue;
            }
            if ($cond['min_age'] && ($ctx['age'] ?? 0) < $cond['min_age']) {
                continue;
            }
            if ($cond['max_age'] && ($ctx['age'] ?? 0) > $cond['max_age']) {
                continue;
            }
            return true;
        }
        return false;
    }

    private function loadBaseProfile(array $ctx): array
    {
        $stmt = $this->pdo->query('SELECT * FROM base_profile ORDER BY priority ASC');
        $profiles = $stmt->fetchAll();
        foreach ($profiles as $p) {
            $match = true;
            if ($p['species_id'] && $p['species_id'] != ($ctx['species_id'] ?? null)) {
                $match = false;
            }
            if ($p['gender_id'] && $p['gender_id'] != ($ctx['gender_id'] ?? null)) {
                $match = false;
            }
            if ($p['life_stage_id'] && $p['life_stage_id'] != ($ctx['life_stage_id'] ?? null)) {
                $match = false;
            }
            if ($match) {
                return Util::jsonDecode($p['profile_json']);
            }
        }
        return [];
    }

    private function evaluateRule(array $rule, array $values, array $ctx, array $baseProfile)
    {
        switch ($rule['distribution']) {
            case 'gaussian':
                return $this->evalGaussian($rule['params'], $values, $ctx, $baseProfile);
            case 'uniform':
                return $this->evalUniform($rule['params'], $values, $ctx, $baseProfile);
            case 'linear':
                return $this->evalLinear($rule['params'], $values, $ctx, $baseProfile);
            case 'ratio':
                return $this->evalRatio($rule['params'], $values, $ctx, $baseProfile);
            case 'piecewise':
                return $this->evalPiecewise($rule['params'], $values, $ctx, $baseProfile);
            case 'sigmoid':
                return $this->evalSigmoid($rule['params'], $values, $ctx, $baseProfile);
            case 'choice':
                return $this->evalChoice($rule['params'], $values, $ctx, $baseProfile);
            default:
                throw new RuntimeException('Unknown distribution ' . $rule['distribution']);
        }
    }

    private function evalGaussian(array $params, array $values, array $ctx, array $baseProfile)
    {
        $mean = $this->resolveParam($params['mean'] ?? 0, $values, $ctx, $baseProfile);
        if (isset($params['mean_formula']) && isset($ctx['age'])) {
            $mean = $params['mean_formula']['start'] + ($ctx['age'] - ($params['min_age'] ?? 0)) * $params['mean_formula']['slope'];
        }
        $std = $params['stddev'] ?? 1;
        $val = $this->rng->gaussian($mean, $std);
        if (!empty($params['inputs'])) {
            $val = $this->applyInputs($val, $params['inputs'], $values, $ctx);
        }
        if (!empty($params['decline_after']) && isset($ctx['age'])) {
            $decl = $params['decline_after'];
            $excess = max(0, $ctx['age'] - ($decl['age'] ?? 0));
            $val += $excess * ($decl['rate'] ?? 0);
        }
        $val = $this->applyClampRound($val, $params);
        return $val;
    }

    private function evalUniform(array $params, array $values, array $ctx, array $baseProfile)
    {
        $min = $this->resolveParam($params['min'] ?? 0, $values, $ctx, $baseProfile);
        $max = $this->resolveParam($params['max'] ?? 1, $values, $ctx, $baseProfile);
        $val = $this->rng->uniform($min, $max);
        return $this->applyClampRound($val, $params);
    }

    private function evalLinear(array $params, array $values, array $ctx, array $baseProfile)
    {
        $base = $this->resolveParam($params['base'] ?? 0, $values, $ctx, $baseProfile);
        $val = $base;
        foreach ($params['inputs'] ?? [] as $input) {
            $sourceVal = $this->fetchValue($input['key'] ?? null, $values, $ctx, $baseProfile);
            if (isset($input['ref_key'])) {
                $ref = $this->fetchValue($input['ref_key'], $values, $ctx, $baseProfile);
                $sourceVal = $sourceVal - $ref;
            }
            $factor = $input['factor'] ?? 1;
            if (($input['op'] ?? 'add') === 'sub') {
                $val -= $sourceVal * $factor;
            } else {
                $val += $sourceVal * $factor;
            }
        }
        // optional cup mapping
        if (isset($params['cup_map'])) {
            $diff = $val;
            foreach ($params['cup_map'] as [$threshold, $label]) {
                if ($diff <= $threshold) {
                    return $label;
                }
            }
            return end($params['cup_map'])[1];
        }
        return $this->applyClampRound($val, $params);
    }

    private function evalRatio(array $params, array $values, array $ctx, array $baseProfile)
    {
        $inputVal = $this->fetchValue($params['input_key'] ?? null, $values, $ctx, $baseProfile);
        $power = $params['power'] ?? 1;
        $factor = $params['factor'] ?? 1;
        $factorKey = $params['factor_key'] ?? null;
        if (is_array($factor) && isset($factor['context']) && isset($ctx[$factor['context']])) {
            $factor = $ctx[$factor['context']];
        }
        if (is_array($factor) && isset($factor['per_gender']) && isset($ctx['gender_slug'])) {
            $factor = $factor['per_gender'][$ctx['gender_slug']] ?? 1;
        }
        if (isset($params['factor_per_gender']) && isset($ctx['gender_slug'])) {
            $factor = $params['factor_per_gender'][$ctx['gender_slug']] ?? $factor;
        }
        $val = pow($inputVal, $power) * $factor;
        if ($factorKey) {
            $fk = $this->fetchValue($factorKey, $values, $ctx, $baseProfile);
            $val *= $fk;
        }
        if (!empty($params['offset_input'])) {
            $offsetVal = $this->fetchValue($params['offset_input']['key'], $values, $ctx, $baseProfile);
            $offFactor = $params['offset_input']['factor'] ?? 1;
            $op = $params['offset_input']['op'] ?? 'add';
            $val = $op === 'sub' ? $val - ($offsetVal * $offFactor) : $val + ($offsetVal * $offFactor);
        }
        $val = $this->applyClampRound($val, $params);
        return $val;
    }

    private function evalPiecewise(array $params, array $values, array $ctx, array $baseProfile)
    {
        $input = $this->fetchValue($params['input_key'] ?? null, $values, $ctx, $baseProfile);
        foreach ($params['cases'] as $case) {
            $min = $case['min'] ?? null;
            $max = $case['max'] ?? null;
            if (($min !== null && $input < $min) || ($max !== null && $input > $max)) {
                continue;
            }
            if (isset($case['distribution'])) {
                $dist = $case['distribution'];
                $dist['clamp'] = $dist['clamp'] ?? $params['clamp'] ?? null;
                $dist['round'] = $dist['round'] ?? $params['round'] ?? null;
                if (($dist['type'] ?? '') === 'gaussian') {
                    return $this->evalGaussian($dist, $values, $ctx, $baseProfile);
                }
            }
            if (isset($case['value'])) {
                return $case['value'];
            }
            if (isset($case['value_from'])) {
                return $this->fetchValue($case['value_from'], $values, $ctx, $baseProfile);
            }
            if (isset($case['value_from_formula'])) {
                return $this->formulaHelper($case['value_from_formula'], $values, $ctx, $baseProfile);
            }
        }
        return $params['default'] ?? null;
    }

    private function evalSigmoid(array $params, array $values, array $ctx, array $baseProfile)
    {
        $input = $this->fetchValue($params['input_key'], $values, $ctx, $baseProfile);
        $k = $params['k'] ?? 1;
        $x0 = $params['x0'] ?? 0;
        $L = $params['L'] ?? 1;
        $val = $L / (1 + exp(-$k * ($input - $x0)));
        return $this->applyClampRound($val, $params);
    }

    private function evalChoice(array $params, array $values, array $ctx, array $baseProfile)
    {
        if (!empty($params['respect_context']) && isset($ctx['gender_slug'])) {
            return $ctx['gender_slug'];
        }
        $selectorKey = $params['selector_key'] ?? null;
        $selector = $selectorKey ? $this->fetchValue($selectorKey, $values, $ctx, $baseProfile) : null;
        $options = [];
        $weights = [];
        if ($selector && !empty($params['options'])) {
            foreach ($params['options'] as $opt) {
                if (($opt['value'] ?? null) === $selector && isset($opt['values'])) {
                    return $opt['values'][array_rand($opt['values'])];
                }
            }
        }
        foreach ($params['options'] ?? [] as $opt) {
            $w = $opt['weight'] ?? 1;
            if (!empty($opt['age_weight_overrides']) && isset($ctx['age'])) {
                foreach ($opt['age_weight_overrides'] as $ow) {
                    if (($ow['min_age'] ?? 0) <= $ctx['age']) {
                        $w = $ow['weight'];
                    }
                }
            }
            $options[] = $opt['value'];
            $weights[] = $w;
        }
        $choice = $this->rng->choice($options, $weights);
        if (!empty($params['combine_with_lastnames']) && $selector) {
            $last = $params['combine_with_lastnames'][array_rand($params['combine_with_lastnames'])];
            return $choice . ' ' . $last;
        }
        return $choice;
    }

    private function resolveParam($param, array $values, array $ctx, array $baseProfile)
    {
        if (is_array($param)) {
            if (isset($param['male']) && isset($ctx['gender_slug']) && $ctx['gender_slug'] === 'male') {
                return $param['male'];
            }
            if (isset($param['female']) && isset($ctx['gender_slug']) && $ctx['gender_slug'] === 'female') {
                return $param['female'];
            }
            if (isset($param['child']) || isset($param['preteen']) || isset($param['teen']) || isset($param['adult'])) {
                $age = $ctx['age'] ?? 0;
                if ($age <= ($param['child_age'] ?? 5)) {
                    return $param['child'] ?? $param['adult'];
                }
                if ($age <= ($param['preteen_age'] ?? 10)) {
                    return $param['preteen'] ?? $param['adult'];
                }
                if ($age <= ($param['teen_age'] ?? 15)) {
                    return $param['teen'] ?? $param['adult'];
                }
                return $param['adult'];
            }
            if (isset($param['context']) && isset($ctx[$param['context']])) {
                return $ctx[$param['context']];
            }
            if (isset($param['per_gender']) && isset($ctx['gender_slug'])) {
                return $param['per_gender'][$ctx['gender_slug']] ?? null;
            }
            if (isset($param['base_male']) || isset($param['base_female'])) {
                $val = $ctx['gender_slug'] === 'male' ? ($param['base_male'] ?? 0) : ($param['base_female'] ?? 0);
                return $this->applyInputs($val, $param['inputs'] ?? [], $values, $ctx);
            }
            if (isset($param['value_from'])) {
                return $this->fetchValue($param['value_from'], $values, $ctx, $baseProfile);
            }
        }
        if (is_string($param) && str_starts_with($param, 'base.')) {
            $k = substr($param, 5);
            return $baseProfile[$k] ?? 0;
        }
        return $param;
    }

    private function applyInputs($base, array $inputs, array $values, array $ctx)
    {
        $val = $base;
        foreach ($inputs as $input) {
            $key = $input['key'] ?? null;
            $op = $input['op'] ?? 'add';
            $source = $this->fetchValue($key, $values, $ctx, []);
            $factor = $input['factor'] ?? 1;
            if ($op === 'mul') {
                $val *= $source * $factor;
            } elseif ($op === 'add') {
                $val += $source * $factor;
            } elseif ($op === 'sub') {
                $val -= $source * $factor;
            } elseif ($op === 'div') {
                $val /= max(0.0001, $source * $factor);
            } elseif ($op === 'min') {
                $val = min($val, $source);
            } elseif ($op === 'max') {
                $val = max($val, $source);
            } elseif ($op === 'lerp') {
                $t = $input['t'] ?? 0.5;
                $val = $val + ($source - $val) * $t;
            } elseif ($op === 'range_add' && isset($input['ranges'])) {
                foreach ($input['ranges'] as $r) {
                    if ((!isset($r['min']) || $source >= $r['min']) && (!isset($r['max']) || $source <= $r['max'])) {
                        $val += $r['delta'] ?? 0;
                    }
                }
            }
        }
        return $val;
    }

    private function applyClampRound($val, array $params)
    {
        if (isset($params['clamp']['min'])) {
            $val = max($params['clamp']['min'], $val);
        }
        if (isset($params['clamp']['max'])) {
            $val = min($params['clamp']['max'], $val);
        }
        if (isset($params['round'])) {
            $factor = pow(10, (int)$params['round']);
            $val = round($val * $factor) / $factor;
        }
        return $val;
    }

    private function fetchValue(?string $key, array $values, array $ctx, array $baseProfile)
    {
        if ($key === null) {
            return 0;
        }
        if (str_starts_with($key, 'ctx.')) {
            $k = substr($key, 4);
            return $ctx[$k] ?? 0;
        }
        if ($key === 'meta.age') {
            return $ctx['age'] ?? 0;
        }
        if ($key === 'meta.gender') {
            return $ctx['gender_slug'] ?? ($ctx['gender'] ?? null);
        }
        if (str_starts_with($key, 'base.')) {
            $k = substr($key, 5);
            return $baseProfile[$k] ?? 0;
        }
        $parts = explode('.', $key);
        $cur = $values;
        foreach ($parts as $p) {
            if (!isset($cur[$p])) {
                return null;
            }
            $cur = $cur[$p];
        }
        return $cur;
    }

    private function setValue(array &$target, string $path, $value): void
    {
        $parts = explode('.', $path);
        $ref =& $target;
        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) {
                $ref[$p] = [];
            }
            $ref =& $ref[$p];
        }
        $ref = $value;
    }

    private function postProcess(array $values, array $ctx): array
    {
        // Add BMI badge data, clothing formulas etc.
        // Clothing formulas
        if (isset($values['meta']['gender'])) {
            $ctx['gender_slug'] = $values['meta']['gender'];
        }
        if (isset($values['meta']['age'])) {
            $ctx['age'] = $values['meta']['age'];
        }
        // clothing formulas require waist/inseam etc.
        if (($values['clothing']['pants'] ?? null) === null && isset($values['measurements']['waist'], $values['legs']['inseam'])) {
            $values['clothing']['pants'] = $this->formulaHelper('pants', $values, $ctx, []);
        }
        if (($values['clothing']['shirt'] ?? null) === null && isset($values['measurements']['chest'])) {
            $values['clothing']['shirt'] = $this->formulaHelper('shirt', $values, $ctx, []);
        }
        if (($values['clothing']['dress'] ?? null) === null && isset($values['measurements']['hips']) && ($ctx['gender_slug'] ?? '') === 'female') {
            $values['clothing']['dress'] = $this->formulaHelper('dress', $values, $ctx, []);
        }
        if (($values['clothing']['bra'] ?? null) === null && ($ctx['gender_slug'] ?? '') === 'female' && isset($values['measurements']['underbust'], $values['measurements']['chest'])) {
            $values['clothing']['bra'] = $this->formulaHelper('bra', $values, $ctx, []);
        }

        return $values;
    }

    private function formulaHelper(string $type, array $values, array $ctx, array $base)
    {
        switch ($type) {
            case 'pants':
                if (($ctx['age'] ?? 0) < 15) {
                    return 'Kindergröße ' . (round(($values['body']['height'] ?? 0) / 6) * 6);
                }
                $wInch = round(($values['measurements']['waist'] ?? 0) / 2.54);
                $lInch = round(($values['legs']['inseam'] ?? 0) / 2.54);
                $lStandard = $lInch < 31 ? 30 : ($lInch < 33 ? 32 : 34);
                if (($ctx['gender_slug'] ?? 'male') === 'male') {
                    return 'W' . $wInch . ' / L' . $lStandard;
                }
                return 'Jeans: ' . max(24, $wInch - 5) . ' / ' . $lStandard;
            case 'shirt':
                $chest = $values['measurements']['chest'] ?? 0;
                if (($ctx['gender_slug'] ?? 'male') === 'male') {
                    return $chest < 94 ? 'S (46)' : ($chest < 102 ? 'M (50)' : 'L (54)');
                }
                return $chest < 92 ? '36 (S)' : ($chest < 100 ? '38/40 (M)' : '42 (L)');
            case 'dress':
                $hips = $values['measurements']['hips'] ?? 0;
                if (($ctx['gender_slug'] ?? 'male') !== 'female') {
                    return null;
                }
                return $hips < 92 ? '36 (S)' : ($hips < 100 ? '38/40 (M)' : '42 (L)');
            case 'bra':
                $under = $values['measurements']['underbust'] ?? 0;
                if (!$under) {
                    return null;
                }
                $band = round($under / 5) * 5;
                $cup = $this->formulaHelper('cup', $values, $ctx, $base);
                return $band . $cup;
            case 'cup':
                $diff = ($values['measurements']['chest'] ?? 0) - ($values['measurements']['underbust'] ?? 0);
                $cups = [['AA', 10], ['A', 12], ['B', 14], ['C', 16], ['D', 18], ['E', 20], ['F', 22]];
                foreach ($cups as [$label, $thr]) {
                    if ($diff <= $thr) {
                        return $label;
                    }
                }
                return 'F';
        }
        return null;
    }
}
