@extends('layouts.app')

@section('page_title')
    @include('partials.page-title', ['title' => 'Hesabım'])
@endsection

@section('content')
<div class="container main-page">
    <div class="account-layout">
        <ul class="account-nav">
            <li @class(['active' => request()->routeIs('account')])><a href="{{ route('account') }}">Kontrol Paneli</a></li>
            <li @class(['active' => request()->routeIs('account.order*')])><a href="{{ route('account.orders') }}">Siparişler</a></li>
            <li @class(['active' => request()->routeIs('account.edit')])><a href="{{ route('account.edit') }}">Hesap Detayları</a></li>
            <li><a href="{{ route('wishlist') }}">Beğendiklerim</a></li>
            <li><a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit()">Çıkış Yap</a></li>
        </ul>
        <div>
            @include('partials.flash')
            @yield('account')
        </div>
    </div>
    <form id="logout-form" method="post" action="{{ route('logout') }}">@csrf</form>
</div>
@endsection
