<?php

declare(strict_types=1);

namespace App\Services\Betting;

final class ArbitrageDetector
{
    /**
     * Detect 3-way arbitrage (1X2) from best odds.
     *
     * Arbitrage condition:
     * - sum(1/odds_home + 1/odds_draw + 1/odds_away) < 1
     *
     * profit% = (1 - sum) * 100
     *
     * Also returns stake ratios proportional to (1/odds) divided by the sum.
     */
    public static function detectThreeWayArbitrage(
        float $homeOdds,
        ?float $drawOdds,
        float $awayOdds
    ): array {
        if ($homeOdds <= 1.0 || $awayOdds <= 1.0 || $drawOdds === null || $drawOdds <= 1.0) {
            return [
                'is_arbitrage' => false,
                'implied_probability_sum' => 0.0,
                'profit_percentage' => 0.0,
                'stake_ratios' => [
                    'home' => 0.0,
                    'draw' => 0.0,
                    'away' => 0.0,
                ],
            ];
        }

        $inverseHome = 1.0 / $homeOdds;
        $inverseDraw = 1.0 / $drawOdds;
        $inverseAway = 1.0 / $awayOdds;

        $impliedSum = $inverseHome + $inverseDraw + $inverseAway;
        $isArb = $impliedSum > 0.0 && $impliedSum < 1.0;
        $profitPercentage = ($impliedSum > 0.0) ? (1.0 - $impliedSum) * 100.0 : 0.0;

        $stakeRatios = [
            'home' => ($impliedSum > 0.0) ? $inverseHome / $impliedSum : 0.0,
            'draw' => ($impliedSum > 0.0) ? $inverseDraw / $impliedSum : 0.0,
            'away' => ($impliedSum > 0.0) ? $inverseAway / $impliedSum : 0.0,
        ];

        return [
            'is_arbitrage' => $isArb,
            'implied_probability_sum' => $impliedSum,
            'profit_percentage' => $profitPercentage,
            'stake_ratios' => $stakeRatios,
        ];
    }
}

