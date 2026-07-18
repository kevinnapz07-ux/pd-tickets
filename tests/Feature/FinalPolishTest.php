<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Event;
use App\Models\User;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_basic_seo_and_skip_navigation(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('events.index').'">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('class="skip-link"', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('class="public-site"', false);
    }

    public function test_event_and_article_images_have_lazy_loading_and_local_fallback(): void
    {
        $event = Event::create([
            'title' => 'Event Final Polish',
            'description' => 'Event dengan poster.',
            'image_path' => 'events/missing-poster.webp',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 50,
            'price' => 0,
        ]);
        $article = Article::create([
            'title' => 'Artikel Final Polish',
            'summary' => 'Ringkasan artikel.',
            'content' => '<p>Isi artikel.</p>',
            'thumbnail_path' => 'articles/missing-thumbnail.webp',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('loading="lazy"', false)
            ->assertSee('decoding="async"', false)
            ->assertSee('data-image-fallback="'.asset('images/event-placeholder.svg').'"', false);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('data-image-fallback="'.asset('images/event-placeholder.svg').'"', false);
    }

    public function test_legacy_admin_layout_is_not_marked_as_public_site(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Final Polish',
            'email' => 'admin.final@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('class="legacy-admin"', false)
            ->assertDontSee('class="public-site"', false);
    }

    public function test_sensitive_ticket_url_is_not_exposed_in_seo_metadata(): void
    {
        $event = Event::create([
            'title' => 'Event Tiket Sensitif',
            'description' => 'Event pengujian metadata.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 10,
            'price' => 0,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'name' => 'Peserta Aman',
            'email' => 'peserta.aman@gmail.com',
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $this->get(route('tickets.verify', $registration->verification_token))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false)
            ->assertDontSee('<link rel="canonical"', false)
            ->assertDontSee('<meta property="og:url"', false)
            ->assertDontSee($registration->verification_token);
    }
}
