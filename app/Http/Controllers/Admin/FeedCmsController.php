<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageFeedPriority;
use App\Models\Page;
use App\Models\PageRevision;
use App\Services\FeedCms\FeedPriorityService;
use App\Services\FeedCms\PageRevisionService;
use App\Services\FeedCms\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeedCmsController extends Controller
{
    public function index(Request $request, FeedPriorityService $priorities): View
    {
        return view('admin.feed-cms.index', [
            'filters' => $request->only(['status', 'search']),
            'pages' => Page::query()
                ->with('user')
                ->withCount('revisions')
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', "%{$request->input('search')}%"))
                ->latest('updated_at')
                ->paginate(20),
            'priorities' => $priorities->priorities()->keyBy('content_type'),
            'contentTypes' => HomepageFeedPriority::CONTENT_TYPES,
            'statuses' => Page::STATUSES,
            'stats' => [
                'draft' => Page::query()->where('status', Page::STATUS_DRAFT)->count(),
                'published' => Page::query()->where('status', Page::STATUS_PUBLISHED)->count(),
                'archived' => Page::query()->where('status', Page::STATUS_ARCHIVED)->count(),
                'system' => Page::query()->where('is_system_page', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.feed-cms.create', [
            'page' => new Page([
                'status' => Page::STATUS_DRAFT,
                'is_system_page' => false,
                'content_blocks' => [['type' => 'paragraph', 'content' => '']],
                'seo_meta' => [],
            ]),
            'statuses' => Page::STATUSES,
        ]);
    }

    public function store(Request $request, PageService $pages): RedirectResponse
    {
        $page = $pages->create($this->validatedPage($request), $request->user());

        return redirect()
            ->route('admin.dashboard.feed-cms.pages.edit', $page)
            ->with('status', 'CMS page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.feed-cms.edit', [
            'page' => $page->load(['user', 'revisions.changer']),
            'statuses' => Page::STATUSES,
            'revisions' => $page->revisions()->with('changer')->latest('created_at')->get(),
        ]);
    }

    public function update(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        $pages->update($page, $this->validatedPage($request, $page), $request->user());

        return redirect()
            ->route('admin.dashboard.feed-cms.pages.edit', $page)
            ->with('status', 'CMS page updated.');
    }

    public function publish(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        $pages->publish($page, $request->user());

        return redirect()
            ->route('admin.dashboard.feed-cms.pages.edit', $page)
            ->with('status', 'CMS page published.');
    }

    public function archive(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        $pages->archive($page, $request->user());

        return redirect()
            ->route('admin.dashboard.feed-cms.pages.edit', $page)
            ->with('status', 'CMS page archived.');
    }

    public function rollback(Request $request, Page $page, PageRevision $pageRevision, PageRevisionService $revisions): RedirectResponse
    {
        $revisions->rollback($page, $pageRevision, $request->user());

        return redirect()
            ->route('admin.dashboard.feed-cms.pages.edit', $page)
            ->with('status', 'CMS page revision restored.');
    }

    public function updatePriority(Request $request, string $contentType, FeedPriorityService $priorities): RedirectResponse
    {
        $validated = $request->validate([
            'priority_weight' => ['required', 'integer', 'min:-100', 'max:1000'],
            'is_highlighted' => ['nullable', 'boolean'],
            'highlight_color' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $priorities->updatePriority($contentType, [
            'priority_weight' => $validated['priority_weight'],
            'is_highlighted' => $request->boolean('is_highlighted'),
            'highlight_color' => $validated['highlight_color'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ], $request->user());

        return redirect()
            ->route('admin.dashboard.feed-cms.index')
            ->with('status', 'Feed priority updated.');
    }

    private function validatedPage(Request $request, ?Page $page = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'status' => ['required', 'string', Rule::in(Page::STATUSES)],
            'is_system_page' => ['nullable', 'boolean'],
            'content_blocks_json' => ['required', 'string'],
            'seo_meta_json' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        $validated['content_blocks'] = $this->decodeJsonField($validated['content_blocks_json'], 'content_blocks_json');
        $validated['seo_meta'] = filled($validated['seo_meta_json'] ?? null)
            ? $this->decodeJsonField($validated['seo_meta_json'], 'seo_meta_json')
            : null;
        $validated['is_system_page'] = $request->boolean('is_system_page');
        unset($validated['content_blocks_json'], $validated['seo_meta_json']);

        return $validated;
    }

    private function decodeJsonField(string $value, string $field): array
    {
        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([$field => 'The JSON value must be a valid object or array.']);
        }

        return $decoded;
    }
}
