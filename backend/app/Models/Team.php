<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Team extends Model
{
    protected $table = 'teams';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'country',
        'provider_team_key',
    ];
}

