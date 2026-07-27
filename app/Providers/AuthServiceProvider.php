<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\HandbookArticle;
use App\Models\HandbookCategory;
use App\Models\KnowledgeDomain;
use App\Models\LibraryItem;
use App\Models\PartnerMember;
use App\Models\PartnerPresentation;
use App\Models\PartnerProduct;
use App\Models\PartnerProfile;
use App\Models\Question;
use App\Models\UserReputation;
use App\Models\WeeklyTheme;
use App\Policies\AnswerPolicy;
use App\Policies\HandbookArticlePolicy;
use App\Policies\HandbookCategoryPolicy;
use App\Policies\KnowledgeDomainPolicy;
use App\Policies\LibraryItemPolicy;
use App\Policies\PartnerMemberPolicy;
use App\Policies\PartnerPresentationPolicy;
use App\Policies\PartnerProductPolicy;
use App\Policies\PartnerProfilePolicy;
use App\Policies\QuestionPolicy;
use App\Policies\ReputationPolicy;
use App\Policies\WeeklyThemePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Answer::class => AnswerPolicy::class,
        HandbookArticle::class => HandbookArticlePolicy::class,
        HandbookCategory::class => HandbookCategoryPolicy::class,
        KnowledgeDomain::class => KnowledgeDomainPolicy::class,
        LibraryItem::class => LibraryItemPolicy::class,
        PartnerMember::class => PartnerMemberPolicy::class,
        PartnerPresentation::class => PartnerPresentationPolicy::class,
        PartnerProduct::class => PartnerProductPolicy::class,
        PartnerProfile::class => PartnerProfilePolicy::class,
        Question::class => QuestionPolicy::class,
        UserReputation::class => ReputationPolicy::class,
        WeeklyTheme::class => WeeklyThemePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
