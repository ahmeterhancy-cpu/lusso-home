@extends('layouts.app')

@section('title', 'Hesabım')
@section('page_title')
    @include('partials.page-title', ['title' => 'Hesabım'])
@endsection

@section('content')
<div class="container">
    <div style="padding-top:20px">@include('partials.flash')</div>
    <div class="auth-cols">
        <div>
            <h2>{{ icon('login') }} Giriş Yap</h2>
            <form method="post" action="{{ route('login') }}">
                @csrf
                <div class="form-row"><label for="email">Kullanıcı adı veya e-mail <span class="req">*</span></label><input id="email" type="text" name="email" value="{{ old('email') }}" required autocomplete="username"></div>
                <div class="form-row"><label for="password">Şifre <span class="req">*</span></label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
                <button type="submit" class="btn btn-login">Giriş</button>
                <div class="auth-foot">
                    <label class="check"><input type="checkbox" name="remember" value="1"> Beni Hatırla</label>
                    <a href="{{ route('password.request') }}">{{ icon('support') }} Şifreni Unuttun mu?</a>
                </div>
            </form>
        </div>
        <div>
            <h2>Üye Ol</h2>
            @if ($mode === 'register' || $errors->has('name') || $errors->has('password') || old('name'))
                <form method="post" action="{{ route('register') }}" style="text-align:left">
                    @csrf
                    <div class="form-row"><label for="r-name">Ad Soyad <span class="req">*</span></label><input id="r-name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
                    <div class="form-row"><label for="r-email">E-posta adresi <span class="req">*</span></label><input id="r-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
                    <div class="form-row"><label for="r-password">Şifre <span class="req">*</span></label><input id="r-password" type="password" name="password" required autocomplete="new-password" minlength="8"></div>
                    <div class="form-row"><label for="r-password2">Şifre (tekrar) <span class="req">*</span></label><input id="r-password2" type="password" name="password_confirmation" required autocomplete="new-password"></div>
                    <label class="check" style="margin-bottom:20px"><input type="checkbox" name="terms" value="1"> <span><a href="{{ route('page', 'uyelik') }}" target="_blank">Üyelik sözleşmesini</a> okudum, kabul ediyorum.</span></label>
                    <button type="submit" class="btn btn-black btn-block">Üye Ol</button>
                </form>
            @else
                <p>Bu siteye kayıt olarak sipariş durumunuzu ve geçmişinizi görebilirsiniz. Aşağıdaki alanları doldurun, hesabınız hemen oluşturulsun. Yalnızca alışverişi daha hızlı ve kolay hale getirmek için gerekli bilgileri isteriz.</p>
                <a href="{{ route('account', ['action' => 'register']) }}" class="btn btn-black">Üye Ol</a>
            @endif
        </div>
    </div>
</div>
@endsection
