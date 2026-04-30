<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OddsLatest extends Model
{
    protected $table = 'odds_latest';

    public $timestamps = false;

    protected $fillable = [
        'match_id',
        'league_id',
        'home_team_id',
        'away_team_id',
        'bookmaker_id',
        'odds_type',
        'home_odds',
        'draw_odds',
        'away_odds',
        'captured_at',
        'updated_at',
        'source_provider',
    ];

    protected $casts = [
        'home_odds' => 'float',
        'draw_odds' => 'float',
        'away_odds' => 'float',
        'captured_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

