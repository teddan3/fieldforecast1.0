<?php

declare(strict_types=1);

namespace App\Services\Odds;

use App\Services\Odds\Providers\OddsProviderInterface;
use DateTimeImmutable;

/**
 * Ingests odds from provider(s) on a schedule.
 *
 * Pipeline:
 * 1) Fetch events for a sport window
 * 2) For each event, fetch H2H odds by bookmaker
 * 3) Normalize provider odds into {home,draw,away}
 * 4) Persist odds_latest + odds_snapshots
 * 5) Compute insights (value bet + arbitrage) and persist them
 */
final class OddsIngestionService
{
    public function __construct(
        private readonly OddsProviderInterface $provider,
        private readonly OddsNormalizer $normalizer,
        private readonly OddsComparisonService $comparisonService,
        private readonly OddsPersistenceInterface $persistence
    ) {}

    /**
     * @return int number of events processed
     */
    public function ingestSport(string $sportKey, int $fromUnix, int $toUnix): int
    {
        $events = $this->provider->listEventsForSport($sportKey, $fromUnix, $toUnix);
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $processed = 0;
        foreach ($events as $event) {
            $eventKey = (string) ($event['event_key'] ?? '');
            if ($eventKey === '') {
                continue;
            }

            $leagueName = (string) ($event['league']['name'] ?? 'Unknown League');
            $providerLeagueKey = $event['league']['key'] ?? null;

            $homeTeamName = $event['home_team'] ?? null;
            $awayTeamName = $event['away_team'] ?? null;
            if ($homeTeamName === null || $awayTeamName === null) {
                continue;
            }

            $startTimeRaw = (string) ($event['start_time_utc'] ?? '');
            $startTimeUtc = $this->parseUtcOrNow($startTimeRaw, $now);

            $sportName = (string) ($event['sport_title'] ?? $sportKey);

            // Resolve domain entities
            $sportId = $this->persistence->resolveSport($sportKey, $sportName);
            $leagueId = $this->persistence->resolveLeague($sportId, $providerLeagueKey, $leagueName);

            $homeTeamId = $this->persistence->resolveTeam((string) $homeTeamName);
            $awayTeamId = $this->persistence->resolveTeam((string) $awayTeamName);

            $matchId = $this->persistence->resolveMatch(
                $leagueId,
                $homeTeamId,
                $awayTeamId,
                $eventKey,
                $startTimeUtc
            );

            $bookmakerOddsRows = [];
            $oddsByBookmaker = $this->provider->getH2HOddsForEvent($eventKey);

            foreach ($oddsByBookmaker as $bookmakerRow) {
                $providerBookmakerKey = isset($bookmakerRow['bookmaker']['key'])
                    ? (string) $bookmakerRow['bookmaker']['key']
                    : '';
                $providerBookmakerName = isset($bookmakerRow['bookmaker']['name'])
                    ? (string) $bookmakerRow['bookmaker']['name']
                    : 'Unknown';

                $bookmakerId = $this->persistence->resolveBookmaker($providerBookmakerKey, $providerBookmakerName);
                $normalized = $this->normalizer->normalizeH2HMarket(
                    $bookmakerRow['outcomes'] ?? [],
                    (string) $homeTeamName,
                    (string) $awayTeamName
                );

                $homeOdds = $normalized['home_odds'];
                $drawOdds = $normalized['draw_odds'];
                $awayOdds = $normalized['away_odds'];

                if ($homeOdds === null || $awayOdds === null) {
                    continue;
                }

                $oddsType = '1x2';
                $this->persistence->upsertOddsLatest(
                    $matchId,
                    $leagueId,
                    $homeTeamId,
                    $awayTeamId,
                    $bookmakerId,
                    $oddsType,
                    $homeOdds,
                    $drawOdds,
                    $awayOdds,
                    $now
                );

                $this->persistence->insertOddsSnapshot(
                    $matchId,
                    $leagueId,
                    $homeTeamId,
                    $awayTeamId,
                    $bookmakerId,
                    $oddsType,
                    $homeOdds,
                    $drawOdds,
                    $awayOdds,
                    $now
                );

                $bookmakerOddsRows[] = [
                    'bookmaker_id' => $bookmakerId,
                    'home_odds' => $homeOdds,
                    'draw_odds' => $drawOdds,
                    'away_odds' => $awayOdds,
                ];
            }

            if (count($bookmakerOddsRows) > 0) {
                $comparison = $this->comparisonService->compareOneMatch($bookmakerOddsRows);

                // Persist value-bet insights from comparison rows
                $valueRows = [];
                foreach ($comparison['rows'] as $row) {
                    foreach (['home', 'draw', 'away'] as $outcome) {
                        $oddsKey = $outcome . '_odds';
                        $oddsValue = $row[$oddsKey] ?? null;
                        $expectedValueProfit = $row['value_profit'][$outcome] ?? 0.0;
                        $isValue = (bool) ($row['value_bets'][$outcome] ?? false);
                        $details = $row['value_details'][$outcome] ?? null;

                        if ($isValue !== true || $oddsValue === null) {
                            continue;
                        }

                        $valueRows[] = [
                            'bookmaker_id' => $row['bookmaker_id'],
                            'outcome' => $outcome,
                            'bookmaker_odds' => (float) $oddsValue,
                            'implied_probability' => $details ? (float) ($details['implied_probability'] ?? 0.0) : 0.0,
                            'market_implied_probability' => $details ? (float) ($details['market_implied_probability'] ?? 0.0) : 0.0,
                            'market_average_odds' => $details ? (float) ($details['market_average_odds'] ?? 0.0) : 0.0,
                            'expected_value_profit' => $details ? (float) ($details['expected_value_profit'] ?? $expectedValueProfit) : (float) $expectedValueProfit,
                        ];
                    }
                }

                $this->persistence->persistValueBetInsights(
                    $matchId,
                    $leagueId,
                    '1x2',
                    $now,
                    $valueRows
                );

                $this->persistence->persistArbitrageInsight(
                    $matchId,
                    $leagueId,
                    '1x2',
                    $now,
                    $comparison['arbitrage']
                );
            }

            $processed++;
        }

        return $processed;
    }

    private function parseUtcOrNow(string $raw, DateTimeImmutable $fallback): DateTimeImmutable
    {
        try {
            if ($raw === '') {
                return $fallback;
            }
            return new DateTimeImmutable($raw, new \DateTimeZone('UTC'));
        } catch (\Throwable) {
            return $fallback;
        }
    }
}

