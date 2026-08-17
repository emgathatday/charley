@extends('layouts.app')

@section('title', 'Create Admin User')

@section('content')
    <div class="page-head">
        <div>
            <h1>Create Admin User</h1>
            <p>Create a static internal administrator account draft for backend IAM access.</p>
        </div>
        <div class="page-head-actions">
            <a class="btn-ghost" href="{{ route('admin.dashboard.iam.users') }}">Cancel</a>
            <button class="btn-primary" id="saveBtn" type="submit" form="createAdminForm">
                <svg class="icon"><use href="/assets/icons/sprite.svg#icon-security-and-access-svg-viewbox-0"></use></svg>
                Create Admin
            </button>
        </div>
    </div>

    <div class="success-banner" id="successBanner">
        <div class="s-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-security-and-access-svg-viewbox-0"></use></svg></div>
        <div>
            <b>Admin account prepared.</b>
            <span id="successDetail">The internal administrator invitation is ready for review.</span>
        </div>
    </div>

    <form id="createAdminForm" method="POST" action="{{ route('admin.dashboard.iam.users.store-admin') }}">
        @csrf
    <div class="form-col">
        <div class="card">
            <div class="card-head">
                <span class="step-tag">Step 1</span>
                <h2>Basic admin identity</h2>
                <p>Internal identity fields for dashboard users. This static form does not persist data.</p>
            </div>
            <div class="card-body">
                <div class="field-row">
                    <div class="field">
                        <label>Full name<span class="req">*</span></label>
                        <input type="text" id="fullName" name="full_name" required value="{{ old('full_name') }}" placeholder="e.g. Morgan Patel">
                    </div>
                    <div class="field">
                        <label>Display name</label>
                        <input type="text" id="displayName" placeholder="e.g. Morgan P.">
                    </div>
                </div>
                <div class="field-row triple">
                    <div class="field">
                        <label>Email address<span class="req">*</span></label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}" placeholder="admin@charley.local">
                    </div>
                    <div class="field">
                        <label>Username</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="morgan.admin">
                    </div>
                    <div class="field">
                        <label>Department</label>
                        <select id="department">
                            <option>Operations</option>
                            <option>Member Support</option>
                            <option>Verification Team</option>
                            <option>Finance</option>
                            <option>Platform Administration</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <span class="step-tag">Step 2</span>
                <h2>Admin role level</h2>
                <p>Demo role levels only; final RBAC mapping is intentionally deferred.</p>
            </div>
            <div class="card-body">
                <div class="type-grid" id="roleGrid">
                    <label class="type-card checked" data-role="operations">
                        <input type="radio" name="adminRole" value="operations" checked>
                        <div class="t-top">
                            <div class="t-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-actions-button-class-btn-btn-danger"></use></svg></div>
                            <span class="t-check"></span>
                        </div>
                        <div class="t-name">Operations Admin</div>
                        <div class="t-desc">Day-to-day IAM, profile review, and account support tasks.</div>
                        <ul>
                            <li>User and partner account visibility</li>
                            <li>Verification queue actions</li>
                            <li>Account status controls</li>
                        </ul>
                    </label>
                    <label class="type-card" data-role="support">
                        <input type="radio" name="adminRole" value="support">
                        <div class="t-top">
                            <div class="t-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-k-habib-flagged-for"></use></svg></div>
                            <span class="t-check"></span>
                        </div>
                        <div class="t-name">Support Admin</div>
                        <div class="t-desc">Restricted account assistance for member and partner support.</div>
                        <ul>
                            <li>Read-only user profile review</li>
                            <li>Support notes and account flags</li>
                            <li>No platform settings access</li>
                        </ul>
                    </label>
                    <label class="type-card" data-role="super">
                        <input type="radio" name="adminRole" value="super">
                        <div class="t-top">
                            <div class="t-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-audit-log-pat"></use></svg></div>
                            <span class="t-check"></span>
                        </div>
                        <div class="t-name">Super Admin</div>
                        <div class="t-desc">Static privileged placeholder for full dashboard governance.</div>
                        <ul>
                            <li>Platform settings and audit visibility</li>
                            <li>Subscription and billing administration</li>
                            <li>Security-sensitive controls</li>
                        </ul>
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <span class="step-tag">Step 3</span>
                <h2>Permission groups</h2>
                <p>Static demo permission groups for admin review before RBAC wiring.</p>
            </div>
            <div class="card-body">
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Users &amp; Partners</div>
                        <div class="sw-desc">Create, review, and manage platform accounts.</div>
                    </div>
                    <label class="switch"><input class="permission-toggle" type="checkbox" checked><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Profile Verification Queue</div>
                        <div class="sw-desc">Approve, reject, or request more verification information.</div>
                    </div>
                    <label class="switch"><input class="permission-toggle" type="checkbox" checked><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Subscription &amp; Billing</div>
                        <div class="sw-desc">Review partner subscriptions and manual billing context.</div>
                    </div>
                    <label class="switch"><input class="permission-toggle" type="checkbox"><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Platform Settings &amp; Audit Log</div>
                        <div class="sw-desc">Security-sensitive settings and internal activity review.</div>
                    </div>
                    <label class="switch"><input class="permission-toggle" type="checkbox"><span class="slider"></span></label>
                </div>
                <div class="field-row single" style="margin-top:16px;">
                    <div class="field">
                        <label>Selected permission groups</label>
                        <div class="rank-readout">
                            <div class="r-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-security-and-access-svg-viewbox-0"></use></svg></div>
                            <div>
                                <div class="r-text">Static permission preview</div>
                                <div class="r-value" id="permissionCount">2 groups selected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <span class="step-tag">Step 4</span>
                <h2>Access &amp; security</h2>
                <p>Initial login and security settings for the internal admin account.</p>
            </div>
            <div class="card-body">
                <div class="field-row single" style="margin-bottom:6px;">
                    <div class="field">
                        <label>Sign-in method</label>
                        <div class="radio-pill-group">
                            <label class="radio-pill"><input type="radio" name="signin" value="invite" checked><span>Send admin invitation</span></label>
                            <label class="radio-pill"><input type="radio" name="signin" value="password"><span>Set temporary password</span></label>
                        </div>
                    </div>
                </div>
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Require multi-factor authentication</div>
                        <div class="sw-desc">Recommended for every administrator account.</div>
                    </div>
                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Force password reset on first login</div>
                        <div class="sw-desc">Applies when a temporary password is used.</div>
                    </div>
                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <div>
                        <div class="sw-label">Send notification email</div>
                        <div class="sw-desc">Sends the admin onboarding message after approval.</div>
                    </div>
                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <span class="step-tag">Step 5</span>
                <h2>Account status</h2>
                <p>Static account status options for the initial admin record.</p>
            </div>
            <div class="card-body">
                <div class="field-row">
                    <div class="field">
                        <label>Status</label>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option>Pending Review</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Access starts</label>
                        <input type="date" value="2026-07-21">
                    </div>
                </div>
                <div class="field-row single">
                    <div class="field">
                        <label>Internal admin notes</label>
                        <textarea id="adminNotes" name="admin_notes" placeholder="e.g. Access approved by platform owner for verification operations coverage."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <div class="action-bar-left">Static UI only. Persistence and RBAC wiring are deferred.</div>
            <div class="action-bar-right">
                <button class="btn-secondary" type="button" onclick="saveDraft()">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>
                    Save Draft
                </button>
                <button class="btn-primary" type="submit" form="createAdminForm">
                    <svg class="icon"><use href="/assets/icons/sprite.svg#icon-security-and-access-svg-viewbox-0"></use></svg>
                    Create Admin
                </button>
            </div>
        </div>
    </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const page = {
                init() {
                    this.bindRoleCards();
                    this.bindPermissionToggles();
                    this.updatePermissionCount();
                },
                bindRoleCards() {
                    document.querySelectorAll('#roleGrid .type-card').forEach((card) => {
                        card.addEventListener('click', () => this.selectRole(card));
                    });
                },
                selectRole(selectedCard) {
                    document.querySelectorAll('#roleGrid .type-card').forEach((card) => {
                        const input = card.querySelector('input[name="adminRole"]');
                        card.classList.remove('checked');
                        if (input) input.checked = false;
                    });
                    selectedCard.classList.add('checked');
                    const input = selectedCard.querySelector('input[name="adminRole"]');
                    if (input) input.checked = true;
                },
                bindPermissionToggles() {
                    document.querySelectorAll('.permission-toggle').forEach((toggle) => {
                        toggle.addEventListener('change', () => this.updatePermissionCount());
                    });
                },
                updatePermissionCount() {
                    const count = document.querySelectorAll('.permission-toggle:checked').length;
                    const target = document.getElementById('permissionCount');
                    if (target) target.textContent = count + ' groups selected';
                },
                createAdmin() {
                    const name = this.getInputValue('fullName');
                    const email = this.getInputValue('email');
                    if (!name) {
                        alert('Please enter a full name before creating the admin account.');
                        return;
                    }
                    if (!email) {
                        alert('Please enter an email address before creating the admin account.');
                        return;
                    }
                    this.showSuccess(email);
                },
                saveDraft() {
                    const banner = document.getElementById('successBanner');
                    const detail = document.getElementById('successDetail');
                    if (detail) detail.textContent = 'The static admin draft has been saved for review.';
                    if (banner) banner.classList.add('show');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                getInputValue(id) {
                    const input = document.getElementById(id);
                    return input ? input.value.trim() : '';
                },
                showSuccess(email) {
                    const banner = document.getElementById('successBanner');
                    const detail = document.getElementById('successDetail');
                    if (detail) detail.textContent = 'An admin invitation has been queued for ' + email + '.';
                    if (banner) banner.classList.add('show');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            };
            window.createAdmin = () => page.createAdmin();
            window.saveDraft = () => page.saveDraft();
            document.addEventListener('DOMContentLoaded', () => page.init());
        })();
    </script>
@endpush
