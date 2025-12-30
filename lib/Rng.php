<?php
namespace Ucg;

class Rng
{
    private $seeded = false;

    public function seed(?int $seed): void
    {
        if ($seed !== null) {
            mt_srand($seed);
            $this->seeded = true;
        }
    }

    public function uniform(float $min, float $max): float
    {
        $r = mt_rand() / mt_getrandmax();
        return $min + ($max - $min) * $r;
    }

    public function gaussian(float $mean, float $stddev): float
    {
        $u = 0.0;
        $v = 0.0;
        while ($u === 0.0) {
            $u = $this->uniform(0, 1);
        }
        while ($v === 0.0) {
            $v = $this->uniform(0, 1);
        }
        $z = sqrt(-2.0 * log($u)) * cos(2.0 * M_PI * $v);
        return $mean + $stddev * $z;
    }

    public function choice(array $options, ?array $weights = null)
    {
        if (!$weights) {
            return $options[array_rand($options)];
        }
        $total = array_sum($weights);
        $r = $this->uniform(0, $total);
        $acc = 0;
        foreach ($options as $idx => $val) {
            $acc += $weights[$idx];
            if ($r <= $acc) {
                return $val;
            }
        }
        return end($options);
    }
}
