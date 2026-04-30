<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Sport extends Model
{
    protected $table = 'sports';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'slug',
        'provider_sport_key',
    ];
}

