<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'provider' => $this->provider,
            'token_expires_at' => $this->token_expires_at,
            'config_metadata' => $this->config_metadata,
        ];
    }
}
