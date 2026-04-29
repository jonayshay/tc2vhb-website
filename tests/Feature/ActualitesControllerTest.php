<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActualitesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_published_articles(): void
    {
        News::factory()->published()->count(3)->create();
        News::factory()->create(['status' => 'draft']);
        News::factory()->archived()->create();

        $response = $this->get('/actualites');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Actualites/Index')
                ->has('articles.data', 3)
        );
    }

    public function test_index_returns_articles_ordered_by_published_at_desc(): void
    {
        $old = News::factory()->published()->create(['published_at' => now()->subDays(5)]);
        $recent = News::factory()->published()->create(['published_at' => now()->subDay()]);

        $response = $this->get('/actualites');

        $response->assertInertia(fn (Assert $page) =>
            $page->component('Actualites/Index')
                ->where('articles.data.0.id', $recent->id)
                ->where('articles.data.1.id', $old->id)
        );
    }

    public function test_show_returns_published_article(): void
    {
        $article = News::factory()->published()->create(['slug' => 'mon-article']);

        $response = $this->get('/actualites/mon-article');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Actualites/Show')
                ->where('article.slug', 'mon-article')
        );
    }

    public function test_show_returns_404_for_draft_article(): void
    {
        News::factory()->create(['slug' => 'brouillon', 'status' => 'draft']);

        $this->get('/actualites/brouillon')->assertNotFound();
    }

    public function test_show_returns_404_for_archived_article(): void
    {
        News::factory()->archived()->create(['slug' => 'archive']);

        $this->get('/actualites/archive')->assertNotFound();
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/actualites/inexistant')->assertNotFound();
    }
}
