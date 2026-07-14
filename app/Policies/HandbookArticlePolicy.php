<?php

namespace App\Policies;

use App\Models\HandbookArticle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HandbookArticlePolicy
{
    public function before(?User $user): ?bool
    {
        return $user?->role === 'admin' ? true : null;
    }

    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, HandbookArticle $handbookArticle): Response
    {
        return $handbookArticle->status === 'published'
            ? Response::allow()
            : Response::deny('Handbook article is not published.');
    }

    public function create(User $user): Response
    {
        return Response::deny('Only admins can create handbook articles.');
    }

    public function update(User $user, HandbookArticle $handbookArticle): Response
    {
        return Response::deny('Only admins can update handbook articles.');
    }

    public function delete(User $user, HandbookArticle $handbookArticle): Response
    {
        return Response::deny('Only admins can delete handbook articles.');
    }

    public function publish(User $user, HandbookArticle $handbookArticle): Response
    {
        return Response::deny('Only admins can publish handbook articles.');
    }

    public function archive(User $user, HandbookArticle $handbookArticle): Response
    {
        return Response::deny('Only admins can archive handbook articles.');
    }

    public function updateAiTrainable(User $user, HandbookArticle $handbookArticle): Response
    {
        return Response::deny('Only admins can change handbook AI training eligibility.');
    }

    public function linkRelatedItem(User $user, HandbookArticle $handbookArticle): Response
    {
        return Response::deny('Only admins can link handbook related items.');
    }

    public function manage(User $user): Response
    {
        return Response::deny('Only admins can manage handbook articles.');
    }
}
