<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

final class Kernel extends ConsoleKernel
{
    /**
     * @param Schedule $schedule
     */
    protected function schedule(Schedule $schedule): void
    {
        // Refresh odds frequently to keep comparison UI "live".
        // Adjust to every 30-60 seconds depending on rate limits.
        $schedule->command('odds:fetch')
            ->everyMinute();
    }

    /**
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}

