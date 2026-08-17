<?php

namespace App\Services\Admin;

use App\Models\PlantType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEngineerViewDataService
{
    public function data(): array
    {
        return [
            'plantTypeOptions' => $this->plantTypeOptions(),
            'knowledgeDomainsByPlantType' => $this->knowledgeDomainsByPlantType(),
            'accountTypeCards' => $this->accountTypeCards(),
            'basicInfoRows' => $this->basicInfoRows(),
            'verificationFields' => $this->verificationFields(),
        ];
    }

    private function accountTypeCards(): array
    {
        return [
            [
                'value' => 'member',
                'default' => 'member',
                'icon' => 'icon-user-k-habib-flagged',
                'name' => 'Registered Member',
                'description' => 'Unverified engineer/member account for free registration and pending verification.',
                'details' => [
                    'Maps to users.role = unverified_member',
                    'Verification remains pending until promoted',
                ],
            ],
            [
                'value' => 'professional',
                'icon' => 'icon-verification-queue',
                'name' => 'Professional',
                'description' => 'Verified engineer account via work email, LinkedIn, or company/university letter.',
                'details' => [
                    'Maps to users.role = professional',
                    'Shows Professional-only setup placeholders',
                ],
            ],
        ];
    }

    private function basicInfoRows(): array
    {
        return [
            [
                ['type' => 'text', 'label' => 'First name', 'name' => 'first_name', 'id' => 'firstName', 'placeholder' => 'e.g. Ahmed', 'required' => true, 'column' => 'col-md-4'],
                ['type' => 'text', 'label' => 'Last name', 'name' => 'last_name', 'id' => 'lastName', 'placeholder' => 'e.g. Ghani', 'required' => true, 'column' => 'col-md-4'],
                ['type' => 'email', 'label' => 'Email address', 'name' => 'email', 'id' => 'email', 'placeholder' => 'name@company.com', 'required' => true, 'column' => 'col-md-4'],
            ],
            [
                ['type' => 'text', 'label' => 'Username', 'name' => 'username', 'id' => 'username', 'placeholder' => 'Auto-generated from name or email if left blank', 'column' => 'col-md-6'],
                ['component' => 'select', 'label' => 'Account status', 'name' => 'status', 'id' => 'status', 'default' => 'active', 'options' => ['active' => 'Active', 'suspended' => 'Suspended', 'frozen' => 'Frozen'], 'column' => 'col-md-6'],
            ],
            [
                ['type' => 'text', 'label' => 'Position / job title', 'name' => 'position', 'id' => 'position', 'placeholder' => 'e.g. Process Technology Manager', 'column' => 'col-md-4'],
                ['type' => 'text', 'label' => 'Company / plant name', 'name' => 'company', 'id' => 'company', 'placeholder' => 'e.g. Northgate Ammonia Plant', 'column' => 'col-md-4'],
                ['type' => 'tel', 'label' => 'Phone number', 'name' => 'phone', 'id' => 'phone', 'placeholder' => '+1 555 000 0000', 'column' => 'col-md-4'],
            ],
        ];
    }

    private function verificationFields(): array
    {
        return [
            [
                'component' => 'select',
                'label' => 'Verification method',
                'name' => 'verification_method',
                'id' => 'verifyMethod',
                'default' => 'Professional work email',
                'options' => [
                    'Professional work email' => 'Professional work email',
                    'LinkedIn profile' => 'LinkedIn profile',
                    'Company verification letter' => 'Company verification letter',
                    'University letter' => 'University letter',
                    'Equivalent professional verification' => 'Equivalent professional verification',
                ],
                'column' => 'col',
            ],
            [
                'type' => 'number',
                'label' => 'Years of industry experience',
                'name' => 'years_experience',
                'id' => 'yearsExp',
                'placeholder' => 'e.g. 12',
                'column' => 'col',
                'attributes' => ['min' => 0, 'max' => 60],
            ],
        ];
    }

    private function knowledgeDomainsByPlantType(): array
    {
        if (! Schema::hasTable('knowledge_domains')) {
            return [];
        }

        return DB::table('knowledge_domains')
            ->where('is_active', true)
            ->whereNotNull('plant_type_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'plant_type_id'])
            ->groupBy(fn ($domain) => (string) $domain->plant_type_id)
            ->map(fn ($domains) => $domains->map(fn ($domain) => ['id' => (int) $domain->id, 'name' => (string) $domain->name])->values()->all())
            ->all();
    }

    private function plantTypeOptions(): array
    {
        if (! Schema::hasTable('plant_types')) {
            return [];
        }

        return PlantType::query()
            ->active()
            ->sorted()
            ->pluck('name', 'id')
            ->all();
    }
}
