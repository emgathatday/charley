<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\Taxonomy\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxonomyController extends Controller
{
    public function index(Request $request, TaxonomyService $taxonomy): View
    {
        return view('admin.taxonomy.index', [
            'categories' => Tag::CATEGORIES,
            'filters' => $request->only(['category', 'search']),
            'tags' => $taxonomy->list($request->only(['category', 'search']), 20),
            'selectorTags' => Tag::query()->orderBy('name')->limit(30)->get(),
            'stats' => [
                'total' => Tag::query()->count(),
                'technical' => Tag::query()->category(Tag::CATEGORY_TECHNICAL)->count(),
                'equipment' => Tag::query()->category(Tag::CATEGORY_EQUIPMENT)->count(),
                'process' => Tag::query()->category(Tag::CATEGORY_PROCESS)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.taxonomy.create', [
            'categories' => Tag::CATEGORIES,
        ]);
    }

    public function store(Request $request, TaxonomyService $taxonomy): RedirectResponse
    {
        $tag = $taxonomy->create($this->validatedTag($request));

        return redirect()
            ->route('admin.dashboard.taxonomy.edit', $tag)
            ->with('status', 'Tag created.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.taxonomy.edit', [
            'categories' => Tag::CATEGORIES,
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag, TaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->update($tag, $this->validatedTag($request, $tag));

        return redirect()
            ->route('admin.dashboard.taxonomy.edit', $tag)
            ->with('status', 'Tag updated.');
    }

    public function destroy(Tag $tag, TaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->delete($tag);

        return redirect()
            ->route('admin.dashboard.taxonomy.index')
            ->with('status', 'Tag deleted.');
    }

    private function validatedTag(Request $request, ?Tag $tag = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tag?->id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tag?->id)],
            'category' => ['nullable', 'string', Rule::in(Tag::CATEGORIES)],
        ]);
    }
}
