<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MatchModel extends Model
{
    protected $table = 'matches';

    public $timestamps = true;

    protected $fillable = [
        'league_id',
        'home_team_id',
        'away_team_id',
        'start_time',
        'status',
        'external_match_id',
        'source_provider',
    ];
}

