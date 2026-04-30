<?php

declare(strict_types=1);

namespace App\Services\Odds\Providers;

use Illuminate\Support\Facades\Http;

/**
 * The Odds API adapter.
 *
 * Config expected via Laravel config/env:
 * - ODDS_API_BASE_URL (default https://api.the-odds-api.com/v4)
 * - ODDS_API_KEY
 *
 * Markets:
 * - h2h for 1X2
 * Odds format:
 * - decimal
 *
 * NOTE: This is a scaffold. You will need to ensure the mapping between
 * TheOddsAPI response schema and our normalized structure matches your chosen
 provider parameters (regions/markets/oddsFormat).
 */
final class TheOddsApiProvider implements OddsProviderInterface
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('services.odds_api.base_url', 'https://api.the-odds-api.com/v4');
        $this->apiKey = $apiKey ?? (string) config('services.odds_api.api_key');
    }

    public function listSports(): array
    {
        // The Odds API "sports" endpoint exists, but response shape differs by provider version.
        // We'll keep this scaffold minimal.
        $res = Http::timeout(15)
            ->baseUrl($this->baseUrl)
            ->withHeaders(['x-api-key' => $this->apiKey])
            ->get('/sports');

        if (!$res->ok()) {
            return [];
        }

        $data = $res->json();
        $out = [];
        foreach (($data ?? []) as $sport) {
            if (!isset($sport['key'])) {
                continue;
            }
            $out[] = [
                'key' => (string) $sport['key'],
                'name' => isset($sport['title']) ? (string) $sport['title'] : (string) $sport['key'],
            ];
        }

        return $out;
    }

    public function listEventsForSport(string $sportKey, int $fromUnix, int $toUnix): array
    {
        // TheOddsAPI supports /sports/{sport}/events? (depends on their API version)
        // Typical params:
        // - from, to (unix timestamps)
        // - oddsFormat=decimal, regions=us, market=h2h
        $res = Http::timeout(20)
            ->baseUrl($this->baseUrl)
            ->withHeaders(['x-api-key' => $this->apiKey])
            ->get("/sports/{$sportKey}/events", [
                'from' => $fromUnix,
                'to' => $toUnix,
            ]);

        if (!$res->ok()) {
            return [];
        }

        $data = $res->json();
        $events = [];
        foreach (($data ?? []) as $event) {
            $events[] = [
                'event_key' => (string) ($event['id'] ?? $event['event_id'] ?? ''),
                'sport_title' => (string) ($event['sport_title'] ?? $event['sport_name'] ?? $sportKey),
                'league' => [
                    'name' => (string) ($event['league_title'] ?? $event['league_name'] ?? 'Unknown League'),
                    'key' => isset($event['league_key']) ? (string) $event['league_key'] : null,
                ],
                'home_team' => isset($event['home_team']) ? (string) $event['home_team'] : null,
                'away_team' => isset($event['away_team']) ? (string) $event['away_team'] : null,
                'start_time_utc' => (string) ($event['commence_time'] ?? $event['start_time'] ?? ''),
            ];
        }

        return $events;
    }

    public function getH2HOddsForEvent(string $eventKey): array
    {
        // TheOddsAPI typical endpoint:
        // /events/{eventId}/odds?markets=h2h&oddsFormat=decimal
        $res = Http::timeout(20)
            ->baseUrl($this->baseUrl)
            ->withHeaders(['x-api-key' => $this->apiKey])
            ->get("/events/{$eventKey}/odds", [
                'markets' => 'h2h',
                'oddsFormat' => 'decimal',
            ]);

        if (!$res->ok()) {
            return [];
        }

        // Provider payload differs; normalize to:
        // [
        //   ['bookmaker' => ['name' => 'Book', 'key' => 'pinnacle'], 'outcomes' => [['name'=>'TeamA','price'=>2.1], ...]],
        // ]
        $data = $res->json();
        $out = [];
        foreach (($data ?? []) as $row) {
            $book = [
                'name' => (string) ($row['name'] ?? $row['bookmaker'] ?? $row['provider'] ?? 'unknown'),
                'key' => isset($row['key'])
                    ? (string) $row['key']
                    : (isset($row['bookmaker_key']) ? (string) $row['bookmaker_key'] : null),
            ];

            $outcomes = [];

            // TheOddsAPI typically returns a `markets` array on each bookmaker row.
            // We extract the `h2h` market and flatten its outcomes to:
            //   ['name' => ..., 'price' => ...]
            $markets = $row['markets'] ?? null;
            if (is_array($markets)) {
                foreach ($markets as $market) {
                    $marketKey = isset($market['key']) ? (string) $market['key'] : null;
                    if ($marketKey !== 'h2h') {
                        continue;
                    }

                    $outs = $market['outcomes'] ?? [];
                    if (!is_array($outs)) {
                        continue;
                    }

                    foreach ($outs as $o) {
                        if (!isset($o['name'], $o['price'])) {
                            continue;
                        }
                        $outcomes[] = [
                            'name' => (string) $o['name'],
                            'price' => (float) $o['price'],
                        ];
                    }
                }
            }

            // Fallback: some provider variants might return outcomes directly.
            if (count($outcomes) === 0 && is_array($row['outcomes'] ?? null)) {
                foreach ($row['outcomes'] as $o) {
                    if (!isset($o['name'], $o['price'])) {
                        continue;
                    }
                    $outcomes[] = [
                        'name' => (string) $o['name'],
                        'price' => (float) $o['price'],
                    ];
                }
            }

            $out[] = [
                'bookmaker' => $book,
                'outcomes' => $outcomes,
            ];
        }

        return $out;
    }
}

