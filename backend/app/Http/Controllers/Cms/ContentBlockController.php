<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContentBlockController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'blocks' => ContentBlock::query()->with('page:id,title,slug')->orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $block = ContentBlock::query()->create($this->validated($request));

        return response()->json(['block' => $block], 201);
    }

    public function show(ContentBlock $contentBlock): JsonResponse
    {
        return response()->json(['block' => $contentBlock]);
    }

    public function update(Request $request, ContentBlock $contentBlock): JsonResponse
    {
        $contentBlock->update($this->validated($request));

        return response()->json(['block' => $contentBlock->fresh()]);
    }

    public function destroy(ContentBlock $contentBlock): JsonResponse
    {
        $contentBlock->delete();

        return response()->json(['message' => 'Content block deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'page_id' => ['nullable', 'exists:pages,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:hero,rich_text,cta,faq,html'],
            'content' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
        ]);
    }
}
