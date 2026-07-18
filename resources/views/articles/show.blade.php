@extends('layouts.app', [
    'title' => $article->seo_title ?: $article->title,
    'metaDescription' => $article->meta_description ?: $article->summary,
])

@section('content')
    <article class="article-detail">
        <nav class="article-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('events.index') }}">Beranda</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('articles.index') }}">Artikel</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $article->title }}</span>
        </nav>
        <header class="article-detail-header">
            <a href="{{ route('articles.index') }}">← Semua Artikel</a>
            <p class="eyebrow">Artikel PDUG</p>
            <h1>{{ $article->title }}</h1>
            <p>{{ $article->summary }}</p>
            <time datetime="{{ $article->published_at?->toDateString() }}">Dipublikasikan {{ $article->published_at?->translatedFormat('d F Y') }}</time>
        </header>

        <div class="article-detail-media">
            @if ($article->thumbnail_url)
                <img src="{{ $article->thumbnail_url }}" alt="Thumbnail {{ $article->title }}">
            @else
                <img src="{{ asset('images/event-placeholder.svg') }}" alt="" loading="lazy">
            @endif
        </div>

        <div class="article-content">{!! str($article->content)->sanitizeHtml() !!}</div>
    </article>

    @if ($relatedArticles->isNotEmpty())
        <section class="related-articles">
            <div class="section-heading">
                <div><p class="eyebrow">Lanjut Membaca</p><h2>Artikel Terkait</h2></div>
            </div>
            <div class="article-grid">
                @foreach ($relatedArticles as $relatedArticle)
                    @include('articles.partials.card', ['article' => $relatedArticle])
                @endforeach
            </div>
        </section>
    @endif
@endsection
