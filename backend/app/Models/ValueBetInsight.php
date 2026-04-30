<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ValueBetInsight extends Model
{
    protected $table = 'value_bet_insights';

    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'league_id',
        'bookmaker_id',
        'odds_type',
        'outcome',
        'bookmaker_odds',
        'market_average_odds',
        'implied_probability',
        'market_implied_probability',
        'expected_value_profit',
        'captured_at',
        'created_at',
    ];

    protected $casts = [
        'bookmaker_odds' => 'float',
        'market_average_odds' => 'float',
        'implied_probability' => 'float',
        'market_implied_probability' => 'float',
        'expected_value_profit' => 'float',
        'captured_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

