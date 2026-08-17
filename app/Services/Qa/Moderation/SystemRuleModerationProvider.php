<?php

namespace App\Services\Qa\Moderation;

use App\Models\QaModerationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SystemRuleModerationProvider implements QaModerationProvider
{
    public function check(array $payload): ?array
    {
        $rules = $this->activeRulesFor((string) $payload['target_type']);

        foreach ($rules as $rule) {
            $match = $this->matchRule($rule, $payload);

            if ($match !== null) {
                return [
                    'source' => 'system_rule',
                    'severity' => $rule->severity,
                    'reason' => $match['reason'],
                    'evidence' => [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'rule_type' => $rule->rule_type,
                        ...($match['evidence'] ?? []),
                    ],
                ];
            }
        }

        return null;
    }

    private function activeRulesFor(string $targetType): Collection
    {
        return QaModerationRule::query()
            ->where('is_active', true)
            ->whereIn('target_type', [$targetType, 'both'])
            ->orderByDesc('severity')
            ->orderBy('id')
            ->get();
    }

    private function matchRule(QaModerationRule $rule, array $payload): ?array
    {
        $config = $rule->config ?? [];
        $body = (string) ($payload['body'] ?? '');
        $title = (string) ($payload['title'] ?? '');
        $text = trim($title.' '.$body);

        return match ($rule->rule_type) {
            'keyword' => $this->matchKeyword($config, $text),
            'max_links' => $this->matchMaxLinks($config, $text),
            'min_length' => $this->matchMinLength($config, $body),
            'regex' => $this->matchRegex($config, $text),
            'attachment_type' => $this->matchAttachmentType($config, $payload['attachments'] ?? []),
            'custom' => $this->matchCustom($config, $payload),
            default => null,
        };
    }

    private function matchKeyword(array $config, string $text): ?array
    {
        $keywords = collect($config['keywords'] ?? [])->filter()->values();
        $lowerText = Str::lower($text);
        $matched = $keywords->first(fn (string $keyword): bool => Str::contains($lowerText, Str::lower($keyword)));

        return $matched ? [
            'reason' => "Matched blocked keyword: {$matched}.",
            'evidence' => ['keyword' => $matched],
        ] : null;
    }

    private function matchMaxLinks(array $config, string $text): ?array
    {
        $maxLinks = (int) ($config['max_links'] ?? $config['value'] ?? 0);
        preg_match_all('/https?:\/\/\S+/i', $text, $matches);
        $count = count($matches[0] ?? []);

        return $maxLinks > 0 && $count > $maxLinks ? [
            'reason' => "Contains {$count} links, above allowed maximum {$maxLinks}.",
            'evidence' => ['link_count' => $count, 'max_links' => $maxLinks],
        ] : null;
    }

    private function matchMinLength(array $config, string $body): ?array
    {
        $minLength = (int) ($config['min_length'] ?? $config['value'] ?? 0);
        $length = mb_strlen(trim($body));

        return $minLength > 0 && $length < $minLength ? [
            'reason' => "Content length {$length} is below minimum {$minLength}.",
            'evidence' => ['length' => $length, 'min_length' => $minLength],
        ] : null;
    }

    private function matchRegex(array $config, string $text): ?array
    {
        $pattern = $config['pattern'] ?? null;

        if (! is_string($pattern) || $pattern === '') {
            return null;
        }

        $matched = @preg_match($pattern, $text) === 1;

        return $matched ? [
            'reason' => 'Matched moderation regex pattern.',
            'evidence' => ['pattern' => $pattern],
        ] : null;
    }

    private function matchAttachmentType(array $config, array $attachments): ?array
    {
        $blocked = collect($config['blocked_mime_types'] ?? [])->filter()->values();
        $allowed = collect($config['allowed_mime_types'] ?? [])->filter()->values();
        $mimeTypes = collect($attachments)->map(fn ($attachment) => is_array($attachment) ? ($attachment['mime_type'] ?? $attachment['type'] ?? null) : null)->filter()->values();

        $blockedMatch = $mimeTypes->first(fn (string $mimeType): bool => $blocked->contains($mimeType));
        if ($blockedMatch) {
            return [
                'reason' => "Attachment type {$blockedMatch} is blocked.",
                'evidence' => ['mime_type' => $blockedMatch],
            ];
        }

        $outsideAllowed = $allowed->isNotEmpty() ? $mimeTypes->first(fn (string $mimeType): bool => ! $allowed->contains($mimeType)) : null;

        return $outsideAllowed ? [
            'reason' => "Attachment type {$outsideAllowed} is not allowed.",
            'evidence' => ['mime_type' => $outsideAllowed],
        ] : null;
    }

    private function matchCustom(array $config, array $payload): ?array
    {
        if (($config['always_warn'] ?? false) === true) {
            return [
                'reason' => (string) ($config['reason'] ?? 'Matched custom moderation rule.'),
                'evidence' => ['custom' => true],
            ];
        }

        return null;
    }
}
