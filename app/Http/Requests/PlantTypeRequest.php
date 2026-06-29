<?php

namespace App\Http\Requests;

use App\Models\PlantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlantTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->role === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $plantType = $this->route('plantType');
        $plantTypeId = $plantType instanceof PlantType ? $plantType->id : null;
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255', Rule::unique('plant_types', 'name')->ignore($plantTypeId)],
            'slug' => [$required, 'string', 'max:255', Rule::unique('plant_types', 'slug')->ignore($plantTypeId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
