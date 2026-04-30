<?php

declare(strict_types=1);

namespace App\Services\Odds;

/**
 * Odds normalizer converts provider-specific payloads into our standard 1X2 shape.
 *
 * We keep it intentionally generic because different Odds APIs differ in naming
 * (home/away vs outcomes list with 'Draw', etc.).
 */
final class OddsNormalizer
{
    /**
     * Normalize "h2h" (head-to-head) market into decimal odds for home/draw/away.
     *
     * Expected $outcomes shape (example):
     * [
     *   ['name' => 'Team A', 'price' => 2.1],
     *   ['name' => 'Draw',  'price' => 3.5],
     *   ['name' => 'Team B', 'price' => 3.2],
     * ]
     *
     * @return array{home_odds: float|null, draw_odds: float|null, away_odds: float|null}
     */
    public static function normalizeH2HMarket(
        array $outcomes,
        ?string $homeTeamName,
        ?string $awayTeamName
    ): array {
        $homeOdds = null;
        $drawOdds = null;
        $awayOdds = null;

        foreach ($outcomes as $outcome) {
            $name = isset($outcome['name']) ? (string) $outcome['name'] : '';
            $price = isset($outcome['price']) ? (float) $outcome['price'] : null;
            if ($price === null || $price <= 0) {
                continue;
            }

            if ($homeTeamName !== null && $name === $homeTeamName) {
                $homeOdds = $price;
                continue;
            }
            if ($awayTeamName !== null && $name === $awayTeamName) {
                $awayOdds = $price;
                continue;
            }

            // Common representation for draws: "Draw" or "Tie"
            if ($name === 'Draw' || $name === 'Tie') {
                $drawOdds = $price;
                continue;
            }
        }

        return [
            'home_odds' => $homeOdds,
            'draw_odds' => $drawOdds,
            'away_odds' => $awayOdds,
        ];
    }
}

