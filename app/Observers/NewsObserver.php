<?php

namespace App\Observers;

use App\Models\News;

class NewsObserver
{
    public function saving(News $news): void
    {
        if ($news->status === 'published' && $news->published_at === null) {
            $news->published_at = now();
        }
    }
}
