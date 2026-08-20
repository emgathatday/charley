@php
    $adminSidebar = [
        'brand' => [
            'label' => 'Charley',
            'sub_label' => 'Admin Console',
            'route' => 'admin.dashboard.iam.users.engineers',
            'icon' => 'icon-charley-logo',
        ],
        'search' => [
            'placeholder' => 'Search platform...',
            'kbd' => '/',
            'icon' => 'icon-search-2',
        ],
        'groups' => [
            [
                'label' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'icon' => 'icon-dashboard', 'url' => '#'],
                    ['label' => 'Support Inbox', 'icon' => 'icon-support-inbox', 'url' => '#', 'badge' => ['label' => '5', 'class' => 'urgent']],
                ],
            ],
            [
                'label' => 'Member Management',
                'items' => [
                    ['label' => 'Engineers', 'icon' => 'icon-users-5', 'route' => 'admin.dashboard.iam.users.engineers', 'active' => ['route' => 'admin.dashboard.iam.users.engineers'], 'badge' => ['label' => '12', 'class' => 'urgent']],
                    ['label' => 'Partners', 'icon' => 'icon-partners', 'route' => 'admin.dashboard.iam.users.partners', 'active' => ['route' => 'admin.dashboard.iam.users.partners']],
                    ['label' => 'Administrators', 'icon' => 'icon-edit-5', 'route' => 'admin.dashboard.iam.users', 'params' => ['member_view' => 'administrators'], 'active' => ['route' => 'admin.dashboard.iam.users']],
                    ['label' => 'Profile Verification', 'icon' => 'icon-verification-queue', 'route' => 'admin.dashboard.iam.verification-queue', 'active' => ['route' => 'admin.dashboard.iam.verification-queue'], 'badge' => ['label' => '5', 'class' => 'urgent']],
                    ['label' => 'Subscription & Billing', 'icon' => 'icon-billing', 'route' => 'admin.dashboard.subscriptions.index', 'active' => ['route' => 'admin.dashboard.subscriptions.*']],
                    ['label' => 'Monthly Expert Recognition', 'icon' => 'icon-expert-recognition', 'url' => '#'],
                    ['label' => 'Account Penalty & Freeze', 'icon' => 'icon-lock', 'route' => 'admin.dashboard.iam.account-penalty-freeze', 'active' => ['route' => 'admin.dashboard.iam.account-penalty-freeze*']],
                ],
            ],
            [
                'label' => 'Shared Services',
                'items' => [
                    ['label' => 'Media Files', 'icon' => 'icon-files', 'route' => 'admin.dashboard.media-files.index', 'active' => ['route' => 'admin.dashboard.media-files.*']],
                    ['label' => 'Plant Types', 'icon' => 'icon-plant-focus-hydrogen', 'route' => 'admin.dashboard.plant-types.index', 'active' => ['route' => 'admin.dashboard.plant-types.*']],
                    ['label' => 'Taxonomy', 'icon' => 'icon-library', 'route' => 'admin.dashboard.taxonomy.index', 'active' => ['route' => 'admin.dashboard.taxonomy.*']],
                ],
            ],
            [
                'label' => 'Technical Q&A',
                'items' => [
                    ['label' => 'Q&A Management', 'icon' => 'icon-qa', 'route' => 'admin.dashboard.qa.index', 'active' => ['route' => 'admin.dashboard.qa.*', 'except_route' => 'admin.dashboard.qa.weekly-themes']],
                    ['label' => 'Weekly Theme Management', 'icon' => 'icon-weekly-theme', 'route' => 'admin.dashboard.qa.weekly-themes', 'active' => ['route' => 'admin.dashboard.qa.weekly-themes']],
                ],
            ],
            [
                'label' => 'Charley Library',
                'items' => [
                    ['label' => 'Library & PFD Content', 'icon' => 'icon-library', 'route' => 'admin.dashboard.library.items.index', 'active' => ['route' => 'admin.dashboard.library.items.*']],
                    ['label' => 'Knowledge Domains', 'icon' => 'icon-settings-2', 'route' => 'admin.dashboard.library.knowledge-domains.index', 'active' => ['route' => 'admin.dashboard.library.knowledge-domains.*']],
                ],
            ],
            [
                'label' => 'Platform',
                'items' => [
                    ['label' => 'Admin Operations', 'icon' => 'icon-dashboard', 'route' => 'admin.dashboard.admin-operations.index', 'active' => ['route' => 'admin.dashboard.admin-operations.*']],
                    ['label' => 'Feed & CMS', 'icon' => 'icon-announcements', 'route' => 'admin.dashboard.feed-cms.index', 'active' => ['route' => 'admin.dashboard.feed-cms.*']],
                    ['label' => 'Subscription & Billing', 'icon' => 'icon-billing', 'route' => 'admin.dashboard.subscriptions.index', 'active' => ['route' => 'admin.dashboard.subscriptions.*']],
                ],
            ],
        ],
        'footer' => [
            'label' => 'AI Assistant - Operational',
            'sub_label' => 'Backend console ready',
        ],
    ];
@endphp

<x-admin.sidebar-menu :items="$adminSidebar" />
