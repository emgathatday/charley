<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QaModerationWarning;
use App\Services\Qa\WarningFreezeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QaModerationController extends Controller
{
    public function rules(Request $request)
    {
        return view('admin.qa.moderation-rules', [
            'rules' => $this->rulesData(),
            'filters' => $request->only(['rule_type', 'target_type', 'severity', 'is_active']),
        ]);
    }

    public function warnings(Request $request)
    {
        return view('admin.qa.warnings', [
            'warnings' => $this->warningsData($request),
            'warningSummaries' => $this->warningSummaries(),
            'filters' => $request->only(['status', 'source', 'severity']),
        ]);
    }

    public function storeRule(Request $request)
    {
        $payload = $this->rulePayload($request);

        if (Schema::hasTable('qa_moderation_rules')) {
            DB::table('qa_moderation_rules')->insert([
                ...$payload,
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Moderation rule saved.');
    }

    public function updateRule(Request $request, int $rule)
    {
        if (Schema::hasTable('qa_moderation_rules')) {
            DB::table('qa_moderation_rules')->where('id', $rule)->update([
                ...$this->rulePayload($request),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Moderation rule updated.');
    }

    public function toggleRule(int $rule)
    {
        if (Schema::hasTable('qa_moderation_rules')) {
            $current = DB::table('qa_moderation_rules')->where('id', $rule)->first();
            if ($current) {
                DB::table('qa_moderation_rules')->where('id', $rule)->update([
                    'is_active' => ! (bool) $current->is_active,
                    'updated_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Moderation rule toggled.');
    }

    public function reviewWarning(WarningFreezeService $warningFreezeService, int $warning, string $status)
    {
        abort_unless(in_array($status, ['safe', 'dismissed', 'confirmed'], true), 404);

        if (Schema::hasTable('qa_moderation_warnings') && $model = QaModerationWarning::query()->find($warning)) {
            $warningFreezeService->markReviewed($model, $status, auth()->id());
        }

        return back()->with('success', 'Warning review saved.');
    }

    private function rulePayload(Request $request): array
    {
        $ruleType = $request->get('rule_type', 'keyword');

        return [
            'name' => (string) $request->string('name'),
            'rule_type' => $ruleType,
            'target_type' => $request->get('target_type', 'both'),
            'config' => json_encode($this->friendlyConfig($request, $ruleType)),
            'severity' => $request->get('severity', 'medium'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function friendlyConfig(Request $request, string $ruleType): array
    {
        return match ($ruleType) {
            'keyword' => ['keywords' => collect(explode(',', (string) $request->string('keywords')))->map(fn (string $item): string => trim($item))->filter()->values()->all()],
            'max_links' => ['max_links' => $request->integer('max_links')],
            'min_length' => ['min_length' => $request->integer('min_length')],
            'regex' => ['pattern' => (string) $request->string('pattern')],
            'attachment_type' => ['blocked_mime_types' => collect(explode(',', (string) $request->string('blocked_mime_types')))->map(fn (string $item): string => trim($item))->filter()->values()->all()],
            'custom' => ['reason' => (string) $request->string('custom_reason'), 'always_warn' => $request->boolean('always_warn')],
            default => [],
        };
    }

    private function rulesData()
    {
        if (! Schema::hasTable('qa_moderation_rules')) {
            return collect($this->demoRules());
        }

        $rules = DB::table('qa_moderation_rules')
            ->leftJoin('users', 'users.id', '=', 'qa_moderation_rules.created_by')
            ->select('qa_moderation_rules.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
            ->orderByDesc('qa_moderation_rules.is_active')
            ->orderBy('qa_moderation_rules.rule_type')
            ->get()
            ->map(function (object $rule): object {
                $rule->config_text = json_encode(json_decode($rule->config ?: '{}', true), JSON_PRETTY_PRINT);
                $rule->creator_name = $this->displayName($rule);

                return $rule;
            });

        return $rules->isNotEmpty() ? $rules : collect($this->demoRules());
    }

    private function warningsData(Request $request)
    {
        if (! Schema::hasTable('qa_moderation_warnings')) {
            return collect($this->demoWarnings());
        }

        $warnings = DB::table('qa_moderation_warnings')
            ->leftJoin('users', 'users.id', '=', 'qa_moderation_warnings.user_id')
            ->leftJoin('qa_user_warning_summaries', 'qa_user_warning_summaries.user_id', '=', 'qa_moderation_warnings.user_id')
            ->select('qa_moderation_warnings.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email', 'qa_user_warning_summaries.confirmed_warning_count', 'qa_user_warning_summaries.is_frozen')
            ->when($request->get('status'), fn ($query, $status) => $query->where('qa_moderation_warnings.status', $status))
            ->when(! $request->get('status'), fn ($query) => $query->where('qa_moderation_warnings.status', 'pending_review'))
            ->when($request->get('source'), fn ($query, $source) => $query->where('qa_moderation_warnings.source', $source))
            ->when($request->get('severity'), fn ($query, $severity) => $query->where('qa_moderation_warnings.severity', $severity))
            ->latest('qa_moderation_warnings.created_at')
            ->get()
            ->map(function (object $warning): object {
                $warning->user_name = $this->displayName($warning, $warning->user_id);
                $warning->context = $this->warnableContext($warning);
                $warning->evidence_text = json_encode(json_decode($warning->evidence ?: '{}', true), JSON_PRETTY_PRINT);
                $warning->confirmed_warning_count ??= 0;
                $warning->is_frozen = (bool) ($warning->is_frozen ?? false);

                return $warning;
            });

        return $warnings->isNotEmpty() ? $warnings : collect($this->demoWarnings());
    }

    private function warningSummaries()
    {
        if (! Schema::hasTable('qa_user_warning_summaries')) {
            return collect($this->demoWarningSummaries());
        }

        $summaries = DB::table('qa_user_warning_summaries')
            ->leftJoin('users', 'users.id', '=', 'qa_user_warning_summaries.user_id')
            ->select('qa_user_warning_summaries.*', 'users.username as user_username', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'users.email as user_email')
            ->orderByDesc('qa_user_warning_summaries.confirmed_warning_count')
            ->limit(10)
            ->get()
            ->map(function (object $summary): object {
                $summary->user_name = $this->displayName($summary, $summary->user_id);

                return $summary;
            });

        return $summaries->isNotEmpty() ? $summaries : collect($this->demoWarningSummaries());
    }

    private function warnableContext(object $warning): string
    {
        if ($warning->warnable_type === 'question' && Schema::hasTable('questions')) {
            return DB::table('questions')->where('id', $warning->warnable_id)->value('title') ?: 'Question #'.$warning->warnable_id;
        }

        if ($warning->warnable_type === 'answer' && Schema::hasTable('answers')) {
            $answer = DB::table('answers')->where('id', $warning->warnable_id)->first();

            return $answer ? Str::limit($answer->body, 80) : 'Answer #'.$warning->warnable_id;
        }

        return Str::headline($warning->warnable_type).' #'.$warning->warnable_id;
    }

    private function displayName(object $row, ?int $userId = null): string
    {
        $fullName = trim(implode(' ', array_filter([$row->user_first_name ?? null, $row->user_last_name ?? null])));

        return $fullName ?: ($row->user_username ?? null) ?: ($row->user_email ?? null) ?: ($userId ? 'Member #'.$userId : 'System');
    }

    private function demoRules(): array
    {
        return [
            (object) ['id' => 9501, 'name' => 'Blocked outage keywords', 'rule_type' => 'keyword', 'target_type' => 'both', 'config_text' => '{"keywords":["exploit","bypass"]}', 'severity' => 'high', 'is_active' => true, 'creator_name' => 'QA Admin'],
            (object) ['id' => 9502, 'name' => 'Maximum external links', 'rule_type' => 'max_links', 'target_type' => 'question', 'config_text' => '{"max_links":2}', 'severity' => 'medium', 'is_active' => true, 'creator_name' => 'QA Admin'],
            (object) ['id' => 9503, 'name' => 'Minimum answer detail', 'rule_type' => 'min_length', 'target_type' => 'answer', 'config_text' => '{"min_length":80}', 'severity' => 'low', 'is_active' => true, 'creator_name' => 'QA Admin'],
            (object) ['id' => 9504, 'name' => 'Regex for phone numbers', 'rule_type' => 'regex', 'target_type' => 'both', 'config_text' => '{"pattern":"/[0-9]{10,}/"}', 'severity' => 'medium', 'is_active' => false, 'creator_name' => 'QA Admin'],
            (object) ['id' => 9505, 'name' => 'Attachment type guard', 'rule_type' => 'attachment_type', 'target_type' => 'both', 'config_text' => '{"blocked_mime_types":["application/x-msdownload"]}', 'severity' => 'high', 'is_active' => true, 'creator_name' => 'QA Admin'],
            (object) ['id' => 9506, 'name' => 'Custom partner disclosure check', 'rule_type' => 'custom', 'target_type' => 'question', 'config_text' => '{"reason":"Partner disclosure required"}', 'severity' => 'low', 'is_active' => true, 'creator_name' => 'QA Admin'],
        ];
    }

    private function demoWarnings(): array
    {
        return [
            (object) ['id' => 9601, 'user_id' => 9401, 'user_name' => 'Aisha Tran', 'warnable_type' => 'question', 'warnable_id' => 9101, 'context' => 'Demo: compressor vibration after startup', 'source' => 'system_rule', 'severity' => 'high', 'reason' => 'Matched blocked keyword: bypass.', 'evidence_text' => '{"rule_id":9501,"keyword":"bypass"}', 'status' => 'pending_review', 'confirmed_warning_count' => 2, 'is_frozen' => false],
            (object) ['id' => 9602, 'user_id' => 9402, 'user_name' => 'Minh Nguyen', 'warnable_type' => 'answer', 'warnable_id' => 9203, 'context' => 'Compare exchanger approach temperature against clean baseline', 'source' => 'ai', 'severity' => 'medium', 'reason' => 'AI placeholder flagged uncertain safety instruction.', 'evidence_text' => '{"provider":"placeholder"}', 'status' => 'pending_review', 'confirmed_warning_count' => 1, 'is_frozen' => false],
            (object) ['id' => 9603, 'user_id' => 9403, 'user_name' => 'Carlos Rivera', 'warnable_type' => 'question', 'warnable_id' => 9103, 'context' => 'Demo: exchanger fouling trend needs review', 'source' => 'admin', 'severity' => 'low', 'reason' => 'Admin warning for missing operating context.', 'evidence_text' => '{"note":"Manual admin review"}', 'status' => 'confirmed', 'confirmed_warning_count' => 3, 'is_frozen' => true],
        ];
    }

    private function demoWarningSummaries(): array
    {
        return [
            (object) ['user_id' => 9403, 'user_name' => 'Carlos Rivera', 'confirmed_warning_count' => 3, 'is_frozen' => true, 'last_warning_at' => now()->subHours(2)->format('Y-m-d H:i')],
            (object) ['user_id' => 9401, 'user_name' => 'Aisha Tran', 'confirmed_warning_count' => 2, 'is_frozen' => false, 'last_warning_at' => now()->subDay()->format('Y-m-d H:i')],
        ];
    }
}
