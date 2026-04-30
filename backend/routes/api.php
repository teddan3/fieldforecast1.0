<?php

declare(strict_types=1);

use App\Http\Controllers\OddsComparisonController;
use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\ContentBlockController;
use App\Http\Controllers\Cms\PageController;
use App\Http\Controllers\Cms\PostController;
use App\Http\Controllers\Cms\PublicContentController;
use App\Http\Controllers\Cms\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/odds/comparison', [OddsComparisonController::class, 'index']);

Route::prefix('content')->group(function (): void {
    Route::get('/pages', [PublicContentController::class, 'pages']);
    Route::get('/pages/{slug}', [PublicContentController::class, 'page']);
    Route::get('/posts', [PublicContentController::class, 'posts']);
    Route::get('/posts/{slug}', [PublicContentController::class, 'post']);
});

Route::prefix('cms')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('cms.auth')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('pages', PageController::class);
        Route::apiResource('posts', PostController::class);
        Route::apiResource('content-blocks', ContentBlockController::class)
            ->parameters(['content-blocks' => 'contentBlock']);
        Route::apiResource('users', UserController::class);
    });
});

