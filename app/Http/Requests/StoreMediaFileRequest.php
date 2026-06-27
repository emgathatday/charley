<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->status === 'active'
            && in_array($this->user()->role, ['admin', 'professional', 'partner', 'unverified_member'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'],
            'disk' => ['sometimes', 'string', 'max:64'],
            'directory' => ['sometimes', 'string', 'max:255'],
            'file_category' => ['nullable', 'in:image,document,process_diagram,video,presentation,audio,archive,other'],
            'upload_context' => ['nullable', 'in:profile_photo,verification_document,library_item,event_thumbnail,post_attachment,question_attachment,answer_attachment,partner_asset,service_asset,general'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_orphan' => ['sometimes', 'boolean'],
        ];
    }
}
