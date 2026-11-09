@extends('layouts.app')

@section('title', 'Şifremi Unuttum')
@section('page_title')
    @include('partials.page-title', ['title' => 'Şifremi Unuttum', 'crumbs' => [['Hesabım', route('account')], ['Şifremi Unuttum', null]]])
@endsection

@section('content')
<div class="container main-page" style="max-width:560px">
    @include('partials.flash')
    <p>Şifrenizi mi unuttunuz? E-posta adresinizi girin, yeni şifre oluşturmanız için bir bağlantı gönderelim.</p>
    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <div class="form-row"><label for="email">E-posta adresi</label><input id="email" type="email" name="email" value="{{ old('email') }}" required></div>
        <button type="submit" class="btn btn-black">Şifreyi Sıfırla</button>
    </form>
</div>
@endsection
