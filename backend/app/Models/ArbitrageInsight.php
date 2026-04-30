<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ArbitrageInsight extends Model
{
    protected $table = 'arbitrage_insights';

    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'league_id',
        'odds_type',
        'home_bookmaker_id',
        'draw_bookmaker_id',
        'away_bookmaker_id',
        'home_odds',
        'draw_odds',
        'away_odds',
        'implied_probability_sum',
        'profit_percentage',
        'stake_home_ratio',
        'stake_draw_ratio',
        'stake_away_ratio',
        'captured_at',
        'created_at',
    ];

    protected $casts = [
        'home_odds' => 'float',
        'draw_odds' => 'float',
        'away_odds' => 'float',
        'implied_probability_sum' => 'float',
        'profit_percentage' => 'float',
        'stake_home_ratio' => 'float',
        'stake_draw_ratio' => 'float',
        'stake_away_ratio' => 'float',
        'captured_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

