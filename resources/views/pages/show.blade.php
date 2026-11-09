@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@if ($page->meta_description)
    @section('description', $page->meta_description)
@endif
@section('page_title')
    @include('partials.page-title', ['title' => $page->title])
@endsection

@section('content')
<div class="container content-page">
    {!! $page->content !!}
</div>
@endsection
