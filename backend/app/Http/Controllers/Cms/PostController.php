<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class PostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'posts' => Post::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $post = Post::query()->create($this->validated($request) + [
            'author_id' => $request->user()?->id,
        ]);

        return response()->json(['post' => $post], 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(['post' => $post]);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $post->update($this->validated($request));

        return response()->json(['post' => $post->fresh()]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
        ]);

        $data['published_at'] = $data['status'] === 'published' ? Carbon::now() : null;

        return $data;
    }
}
