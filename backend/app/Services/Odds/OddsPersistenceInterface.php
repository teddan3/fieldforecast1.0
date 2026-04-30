<?php

declare(strict_types=1);

namespace App\Services\Odds;

use DateTimeInterface;

/**
 * Abstraction for DB writes during odds ingestion.
 *
 * In a real Laravel project this would be implemented using Eloquent models.
 * Keeping it as an interface makes the ingestion logic testable.
 */
interface OddsPersistenceInterface
{
    public function resolveSport(string $providerSportKey, string $sportName): int;

    public function resolveLeague(int $sportId, ?string $providerLeagueKey, string $leagueName): int;

    public function resolveTeam(string $teamName, ?string $providerTeamKey = null, ?string $country = null): int;

    public function resolveMatch(
        int $leagueId,
        int $homeTeamId,
        int $awayTeamId,
        ?string $externalMatchId,
        DateTimeInterface $startTimeUtc
    ): int;

    /**
     * Ensure our internal bookmaker record exists and return its ID.
     *
     * Affiliate URLs and logos should be managed by admin. Provider key/name are
     * used to map provider->bookmaker.
     */
    public function resolveBookmaker(string $providerBookmakerKey, string $providerBookmakerName): int;

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
    ): void;

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
    ): void;

    public function persistValueBetInsights(
        int $matchId,
        int $leagueId,
        string $oddsType,
        DateTimeInterface $capturedAt,
        array $valueRows
    ): void;

    public function persistArbitrageInsight(
        int $matchId,
        int $leagueId,
        string $oddsType,
        DateTimeInterface $capturedAt,
        array $arbitrage
    ): void;
}

