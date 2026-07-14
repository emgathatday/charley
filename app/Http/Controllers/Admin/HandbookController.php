<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\LibraryItem;
use App\Models\PlantType;
use App\Services\HandbookService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HandbookController extends Controller
{
    public function __construct(private readonly HandbookService $handbook)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'category_id', 'plant_type_id', 'ai_trainable']);

        return view('admin.handbook.index', [
            'stats' => $this->stats(),
            'filters' => $filters,
            'categories' => HandbookCategory::query()
                ->with(['children', 'plantType', 'layoutImage'])
                ->withCount('articles')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
            'categoryTree' => $this->handbook->categoryTree(null, false),
            'hotspots' => $this->handbook->plantLayoutHotspots(null, false),
            'articles' => $this->handbook->searchArticles($filters, 15),
            'plantTypes' => PlantType::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.handbook.create', [
            'categories' => HandbookCategory::query()->orderBy('sort_order')->orderBy('title')->get(),
            'plantTypes' => PlantType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'libraryItems' => $this->libraryItems(),
            'metadataTypes' => ['iow', 'kpi', 'troubleshooting', 'equipment_spec', 'catalyst_info'],
            'relationTypes' => ['calculation_tool', 'library_item', 'partner_presentation', 'ai_shortcut'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedArticle($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $article = HandbookArticle::query()->create($data);

        return redirect()->route('admin.dashboard.handbook.show', $article)
            ->with('success', 'Handbook article created.');
    }

    public function show(HandbookArticle $handbookArticle): View
    {
        return view('admin.handbook.show', [
            'article' => $handbookArticle->load(['category.plantType', 'metadata', 'relatedItems.relatable']),
            'metadataGroups' => $this->handbook->metadataGrouped($handbookArticle),
            'relatedItems' => $handbookArticle->relatedItems()->with('relatable')->orderBy('sort_order')->get(),
            'hotspots' => $this->handbook->plantLayoutHotspots($handbookArticle->category?->plant_type_id, false),
        ]);
    }

    public function edit(HandbookArticle $handbookArticle): View
    {
        return view('admin.handbook.edit', [
            'article' => $handbookArticle->load(['category', 'metadata', 'relatedItems.relatable']),
            'categories' => HandbookCategory::query()->orderBy('sort_order')->orderBy('title')->get(),
            'plantTypes' => PlantType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'libraryItems' => $this->libraryItems(),
            'metadataTypes' => ['iow', 'kpi', 'troubleshooting', 'equipment_spec', 'catalyst_info'],
            'relationTypes' => ['calculation_tool', 'library_item', 'partner_presentation', 'ai_shortcut'],
        ]);
    }

    public function update(Request $request, HandbookArticle $handbookArticle): RedirectResponse
    {
        $data = $this->validatedArticle($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);

        $handbookArticle->update($data);

        return redirect()->route('admin.dashboard.handbook.show', $handbookArticle)
            ->with('success', 'Handbook article updated.');
    }

    public function publish(HandbookArticle $handbookArticle): RedirectResponse
    {
        $this->handbook->publishArticle($handbookArticle, auth()->id());

        return redirect()->route('admin.dashboard.handbook.show', $handbookArticle)
            ->with('success', 'Handbook article published.');
    }

    public function archive(HandbookArticle $handbookArticle): RedirectResponse
    {
        $handbookArticle->update(['status' => 'archived']);

        return redirect()->route('admin.dashboard.handbook.show', $handbookArticle)
            ->with('success', 'Handbook article archived.');
    }

    private function stats(): array
    {
        return [
            'categories' => HandbookCategory::query()->count(),
            'articles' => HandbookArticle::query()->count(),
            'published' => HandbookArticle::query()->where('status', 'published')->count(),
            'drafts' => HandbookArticle::query()->where('status', 'draft')->count(),
            'hotspots' => HandbookCategory::query()->whereNotNull('map_coordinates')->count(),
        ];
    }

    private function libraryItems()
    {
        return LibraryItem::query()
            ->where('status', 'published')
            ->orderBy('title')
            ->limit(50)
            ->get();
    }

    private function validatedArticle(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:handbook_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'optimization_guidance' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'is_ai_trainable' => ['sometimes', 'boolean'],
            'process_description' => ['nullable', 'string'],
        ]);
    }
}
