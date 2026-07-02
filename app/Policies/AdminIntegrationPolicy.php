<?php

namespace App\Policies;

use App\Models\AdminIntegration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AdminIntegrationPolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return $this->deny();
    }

    public function view(?User $user, AdminIntegration $adminIntegration): Response
    {
        return $this->deny();
    }

    public function create(?User $user): Response
    {
        return $this->deny();
    }

    public function delete(?User $user, AdminIntegration $adminIntegration): Response
    {
        return $this->deny();
    }

    private function deny(): Response
    {
        return Response::deny('Only admins can manage admin integrations.');
    }
}
