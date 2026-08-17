<?php

namespace App\Http\Requests\Admin\Qa;

use Illuminate\Foundation\Http\FormRequest;

class ReorderAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.id' => ['required', 'integer', 'exists:answers,id'],
            'answers.*.rank_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function answerRankMap(): array
    {
        return collect($this->validated('answers'))
            ->mapWithKeys(fn (array $answer): array => [$answer['id'] => $answer['rank_order']])
            ->all();
    }
}
