<?php

namespace App\Events;

use App\Models\HandbookArticle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HandbookArticlePublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly HandbookArticle $article)
    {
    }
}
