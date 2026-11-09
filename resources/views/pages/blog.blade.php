@extends('layouts.app')

@section('title', 'Blog')
@section('page_title')
    @include('partials.page-title', ['title' => 'Blog'])
@endsection

@section('content')
<div class="container main-page">
    @if ($posts->isEmpty())
        <p class="empty-note">Henüz yazı yayınlanmadı.</p>
    @else
        <div class="blog-grid">
            @foreach ($posts as $post)
                <article class="post-card">
                    <a href="{{ route('post', $post->slug) }}" class="post-img">@if ($post->image)<img src="{{ media_url($post->image) }}" alt="{{ $post->title }}" loading="lazy">@endif</a>
                    <span class="date">{{ $post->published_at?->translatedFormat('d F Y') }}</span>
                    <h3><a href="{{ route('post', $post->slug) }}">{{ $post->title }}</a></h3>
                    <p>{{ $post->excerpt }}</p>
                </article>
            @endforeach
        </div>
        {{ $posts->links() }}
    @endif
</div>
@endsection
