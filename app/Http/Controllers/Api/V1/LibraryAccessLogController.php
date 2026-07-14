<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryAccessLogResource;
use App\Models\LibraryAccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LibraryAccessLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return LibraryAccessLogResource::collection(
            LibraryAccessLog::query()
                ->when($request->integer('library_item_id'), fn ($query, int $id) => $query->where('library_item_id', $id))
                ->when($request->integer('user_id'), fn ($query, int $id) => $query->where('user_id', $id))
                ->when($request->string('action')->toString(), fn ($query, string $action) => $query->where('action', $action))
                ->latest('created_at')
                ->paginate((int) $request->integer('per_page', 25))
        );
    }
}
