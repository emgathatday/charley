@extends("layouts.rebuild-dashboard")

@section("title", "Create Q&A Question")

@section("content")
    @include("templates.components.alert-session")
    @php
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
        $titleValue = old("title", "");
        $bodyValue = old("body", "");
        $questionMode = old("question_type", "single");
        $selectedPlantTypeId = (string) old("plant_type_id", "");
        $selectedDomainIds = array_map("strval", old("knowledge_domain_ids", []));
        $selectedMediaIds = array_map("strval", old("attachment_media_ids", []));
        $statusValue = old("status", "pending");
        $statusLabel = $statusValue === "published" ? "Active" : "Pending";
        $selectedPlantType = collect($plantTypes)->firstWhere("id", old("plant_type_id"));
        $selectedTheme = collect($weeklyThemes)->firstWhere("id", old("weekly_theme_id"));
        $previewPlant = $selectedPlantType->name ?? "Plant Type";
        $previewTheme = $selectedTheme->title ?? "General Topic";
        $choiceRows = old("choices", [
            ["choice_text" => "Rising methane slip at the same outlet temperature and feed rate.", "explanation" => "The catalyst is losing activity, so conversion drops unless temperature or residence time is adjusted.", "sort_order" => 1, "is_correct" => true],
            ["choice_text" => "Lower tube skin temperature at unchanged duty.", "explanation" => "This usually points away from catalyst deactivation and needs a separate heat-transfer check.", "sort_order" => 2, "is_correct" => false],
            ["choice_text" => "Reduced stack oxygen after burner tuning.", "explanation" => "Burner and excess-air changes can affect furnace behavior but are not the clearest catalyst signal.", "sort_order" => 3, "is_correct" => false],
            ["choice_text" => "Stable hydrogen yield with lower feed pressure.", "explanation" => "This describes a pressure-side change, not a direct sign of catalyst activity loss.", "sort_order" => 4, "is_correct" => false],
        ]);
    @endphp

    <a href="{{ route("admin.dashboard.qa.index") }}" class="back-link">
        <svg class="icon"><use href="/assets/icons/sprite.svg#icon-back-account-penalty"></use></svg>
        Back to Q&amp;A Management
    </a>

    <form class="qa-create-form" action="{{ route("admin.dashboard.qa.questions.store") }}" method="POST">
        @csrf
        <input type="hidden" id="adminQuestionMode" name="question_mode" value="{{ old("question_mode", old("on_behalf_of_partner_id") ? "admin_on_behalf" : "admin_seed") }}">

        <div class="page-head knowledge-hero">
            <div class="page-head-left">
                <div class="company-logo knowledge-domain-logo"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-quiz-and-question-bank"></use></svg></div>
                <div>
                    <div class="page-title-row"><div class="page-title">Create Q&amp;A Question</div><span class="badge knowledge-badge-warning">{{ $statusLabel }}</span><span class="badge knowledge-badge-info">Seed question</span></div>
                    <div class="page-sub"><span>questions</span><span class="sep"></span><span>Plant: {{ $previewPlant }}</span><span class="sep"></span><span>Domain: {{ $previewTheme }}</span></div>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" type="button" onclick="showToast(&quot;Draft save waits for confirmed draft workflow&quot;,&quot;blue&quot;)">Save draft</button>
                <button class="btn btn-primary" type="submit"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-save-as-draft-svg-viewbox-0"></use></svg>Create question</button>
            </div>
        </div>

        <div class="question-create-layout">
            <div class="question-create-main">
                <div class="form-card question-card">
                    <div class="form-card-header"><div class="form-card-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-quiz-and-question-bank"></use></svg></div><div><div class="form-card-title">Question content</div><div class="form-card-sub">This question is created for the Admin Q&amp;A workflow.</div></div></div>
                    <div class="form-card-body">
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <div class="col"><div class="field"><label>Question mode <span class="req">*</span></label><label class="question-mode-toggle" for="questionModeToggle"><input type="checkbox" id="questionModeToggle" name="question_type" value="multi" @checked($questionMode === "multi")><span class="question-mode-track"><strong>Single choice</strong><strong>Multi choice</strong></span></label><div class="field-hint">Single accepts one correct toggle. Multi allows more than one correct answer.</div>@error('question_type')<div class="field-error">{{ $message }}</div>@enderror</div></div>
                            <div class="col"><div class="field"><label for="status">Status</label><select id="status" name="status"><option value="pending" @selected($statusValue === "pending")>pending</option><option value="published" @selected($statusValue === "published")>published</option><option value="hidden" @selected($statusValue === "hidden")>hidden</option><option value="flagged" @selected($statusValue === "flagged")>flagged</option></select>@error('status')<div class="field-error">{{ $message }}</div>@enderror<div class="field-hint">Use pending until the question is reviewed.</div></div></div>
                            <div class="col"><div class="field"><label for="weekly_theme_id">Weekly theme</label><select id="weekly_theme_id" name="weekly_theme_id"><option value="">No weekly theme</option>@foreach ($weeklyThemes as $weeklyTheme)<option value="{{ $weeklyTheme->id }}" @selected((string) old("weekly_theme_id") === (string) $weeklyTheme->id)>{{ $weeklyTheme->title }}</option>@endforeach</select>@error('weekly_theme_id')<div class="field-error">{{ $message }}</div>@enderror</div></div>
                            <div class="col"><div class="field"><label for="on_behalf_of_partner_id">On behalf partner</label><select id="on_behalf_of_partner_id" name="on_behalf_of_partner_id"><option value="">Admin seed question</option>@foreach ($partnerProfiles as $partner)<option value="{{ $partner->id }}" @selected((string) old("on_behalf_of_partner_id") === (string) $partner->id)>{{ $partner->company_name }}</option>@endforeach</select>@error('on_behalf_of_partner_id')<div class="field-error">{{ $message }}</div>@enderror</div></div>
                            <div class="col"><div class="field field-full"><label for="title">Question title <span class="req">*</span></label><input id="title" type="text" name="title" value="{{ $titleValue }}" placeholder="How should operators troubleshoot rising vibration after startup?">@error('title')<div class="field-error">{{ $message }}</div>@enderror</div></div>
                            <div class="col"><div class="field field-full"><label for="body">Question text <span class="req">*</span></label><textarea id="body" class="question-textarea" name="body" placeholder="Add operating context, symptoms, and what guidance the team needs.">{{ $bodyValue }}</textarea>@error('body')<div class="field-error">{{ $message }}</div>@enderror</div></div>
                            <div class="col"><div class="field"><label for="is_anonymous">Public identity</label><label class="question-mode-toggle" for="is_anonymous"><input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" @checked(old("is_anonymous"))><span class="question-mode-track"><strong>Named</strong><strong>Anonymous</strong></span></label><div class="field-hint">Anonymous changes public display only; admins still see the real identity.</div>@error('is_anonymous')<div class="field-error">{{ $message }}</div>@enderror</div></div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header"><div class="form-card-icon blue"><svg class="icon"><use href="/assets/icons/sprite.svg#icon-settings-2"></use></svg></div><div><div class="form-card-title">Plant type &amp; knowledge domains</div><div class="form-card-sub">Plant type persists through plant_types.id; domains persist through question_domain_links.</div></div></div>
                    <div class="form-card-body">
                        <div class="field"><label>Plant type</label><div class="checkbox-chip-group">@foreach ($plantTypes as $plantType)<label class="checkbox-chip"><input type="radio" name="plant_type_id" value="{{ $plantType->id }}" @checked($selectedPlantTypeId === (string) $plantType->id)><span>{{ $plantType->name }}</span></label>@endforeach</div>@error('plant_type_id')<div class="field-error">{{ $message }}</div>@enderror</div>
                        <div class="field field-full"><label>Knowledge domains</label><div class="checkbox-chip-group">@forelse ($knowledgeDomains as $domain)<label class="checkbox-chip"><input type="checkbox" name="knowledge_domain_ids[]" value="{{ $domain->id }}" @checked(in_array((string) $domain->id, $selectedDomainIds, true))><span>{{ $domain->name }}</span></label>@empty<span class="qa-mini-badge">No active knowledge domains</span>@endforelse</div>@error('knowledge_domain_ids')<div class="field-error">{{ $message }}</div>@enderror @error('knowledge_domain_ids.*')<div class="field-error">{{ $message }}</div>@enderror</div>
                    </div>
                </div>

                <div class="table-wrap question-choice-wrap">
                    <div class="table-header table-head-panel">
                        <div class="table-head-main"><div class="table-title">Answer choices</div><div class="table-meta">Use the correct toggles according to the selected question mode.</div></div>
                        <div class="table-head-actions"><button class="btn btn-outline btn-sm" type="button" onclick="showToast(&quot;Reorder behavior waits for confirmed persistence&quot;,&quot;blue&quot;)">Reorder</button><button class="btn btn-primary btn-sm" type="button" onclick="showToast(&quot;Add choice is UI-only until store behavior is confirmed&quot;,&quot;blue&quot;)">Add choice</button></div>
                    </div>
                    <div class="question-choice-list">
                        @foreach ($choiceRows as $index => $choice)
                            @php($choiceKey = chr(65 + $index))
                            @php($isCorrect = (bool) ($choice["is_correct"] ?? false))
                            <div @class(["question-choice-row", "is-correct" => $isCorrect])>
                                <div class="question-choice-key">{{ $choiceKey }}</div>
                                <div class="question-choice-body">
                                    <div class="field"><label>Choice text</label><textarea name="choices[{{ $index }}][choice_text]">{{ $choice["choice_text"] ?? "" }}</textarea></div>
                                    <div class="field"><label>Explanation</label><textarea name="choices[{{ $index }}][explanation]">{{ $choice["explanation"] ?? "" }}</textarea></div>
                                </div>
                                <div class="question-choice-controls"><label class="question-correct-toggle"><input class="question-correct-input" type="checkbox" name="choices[{{ $index }}][is_correct]" value="1" @checked($isCorrect)><span></span><strong>Correct</strong></label><input class="question-sort-input" name="choices[{{ $index }}][sort_order]" type="number" value="{{ $choice["sort_order"] ?? $index + 1 }}" aria-label="Sort order {{ $choiceKey }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="table-wrap question-choice-wrap">
                    <div class="table-header table-head-panel"><div class="table-head-main"><div class="table-title">Attachments</div><div class="table-meta">Use media_files.id only; direct upload and picker behavior remain TODO-safe.</div></div><div class="table-head-actions"><button class="btn btn-outline btn-sm" type="button" onclick="showToast(&quot;Media picker waits for a confirmed attachment workflow&quot;,&quot;blue&quot;)">Attach</button></div></div>
                    <div class="form-card-body"><div class="checkbox-chip-group">@forelse ($mediaFiles as $media)<label class="checkbox-chip"><input type="checkbox" name="attachment_media_ids[]" value="{{ $media->id }}" @checked(in_array((string) $media->id, $selectedMediaIds, true))><span>#{{ $media->id }} {{ $media->original_name }}</span></label>@empty<span class="qa-mini-badge attach"><x-admin.icon name="files" />Media picker pending</span>@endforelse</div>@error('attachment_media_ids')<div class="field-error">{{ $message }}</div>@enderror @error('attachment_media_ids.*')<div class="field-error">{{ $message }}</div>@enderror</div>
                </div>
            </div>

            <div class="question-create-side">
                <div class="side-card">
                    <div class="card card-padded question-preview-card"><div class="card-title">Question preview</div><div class="question-preview-domain">{{ $previewTheme }}</div><div class="question-preview-text" id="questionPreviewText">{{ $bodyValue ?: "What operating symptom indicates early catalyst deactivation?" }}</div><div class="question-preview-options">@foreach ($choiceRows as $index => $choice)<span @class(["is-correct" => (bool) ($choice["is_correct"] ?? false)])>{{ chr(65 + $index) }}. {{ Str::limit($choice["choice_text"] ?? "Choice text", 28) }}</span>@endforeach</div></div>
                    <div class="card card-padded"><div class="card-title">Record mapping</div><div class="knowledge-schema-list"><span>questions</span><span>question_domain_links</span><span>media_files</span><span>users</span></div><div class="knowledge-warning-note">Admin identity is stored by the backend. Partner attribution uses on_behalf_of_partner_id when selected.</div></div>
                    <div class="card card-padded decision-panel"><div class="card-title">Admin actions</div><div class="decision-actions"><button class="btn btn-primary btn-block-spaced" type="submit">Create question</button><button class="btn btn-outline btn-block-spaced" type="button" onclick="showToast(&quot;Draft save waits for confirmed draft workflow&quot;,&quot;blue&quot;)">Save draft</button><a href="{{ route("admin.dashboard.qa.index") }}" class="btn btn-ghost btn-block-spaced">Cancel</a></div></div>
                </div>
            </div>
        </div>

        <div class="action-bar"><span class="action-bar-note">Required: title, question text, mode, and valid selected IDs.</span><div class="action-bar-right"><a href="{{ route("admin.dashboard.qa.index") }}" class="btn btn-outline">Cancel</a><button class="btn btn-primary" type="submit">Create question</button></div></div>
    </form>

    <div class="toast-container" id="toastContainer"></div>
@endsection

@push("scripts")
    <script src="{{ asset("assets/js/pages/qa-management.js") }}"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const modeToggle = document.getElementById("questionModeToggle");
        const questionText = document.getElementById("body");
        const partnerSelect = document.getElementById("on_behalf_of_partner_id");
        const adminQuestionMode = document.getElementById("adminQuestionMode");
        const previewText = document.getElementById("questionPreviewText");
        const correctInputs = Array.from(document.querySelectorAll(".question-correct-input"));

        function syncChoiceRows(activeInput) {
            if (activeInput && activeInput.checked && modeToggle && !modeToggle.checked) {
                correctInputs.forEach(function (input) { if (input !== activeInput) input.checked = false; });
            }
            correctInputs.forEach(function (input) {
                const row = input.closest(".question-choice-row");
                if (row) row.classList.toggle("is-correct", input.checked);
            });
        }

        correctInputs.forEach(function (input) {
            input.addEventListener("change", function () { syncChoiceRows(input); });
        });

        function syncAdminMode() {
            if (adminQuestionMode && partnerSelect) {
                adminQuestionMode.value = partnerSelect.value ? "admin_on_behalf" : "admin_seed";
            }
        }

        if (partnerSelect) {
            partnerSelect.addEventListener("change", syncAdminMode);
        }

        if (modeToggle) {
            modeToggle.addEventListener("change", function () {
                if (!modeToggle.checked) {
                    correctInputs.filter(function (input) { return input.checked; }).slice(1).forEach(function (input) { input.checked = false; });
                }
                syncChoiceRows();
            });
        }

        if (questionText && previewText) {
            questionText.addEventListener("input", function () {
                previewText.textContent = questionText.value.trim() || "What operating symptom indicates early catalyst deactivation?";
            });
        }

        syncAdminMode();
        syncChoiceRows();
    });
    </script>
@endpush
