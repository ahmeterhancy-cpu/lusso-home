@extends('layouts.app')

@section('title', 'Yeni Şifre')

@section('content')
<section class="wrap au">
    @include('account._auth-visual')

    <div class="au-card">
        @include('partials.flash')
        <h1 class="au-title serif">Yeni şifrenizi belirleyin</h1>
        <p class="au-sub">En az 8 karakterlik yeni bir şifre seçin.</p>
        <form method="post" action="{{ route('password.update') }}" class="au-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="au-field">
                <label for="email">E-posta adresi</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
            </div>
            <div class="au-field">
                <label for="password">Yeni şifre</label>
                <div class="au-pw">
                    <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8">
                    <button type="button" data-pw-toggle aria-label="Şifreyi göster">{{ icon('eye') }}</button>
                </div>
            </div>
            <div class="au-field">
                <label for="password_confirmation">Yeni şifre (tekrar)</label>
                <div class="au-pw">
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    <button type="button" data-pw-toggle aria-label="Şifreyi göster">{{ icon('eye') }}</button>
                </div>
            </div>
            <button type="submit" class="mbtn mbtn-dark au-submit">Şifreyi kaydet</button>
        </form>
    </div>
</section>
@endsection
