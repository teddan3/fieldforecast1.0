<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookmakers')) {
            Schema::create('bookmakers', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('provider_key')->nullable()->unique();
                $table->string('logo_url', 500)->nullable();
                $table->string('affiliate_url', 1000)->default('');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('api_credentials')) {
            Schema::create('api_credentials', function (Blueprint $table): void {
                $table->id();
                $table->string('provider_name')->unique();
                $table->string('api_key');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sports')) {
            Schema::create('sports', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('provider_sport_key')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leagues')) {
            Schema::create('leagues', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('sport_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('country')->nullable();
                $table->string('provider_league_key')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('country')->nullable();
                $table->string('provider_team_key')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('matches')) {
            Schema::create('matches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('league_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('home_team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('away_team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
                $table->dateTime('start_time');
                $table->enum('status', ['scheduled', 'in_progress', 'finished'])->default('scheduled');
                $table->string('external_match_id')->nullable();
                $table->string('source_provider')->default('the_odds_api');
                $table->timestamps();
                $table->unique(['source_provider', 'external_match_id'], 'matches_provider_external_unique');
                $table->index(['league_id', 'start_time']);
                $table->index(['home_team_id', 'away_team_id']);
            });
        }

        if (! Schema::hasTable('bookmaker_clicks')) {
            Schema::create('bookmaker_clicks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('bookmaker_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('match_id')->nullable()->index();
                $table->enum('outcome', ['home', 'draw', 'away'])->nullable();
                $table->timestamp('created_at', 6)->useCurrent();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
            });
        }

        if (! Schema::hasTable('odds_latest')) {
            Schema::create('odds_latest', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('league_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('home_team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('away_team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('bookmaker_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->string('odds_type', 50)->default('1x2');
                $table->decimal('home_odds', 10, 4);
                $table->decimal('draw_odds', 10, 4)->nullable();
                $table->decimal('away_odds', 10, 4);
                $table->timestamp('captured_at', 6)->useCurrent();
                $table->timestamp('updated_at', 6)->useCurrent();
                $table->string('source_provider')->default('the_odds_api');
                $table->unique(['match_id', 'bookmaker_id', 'odds_type'], 'odds_latest_match_bookmaker_odds_type_unique');
                $table->index(['league_id', 'captured_at']);
                $table->index(['match_id', 'captured_at']);
                $table->index(['bookmaker_id', 'captured_at']);
            });
        }

        if (! Schema::hasTable('odds_snapshots')) {
            Schema::create('odds_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('league_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('home_team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('away_team_id')->constrained('teams')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('bookmaker_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->string('odds_type', 50)->default('1x2');
                $table->decimal('home_odds', 10, 4);
                $table->decimal('draw_odds', 10, 4)->nullable();
                $table->decimal('away_odds', 10, 4);
                $table->timestamp('captured_at', 6)->useCurrent();
                $table->string('source_provider')->default('the_odds_api');
                $table->index(['match_id', 'captured_at']);
                $table->index(['bookmaker_id', 'match_id', 'captured_at']);
                $table->index(['league_id', 'captured_at']);
            });
        }

        if (! Schema::hasTable('value_bet_insights')) {
            Schema::create('value_bet_insights', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('league_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('bookmaker_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->string('odds_type', 50)->default('1x2');
                $table->enum('outcome', ['home', 'draw', 'away']);
                $table->decimal('bookmaker_odds', 10, 4);
                $table->decimal('market_average_odds', 10, 4);
                $table->decimal('implied_probability', 12, 10);
                $table->decimal('market_implied_probability', 12, 10);
                $table->decimal('expected_value_profit', 12, 6);
                $table->timestamp('captured_at', 6);
                $table->timestamp('created_at', 6)->useCurrent();
                $table->unique(['match_id', 'bookmaker_id', 'outcome', 'captured_at'], 'value_bet_insights_match_bookmaker_outcome_captured_unique');
                $table->index(['match_id', 'captured_at']);
                $table->index(['bookmaker_id', 'captured_at']);
            });
        }

        if (! Schema::hasTable('arbitrage_insights')) {
            Schema::create('arbitrage_insights', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('league_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->string('odds_type', 50)->default('1x2');
                $table->foreignId('home_bookmaker_id')->constrained('bookmakers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('draw_bookmaker_id')->constrained('bookmakers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('away_bookmaker_id')->constrained('bookmakers')->restrictOnDelete()->cascadeOnUpdate();
                $table->decimal('home_odds', 10, 4);
                $table->decimal('draw_odds', 10, 4)->nullable();
                $table->decimal('away_odds', 10, 4);
                $table->decimal('implied_probability_sum', 12, 10);
                $table->decimal('profit_percentage', 12, 6);
                $table->decimal('stake_home_ratio', 12, 10);
                $table->decimal('stake_draw_ratio', 12, 10)->nullable();
                $table->decimal('stake_away_ratio', 12, 10);
                $table->timestamp('captured_at', 6);
                $table->timestamp('created_at', 6)->useCurrent();
                $table->unique(['match_id', 'captured_at']);
                $table->index(['match_id', 'captured_at']);
            });
        }

        if (! Schema::hasTable('user_favorites')) {
            Schema::create('user_favorites', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
                $table->enum('favorite_type', ['team', 'league', 'sport']);
                $table->unsignedBigInteger('favorite_id');
                $table->timestamps();
                $table->unique(['user_id', 'favorite_type', 'favorite_id']);
            });
        }

        if (! Schema::hasTable('user_bookmaker_preferences')) {
            Schema::create('user_bookmaker_preferences', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('bookmaker_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
                $table->integer('priority')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'bookmaker_id']);
            });
        }

        if (! Schema::hasTable('featured_matches')) {
            Schema::create('featured_matches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('match_id')->unique()->constrained('matches')->cascadeOnDelete()->cascadeOnUpdate();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->mediumText('content');
                $table->dateTime('published_at')->nullable()->index();
                $table->boolean('is_published')->default(false);
                $table->string('cover_image_url', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('featured_matches');
        Schema::dropIfExists('user_bookmaker_preferences');
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('arbitrage_insights');
        Schema::dropIfExists('value_bet_insights');
        Schema::dropIfExists('odds_snapshots');
        Schema::dropIfExists('odds_latest');
        Schema::dropIfExists('bookmaker_clicks');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('leagues');
        Schema::dropIfExists('sports');
        Schema::dropIfExists('api_credentials');
        Schema::dropIfExists('bookmakers');
    }
};
