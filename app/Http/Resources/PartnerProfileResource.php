<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'logo_media_id' => $this->logo_media_id,
            'overview' => $this->overview,
            'partner_tier' => $this->partner_tier,
            'plant_type_id' => $this->plant_type_id,
            'plant_type' => PlantTypeResource::make($this->whenLoaded('plantType')),
            'company_type' => $this->company_type,
            'active_partner_subscription_id' => $this->active_partner_subscription_id,
            'keywords' => $this->keywords,
            'references' => $this->references,
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'address' => $this->address,
            'country' => $this->country,
            'website' => $this->website,
            'founded_year' => $this->founded_year,
            'social_links' => $this->social_links,
            'layout_template' => $this->layout_template,
            'feed_highlight_enabled' => $this->feed_highlight_enabled,
            'subscription_status' => $this->subscription_status,
            'subscription_expires_at' => $this->subscription_expires_at,
            'approval_status' => $this->approval_status,
            'verified_at' => $this->verified_at,
            'products_count' => $this->whenCounted('products'),
            'presentations_count' => $this->whenCounted('presentations'),
            'members_count' => $this->whenCounted('members'),
            'user' => $this->whenLoaded('user'),
            'logo_media' => MediaFileResource::make($this->whenLoaded('logoMedia')),
            'active_partner_subscription' => PartnerSubscriptionResource::make($this->whenLoaded('activePartnerSubscription')),
            'plant_types' => PlantTypeResource::collection($this->whenLoaded('plantTypes')),
            'products' => PartnerProductResource::collection($this->whenLoaded('products')),
            'presentations' => PartnerPresentationResource::collection($this->whenLoaded('presentations')),
            'members' => PartnerMemberResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
