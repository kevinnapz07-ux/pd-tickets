@extends('layouts.app', ['title' => 'Artikel'])

@section('content')
    <section class="profile-hero article-hero">
        <div>
            <p class="eyebrow">PDUG</p>
            <h1>Artikel</h1>
            <p>Kabar kegiatan, renungan, dan cerita pertumbuhan dari komunitas PDUG.</p>
        </div>
    </section>

    <section class="section article-index">
        <div class="section-heading">
            <div><p class="eyebrow">Terbaru</p><h2>Semua Artikel</h2></div>
        </div>
        <div class="article-grid">
            @forelse ($articles as $article)
                @include('articles.partials.card', ['article' => $article])
            @empty
                <div class="article-empty">Belum ada artikel yang dipublikasikan.</div>
            @endforelse
        </div>
        {{ $articles->links() }}
    </section>
@endsection
