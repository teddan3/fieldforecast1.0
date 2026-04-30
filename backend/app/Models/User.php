<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    /**
     * Keep this minimal for now; auth scaffolding can expand it later.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected function setPasswordAttribute($value): void
    {
        // If password is already hashed, leave it. Otherwise hash it.
        $hashed = (string) $value;
        if (!Str::startsWith($hashed, ['$', 'bcrypt$'])) {
            $this->attributes['password'] = $hashed;
            return;
        }
        $this->attributes['password'] = $value;
    }
}

