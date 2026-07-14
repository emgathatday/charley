<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryCategoryResource;
use App\Models\LibraryCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LibraryCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LibraryCategoryResource::collection(LibraryCategory::query()->with('children')->orderBy('sort_order')->paginate(50));
    }

    public function store(Request $request): LibraryCategoryResource
    {
        return LibraryCategoryResource::make(LibraryCategory::query()->create($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:library_categories,id'],
            'sort_order' => ['sometimes', 'integer'],
        ])));
    }

    public function show(LibraryCategory $libraryCategory): LibraryCategoryResource
    {
        return LibraryCategoryResource::make($libraryCategory->load('children'));
    }

    public function update(Request $request, LibraryCategory $libraryCategory): LibraryCategoryResource
    {
        $libraryCategory->fill($request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:library_categories,id'],
            'sort_order' => ['sometimes', 'integer'],
        ]))->save();

        return LibraryCategoryResource::make($libraryCategory->refresh());
    }

    public function destroy(LibraryCategory $libraryCategory): Response
    {
        $libraryCategory->delete();

        return response()->noContent();
    }
}
