<?php

declare(strict_types=1);

use App\Http\Controllers\OddsComparisonController;
use Illuminate\Support\Facades\Route;

Route::get('/odds/comparison', [OddsComparisonController::class, 'index']);

