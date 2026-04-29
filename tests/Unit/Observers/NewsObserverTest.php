<?php

namespace Tests\Unit\Observers;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_at_is_set_when_article_is_first_published(): void
    {
        $news = News::factory()->create(['status' => 'draft']);
        $this->assertNull($news->published_at);

        $news->update(['status' => 'published']);

        $this->assertNotNull($news->fresh()->published_at);
    }

    public function test_published_at_is_not_changed_when_article_is_republished(): void
    {
        $original = now()->subDay();
        $news = News::factory()->create([
            'status' => 'published',
            'published_at' => $original,
        ]);

        $news->update(['status' => 'draft']);
        $news->update(['status' => 'published']);

        $this->assertEquals(
            $original->toDateTimeString(),
            $news->fresh()->published_at->toDateTimeString()
        );
    }

    public function test_published_at_is_not_set_when_status_remains_draft(): void
    {
        $news = News::factory()->create(['status' => 'draft']);

        $news->update(['title' => 'Nouveau titre']);

        $this->assertNull($news->fresh()->published_at);
    }
}
