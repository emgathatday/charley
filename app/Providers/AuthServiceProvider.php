<?php

namespace App\Providers;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\KnowledgeDomain;
use App\Models\LibraryItem;
use App\Policies\HandbookArticlePolicy;
use App\Policies\HandbookCategoryPolicy;
use App\Policies\KnowledgeDomainPolicy;
use App\Policies\LibraryItemPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        HandbookArticle::class => HandbookArticlePolicy::class,
        HandbookCategory::class => HandbookCategoryPolicy::class,
        KnowledgeDomain::class => KnowledgeDomainPolicy::class,
        LibraryItem::class => LibraryItemPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
