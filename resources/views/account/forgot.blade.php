@extends('layouts.app')

@section('title', 'Şifremi Unuttum')

@section('content')
<section class="wrap au">
    @include('account._auth-visual')

    <div class="au-card">
        <a href="{{ route('account') }}" class="au-back">{{ icon('arrow-left') }} Girişe dön</a>
        @include('partials.flash')
        <h1 class="au-title serif">Şifrenizi mi unuttunuz?</h1>
        <p class="au-sub">E-posta adresinizi yazın; yeni şifre oluşturmanız için bir bağlantı gönderelim.</p>
        <form method="post" action="{{ route('password.email') }}" class="au-form">
            @csrf
            <div class="au-field">
                <label for="email">E-posta adresi</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="ornek@eposta.com">
            </div>
            <button type="submit" class="mbtn mbtn-dark au-submit">Bağlantı gönder {{ icon('paper-plane') }}</button>
        </form>
    </div>
</section>
@endsection
