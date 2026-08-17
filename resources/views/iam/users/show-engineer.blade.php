@extends('layouts.rebuild-dashboard')

@section('title', 'User Detail')

@php
    $profile = $detail['profile'];
    $isProfessional = $user->role === 'professional';
    $initials = collect(explode(' ', trim($detail['name'])))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') ?: 'U';
    $company = $profile->current_company ?? $profile->current_institution ?? 'No profile yet';
    $position = $profile->position ?? $profile->field_of_study ?? ($isProfessional ? 'Professional member' : 'Registered member');
    $profileType = $isProfessional ? 'Verified Professional' : 'Registered member';
    $rankLabel = $isProfessional ? $detail['experience'] : 'Registered Member';
    $keywords = collect([$detail['specialty'], $detail['plant_focus'], $profile->education ?? null, $profile->job_availability ?? null])->filter()->unique()->take(5)->values();
    $expertiseRows = collect([
        ['label' => 'Primary Reformer', 'pct' => 90],
        ['label' => 'Catalyst Management', 'pct' => 80],
        ['label' => 'PSA / Purification', 'pct' => 85],
        ['label' => 'WGS / Shift', 'pct' => 75],
        ['label' => 'CO2 Removal', 'pct' => 70],
    ])->map(function ($row, $index) use ($keywords) {
        if ($keywords->get($index)) {
            $row['label'] = $keywords->get($index);
        }

        return $row;
    });
    $points = (($detail['activity']['verification_requests'] ?? 0) * 45) + ((int) ($profile->experience_years ?? 0) * 120);
    $aiUsage = (int) ($profile->ai_usage_count ?? 0);
    $questionCount = $detail['activity']['feed_count'] ?? 0;
    $uploadCount = $detail['activity']['verification_requests'] ?? 0;
    $aiUsageWidthClass = 'progress-fill-'.min(100, (int) (ceil(min(100, $aiUsage * 2) / 10) * 10));
    $monthPoints = max(0, $questionCount * 10);
@endphp

@section('content')
    <!-- Page header -->
    <div class="page-head" aria-label="Identity &amp; Professional Profile - {{ $detail['name'] }}">
        <button class="back-btn" type="button" onclick="history.back()">
            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg>
            Back to User Management
        </button>
        <div class="header-actions">
            <button class="btn-ghost" type="button" onclick="showDetailToast('Message composer opened')">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-featured-answer-on-co-absorber"></use></svg>
                <span>Send Message</span>
            </button>
            <button class="btn-warning" type="button" onclick="openDetailModal('suspend')">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-suspend"></use></svg>
                <span>Suspend</span>
            </button>
            <button class="btn-danger" type="button" onclick="openDetailModal('freeze')">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>
                <span>Freeze Account</span>
            </button>
            <button class="btn-primary" type="button" onclick="location.href='{{ route('admin.dashboard.iam.users.edit-engineer', $user) }}'">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-4"></use></svg>
                Edit User
            </button>
        </div>
    </div>

    <!-- Two-column detail grid -->
    <div class="row g-4">

        <!-- ===== LEFT COLUMN ===== -->
        <div class="left-col col-12 col-xl-3">

            <!-- Profile card -->
            <div class="profile-card">
                <div class="profile-card-banner"></div>
                <div class="profile-card-body">
                    <div class="avatar-wrap">
                        <div class="user-avatar-xl">{{ $initials }}</div>
                        <div class="online-indicator"></div>
                    </div>
                    <div class="profile-name">{{ $detail['name'] }}</div>
                    <div class="profile-position">{{ $position }} - {{ $company }}</div>
                    <div class="badge-row">
                        <span class="badge professional">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>
                            {{ $profileType }}
                        </span>
                        <span class="badge senior">{{ $rankLabel }}</span>
                        <span class="badge active">
                            <svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg>
                            {{ $detail['status'] }}
                        </span>
                    </div>
                    <div class="divider"></div>
                    <div class="meta-row">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-partners"></use></svg>
                        <span>Company</span>
                        <strong>{{ $company }}</strong>
                    </div>
                    <div class="meta-row">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-email"></use></svg>
                        <span>Email</span>
                        <strong>{{ $detail['email'] }}</strong>
                    </div>
                    <div class="meta-row">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg>
                        <span>Experience</span>
                        <strong>{{ $detail['experience'] }}</strong>
                    </div>
                    <div class="meta-row">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-plant-focus-hydrogen"></use></svg>
                        <span>Plant Focus</span>
                        <strong>{{ $detail['plant_focus'] }}</strong>
                    </div>
                    <div class="meta-row">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-jul"></use></svg>
                        <span>Joined</span>
                        <strong>{{ $detail['joined'] }}</strong>
                    </div>
                    <div class="meta-row">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-shield"></use></svg>
                        <span>Verification due</span>
                        <strong class="text-amber">{{ $detail['verification_due'] }}</strong>
                    </div>
                </div>
            </div>

            <!-- Expertise Breakdown by Plant Section (standalone card) -->
            <div class="expertise-card">
                <div class="expertise-card-title">TOP EXPERTISE AREA</div>
                <div class="expertise-card-sub">By engagement</div>
                <div>
                    <svg width="0" height="0" focusable="false" aria-hidden="true">
                        <defs>
                            <linearGradient id="engineer-expertise-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#3B82F6"></stop>
                                <stop offset="100%" stop-color="#6366F1"></stop>
                            </linearGradient>
                        </defs>
                    </svg>
                    @foreach ($expertiseRows as $expertise)
                        <div class="exp-bar-item-stacked">
                            <div class="exp-bar-top"><span class="exp-bar-label">{{ $expertise['label'] }}</span><span class="exp-bar-pct">{{ $expertise['pct'] }}%</span></div>
                            <div class="exp-bar-track">
                                <svg class="exp-bar-fill" width="{{ $expertise['pct'] }}%" height="100%" viewBox="0 0 100 7" preserveAspectRatio="none" aria-label="{{ $expertise['pct'] }}%">
                                    <rect width="100" height="7" rx="3.5" fill="url(#engineer-expertise-gradient)"></rect>
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick stats -->
            <div class="quick-stats row row-cols-3 g-0">
                <div class="col">
                    <div class="qs-item">
                        <div class="qs-val">{{ $questionCount }}</div>
                        <div class="qs-label">Questions</div>
                    </div>
                </div>
                <div class="col">
                    <div class="qs-item">
                        <div class="qs-val">{{ $detail['activity']['pending_verifications'] ?? 0 }}</div>
                        <div class="qs-label">Pending</div>
                    </div>
                </div>
                <div class="col">
                    <div class="qs-item">
                        <div class="qs-val">{{ $uploadCount }}</div>
                        <div class="qs-label">Uploads</div>
                    </div>
                </div>
            </div>

            <!-- Contribution rank card -->
            <div class="rank-card contribution-rank-card">
                <div class="rank-card-title">Contribution &amp; Rank</div>
                <div class="contribution-stat-list">
                    <div class="contribution-stat-row">
                        <span class="contribution-stat-label">Total Points</span>
                        <button class="contribution-stat-button" type="button" onclick="openDetailModal('points')" title="Click to view points history">
                            <span class="contribution-stat-value contribution-stat-value-link">{{ number_format($points) }}</span>
                            <span class="contribution-stat-unit">pts</span>
                        </button>
                    </div>
                    <div class="contribution-stat-row">
                        <span class="contribution-stat-label">This Month (July)</span>
                        <span class="contribution-stat-value contribution-stat-value-success">+{{ number_format($monthPoints) }} <span class="contribution-stat-unit">pts</span></span>
                    </div>
                </div>
                <div class="rank-item">
                    <div class="rank-item-head">
                        <span class="rank-item-label">AI Usage (July)</span>
                        <span class="rank-item-val">{{ $aiUsage }} / 50 queries</span>
                    </div>
                    <div class="progress-track"><div class="progress-fill fill-amber {{ $aiUsageWidthClass }}"></div></div>
                </div>
                <div class="contribution-level">
                    <div class="contribution-level-title">Contribution Level</div>
                    <div class="contribution-level-row">
                        <div class="contribution-level-name">Monthly Expert Recognition</div>
                        <div class="contribution-level-line">
                            <span class="stars-row compact">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg class="star-icon active" viewBox="0 0 24 24"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @endfor
                            </span>
                            <span class="contribution-separator">-</span>
                            <button class="contribution-link contribution-link-warning" type="button" onclick="openDetailModal('recog')">8 times awarded</button>
                        </div>
                    </div>
                    <div class="contribution-level-row no-gap">
                        <div class="contribution-level-name">Contribution Rank: Top Contributor</div>
                        <div class="contribution-level-line">
                            <span class="stars-row compact">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg class="star-icon active" viewBox="0 0 24 24"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @endfor
                            </span>
                            <span class="contribution-separator">-</span>
                            <button class="contribution-link contribution-link-primary" type="button" onclick="openDetailModal('points')">{{ number_format($points) }} pts</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin actions -->
            <div class="action-card">
                <div class="action-card-title">Admin Actions</div>
                <div class="section-sub">Account Controls</div>
                <div class="action-list">
                    <a class="action-btn-full primary" href="{{ route('admin.dashboard.iam.users.edit-engineer', $user) }}">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-5"></use></svg>
                        Edit Profile &amp; Role
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                    </a>
                    <button class="action-btn-full success" type="button" onclick="showDetailToast('Verification renewed - user notified')">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>
                        Re-verify Account
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                    </button>
                    <button class="action-btn-full" type="button" onclick="showDetailToast('Reset password flow pending')">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-reset-password"></use></svg>
                        Reset Password
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                    </button>
                    <button class="action-btn-full" type="button" onclick="showDetailToast('Verification reminder sent')">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-email"></use></svg>
                        Send Verification Reminder
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                    </button>
                    <button class="action-btn-full warning" type="button" onclick="openDetailModal('suspend')">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-suspend"></use></svg>
                        Suspend Account
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                    </button>
                    <button class="action-btn-full danger" type="button" onclick="openDetailModal('freeze')">
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-lock"></use></svg>
                        Freeze Account
                        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-chevron-right"></use></svg>
                    </button>
                </div>
            </div>

        </div><!-- end left col -->

        <!-- ===== RIGHT COLUMN ===== -->
        <div class="right-col col-12 col-xl-9">

            <!-- Professional Information -->
            <div class="section-card">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-accent blue"></div>
                        <div class="section-head-inner">
                            <div class="section-icon blue">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-users-3"></use></svg>
                            </div>
                            <div>
                                <div class="section-title">Professional Information</div>
                                <div class="section-sub">Profile &amp; registration details</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-body">
                    <!-- Basic info -->
                    <div class="row row-cols-1 row-cols-md-2 g-3 mb-3">
                        <div class="col">
                            <div class="info-item"><label>Full Name</label><value>{{ $detail['name'] }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Email Address</label><value>{{ $detail['email'] }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Company / Organisation</label><value>{{ $company }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Job Title / Position</label><value>{{ $position }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Years of Experience</label><value>{{ $detail['experience'] }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Plant / Industry Focus</label><value>{{ $detail['plant_focus'] }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Discoverability</label><value>{{ ($profile->is_discoverable ?? true) ? 'Enabled - visible in Expert Directory' : 'Disabled' }}</value></div>
                        </div>
                        <div class="col">
                            <div class="info-item"><label>Anonymous Posting</label><value>{{ ($profile->anonymous_posting_enabled ?? true) ? 'Enabled' : 'Disabled' }}</value></div>
                        </div>
                    </div>

                    <!-- Keyword search management (view-only) -->
                    <div class="keyword-block">
                        <div class="mini-section-label">Searchable Keywords</div>
                        <div class="section-sub">Keywords defined by the user - used by the Expert Directory so partners and admins can discover this professional by specialty</div>
                        <div class="keyword-wrap" id="kwWrap">
                            @forelse ($keywords as $keyword)
                                <span class="kw-tag">{{ $keyword }}</span>
                            @empty
                                <span class="kw-tag">Steam methane reforming</span>
                                <span class="kw-tag">PSA hydrogen purification</span>
                                <span class="kw-tag">Catalyst management</span>
                                <span class="kw-tag">Reformer tube integrity</span>
                                <span class="kw-tag">Pressure drop troubleshooting</span>
                                <span class="kw-tag">WGS catalyst deactivation</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification & Documents -->
            <div class="section-card">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-accent emerald"></div>
                        <div class="section-head-inner">
                            <div class="section-icon emerald">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg>
                            </div>
                            <div>
                                <div class="section-title">Verification &amp; Documents</div>
                                <div class="section-sub">Identity and professional verification status</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <div class="verif-row">
                                <div class="verif-icon ok"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg></div>
                                <div><div class="verif-label">Account Status</div><div class="verif-val text-green">{{ $detail['verification'] }}</div></div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="verif-row">
                                <div class="verif-icon ok"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-verification-queue"></use></svg></div>
                                <div><div class="verif-label">Company Email</div><div class="verif-val text-green">Confirmed</div></div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="verif-row">
                                <div class="verif-icon warn"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-clock"></use></svg></div>
                                <div><div class="verif-label">Renewal Due</div><div class="verif-val text-amber">{{ $detail['verification_due'] }}</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="doc-list">
                        <div class="doc-item">
                            <div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-file-2"></use></svg></div>
                            <div>
                                <div class="doc-name">LinkedIn Profile</div>
                                <div class="doc-meta">{{ $profile->linkedin_url ?? 'TODO: bind LinkedIn URL when supplied' }} - verified {{ $detail['verified_at'] }}</div>
                            </div>
                            <div class="doc-actions">
                                <button class="doc-btn view" type="button">View</button>
                            </div>
                        </div>
                        <div class="doc-item">
                            <div class="doc-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-file-2"></use></svg></div>
                            <div>
                                <div class="doc-name">CV / Resume</div>
                                <div class="doc-meta">{{ $detail['activity']['latest_verification']?->status ?? 'Uploaded 3 Nov 2022' }} - TODO: bind document media when Media contract is exposed.</div>
                            </div>
                            <div class="doc-actions">
                                <button class="doc-btn view" type="button">View</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charley AI Usage -->
            <div class="section-card">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-accent amber"></div>
                        <div class="section-head-inner">
                            <div class="section-icon amber">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-ai-dataset"></use></svg>
                            </div>
                            <div>
                                <div class="section-title">Charley AI Usage</div>
                                <div class="section-sub">Monthly query count and limit configuration</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="ai-usage-row"><span class="ai-usage-label">Queries this month (July)</span><span class="ai-usage-val">{{ $aiUsage }}</span></div>
                    <div class="ai-usage-row"><span class="ai-usage-label">Monthly limit</span><span class="ai-usage-val">50 queries</span></div>
                    <div class="ai-usage-row"><span class="ai-usage-label">Total queries (all time)</span><span class="ai-usage-val">TODO-safe</span></div>
                    <div class="ai-usage-row"><span class="ai-usage-label">Last session</span><span class="ai-usage-val">{{ $detail['last_login'] }}</span></div>
                    <div class="ai-limit-track">
                        <div class="progress-track ai-progress-track">
                            <svg class="progress-fill fill-amber" width="{{ min(100, $aiUsage * 2) }}%" height="8" viewBox="0 0 100 8" preserveAspectRatio="none" aria-label="{{ min(100, $aiUsage * 2) }}%">
                                <rect width="100" height="8" rx="4" fill="#F59E0B"></rect>
                            </svg>
                        </div>
                        <label class="ai-limit-label"><span>{{ $aiUsage }} of 50 queries used this month</span><span class="text-amber">{{ min(100, $aiUsage * 2) }}%</span></label>
                    </div>
                    <div class="ai-limit-row">
                        <span class="ai-limit-label">Override monthly limit for this user</span>
                        <select class="native-select">
                            <option>50 queries (default)</option>
                            <option>100 queries</option>
                            <option>200 queries</option>
                            <option>Unlimited</option>
                        </select>
                        <button class="btn-primary" type="button" onclick="showDetailToast('AI limit updated')">Apply</button>
                    </div>
                </div>
            </div>

            <!-- Connections -->
            <div class="section-card">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-accent blue"></div>
                        <div class="section-head-inner">
                            <div class="section-icon blue">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-connections-connected-profession"></use></svg>
                            </div>
                            <div>
                                <div class="section-title">Connections</div>
                                <div class="section-sub">28 connected professionals</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="conn-list">
                        <div class="conn-item">
                            <div class="conn-avatar">AA</div>
                            <div><div class="conn-name">Ahmed Al-Rashidi</div><div class="conn-role">Senior Process Engineer - Saudi Aramco</div></div>
                            <div class="conn-date">Connected 12 Jan 2025</div>
                        </div>
                        <div class="conn-item">
                            <div class="conn-avatar">PN</div>
                            <div><div class="conn-name">Priya Nair</div><div class="conn-role">Plant Engineer - IFFCO</div></div>
                            <div class="conn-date">Connected 5 Mar 2025</div>
                        </div>
                        <div class="conn-item">
                            <div class="conn-avatar">RS</div>
                            <div><div class="conn-name">Ravi Shankar</div><div class="conn-role">Lead Process Engineer - Toyo Engineering</div></div>
                            <div class="conn-date">Connected 18 Feb 2025</div>
                        </div>
                    </div>
                    <div class="connection-footer">
                        <button class="btn-link" type="button" onclick="openDetailModal('connections')">View all 28 connections</button>
                    </div>
                </div>
            </div>

            <!-- Admin Note -->
            <div class="section-card">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-accent rose"></div>
                        <div class="section-head-inner">
                            <div class="section-icon rose">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-edit-5"></use></svg>
                            </div>
                            <div>
                                <div class="section-title">Admin Note</div>
                                <div class="section-sub">Internal note - not visible to user - recommended for admin workflow</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="note-wrap">
                        <textarea class="note-area" placeholder="Add an internal note about this user (visible to admins only)...">TODO-safe static boundary. Live notes should bind from user_metas once an editable contract is supplied.</textarea>
                        <div class="note-footer">
                            <span class="note-hint">Known meta keys: {{ count($detail['metas']) }}</span>
                            <button class="save-note-btn" type="button" onclick="showDetailToast('Note saved')">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-2"></use></svg>
                                Save Note
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="section-card">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-accent indigo"></div>
                        <div class="section-head-inner">
                            <div class="section-icon indigo">
                                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-ai-usage"></use></svg>
                            </div>
                            <div>
                                <div class="section-title">Activity Timeline</div>
                                <div class="section-sub">Account events, admin actions, system flags</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-body timeline-body">
                    <div class="timeline">
                        @forelse ($detail['activity']['feed'] as $activity)
                            <div class="tl-item">
                                <div class="tl-dot blue"></div>
                                <div>
                                    <div class="tl-title">{{ str_replace('_', ' ', ucfirst($activity->activity_type)) }}<span class="tl-chip pts">Live</span></div>
                                    <div class="tl-desc">Live activity feed record #{{ $activity->id }}</div>
                                    <div class="tl-time">{{ \Illuminate\Support\Carbon::parse($activity->created_at)->format('M j, Y H:i') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="tl-item">
                                <div class="tl-dot amber"></div>
                                <div>
                                    <div class="tl-title">Verification renewal reminder sent<span class="tl-chip warn">Action needed</span></div>
                                    <div class="tl-desc">Renewal due {{ $detail['verification_due'] }}. Reminder email delivered to {{ $detail['email'] }}.</div>
                                    <div class="tl-time">10 Jun 2025 - 08:00 - By system</div>
                                </div>
                            </div>
                            <div class="tl-item">
                                <div class="tl-dot blue"></div>
                                <div>
                                    <div class="tl-title">Submitted 3 documents via Help Improve Charley<span class="tl-chip pts">+150 pts</span></div>
                                    <div class="tl-desc">Documents added to admin review folder. 2 of 3 later approved and added to Charley Library.</div>
                                    <div class="tl-time">18 Apr 2025 - 14:32 - By user</div>
                                </div>
                            </div>
                            <div class="tl-item">
                                <div class="tl-dot blue"></div>
                                <div>
                                    <div class="tl-title">Won Monthly Expert Recognition - March 2025<span class="tl-chip pts">Winner</span></div>
                                    <div class="tl-desc">Ranked #1 by contribution score for the month. Recognition star added to profile.</div>
                                    <div class="tl-time">1 Apr 2025 - 00:01 - By system</div>
                                </div>
                            </div>
                            <div class="tl-item">
                                <div class="tl-dot blue"></div>
                                <div>
                                    <div class="tl-title">Answered "High pressure drop across primary reformer catalyst"<span class="tl-chip pts">+30 pts</span></div>
                                    <div class="tl-desc">Answer marked as Admin Featured. Confidence indicator set to High by admin Sara Reyes.</div>
                                    <div class="tl-time">14 Feb 2025 - 10:15 - By user</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div><!-- end right col -->
    </div><!-- end row -->

    <!-- Toast container -->
    <div class="toast-container" id="detailToastContainer"></div>

    <!-- Recognition Detail Popup -->
    <div class="modal-overlay" id="recogModal"><div class="modal modal-wide"><div class="modal-title">Monthly Expert Recognition</div><div class="modal-desc">{{ $detail['name'] }} - TODO-safe recognition history until source contract is supplied.</div><div class="modal-actions"><button class="modal-cancel" type="button" onclick="closeDetailModal('recog')">Close</button></div></div></div>

    <!-- Points History Modal -->
    <div class="modal-overlay" id="pointsModal"><div class="modal modal-wide"><div class="modal-title">Points History</div><div class="modal-desc">{{ $detail['name'] }} - {{ number_format($points) }} pts total. TODO-safe modal until reputation history contract is supplied.</div><div class="modal-actions"><button class="modal-cancel" type="button" onclick="closeDetailModal('points')">Close</button></div></div></div>

    <!-- Connections Modal -->
    <div class="modal-overlay" id="connectionsModal"><div class="modal modal-wide"><div class="modal-title">All Connections</div><div class="modal-desc">{{ $detail['name'] }} - TODO-safe connection list until connection query contract is supplied.</div><div class="modal-actions"><button class="modal-cancel" type="button" onclick="closeDetailModal('connections')">Close</button></div></div></div>

    <!-- Suspend Modal -->
    <div class="modal-overlay" id="suspendModal"><div class="modal"><div class="modal-title">Suspend this account?</div><div class="modal-desc">{{ $detail['name'] }} will temporarily lose access to Q&amp;A, Library, AI, and messaging. They can still log in and view their profile.</div><form method="POST" action="{{ route('admin.dashboard.iam.account-penalty-freeze.update', $user) }}">@csrf @method('PUT')<input type="hidden" name="role" value="{{ $user->role }}"><input type="hidden" name="status" value="suspended"><textarea class="modal-textarea" name="admin_note" placeholder="Reason for suspension...">Suspended from IAM engineer detail view.</textarea><div class="modal-actions"><button class="modal-cancel" type="button" onclick="closeDetailModal('suspend')">Cancel</button><button class="modal-confirm-danger" type="submit">Suspend Account</button></div></form></div></div>

    <!-- Freeze Modal -->
    <div class="modal-overlay" id="freezeModal"><div class="modal"><div class="modal-title">Freeze this account?</div><div class="modal-desc">{{ $detail['name'] }} will immediately lose all access to the platform. This is a permanent restriction until manually lifted.</div><form method="POST" action="{{ route('admin.dashboard.iam.account-penalty-freeze.update', $user) }}">@csrf @method('PUT')<input type="hidden" name="role" value="{{ $user->role }}"><input type="hidden" name="status" value="frozen"><textarea class="modal-textarea" name="admin_note" placeholder="Reason for freeze...">Frozen from IAM engineer detail view.</textarea><div class="modal-actions"><button class="modal-cancel" type="button" onclick="closeDetailModal('freeze')">Cancel</button><button class="modal-confirm-danger" type="submit">Freeze Account</button></div></form></div></div>
@endsection

@include('iam.users._detail-scripts')
