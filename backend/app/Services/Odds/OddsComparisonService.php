<?php

declare(strict_types=1);

namespace App\Services\Odds;

use App\Services\Betting\ArbitrageDetector;
use App\Services\Betting\ValueBetDetector;

/**
 * Builds odds comparison payloads and computes:
 * - best odds per outcome (max decimal odds)
 * - market average odds per outcome
 * - value bet flags (bookmaker EV vs market-implied)
 * - 3-way arbitrage detection using the best odds
 */
final class OddsComparisonService
{
    /**
     * @param array $bookmakerRows list of bookmakers odds:
     *   [
     *     [
     *       'bookmaker_id' => 1,
     *       'home_odds' => 2.1,
     *       'draw_odds' => 3.4,
     *       'away_odds' => 3.2,
     *     ],
     *     ...
     *   ]
     *
     * @return array{
     *   best_odds: array{home: float|null, draw: float|null, away: float|null},
     *   market_average_odds: array{home: float|null, draw: float|null, away: float|null},
     *   rows: array<int, array{
     *     bookmaker_id: int|float|string,
     *     home_odds: float,
     *     draw_odds: float|null,
     *     away_odds: float,
     *     is_best_home: bool,
     *     is_best_draw: bool,
     *     is_best_away: bool,
     *     value_bets: array{home: bool, draw: bool, away: bool},
     *     value_profit: array{home: float, draw: float, away: float},
 *     value_details: array{
 *       home: array{implied_probability: float, market_implied_probability: float, market_average_odds: float, expected_value_profit: float},
 *       draw: array{implied_probability: float, market_implied_probability: float, market_average_odds: float, expected_value_profit: float},
 *       away: array{implied_probability: float, market_implied_probability: float, market_average_odds: float, expected_value_profit: float},
 *     },
     *   }>,
     *   arbitrage: array{
     *     is_arbitrage: bool,
     *     implied_probability_sum: float,
     *     profit_percentage: float,
     *     stake_ratios: array{home: float, draw: float, away: float},
     *   }
     * }
     */
    public function compareOneMatch(array $bookmakerRows): array
    {
        $bestHome = null;
        $bestDraw = null;
        $bestAway = null;
        $bestHomeBookmakerId = null;
        $bestDrawBookmakerId = null;
        $bestAwayBookmakerId = null;

        $homeOddsList = [];
        $drawOddsList = [];
        $awayOddsList = [];

        foreach ($bookmakerRows as $row) {
            $bookmakerId = $row['bookmaker_id'] ?? null;
            $home = isset($row['home_odds']) ? (float) $row['home_odds'] : null;
            $draw = array_key_exists('draw_odds', $row) ? ($row['draw_odds'] !== null ? (float) $row['draw_odds'] : null) : null;
            $away = isset($row['away_odds']) ? (float) $row['away_odds'] : null;

            if ($home !== null && $home > 1.0) {
                $homeOddsList[] = $home;
                if ($bestHome === null || $home > $bestHome) {
                    $bestHome = $home;
                    $bestHomeBookmakerId = $bookmakerId;
                }
            }
            if ($draw !== null && $draw > 1.0) {
                $drawOddsList[] = $draw;
                if ($bestDraw === null || $draw > $bestDraw) {
                    $bestDraw = $draw;
                    $bestDrawBookmakerId = $bookmakerId;
                }
            }
            if ($away !== null && $away > 1.0) {
                $awayOddsList[] = $away;
                if ($bestAway === null || $away > $bestAway) {
                    $bestAway = $away;
                    $bestAwayBookmakerId = $bookmakerId;
                }
            }
        }

        $marketAverageOdds = [
            'home' => $this->avgOrNull($homeOddsList),
            'draw' => $this->avgOrNull($drawOddsList),
            'away' => $this->avgOrNull($awayOddsList),
        ];

        $rows = [];
        foreach ($bookmakerRows as $row) {
            $bookmakerId = $row['bookmaker_id'] ?? null;

            $homeOdds = isset($row['home_odds']) ? (float) $row['home_odds'] : 0.0;
            $drawOdds = array_key_exists('draw_odds', $row) ? ($row['draw_odds'] !== null ? (float) $row['draw_odds'] : null) : null;
            $awayOdds = isset($row['away_odds']) ? (float) $row['away_odds'] : 0.0;

            $isBestHome = ($bestHome !== null) && abs($homeOdds - $bestHome) < 0.00001;
            $isBestDraw = ($bestDraw !== null) && $drawOdds !== null && abs($drawOdds - $bestDraw) < 0.00001;
            $isBestAway = ($bestAway !== null) && abs($awayOdds - $bestAway) < 0.00001;

            $valueHome = false;
            $valueDraw = false;
            $valueAway = false;
            $profitHome = 0.0;
            $profitDraw = 0.0;
            $profitAway = 0.0;

            $detailsHome = [
                'implied_probability' => 0.0,
                'market_implied_probability' => 0.0,
                'market_average_odds' => 0.0,
                'expected_value_profit' => 0.0,
            ];
            $detailsDraw = [
                'implied_probability' => 0.0,
                'market_implied_probability' => 0.0,
                'market_average_odds' => 0.0,
                'expected_value_profit' => 0.0,
            ];
            $detailsAway = [
                'implied_probability' => 0.0,
                'market_implied_probability' => 0.0,
                'market_average_odds' => 0.0,
                'expected_value_profit' => 0.0,
            ];

            if ($marketAverageOdds['home'] !== null && $homeOdds > 1.0) {
                $calc = ValueBetDetector::detectValueBet($homeOdds, (float) $marketAverageOdds['home']);
                $valueHome = (bool) $calc['is_value_bet'];
                $profitHome = (float) $calc['expected_value_profit'];
                $detailsHome = [
                    'implied_probability' => (float) $calc['implied_probability'],
                    'market_implied_probability' => (float) $calc['market_implied_probability'],
                    'market_average_odds' => (float) $marketAverageOdds['home'],
                    'expected_value_profit' => (float) $calc['expected_value_profit'],
                ];
            }

            if ($marketAverageOdds['draw'] !== null && $drawOdds !== null && $drawOdds > 1.0) {
                $calc = ValueBetDetector::detectValueBet($drawOdds, (float) $marketAverageOdds['draw']);
                $valueDraw = (bool) $calc['is_value_bet'];
                $profitDraw = (float) $calc['expected_value_profit'];
                $detailsDraw = [
                    'implied_probability' => (float) $calc['implied_probability'],
                    'market_implied_probability' => (float) $calc['market_implied_probability'],
                    'market_average_odds' => (float) $marketAverageOdds['draw'],
                    'expected_value_profit' => (float) $calc['expected_value_profit'],
                ];
            }

            if ($marketAverageOdds['away'] !== null && $awayOdds > 1.0) {
                $calc = ValueBetDetector::detectValueBet($awayOdds, (float) $marketAverageOdds['away']);
                $valueAway = (bool) $calc['is_value_bet'];
                $profitAway = (float) $calc['expected_value_profit'];
                $detailsAway = [
                    'implied_probability' => (float) $calc['implied_probability'],
                    'market_implied_probability' => (float) $calc['market_implied_probability'],
                    'market_average_odds' => (float) $marketAverageOdds['away'],
                    'expected_value_profit' => (float) $calc['expected_value_profit'],
                ];
            }

            $rows[] = [
                'bookmaker_id' => $bookmakerId,
                'home_odds' => $homeOdds,
                'draw_odds' => $drawOdds,
                'away_odds' => $awayOdds,
                'is_best_home' => $isBestHome,
                'is_best_draw' => $isBestDraw,
                'is_best_away' => $isBestAway,
                'value_bets' => [
                    'home' => $valueHome,
                    'draw' => $valueDraw,
                    'away' => $valueAway,
                ],
                'value_profit' => [
                    'home' => $profitHome,
                    'draw' => $profitDraw,
                    'away' => $profitAway,
                ],
                'value_details' => [
                    'home' => $detailsHome,
                    'draw' => $detailsDraw,
                    'away' => $detailsAway,
                ],
            ];
        }

        $arbitrage = ArbitrageDetector::detectThreeWayArbitrage(
            (float) ($bestHome ?? 0.0),
            $bestDraw !== null ? (float) $bestDraw : null,
            (float) ($bestAway ?? 0.0)
        );

        return [
            'best_odds' => [
                'home' => $bestHome,
                'draw' => $bestDraw,
                'away' => $bestAway,
            ],
            'best_bookmaker_ids' => [
                'home' => $bestHomeBookmakerId,
                'draw' => $bestDrawBookmakerId,
                'away' => $bestAwayBookmakerId,
            ],
            'market_average_odds' => $marketAverageOdds,
            'rows' => $rows,
            'arbitrage' => $arbitrage + [
                'home_bookmaker_id' => $bestHomeBookmakerId !== null ? (int) $bestHomeBookmakerId : 0,
                'draw_bookmaker_id' => $bestDrawBookmakerId !== null ? (int) $bestDrawBookmakerId : 0,
                'away_bookmaker_id' => $bestAwayBookmakerId !== null ? (int) $bestAwayBookmakerId : 0,
                'home_odds' => $bestHome !== null ? (float) $bestHome : 0.0,
                'draw_odds' => $bestDraw !== null ? (float) $bestDraw : null,
                'away_odds' => $bestAway !== null ? (float) $bestAway : 0.0,
            ],
        ];
    }

    private function avgOrNull(array $values): ?float
    {
        $values = array_values(array_filter($values, fn ($v) => is_numeric($v) && (float) $v > 1.0));
        if (count($values) === 0) {
            return null;
        }
        return array_sum($values) / count($values);
    }
}

