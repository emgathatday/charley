<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\UserFeedCache;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.index', [
            'filters' => $request->only(['search']),
            'pages' => Page::query()
                ->published()
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', "%{$request->input('search')}%"))
                ->orderByDesc('published_at')
                ->paginate(12),
        ]);
    }

    public function show(string $slug): View
    {
        $page = Page::query()->published()->slug($slug)->firstOrFail();
        $page->increment('view_count');

        return view('pages.show', ['page' => $page->refresh()]);
    }

    public function feed(Request $request): View
    {
        $search = $request->input('search');
        $user = $request->user();

        $personalizedItems = $user
            ? UserFeedCache::query()
                ->with('feedable')
                ->whereBelongsTo($user)
                ->fresh()
                ->when($search, fn ($query) => $query->whereHasMorph('feedable', [Page::class], fn ($feedableQuery) => $feedableQuery->where('title', 'like', "%{$search}%")))
                ->orderByDesc('priority_score')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
            : collect();

        return view('feed.index', [
            'filters' => $request->only(['search']),
            'personalizedItems' => $personalizedItems,
            'publishedPages' => Page::query()
                ->published()
                ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->orderByDesc('published_at')
                ->limit(20)
                ->get(),
        ]);
    }
}
