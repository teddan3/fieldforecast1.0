<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class PageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'pages' => Page::query()
                ->withCount('contentBlocks')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $page = Page::query()->create($this->validated($request) + [
            'author_id' => $request->user()?->id,
        ]);

        return response()->json(['page' => $page->load('contentBlocks')], 201);
    }

    public function show(Page $page): JsonResponse
    {
        return response()->json(['page' => $page->load('contentBlocks')]);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $page->update($this->validated($request));

        return response()->json(['page' => $page->fresh()->load('contentBlocks')]);
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'template' => ['nullable', 'string', 'max:80'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        $data['template'] = $data['template'] ?? 'default';
        $data['published_at'] = ($data['is_published'] ?? false) ? Carbon::now() : null;

        return $data;
    }
}
