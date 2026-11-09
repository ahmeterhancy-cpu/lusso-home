@extends('layouts.app')

@section('title', 'Yeni Şifre')
@section('page_title')
    @include('partials.page-title', ['title' => 'Yeni Şifre'])
@endsection

@section('content')
<div class="container main-page" style="max-width:560px">
    @include('partials.flash')
    <form method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-row"><label for="email">E-posta adresi</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" required></div>
        <div class="form-row"><label for="password">Yeni şifre</label><input id="password" type="password" name="password" required autocomplete="new-password"></div>
        <div class="form-row"><label for="password_confirmation">Yeni şifre (tekrar)</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button type="submit" class="btn btn-black">Şifreyi Kaydet</button>
    </form>
</div>
@endsection
