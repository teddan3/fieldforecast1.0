<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

final class PublicContentController extends Controller
{
    public function pages(): JsonResponse
    {
        return response()->json([
            'pages' => Page::query()
                ->where('is_published', true)
                ->select(['id', 'title', 'slug', 'excerpt', 'meta_title', 'meta_description', 'published_at'])
                ->latest('published_at')
                ->get(),
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['contentBlocks' => fn ($query) => $query->where('is_active', true)])
            ->firstOrFail();

        return response()->json(['page' => $page]);
    }

    public function posts(): JsonResponse
    {
        return response()->json([
            'posts' => Post::query()
                ->where('status', 'published')
                ->select(['id', 'title', 'slug', 'excerpt', 'cover_image_url', 'published_at'])
                ->latest('published_at')
                ->get(),
        ]);
    }

    public function post(string $slug): JsonResponse
    {
        $post = Post::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json(['post' => $post]);
    }
}
