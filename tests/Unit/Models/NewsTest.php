<?php

namespace Tests\Unit\Models;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_can_be_created_with_required_fields(): void
    {
        $news = News::factory()->create([
            'title' => 'Premier article',
            'slug' => 'premier-article',
            'content' => '<p>Contenu test</p>',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('news', [
            'title' => 'Premier article',
            'slug' => 'premier-article',
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_published_at_is_cast_to_carbon(): void
    {
        $news = News::factory()->published()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $news->published_at);
    }

    public function test_featured_image_is_nullable(): void
    {
        $news = News::factory()->create(['featured_image' => null]);

        $this->assertNull($news->featured_image);
    }
}
