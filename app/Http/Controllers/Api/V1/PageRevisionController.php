<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeedCmsAdminRequest;
use App\Http\Requests\PageRevisionRollbackRequest;
use App\Http\Resources\PageResource;
use App\Http\Resources\PageRevisionResource;
use App\Models\Page;
use App\Models\PageRevision;
use App\Services\FeedCms\PageRevisionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageRevisionController extends Controller
{
    public function __construct(private readonly PageRevisionService $revisions)
    {
    }

    public function index(FeedCmsAdminRequest $request, Page $page): AnonymousResourceCollection
    {
        return PageRevisionResource::collection(
            $page->revisions()->latest('created_at')->paginate(20),
        );
    }

    public function rollback(PageRevisionRollbackRequest $request, Page $page, PageRevision $pageRevision): PageResource
    {
        return new PageResource($this->revisions->rollback($page, $pageRevision, $request->user()));
    }
}
