@extends('layouts.app')

@section('title', $page->title)
@section('page_title')
    @include('partials.page-title', ['title' => $page->title])
@endsection

@section('content')
<div class="container content-page">
    {!! $page->content !!}
</div>
@endsection
