<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Odds\OddsComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class OddsComparisonController extends Controller
{
    public function __construct(private readonly OddsComparisonService $comparisonService)
    {
    }

    /**
     * Comparison payload for odds table.
     *
     * Query params:
     * - sport: sport slug
     * - league: league slug
     * - from: ISO8601
     * - to: ISO8601
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sport' => ['nullable', 'string', 'max:191'],
            'league' => ['nullable', 'string', 'max:191'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $sportSlug = $validated['sport'] ?? null;
        $leagueSlug = $validated['league'] ?? null;
        $limit = (int) ($validated['limit'] ?? 50);

        $from = isset($validated['from']) ? $request->date($validated['from'])->format('Y-m-d H:i:s') : null;
        $to = isset($validated['to']) ? $request->date($validated['to'])->format('Y-m-d H:i:s') : null;

        // Pull matches with their latest odds per bookmaker.
        // odds_latest is already "latest", so we simply join.
        $rows = DB::table('odds_latest')
            ->join('matches', 'odds_latest.match_id', '=', 'matches.id')
            ->join('leagues', 'matches.league_id', '=', 'leagues.id')
            ->join('sports', 'leagues.sport_id', '=', 'sports.id')
            ->join('teams as home', 'matches.home_team_id', '=', 'home.id')
            ->join('teams as away', 'matches.away_team_id', '=', 'away.id')
            ->join('bookmakers', 'odds_latest.bookmaker_id', '=', 'bookmakers.id')
            ->select([
                'matches.id as match_id',
                'matches.start_time as start_time',
                'sports.slug as sport_slug',
                'sports.name as sport_name',
                'leagues.slug as league_slug',
                'leagues.name as league_name',
                'home.id as home_team_id',
                'home.name as home_team_name',
                'away.id as away_team_id',
                'away.name as away_team_name',
                'bookmakers.id as bookmaker_id',
                'bookmakers.name as bookmaker_name',
                'bookmakers.logo_url as bookmaker_logo_url',
                'bookmakers.affiliate_url as bookmaker_affiliate_url',
                'odds_latest.home_odds as home_odds',
                'odds_latest.draw_odds as draw_odds',
                'odds_latest.away_odds as away_odds',
                'odds_latest.captured_at as captured_at',
            ])
            ->where('odds_latest.odds_type', '=', '1x2')
            ->when($sportSlug !== null, fn ($q) => $q->where('sports.slug', '=', $sportSlug))
            ->when($leagueSlug !== null, fn ($q) => $q->where('leagues.slug', '=', $leagueSlug))
            ->when($from !== null, fn ($q) => $q->where('matches.start_time', '>=', $from))
            ->when($to !== null, fn ($q) => $q->where('matches.start_time', '<=', $to))
            ->orderBy('matches.start_time', 'asc')
            ->limit($limit * 20) // heuristic: each match might have multiple bookmakers
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'matches' => [],
                'meta' => [
                    'count' => 0,
                    'server_time' => now()->toISOString(),
                ],
            ]);
        }

        // Group by match_id
        $grouped = [];
        foreach ($rows as $row) {
            $matchId = (int) $row->match_id;
            if (!isset($grouped[$matchId])) {
                $grouped[$matchId] = [
                    'match_id' => $matchId,
                    'start_time' => $row->start_time,
                    'sport' => [
                        'slug' => $row->sport_slug,
                        'name' => $row->sport_name,
                    ],
                    'league' => [
                        'slug' => $row->league_slug,
                        'name' => $row->league_name,
                    ],
                    'teams' => [
                        'home' => [
                            'id' => (int) $row->home_team_id,
                            'name' => $row->home_team_name,
                        ],
                        'away' => [
                            'id' => (int) $row->away_team_id,
                            'name' => $row->away_team_name,
                        ],
                    ],
                    'bookmakers' => [],
                ];
            }

            $grouped[$matchId]['bookmakers'][(int) $row->bookmaker_id] = [
                'bookmaker_id' => (int) $row->bookmaker_id,
                'bookmaker_name' => $row->bookmaker_name,
                'bookmaker_logo_url' => $row->bookmaker_logo_url,
                'bookmaker_affiliate_url' => $row->bookmaker_affiliate_url,
                'home_odds' => (float) $row->home_odds,
                'draw_odds' => $row->draw_odds !== null ? (float) $row->draw_odds : null,
                'away_odds' => (float) $row->away_odds,
            ];
        }

        $matchesPayload = [];
        foreach ($grouped as $match) {
            $bookmakerMap = $match['bookmakers'];

            // Build calculation rows (no affiliate info needed).
            $calcRows = [];
            foreach ($bookmakerMap as $bookmakerRow) {
                $calcRows[] = [
                    'bookmaker_id' => $bookmakerRow['bookmaker_id'],
                    'home_odds' => $bookmakerRow['home_odds'],
                    'draw_odds' => $bookmakerRow['draw_odds'],
                    'away_odds' => $bookmakerRow['away_odds'],
                ];
            }

            $comparison = $this->comparisonService->compareOneMatch($calcRows);

            // Attach flags back onto bookmaker rows.
            $bookmakerRows = [];
            foreach ($bookmakerMap as $bookmakerId => $bookmakerRow) {
                // Find matching calc result by bookmaker_id.
                $calcRow = null;
                foreach ($comparison['rows'] as $r) {
                    if ((int) $r['bookmaker_id'] === (int) $bookmakerId) {
                        $calcRow = $r;
                        break;
                    }
                }
                if ($calcRow === null) {
                    continue;
                }

                $bookmakerRow['is_best_home'] = $calcRow['is_best_home'];
                $bookmakerRow['is_best_draw'] = $calcRow['is_best_draw'];
                $bookmakerRow['is_best_away'] = $calcRow['is_best_away'];
                $bookmakerRow['value_bets'] = $calcRow['value_bets'];
                $bookmakerRow['value_profit'] = $calcRow['value_profit'];

                $bookmakerRows[] = $bookmakerRow;
            }

            $matchesPayload[] = [
                'match_id' => (int) $match['match_id'],
                'start_time' => $match['start_time'],
                'sport' => $match['sport'],
                'league' => $match['league'],
                'teams' => $match['teams'],
                'best_odds' => $comparison['best_odds'],
                'best_bookmaker_ids' => $comparison['best_bookmaker_ids'],
                'arbitrage' => $comparison['arbitrage'],
                'bookmakers' => $bookmakerRows,
            ];
        }

        return response()->json([
            'matches' => $matchesPayload,
            'meta' => [
                'count' => count($matchesPayload),
                'server_time' => now()->toISOString(),
            ],
        ]);
    }
}

