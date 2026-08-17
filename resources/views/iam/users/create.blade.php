@extends('layouts.app')

@section('title', 'Create User')

@push('styles')
    <style>
        .create-entry-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        .create-entry-card {
            display: flex;
            flex-direction: column;
            gap: 18px;
            min-height: 360px;
            padding: 22px;
            border: 1px solid var(--border, #E5E7EB);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .create-entry-card.featured {
            border-color: rgba(59, 130, 246, 0.48);
            box-shadow: 0 14px 30px rgba(59, 130, 246, 0.13);
        }
        .create-entry-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }
        .create-entry-icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #1D4ED8;
            background: #EFF6FF;
        }
        .create-entry-icon.partner {
            color: #047857;
            background: #ECFDF5;
        }
        .create-entry-icon.admin {
            color: #B45309;
            background: #FFFBEB;
        }
        .create-entry-icon .icon {
            width: 22px;
            height: 22px;
        }
        .create-entry-badge {
            padding: 5px 9px;
            border-radius: 8px;
            background: #F8FAFC;
            color: var(--ink-faint, #64748B);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .create-entry-title {
            margin: 0;
            color: var(--ink, #0F172A);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0;
        }
        .create-entry-copy {
            margin: 0;
            color: var(--ink-soft, #475569);
            font-size: 14px;
            line-height: 1.55;
        }
        .create-entry-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
            color: var(--ink-soft, #475569);
            font-size: 13px;
            line-height: 1.45;
        }
        .create-entry-list li {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .create-entry-list .icon {
            width: 15px;
            height: 15px;
            flex: 0 0 15px;
            margin-top: 2px;
            color: #2563EB;
        }
        .create-entry-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .create-entry-actions .btn-primary,
        .create-entry-actions .btn-outline {
            width: 100%;
            justify-content: center;
            text-decoration: none;
        }
        @media (max-width: 1100px) {
            .create-entry-grid {
                grid-template-columns: 1fr;
            }
            .create-entry-card {
                min-height: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Create User</h1>
            <p>Select the backend account creation flow for the new Admin, Engineer, or Partner profile.</p>
        </div>
        <div class="page-head-actions">
            <a class="btn-ghost" href="{{ route('admin.dashboard.iam.users') }}">Cancel</a>
        </div>
    </div>

    <div class="create-entry-grid">
        <section class="create-entry-card featured">
            <div class="create-entry-top">
                <div class="create-entry-icon">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-management-12-svg-viewbox-0"></use></svg>
                </div>
                <span class="create-entry-badge">Engineer</span>
            </div>
            <div>
                <h2 class="create-entry-title">Engineer Account</h2>
                <p class="create-entry-copy">Professional and registered member creation flow aligned to the rebuilt Create New User design source.</p>
            </div>
            <ul class="create-entry-list">
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-diamond-partner-licensors"></use></svg><span>Registered Member and Professional account types.</span></li>
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-profile-verification-queue-5-svg"></use></svg><span>Verification method, expertise rank, and access setup sections.</span></li>
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-click-to-upload-cv-company"></use></svg><span>Source design: create-new-user.html.</span></li>
            </ul>
            <div class="create-entry-actions">
                <a class="btn-primary" href="{{ route('admin.dashboard.iam.users.create-engineer') }}">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-user-3-users-sel"></use></svg>
                    Create Engineer
                </a>
            </div>
        </section>

        <section class="create-entry-card">
            <div class="create-entry-top">
                <div class="create-entry-icon partner">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-management-path-d-m9-12"></use></svg>
                </div>
                <span class="create-entry-badge">Partner</span>
            </div>
            <div>
                <h2 class="create-entry-title">Partner Account</h2>
                <p class="create-entry-copy">Company, contact, subscription, and partner-tier onboarding flow aligned to the rebuilt partner source.</p>
            </div>
            <ul class="create-entry-list">
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-subscription-and-billing"></use></svg><span>Diamond, Gold, and Platinum partner setup context.</span></li>
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-company-logo-and-branding-displayed"></use></svg><span>Company profile and branding upload sections.</span></li>
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-click-to-upload-or-drag"></use></svg><span>Source design: create-new-partner.html.</span></li>
            </ul>
            <div class="create-entry-actions">
                <a class="btn-primary" href="{{ route('admin.dashboard.iam.users.create-partner') }}">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-add-partner-manually-div-class-stat-icon"></use></svg>
                    Create Partner
                </a>
            </div>
        </section>

        <section class="create-entry-card">
            <div class="create-entry-top">
                <div class="create-entry-icon admin">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-security-and-access-svg-viewbox-0"></use></svg>
                </div>
                <span class="create-entry-badge">Admin</span>
            </div>
            <div>
                <h2 class="create-entry-title">Admin Account</h2>
                <p class="create-entry-copy">Internal administrator setup placeholder for operations staff and privileged dashboard users.</p>
            </div>
            <ul class="create-entry-list">
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-account-penalty-and-freeze-3"></use></svg><span>Role, access, and security controls reserved for admin users.</span></li>
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-audit-log-pat"></use></svg><span>Static internal form until a dedicated source design exists.</span></li>
                <li><svg class="icon"><use href="/assets/icons/sprite.svg#icon-host-webinars-or-events-line"></use></svg><span>No backend persistence is attached in this task.</span></li>
            </ul>
            <div class="create-entry-actions">
                <a class="btn-outline" href="{{ route('admin.dashboard.iam.users.create-admin') }}">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-actions-button-class-btn-btn-danger"></use></svg>
                    Create Admin
                </a>
            </div>
        </section>
    </div>
@endsection