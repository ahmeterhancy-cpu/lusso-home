@extends('layouts.app')

@section('title', 'Beğendiklerim')
@section('page_title')
    @include('partials.page-title', ['title' => 'Beğendiklerim'])
@endsection

@section('content')
<div class="container main-page">
    @include('partials.flash')
    @if ($products->isEmpty())
        <div class="cart-empty">
            <div class="big-icon">{{ icon('heart') }}</div>
            <h2>Beğendiğiniz ürün yok.</h2>
            <p>Ürünlerdeki kalp simgesine tıklayarak listenizi oluşturabilirsiniz.</p>
            <a href="{{ route('shop') }}" class="btn btn-primary btn-large">Mağazaya Geri Dön</a>
        </div>
    @else
        <div class="products-grid">
            @foreach ($products as $product)
                @include('partials.product-card')
            @endforeach
        </div>
    @endif
</div>
@endsection
