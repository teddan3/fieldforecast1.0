<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Odds\EloquentOddsPersistence;
use App\Services\Odds\OddsPersistenceInterface;
use App\Services\Odds\Providers\OddsProviderInterface;
use App\Services\Odds\Providers\TheOddsApiProvider;
use Illuminate\Support\ServiceProvider;

final class OddsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OddsPersistenceInterface::class, EloquentOddsPersistence::class);
        $this->app->bind(OddsProviderInterface::class, function () {
            return new TheOddsApiProvider();
        });
    }
}

