@extends('layouts.app')

@section('title', 'Dashboard View Inventory')

@php
    $rebuildPages = [
        'account-penalty-freeze-detail.html',
        'account-penalty-freeze.html',
        'admin-login.html',
        'admin-profile.html',
        'announcement-detail.html',
        'announcement-management.html',
        'create-announcement.html',
        'create-new-partner.html',
        'create-new-user.html',
        'edit-announcement.html',
        'partner-detail.html',
        'partner-management.html',
        'platform-settings.html',
        'qa-detail.html',
        'qa-management.html',
        'sidebar-template.html',
        'user-detail.html',
        'user-management.html',
        'weekly-theme-management.html',
    ];

    $inventory = [
        [
            'module' => 'IDENTITY_ACCESS_MANAGEMENT',
            'status' => 'matched',
            'routes' => [
                'admin.dashboard.iam.users' => '/dashboard/iam/users',
                'admin.dashboard.iam.users.show' => '/dashboard/iam/users/{user}',
                'admin.dashboard.iam.verification-queue' => '/dashboard/iam/verification-queue',
                'admin.dashboard.iam.user-security' => '/dashboard/iam/user-security/{user?}',
            ],
            'views' => [
                'iam.users',
                'iam.users.show-engineer',
                'iam.users.show-partner',
                'iam.users.show-admin',
                'iam.verification-queue',
                'iam.user-security',
            ],
            'targets' => [
                'user-management.html',
                'user-detail.html',
                'admin-profile.html',
            ],
            'note' => 'User Management is the first migrated view. Verification Queue and User Security keep existing views until exact rebuild targets are assigned.',
        ],
        [
            'module' => 'PARTNER_PROFILES',
            'status' => 'matched',
            'routes' => [
                'admin.dashboard.partner-profiles.index' => '/dashboard/partner-profiles',
                'admin.dashboard.partner-profiles.create' => '/dashboard/partner-profiles/create',
                'admin.dashboard.partner-profiles.show' => '/dashboard/partner-profiles/{partnerProfile}',
                'admin.dashboard.partner-profiles.edit' => '/dashboard/partner-profiles/{partnerProfile}/edit',
            ],
            'views' => [
                'admin.partner-profiles.index',
                'admin.partner-profiles.create',
                'admin.partner-profiles.show',
                'admin.partner-profiles.edit',
            ],
            'targets' => [
                'partner-management.html',
                'create-new-partner.html',
                'partner-detail.html',
            ],
            'note' => 'Partner index/create/detail screens have direct rebuild targets. Edit can reuse the create/detail visual language later.',
        ],
        [
            'module' => 'QA',
            'status' => 'matched',
            'routes' => [
                'admin.dashboard.qa.index' => '/dashboard/qa',
                'admin.dashboard.qa.questions' => '/dashboard/qa/questions',
                'admin.dashboard.qa.answers' => '/dashboard/qa/answers',
                'admin.dashboard.qa.weekly-themes' => '/dashboard/qa/weekly-themes',
                'admin.dashboard.qa.reputation' => '/dashboard/qa/reputation',
                'admin.dashboard.qa.leaderboard' => '/dashboard/qa/leaderboard',
                'admin.dashboard.qa.flagged' => '/dashboard/qa/flagged',
            ],
            'views' => [
                'admin.qa.index',
                'admin.qa.questions',
                'admin.qa.answers',
                'admin.qa.weekly-themes',
                'admin.qa.reputation',
                'admin.qa.leaderboard',
                'admin.qa.flagged',
            ],
            'targets' => [
                'qa-management.html',
                'qa-detail.html',
                'weekly-theme-management.html',
            ],
            'note' => 'QA overview/review screens map to QA Management. Detail workflows map to QA Detail. Reputation and leaderboard remain placeholder-ready.',
        ],
        [
            'module' => 'ADMIN_OPERATIONS',
            'status' => 'partial',
            'routes' => [
                'admin.dashboard.admin-operations.index' => '/dashboard/admin-operations',
                'admin.dashboard.admin-operations.support-tickets.create' => '/dashboard/admin-operations/support-tickets/create',
                'admin.dashboard.admin-operations.account-penalties.create' => '/dashboard/admin-operations/account-penalties/create',
                'admin.dashboard.admin-operations.platform-settings.edit' => '/dashboard/admin-operations/platform-settings/edit/{platformSetting?}',
                'admin.dashboard.admin-operations.admin-integrations.create' => '/dashboard/admin-operations/admin-integrations/create',
            ],
            'views' => [
                'admin.admin-operations.index',
                'admin.admin-operations.support-tickets.create',
                'admin.admin-operations.account-penalties.create',
                'admin.admin-operations.platform-settings.edit',
                'admin.admin-operations.admin-integrations.create',
            ],
            'targets' => [
                'account-penalty-freeze.html',
                'account-penalty-freeze-detail.html',
                'platform-settings.html',
            ],
            'note' => 'Penalty and platform settings screens have matching rebuild targets. Support tickets, integrations, and content approval remain placeholder-ready.',
        ],
        [
            'module' => 'FEED_CMS',
            'status' => 'matched',
            'routes' => [
                'admin.dashboard.feed-cms.index' => '/dashboard/feed-cms',
                'admin.dashboard.feed-cms.pages.create' => '/dashboard/feed-cms/pages/create',
                'admin.dashboard.feed-cms.pages.edit' => '/dashboard/feed-cms/pages/{page}/edit',
            ],
            'views' => [
                'admin.feed-cms.index',
                'admin.feed-cms.create',
                'admin.feed-cms.edit',
            ],
            'targets' => [
                'announcement-management.html',
                'create-announcement.html',
                'edit-announcement.html',
                'announcement-detail.html',
            ],
            'note' => 'Feed CMS maps to announcement management screens. Detail can be introduced later without route renames.',
        ],
        [
            'module' => 'SUBSCRIPTIONS',
            'status' => 'placeholder-ready',
            'routes' => [
                'admin.dashboard.subscriptions.index' => '/dashboard/subscriptions',
                'admin.dashboard.subscriptions.tiers.create' => '/dashboard/subscriptions/tiers/create',
                'admin.dashboard.subscriptions.tiers.edit' => '/dashboard/subscriptions/tiers/{subscriptionTier}/edit',
                'admin.dashboard.subscriptions.member-plans.create' => '/dashboard/subscriptions/member-plans/create',
                'admin.dashboard.subscriptions.member-plans.edit' => '/dashboard/subscriptions/member-plans/{memberSubscriptionPlan}/edit',
            ],
            'views' => [
                'admin.subscriptions.index',
                'admin.subscriptions.tiers.create',
                'admin.subscriptions.tiers.edit',
                'admin.subscriptions.member-plans.create',
                'admin.subscriptions.member-plans.edit',
            ],
            'targets' => [],
            'note' => 'No dedicated subscription rebuild HTML exists yet. Keep current routes/views for later UI implementation.',
        ],
        [
            'module' => 'LIBRARY',
            'status' => 'placeholder-ready',
            'routes' => [
                'admin.dashboard.library.items.index' => '/dashboard/library/items',
                'admin.dashboard.library.items.create' => '/dashboard/library/items/create',
                'admin.dashboard.library.items.show' => '/dashboard/library/items/{libraryItem}',
                'admin.dashboard.library.items.edit' => '/dashboard/library/items/{libraryItem}/edit',
                'admin.dashboard.library.knowledge-domains.index' => '/dashboard/library/knowledge-domains',
                'admin.dashboard.library.rank-tiers.index' => '/dashboard/library/rank-tiers',
            ],
            'views' => [
                'admin.library.items.index',
                'admin.library.items.create',
                'admin.library.items.show',
                'admin.library.items.edit',
                'admin.library.knowledge-domains',
                'admin.library.rank-tiers',
            ],
            'targets' => [],
            'note' => 'No Charley Library rebuild HTML exists yet. Current route/view structure stays intact.',
        ],
        [
            'module' => 'SHARED_DASHBOARD_PRIMITIVES',
            'status' => 'placeholder-ready',
            'routes' => [
                'admin.dashboard.media-files.index' => '/dashboard/media-files',
                'admin.dashboard.media-files.show' => '/dashboard/media-files/{mediaFile}',
                'admin.dashboard.plant-types.index' => '/dashboard/plant-types',
                'admin.dashboard.plant-types.create' => '/dashboard/plant-types/create',
                'admin.dashboard.plant-types.edit' => '/dashboard/plant-types/{plantType}/edit',
                'admin.dashboard.taxonomy.index' => '/dashboard/taxonomy',
                'admin.dashboard.taxonomy.create' => '/dashboard/taxonomy/create',
                'admin.dashboard.taxonomy.edit' => '/dashboard/taxonomy/{tag}/edit',
            ],
            'views' => [
                'admin.media-files.index',
                'admin.plant-types.index',
                'admin.plant-types.create',
                'admin.plant-types.edit',
                'admin.taxonomy.index',
                'admin.taxonomy.create',
                'admin.taxonomy.edit',
            ],
            'targets' => [],
            'note' => 'Media Files, Plant Types, and Taxonomy have no matching rebuild HTML. They remain placeholder-ready.',
        ],
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-title-row">
            <div>
                <h1 class="page-title">Dashboard View Inventory</h1>
                <p class="page-subtitle">Existing backend routes mapped to available rebuild HTML targets.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Available Rebuild Pages</div>
                <div class="card-subtitle">{{ count($rebuildPages) }} HTML files in rebuild/pages</div>
            </div>
        </div>
        <div class="card-body">
            <div class="keyword-wrap">
                @foreach ($rebuildPages as $page)
                    <span class="kw-tag">{{ $page }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-header">
            <div>
                <div class="table-title">Route And View Mapping</div>
                <div class="table-meta">Routes are not deleted, renamed, or moved by this inventory.</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Status</th>
                    <th>Existing Routes</th>
                    <th>Current Views</th>
                    <th>Target HTML</th>
                    <th>Implementation Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($inventory as $item)
                    <tr>
                        <td><strong>{{ $item['module'] }}</strong></td>
                        <td>
                            <span class="badge {{ $item['status'] === 'matched' ? 'active' : ($item['status'] === 'partial' ? 'warned' : 'pending') }}">
                                <span class="badge-dot"></span>{{ $item['status'] }}
                            </span>
                        </td>
                        <td>
                            <div class="info-grid">
                                @foreach ($item['routes'] as $name => $uri)
                                    <div class="info-item">
                                        <div class="info-label">{{ $name }}</div>
                                        <div class="info-value">{{ $uri }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @foreach ($item['views'] as $view)
                                <span class="kw-tag">{{ $view }}</span>
                            @endforeach
                        </td>
                        <td>
                            @forelse ($item['targets'] as $target)
                                <span class="kw-tag">{{ $target }}</span>
                            @empty
                                <span class="text-secondary">No matching HTML</span>
                            @endforelse
                        </td>
                        <td>{{ $item['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
