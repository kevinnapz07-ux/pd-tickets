<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleAndEventMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_articles_are_visible_publicly(): void
    {
        $published = Article::create([
            'title' => 'Renungan Pengharapan',
            'summary' => 'Ringkasan artikel yang sudah diterbitkan.',
            'content' => '<h2>Paragraf pertama.</h2><p>Paragraf kedua.</p>',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'seo_title' => 'Renungan PDUG',
            'meta_description' => 'Deskripsi pencarian artikel PDUG.',
        ]);

        $draft = Article::create([
            'title' => 'Artikel Draft',
            'summary' => 'Artikel ini belum diterbitkan.',
            'content' => 'Isi draft.',
            'is_published' => false,
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);

        $this->get(route('articles.show', $published))
            ->assertOk()
            ->assertSee($published->summary)
            ->assertSee('<h2>Paragraf pertama.</h2>', false)
            ->assertSee('<title>Renungan PDUG • PD Gunadarma Event</title>', false)
            ->assertSee('Deskripsi pencarian artikel PDUG.');

        $this->get(route('articles.show', $draft))->assertNotFound();
    }

    public function test_about_page_shows_only_three_latest_articles(): void
    {
        foreach (range(1, 4) as $number) {
            Article::create([
                'title' => "Artikel {$number}",
                'summary' => "Ringkasan {$number}",
                'content' => "Isi {$number}",
                'is_published' => true,
                'published_at' => now()->subDays(4 - $number),
            ]);
        }

        $this->get(route('profile.pdug'))
            ->assertOk()
            ->assertSee('Lihat Semua Artikel')
            ->assertSee('Artikel 4')
            ->assertSee('Artikel 3')
            ->assertSee('Artikel 2')
            ->assertDontSee('Artikel 1');
    }

    public function test_event_photo_and_placeholder_are_rendered(): void
    {
        $withPhoto = $this->event(['title' => 'Event Dengan Poster', 'image_path' => 'events/poster.webp']);
        $withoutPhoto = $this->event(['title' => 'Event Tanpa Poster']);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee($withPhoto->image_url)
            ->assertSee('images/event-placeholder.svg')
            ->assertSee($withoutPhoto->title);

        $this->get(route('events.show', $withoutPhoto))
            ->assertOk()
            ->assertSee('Poster event belum tersedia');
    }

    public function test_replaced_event_and_article_media_are_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('events/old-event.jpg', 'old');
        Storage::disk('public')->put('articles/old-article.jpg', 'old');

        $event = $this->event(['image_path' => 'events/old-event.jpg']);
        $article = Article::create([
            'title' => 'Artikel Media',
            'summary' => 'Ringkasan.',
            'content' => 'Isi.',
            'thumbnail_path' => 'articles/old-article.jpg',
        ]);

        $event->update(['image_path' => 'events/new-event.jpg']);
        $article->update(['thumbnail_path' => 'articles/new-article.jpg']);

        Storage::disk('public')->assertMissing('events/old-event.jpg');
        Storage::disk('public')->assertMissing('articles/old-article.jpg');
    }

    public function test_article_slugs_are_generated_uniquely(): void
    {
        $first = Article::create([
            'title' => 'Judul yang Sama',
            'summary' => 'Ringkasan pertama.',
            'content' => '<p>Isi pertama.</p>',
        ]);
        $second = Article::create([
            'title' => 'Judul yang Sama',
            'summary' => 'Ringkasan kedua.',
            'content' => '<p>Isi kedua.</p>',
        ]);

        $this->assertSame('judul-yang-sama', $first->slug);
        $this->assertSame('judul-yang-sama-2', $second->slug);
    }

    public function test_article_detail_has_breadcrumb_and_related_articles(): void
    {
        $article = Article::create([
            'title' => 'Artikel Utama',
            'summary' => 'Ringkasan utama.',
            'content' => '<p>Isi utama.</p>',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);
        $related = Article::create([
            'title' => 'Artikel Terkait Satu',
            'summary' => 'Ringkasan terkait.',
            'content' => '<p>Isi terkait.</p>',
            'is_published' => true,
            'published_at' => now()->subHours(2),
        ]);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('Artikel Terkait')
            ->assertSee($related->title);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Event Test',
            'description' => 'Deskripsi event.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 50,
            'price' => 0,
            'is_published' => true,
            'registration_is_open' => true,
        ], $attributes));
    }
}
