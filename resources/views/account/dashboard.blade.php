@extends('account._layout')

@section('title', 'Hesabım')

@section('account')
    <p>Merhaba <strong>{{ $user->name }}</strong> (<strong>{{ $user->name }}</strong> değil misiniz? <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit()">Çıkış yapın</a>)</p>
    <p>Hesap kontrol panelinizden <a href="{{ route('account.orders') }}">son siparişlerinizi</a> görüntüleyebilir, <a href="{{ route('account.edit') }}">teslimat adresinizi, şifrenizi ve hesap bilgilerinizi</a> düzenleyebilirsiniz.</p>
    @if ($recent->isNotEmpty())
        <h4 style="margin-top:30px">Son siparişler</h4>
        @include('account._orders-table', ['orders' => $recent])
    @endif
@endsection
