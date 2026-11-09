@extends('layouts.app')

@section('title', 'Giriş Yap / Üye Ol')

@section('content')
@php
    // Kayıt formunda hata varsa ya da ?action=register ile gelindiyse "Üye ol" sekmesi açık başlar.
    $showRegister = $mode === 'register' || $errors->hasAny(['name', 'password', 'terms']) || old('name');
@endphp
<section class="wrap au">
    @include('account._auth-visual')

    <div class="au-card" data-auth>
        <div class="au-tabs" role="tablist">
            <a href="{{ route('account') }}" role="tab" id="tab-login" aria-controls="panel-login" aria-selected="{{ $showRegister ? 'false' : 'true' }}" data-auth-tab="login">Giriş yap</a>
            <a href="{{ route('account', ['action' => 'register']) }}" role="tab" id="tab-register" aria-controls="panel-register" aria-selected="{{ $showRegister ? 'true' : 'false' }}" data-auth-tab="register">Üye ol</a>
        </div>

        @include('partials.flash')

        <div id="panel-login" role="tabpanel" aria-labelledby="tab-login" @if ($showRegister) hidden @endif>
            <h1 class="au-title serif">Tekrar hoş geldiniz</h1>
            <p class="au-sub">Siparişlerinizi ve beğendiklerinizi görmek için giriş yapın.</p>
            <form method="post" action="{{ route('login') }}" class="au-form">
                @csrf
                <div class="au-field">
                    <label for="email">E-posta adresi</label>
                    <input id="email" type="text" name="email" value="{{ $showRegister ? '' : old('email') }}" required autocomplete="username" placeholder="ornek@eposta.com">
                </div>
                <div class="au-field">
                    <div class="au-label-row"><label for="password">Şifre</label><a href="{{ route('password.request') }}">Şifremi unuttum</a></div>
                    <div class="au-pw">
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                        <button type="button" data-pw-toggle aria-label="Şifreyi göster">{{ icon('eye') }}</button>
                    </div>
                </div>
                <label class="au-check"><input type="checkbox" name="remember" value="1"> <span>Beni hatırla</span></label>
                <button type="submit" class="mbtn mbtn-dark au-submit">Giriş yap {{ icon('arrow-right') }}</button>
            </form>
            <p class="au-switch">Hesabınız yok mu? <a href="{{ route('account', ['action' => 'register']) }}" data-auth-tab="register">Hemen üye olun</a></p>
        </div>

        <div id="panel-register" role="tabpanel" aria-labelledby="tab-register" @unless ($showRegister) hidden @endunless>
            <h1 class="au-title serif">Lusso'ya katılın</h1>
            <p class="au-sub">Bir dakikada hesabınızı oluşturun; siparişleriniz ve beğendikleriniz tek yerde.</p>
            <form method="post" action="{{ route('register') }}" class="au-form">
                @csrf
                <div class="au-field">
                    <label for="r-name">Ad soyad</label>
                    <input id="r-name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
                </div>
                <div class="au-field">
                    <label for="r-email">E-posta adresi</label>
                    <input id="r-email" type="email" name="email" value="{{ $showRegister ? old('email') : '' }}" required autocomplete="email" placeholder="ornek@eposta.com">
                </div>
                <div class="au-grid">
                    <div class="au-field">
                        <label for="r-password">Şifre</label>
                        <div class="au-pw">
                            <input id="r-password" type="password" name="password" required autocomplete="new-password" minlength="8">
                            <button type="button" data-pw-toggle aria-label="Şifreyi göster">{{ icon('eye') }}</button>
                        </div>
                    </div>
                    <div class="au-field">
                        <label for="r-password2">Şifre (tekrar)</label>
                        <div class="au-pw">
                            <input id="r-password2" type="password" name="password_confirmation" required autocomplete="new-password">
                            <button type="button" data-pw-toggle aria-label="Şifreyi göster">{{ icon('eye') }}</button>
                        </div>
                    </div>
                </div>
                <p class="au-hint">En az 8 karakter.</p>
                <label class="au-check"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> <span><a href="{{ route('page', 'uyelik') }}" target="_blank">Üyelik sözleşmesini</a> okudum, kabul ediyorum.</span></label>
                <button type="submit" class="mbtn mbtn-dark au-submit">Üye ol {{ icon('arrow-right') }}</button>
            </form>
            <p class="au-switch">Zaten üye misiniz? <a href="{{ route('account') }}" data-auth-tab="login">Giriş yapın</a></p>
        </div>
    </div>
</section>
@endsection
