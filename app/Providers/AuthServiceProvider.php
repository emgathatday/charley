<?php

namespace App\Providers;

use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Policies\HandbookArticlePolicy;
use App\Policies\HandbookCategoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        HandbookArticle::class => HandbookArticlePolicy::class,
        HandbookCategory::class => HandbookCategoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
