<?php

declare(strict_types=1);

return [
    // Odds API provider settings
    'odds_api' => [
        'base_url' => env('ODDS_API_BASE_URL', 'https://api.the-odds-api.com/v4'),
        'api_key' => env('ODDS_API_KEY'),

        // Used by Odds ingestion scheduler (FetchOddsCommand reads this)
        // Comma-separated in .env as "ODDS_API_SPORT_KEYS"
        'sport_keys' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('ODDS_API_SPORT_KEYS', '')))
        )),
    ],
];

