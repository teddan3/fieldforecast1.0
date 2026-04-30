<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
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
        'role',
        'is_active',
        'cms_token',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'cms_token',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected function setPasswordAttribute($value): void
    {
        $hashed = (string) $value;
        if ($hashed === '') {
            return;
        }

        $this->attributes['password'] = Str::startsWith($hashed, ['$2y$', '$argon2'])
            ? $hashed
            : Hash::make($hashed);
    }
}

