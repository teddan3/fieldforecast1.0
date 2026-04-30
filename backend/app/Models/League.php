<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class League extends Model
{
    protected $table = 'leagues';

    public $timestamps = true;

    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'country',
        'provider_league_key',
    ];
}

