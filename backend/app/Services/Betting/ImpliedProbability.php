<?php

declare(strict_types=1);

namespace App\Services\Betting;

final class ImpliedProbability
{
    /**
     * Convert decimal odds to implied probability.
     * Example: 2.50 => 0.4
     */
    public static function fromDecimalOdds(float $decimalOdds): float
    {
        if ($decimalOdds <= 1.0) {
            // Odds <= 1 are invalid for 1X2 markets; treat as 0 probability to avoid exploding math.
            return 0.0;
        }

        return 1.0 / $decimalOdds;
    }
}

