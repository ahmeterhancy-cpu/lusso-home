@extends('account._layout')

@section('title', 'Hesap Detayları')

@section('account')
    <form method="post" action="{{ route('account.update') }}">
        @csrf
        <div class="form-grid">
            <div class="form-row"><label for="name">Ad Soyad <span class="req">*</span></label><input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="form-row"><label for="email">E-posta <span class="req">*</span></label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
            <div class="form-row"><label for="phone">Telefon</label><input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}"></div>
            <div class="form-row"><label for="city">Şehir</label><input id="city" type="text" name="city" value="{{ old('city', $user->city) }}"></div>
            <div class="form-row full"><label for="address">Adres</label><input id="address" type="text" name="address" value="{{ old('address', $user->address) }}"></div>
        </div>
        <fieldset style="border:1px solid #e6e6e6;padding:20px;margin:10px 0 25px">
            <legend style="padding:0 8px">Şifre değişikliği</legend>
            <div class="form-row"><label for="current_password">Mevcut şifre (değiştirmeyecekseniz boş bırakın)</label><input id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
            <div class="form-grid">
                <div class="form-row"><label for="password">Yeni şifre</label><input id="password" type="password" name="password" autocomplete="new-password"></div>
                <div class="form-row"><label for="password_confirmation">Yeni şifre (tekrar)</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
            </div>
        </fieldset>
        <button type="submit" class="btn btn-black">Değişiklikleri Kaydet</button>
    </form>
@endsection
