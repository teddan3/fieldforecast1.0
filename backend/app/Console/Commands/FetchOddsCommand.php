<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Odds\OddsIngestionService;
use Illuminate\Console\Command;

final class FetchOddsCommand extends Command
{
    protected $signature = 'odds:fetch {--sportKey=}';
    protected $description = 'Fetch and refresh odds from configured providers';

    public function __construct(private readonly OddsIngestionService $ingestionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Pull events slightly ahead and slightly behind, so late updates still get captured.
        $now = time();
        $from = $now - 15 * 60;  // 15 minutes ago
        $to = $now + 2 * 60 * 60; // next 2 hours

        $sportKey = (string) $this->option('sportKey');

        // In production, resolve sport keys from DB/config.
        $sportKeys = $sportKey !== '' ? [$sportKey] : (array) config('services.odds_api.sport_keys', []);
        if (count($sportKeys) === 0) {
            $this->warn('No sport_keys configured for odds fetching.');
            return self::SUCCESS;
        }

        $total = 0;
        foreach ($sportKeys as $key) {
            $total += $this->ingestionService->ingestSport((string) $key, (int) $from, (int) $to);
        }

        $this->info("Processed {$total} events.");
        return self::SUCCESS;
    }
}

