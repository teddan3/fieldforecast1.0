<?php

declare(strict_types=1);

namespace App\Services\Odds\Providers;

interface OddsProviderInterface
{
    /**
     * Return provider sports keys (and/or metadata if available).
     * Example output:
     * [
     *   ['key' => 'basketball_nba', 'name' => 'NBA'],
     *   ...
     * ]
     */
    public function listSports(): array;

    /**
     * Fetch upcoming and in-progress events for a sport within a time window.
     *
     * @return array<int, array{
     *   event_key: string,
     *   league: array{name: string, key: string|null},
     *   home_team: string,
     *   away_team: string,
     *   start_time_utc: string // ISO 8601
     * }>
     */
    public function listEventsForSport(string $sportKey, int $fromUnix, int $toUnix): array;

    /**
     * Fetch H2H odds (1X2) for a single event.
     *
     * @return array<int, array{
     *   bookmaker: array{name: string, key: string|null},
     *   outcomes: array<int, array{name: string, price: float}>
     * }>
     */
    public function getH2HOddsForEvent(string $eventKey): array;
}

