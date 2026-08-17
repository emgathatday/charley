@extends('layouts.rebuild-dashboard')

@section('title', 'Question Detail')

@section('content')
@php
    $answers = collect($questionAnswers ?? []);
    $status = $question['status'] ?? 'published';
    $isFlagged = $status === 'flagged' || (int) ($question['warning_count'] ?? 0) > 0;
    $isPublished = in_array($status, ['published', 'active'], true);
    $publishClass = $isPublished ? 'published' : 'unpublished';
    $publishLabel = $question['status_label'] ?? ($isPublished ? 'Active' : Str::headline($status));
    $attachments = collect($question['attachments'] ?? []);
    $topic = $question['domains'] ?: ($question['theme'] ?? 'Open discussion');
    $topicLabel = trim(explode(',', $topic)[0] ?? 'Open discussion');
    $postedAt = ! empty($question['created_at']) ? \Illuminate\Support\Carbon::parse($question['created_at'])->format('d M Y, H:i') : '-';
    $answerCount = $question['answer_count'] ?? $answers->count();
    $authorName = $question['author'] ?? 'Member';
    $isAnonymous = (bool) ($question['is_anonymous'] ?? false);
    $initials = fn ($name) => collect(explode(' ', (string) $name))->filter()->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->take(2)->implode('') ?: 'QA';
@endphp

    @include('templates.components.alert-session')

    <a class="back-link" href="{{ route('admin.dashboard.qa.index') }}">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-to-account-penalty-and"></use></svg>
        Back to Q&amp;A Management
    </a>

    <div class="flag-banner @unless($isFlagged) d-none @endunless" id="qdFlagBanner">
        <div class="flag-banner-icon"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-diamond-and-gold-partner-posts"></use></svg></div>
        <div class="flag-banner-body">
            <div class="flag-banner-title">This question has been flagged for review</div>
            <div class="flag-banner-text" id="qdFlagBannerText">Community or automated moderation marked this question for admin review.</div>
            <div class="flag-banner-actions">
                <form method="POST" action="{{ route('admin.dashboard.qa.questions.status', [$question['id'], 'published']) }}">
                    @csrf
                    <button class="flag-banner-safe-btn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>Mark as Safe</button>
                </form>
                <span class="flag-banner-dismiss-hint"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-partner-tier-diamond-gold"></use></svg>Marking as safe removes this warning and restores the question.</span>
            </div>
        </div>
    </div>

    <div class="detail-sidebar-grid qd-page-grid">
        <div class="detail-sidebar-main">
            <div class="qd-main-card">
                <div class="qa-badge-row" id="qdBadges">
                    <span class="qa-mini-badge plant">{{ $question['plant'] }}</span>
                    <span class="qa-mini-badge topic">{{ $topicLabel }}</span>
                    <span class="publish-pill {{ $publishClass }}" id="qdPublishPill"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>{{ $publishLabel }}</span>
                    @if ($isFlagged)
                        <span class="status-pill flagged"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-moderation-and-reports-path-d-m14"></use></svg>Flagged</span>
                    @endif
                </div>
                <div class="qd-title" id="qdTitle">{{ $question['title'] }}</div>
                <div class="qd-meta-row" id="qdMeta"><span>Posted by {{ $authorName }}</span><span>-</span><span>{{ $postedAt }}</span><span>-</span><span>{{ $question['views'] ?? 0 }} views</span></div>

                <div class="qd-section-label">Question</div>
                <div class="qd-question-text" id="qdQuestionText">{{ $question['body'] }}</div>

                @if ($isAnonymous)
                    <div class="qd-admin-note" id="qdAdminNote"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-attachments"></use></svg><span id="qdAdminNoteText">Admin-only: real identity retained for moderation. {{ $question['author_email'] ?? '' }}</span></div>
                @endif

                <div id="qdAttachWrap" @class(['d-none' => $attachments->isEmpty()])>
                    <div class="qd-section-label qd-attach-label">Attachments</div>
                    <div class="qd-attach-list" id="qdAttachList">
                        @foreach ($attachments as $attachment)
                            <div class="qd-attach-item"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-attachments"></use></svg><span>{{ $attachment->original_name }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="qd-main-card">
                <div class="qd-section-label" id="qdAnswersLabel">Answers ({{ $answerCount }})</div>
                <div id="qdAnswersList">
                    @forelse ($answers as $index => $answer)
                        <div class="qd-answer @if($answer['featured'] ?? false) featured-answer @endif" id="answerBlock{{ $index }}">
                            <div class="qd-answer-head">
                                <div class="qd-answer-author">
                                    <div class="qd-author-avatar avatar-default">{{ $initials($answer['author'] ?? 'Answer') }}</div>
                                    <div><div class="qd-answer-author-name">{{ $answer['author'] }}</div><div class="qd-answer-author-role">{{ Str::headline($answer['confidence'] ?? 'unrated') }} confidence</div></div>
                                </div>
                                <div class="qd-answer-badges" id="answerBadges{{ $index }}">
                                    @if ($answer['featured'] ?? false)
                                        <span class="status-pill featured"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-featured-star"></use></svg>Admin Featured</span>
                                    @endif
                                    @if ($answer['warning'] ?? null)
                                        <span class="status-pill flagged"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-moderation-and-reports-path-d-m14"></use></svg>{{ Str::headline($answer['warning']->severity) }} Warning</span>
                                    @endif
                                </div>
                            </div>
                            @if ($answer['warning'] ?? null)
                                <div class="flag-banner-text">{{ $answer['warning']->reason }}</div>
                            @endif
                            <div class="qd-answer-text">{{ $answer['body'] }}</div>
                            <div id="nestedReplies{{ $index }}"></div>
                            <div class="qd-answer-foot">
                                <div class="qd-answer-foot-left">
                                    @if ($answer['featured'] ?? false)
                                        <form method="POST" action="{{ route('admin.dashboard.qa.answers.unfeature', $answer['id']) }}">@csrf<button class="qd-feature-btn active" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-featured-star"></use></svg>Featured - Unfeature</button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.dashboard.qa.answers.feature', $answer['id']) }}">@csrf<input type="hidden" name="confidence_level" value="high"><input type="hidden" name="admin_rank_order" value="1"><button class="qd-feature-btn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-admin-featured-star"></use></svg>Feature Answer</button></form>
                                    @endif
                                    <button class="qd-delete-answer-btn" type="button" onclick="showToast('Delete answer is UI-only until behavior is confirmed','red')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-delete"></use></svg>Delete Answer</button>
                                </div>
                                <button class="qd-reply-btn" type="button" onclick="toggleAnswerReply({{ $index }})"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-reply-to-answer"></use></svg>Reply</button>
                            </div>
                            <div class="qd-reply-composer d-none" id="replyComposer{{ $index }}">
                                <textarea class="qd-reply-textarea" id="replyText{{ $index }}" placeholder="Reply to {{ $answer['author'] }}'s answer..."></textarea>
                                <div class="qd-reply-actions"><button class="btn-ghost" type="button" onclick="toggleAnswerReply({{ $index }})">Cancel</button><button class="btn-primary" type="button" onclick="showToast('Admin reply persistence is waiting for a confirmed route','blue')">Publish Reply</button></div>
                            </div>
                        </div>
                    @empty
                        <div class="qd-empty-answers">No answers yet - this question is still open.</div>
                    @endforelse
                </div>
            </div>

            <div class="qd-main-card">
                <div class="qd-section-label">Post an Admin Reply</div>
                <textarea class="qd-reply-textarea qd-main-reply-textarea" id="qdMainReplyText" placeholder="Write an official admin answer to this question..."></textarea>
                <div class="qd-reply-actions"><button class="btn-ghost" type="button" onclick="document.getElementById('qdMainReplyText').value=''">Clear</button><button class="btn-primary" type="button" onclick="publishMainReply()"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-create-user-svg-viewbox-0-0"></use></svg>Publish Answer</button></div>
            </div>
        </div>

        <div class="detail-sidebar-aside">
            <div class="info-card" id="posterCard">
                <div class="info-card-title">Posted By</div>
                <div class="qd-poster-head">
                    <div class="qd-author-avatar qd-author-avatar-lg" id="posterAvatar">{{ $initials($authorName) }}</div>
                    <div class="qd-poster-text"><div class="qd-poster-name" id="posterName">{{ $authorName }}</div><div class="qd-poster-role" id="posterRole">{{ $question['author_meta'] ?? $question['author_role'] ?? 'Community member' }}</div></div>
                </div>
                @if ($isAnonymous)
                    <div class="qd-admin-note qd-admin-note-compact" id="posterAdminNote"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-attachments"></use></svg><span id="posterAdminNoteText">Admin-only: identity visible for moderation.</span></div>
                @endif
                <div class="info-row info-row-separated"><span class="info-row-label">Account Type</span><span class="info-row-value" id="posterAccountType">{{ $question['author_role'] ?? 'Community member' }}</span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Email</span><span class="info-row-value" id="posterCompany">{{ $question['author_email'] ?? 'No email recorded' }}</span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Member Since</span><span class="info-row-value" id="posterMemberSince">-</span></div>
                <button class="qd-action-btn qd-action-spaced" type="button" onclick="showToast('Profile route is not confirmed for this task','blue')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-user-management-12-svg-viewbox-0"></use></svg>View Full Profile</button>
            </div>

            <div class="info-card">
                <div class="info-card-title">Question Info</div>
                <div class="info-row info-row-separated"><span class="info-row-label">Publish Status</span><span class="info-row-value" id="infoPublish"><span class="publish-pill qd-info-publish-pill {{ $publishClass }}" id="infoPublishPill">{{ $publishLabel }}</span></span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Plant Type</span><span class="info-row-value" id="infoPlant">{{ $question['plant'] }}</span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Topic</span><span class="info-row-value" id="infoTopic">{{ $topicLabel }}</span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Posted</span><span class="info-row-value" id="infoPosted">{{ $postedAt }}</span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Views</span><span class="info-row-value" id="infoViews">{{ $question['views'] ?? 0 }}</span></div>
                <div class="info-row info-row-separated"><span class="info-row-label">Answers</span><span class="info-row-value" id="infoAnswers">{{ $answerCount }}</span></div>
            </div>

            <div class="info-card">
                <div class="info-card-title">Admin Actions</div>
                <form method="POST" action="{{ route('admin.dashboard.qa.questions.status', [$question['id'], 'flagged']) }}">@csrf<button class="qd-action-btn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-mark-for-review-svg-viewbox-0"></use></svg>Mark for Review</button></form>
                <form method="POST" action="{{ route('admin.dashboard.qa.questions.status', [$question['id'], 'hidden']) }}">@csrf<button class="qd-action-btn neutral" id="unpublishBtn" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-un-publish-question-svg-viewbox-0-0"></use></svg><span id="unpublishBtnText">Un-publish Question</span></button></form>
                <button class="qd-action-btn danger" type="button" onclick="showToast('Delete Question is UI-only until behavior is confirmed','red')"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-delete"></use></svg>Delete Question</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>
@endsection

@push('scripts')
<script>
function toggleAnswerReply(index){
    const el = document.getElementById('replyComposer' + index);
    if (!el) return;
    el.classList.toggle('d-none');
    const input = document.getElementById('replyText' + index);
    if (input && !el.classList.contains('d-none')) input.focus();
}
function publishMainReply(){
    const input = document.getElementById('qdMainReplyText');
    if (!input || !input.value.trim()) { showToast('Write an answer before publishing','red'); return; }
    showToast('Admin reply persistence is waiting for a confirmed route','blue');
}
</script>
@endpush