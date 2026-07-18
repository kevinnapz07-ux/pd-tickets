<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::published()
            ->latest('published_at')
            ->paginate(9);

        return view('articles.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless(
            $article->is_published
            && $article->published_at
            && $article->published_at->isPast(),
            404,
        );

        $relatedArticles = Article::published()
            ->whereKeyNot($article->getKey())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('articles.show', compact('article', 'relatedArticles'));
    }
}
