<?php

declare(strict_types=1);

namespace App\Services\Betting;

final class ValueBetDetector
{
    /**
     * Detect whether a bookmaker price is a "value bet" given a market estimate.
     *
     * Approach:
     * - impliedProbability = 1 / bookmakerOdds
     * - marketImpliedProbability = 1 / marketAverageOdds
     * - expected profit per 1 stake (decimal odds) = p_market * bookmakerOdds - 1
     *
     * Positive expected profit => "value"
     */
    public static function detectValueBet(float $bookmakerOdds, float $marketAverageOdds): array
    {
        if ($bookmakerOdds <= 1.0 || $marketAverageOdds <= 1.0) {
            return [
                'is_value_bet' => false,
                'implied_probability' => 0.0,
                'market_implied_probability' => 0.0,
                'expected_value_profit' => 0.0,
            ];
        }

        $impliedProbability = ImpliedProbability::fromDecimalOdds($bookmakerOdds);
        $marketImpliedProbability = ImpliedProbability::fromDecimalOdds($marketAverageOdds);
        $expectedValueProfit = ($marketImpliedProbability * $bookmakerOdds) - 1.0;

        return [
            'is_value_bet' => $expectedValueProfit > 0.0,
            'implied_probability' => $impliedProbability,
            'market_implied_probability' => $marketImpliedProbability,
            'expected_value_profit' => $expectedValueProfit,
        ];
    }
}

