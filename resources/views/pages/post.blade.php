@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@section('description', $post->meta_description ?: $post->excerpt)
@section('page_title')
    @include('partials.page-title', ['title' => $post->title, 'crumbs' => [['Blog', route('blog')], [$post->title, null]]])
@endsection

@section('content')
<div class="container content-page" style="max-width:860px">
    @if ($post->image)<img src="{{ media_url($post->image) }}" alt="{{ $post->title }}" style="width:100%;margin-bottom:30px">@endif
    {!! $post->content !!}
</div>
@endsection
