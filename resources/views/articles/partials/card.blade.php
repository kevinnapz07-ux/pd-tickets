<article class="article-card" data-reveal>
    <a class="article-card-media" href="{{ route('articles.show', $article) }}" tabindex="-1" aria-hidden="true">
        @if ($article->thumbnail_url)
            <img src="{{ $article->thumbnail_url }}" alt="" loading="lazy">
        @else
            <img src="{{ asset('images/event-placeholder.svg') }}" alt="" loading="lazy">
        @endif
    </a>
    <div class="article-card-body">
        <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d F Y') }}</time>
        <h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3>
        <p>{{ $article->summary }}</p>
        <a class="article-read-link" href="{{ route('articles.show', $article) }}">Baca Artikel <span aria-hidden="true">→</span></a>
    </div>
</article>
