<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('editor')->after('password');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            if (! Schema::hasColumn('users', 'cms_token')) {
                $table->string('cms_token', 80)->nullable()->unique()->after('is_active');
            }
        });

        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->mediumText('content')->nullable();
                $table->string('template')->default('default');
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_blocks')) {
            Schema::create('content_blocks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('page_id')->nullable()->constrained('pages')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('name');
                $table->string('type')->default('rich_text');
                $table->mediumText('content')->nullable();
                $table->json('settings')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('posts', 'status')) {
                $table->string('status')->default('draft')->after('content');
            }

            if (! Schema::hasColumn('posts', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('cover_image_url');
            }

            if (! Schema::hasColumn('posts', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }

            if (! Schema::hasColumn('posts', 'author_id')) {
                $table->foreignId('author_id')->nullable()->after('meta_description')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('pages');
    }
};
