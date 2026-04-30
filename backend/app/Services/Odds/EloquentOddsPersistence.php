<?php

declare(strict_types=1);

namespace App\Services\Odds;

use App\Models\ArbitrageInsight;
use App\Models\Bookmaker;
use App\Models\League;
use App\Models\MatchModel;
use App\Models\OddsLatest;
use App\Models\OddsSnapshot;
use App\Models\Sport;
use App\Models\Team;
use App\Models\ValueBetInsight;
use DateTimeInterface;

final class EloquentOddsPersistence implements OddsPersistenceInterface
{
    public function resolveSport(string $providerSportKey, string $sportName): int
    {
        $slug = $this->slugify($sportName);

        $sport = Sport::updateOrCreate(
            ['provider_sport_key' => $providerSportKey],
            ['name' => $sportName, 'slug' => $slug, 'provider_sport_key' => $providerSportKey]
        );

        return (int) $sport->id;
    }

    public function resolveLeague(int $sportId, ?string $providerLeagueKey, string $leagueName): int
    {
        $slug = $this->slugify($leagueName);

        if ($providerLeagueKey !== null && $providerLeagueKey !== '') {
            $league = League::updateOrCreate(
                ['provider_league_key' => $providerLeagueKey],
                ['sport_id' => $sportId, 'name' => $leagueName, 'slug' => $slug, 'provider_league_key' => $providerLeagueKey]
            );
        } else {
            $league = League::updateOrCreate(
                ['sport_id' => $sportId, 'slug' => $slug],
                ['sport_id' => $sportId, 'name' => $leagueName, 'slug' => $slug, 'provider_league_key' => null]
            );
        }

        return (int) $league->id;
    }

    public function resolveTeam(string $teamName, ?string $providerTeamKey = null, ?string $country = null): int
    {
        $slug = $this->slugify($teamName);

        if ($providerTeamKey !== null && $providerTeamKey !== '') {
            $team = Team::updateOrCreate(
                ['provider_team_key' => $providerTeamKey],
                ['name' => $teamName, 'slug' => $slug, 'country' => $country, 'provider_team_key' => $providerTeamKey]
            );
        } else {
            $team = Team::updateOrCreate(
                ['slug' => $slug],
                ['name' => $teamName, 'slug' => $slug, 'country' => $country, 'provider_team_key' => null]
            );
        }

        return (int) $team->id;
    }

    public function resolveMatch(
        int $leagueId,
        int $homeTeamId,
        int $awayTeamId,
        ?string $externalMatchId,
        DateTimeInterface $startTimeUtc
    ): int {
        $externalMatchId = $externalMatchId ?? '';
        if ($externalMatchId === '') {
            // Fallback: if provider doesn't give an external ID, we attempt stable uniqueness via start_time + teams.
            // Note: production should enforce provider IDs to avoid duplicates.
            $match = MatchModel::updateOrCreate(
                [
                    'league_id' => $leagueId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'start_time' => $startTimeUtc->format('Y-m-d H:i:s.u'),
                ],
                [
                    'league_id' => $leagueId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'start_time' => $startTimeUtc->format('Y-m-d H:i:s.u'),
                    'status' => 'scheduled',
                    'external_match_id' => null,
                    'source_provider' => 'the_odds_api',
                ]
            );
        } else {
            $match = MatchModel::updateOrCreate(
                [
                    'source_provider' => 'the_odds_api',
                    'external_match_id' => $externalMatchId,
                ],
                [
                    'league_id' => $leagueId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'start_time' => $startTimeUtc->format('Y-m-d H:i:s.u'),
                    'status' => 'scheduled',
                    'external_match_id' => $externalMatchId,
                    'source_provider' => 'the_odds_api',
                ]
            );
        }

        return (int) $match->id;
    }

    public function resolveBookmaker(string $providerBookmakerKey, string $providerBookmakerName): int
    {
        // Prefer provider key when available; otherwise use name as identity.
        if ($providerBookmakerKey !== null && $providerBookmakerKey !== '') {
            $bookmaker = Bookmaker::updateOrCreate(
                ['provider_key' => $providerBookmakerKey],
                [
                    'name' => $providerBookmakerName,
                    'provider_key' => $providerBookmakerKey,
                    'affiliate_url' => '',
                    'logo_url' => null,
                    'is_active' => 1,
                ]
            );
        } else {
            $bookmaker = Bookmaker::updateOrCreate(
                ['name' => $providerBookmakerName],
                [
                    'name' => $providerBookmakerName,
                    'provider_key' => null,
                    'affiliate_url' => '',
                    'logo_url' => null,
                    'is_active' => 1,
                ]
            );
        }

        return (int) $bookmaker->id;
    }

    public function upsertOddsLatest(
        int $matchId,
        int $leagueId,
        int $homeTeamId,
        int $awayTeamId,
        int $bookmakerId,
        string $oddsType,
        float $homeOdds,
        ?float $drawOdds,
        float $awayOdds,
        DateTimeInterface $capturedAt
    ): void {
        OddsLatest::updateOrCreate(
            [
                'match_id' => $matchId,
                'bookmaker_id' => $bookmakerId,
                'odds_type' => $oddsType,
            ],
            [
                'league_id' => $leagueId,
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'home_odds' => $homeOdds,
                'draw_odds' => $drawOdds,
                'away_odds' => $awayOdds,
                'captured_at' => $capturedAt->format('Y-m-d H:i:s.u'),
                'updated_at' => $capturedAt->format('Y-m-d H:i:s.u'),
                'source_provider' => 'the_odds_api',
            ]
        );
    }

    public function insertOddsSnapshot(
        int $matchId,
        int $leagueId,
        int $homeTeamId,
        int $awayTeamId,
        int $bookmakerId,
        string $oddsType,
        float $homeOdds,
        ?float $drawOdds,
        float $awayOdds,
        DateTimeInterface $capturedAt
    ): void {
        OddsSnapshot::create([
            'match_id' => $matchId,
            'league_id' => $leagueId,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'bookmaker_id' => $bookmakerId,
            'odds_type' => $oddsType,
            'home_odds' => $homeOdds,
            'draw_odds' => $drawOdds,
            'away_odds' => $awayOdds,
            'captured_at' => $capturedAt->format('Y-m-d H:i:s.u'),
            'source_provider' => 'the_odds_api',
        ]);
    }

    public function persistValueBetInsights(
        int $matchId,
        int $leagueId,
        string $oddsType,
        DateTimeInterface $capturedAt,
        array $valueRows
    ): void {
        $captured = $capturedAt->format('Y-m-d H:i:s.u');

        foreach ($valueRows as $row) {
            $outcome = (string) ($row['outcome'] ?? '');
            if ($outcome === '' || !in_array($outcome, ['home', 'draw', 'away'], true)) {
                continue;
            }

            $bookmakerId = (int) ($row['bookmaker_id'] ?? 0);
            $bookmakerOdds = (float) ($row['bookmaker_odds'] ?? 0.0);
            $expectedValueProfit = (float) ($row['expected_value_profit'] ?? 0.0);
            $impliedProbability = (float) ($row['implied_probability'] ?? 0.0);
            $marketImpliedProbability = (float) ($row['market_implied_probability'] ?? 0.0);
            $marketAverageOdds = (float) ($row['market_average_odds'] ?? 0.0);

            ValueBetInsight::updateOrCreate(
                [
                    'match_id' => $matchId,
                    'bookmaker_id' => $bookmakerId,
                    'outcome' => $outcome,
                    'captured_at' => $captured,
                ],
                [
                    'league_id' => $leagueId,
                    'odds_type' => $oddsType,
                    'bookmaker_odds' => $bookmakerOdds,
                    'market_average_odds' => $marketAverageOdds,
                    'implied_probability' => $impliedProbability,
                    'market_implied_probability' => $marketImpliedProbability,
                    'expected_value_profit' => $expectedValueProfit,
                    'created_at' => $captured,
                    'captured_at' => $captured,
                ]
            );
        }
    }

    public function persistArbitrageInsight(
        int $matchId,
        int $leagueId,
        string $oddsType,
        DateTimeInterface $capturedAt,
        array $arbitrage
    ): void {
        $isArb = (bool) ($arbitrage['is_arbitrage'] ?? false);
        if (!$isArb) {
            return;
        }

        $homeOdds = (float) ($arbitrage['home_odds'] ?? 0.0);
        $drawOdds = $arbitrage['draw_odds'] ?? null;
        $awayOdds = (float) ($arbitrage['away_odds'] ?? 0.0);

        if ($homeOdds <= 1.0 || $awayOdds <= 1.0 || $drawOdds === null || (float) $drawOdds <= 1.0) {
            return;
        }

        $stakeRatios = $arbitrage['stake_ratios'] ?? [];
        $stakeHomeRatio = (float) ($stakeRatios['home'] ?? 0.0);
        $stakeDrawRatio = isset($stakeRatios['draw']) ? (float) $stakeRatios['draw'] : null;
        $stakeAwayRatio = (float) ($stakeRatios['away'] ?? 0.0);

        ArbitrageInsight::updateOrCreate(
            [
                'match_id' => $matchId,
                'captured_at' => $capturedAt->format('Y-m-d H:i:s.u'),
            ],
            [
                'league_id' => $leagueId,
                'odds_type' => $oddsType,
                'home_bookmaker_id' => (int) ($arbitrage['home_bookmaker_id'] ?? 0),
                'draw_bookmaker_id' => (int) ($arbitrage['draw_bookmaker_id'] ?? 0),
                'away_bookmaker_id' => (int) ($arbitrage['away_bookmaker_id'] ?? 0),
                'home_odds' => $homeOdds,
                'draw_odds' => (float) $drawOdds,
                'away_odds' => $awayOdds,
                'implied_probability_sum' => (float) ($arbitrage['implied_probability_sum'] ?? 0.0),
                'profit_percentage' => (float) ($arbitrage['profit_percentage'] ?? 0.0),
                'stake_home_ratio' => $stakeHomeRatio,
                'stake_draw_ratio' => $stakeDrawRatio,
                'stake_away_ratio' => $stakeAwayRatio,
                'created_at' => $capturedAt->format('Y-m-d H:i:s.u'),
                'captured_at' => $capturedAt->format('Y-m-d H:i:s.u'),
            ]
        );
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        $value = trim($value, '-');
        return $value !== '' ? $value : 'unknown';
    }
}

