<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProfilePlantType extends Model
{
    protected $table = 'partner_profile_plant_type';

    protected $fillable = [
        'partner_profile_id',
        'plant_type_id',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'partner_profile_id' => 'integer',
            'plant_type_id' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function partnerProfile(): BelongsTo
    {
        return $this->belongsTo(PartnerProfile::class);
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }
}
