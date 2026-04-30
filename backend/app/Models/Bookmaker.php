<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Bookmaker extends Model
{
    protected $table = 'bookmakers';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'provider_key',
        'logo_url',
        'affiliate_url',
        'is_active',
    ];
}

