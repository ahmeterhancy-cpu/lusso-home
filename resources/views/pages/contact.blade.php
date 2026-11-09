@extends('layouts.app')

@section('title', 'İletişim')

@section('content')
<div class="contact-map">
    <iframe src="{{ setting('map_embed', 'https://www.google.com/maps?q=Lusso+Home+Concept+Girne&output=embed') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Lusso Home Concept harita"></iframe>
</div>
<div class="container">
    <div class="contact-cols">
        <div>
            <h2>İletişim Formu</h2>
            @include('partials.flash')
            <form method="post" action="{{ route('contact.send') }}">
                @csrf
                <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="form-grid">
                    <div class="form-row"><label for="name">Ad Soyad <span class="req">*</span></label><input id="name" type="text" name="name" value="{{ old('name') }}" required></div>
                    <div class="form-row"><label for="phone">Telefon</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}"></div>
                    <div class="form-row full"><label for="email">E-posta <span class="req">*</span></label><input id="email" type="email" name="email" value="{{ old('email') }}" required></div>
                    <div class="form-row full"><label for="message">Mesajınız <span class="req">*</span></label><textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea></div>
                </div>
                <button type="submit" class="btn btn-black">Gönder</button>
            </form>
        </div>
        <div>
            <h2>İletişim Bilgileri</h2>
            <div class="contact-info">
                {{ icon('paper-plane') }}
                <div>{!! setting('contact_text', 'Neopark no 6, şht Orhan Durusoy sokak, Girne (Eziç Premierin karşısında)<br>Tel: 0392 815 82 83 – 0539 109 99 00<br>E-Mail:info@lussohomeconcept.com') !!}</div>
            </div>
            <div class="socials">
                @if ($fb = setting('facebook', 'https://www.facebook.com/lussohomeconcept'))<a href="{{ $fb }}" class="fb" target="_blank" rel="noopener" aria-label="Facebook">{{ icon('facebook') }}</a>@endif
                @if ($ig = setting('instagram', 'https://www.instagram.com/lussohomeconcept'))<a href="{{ $ig }}" class="ig" target="_blank" rel="noopener" aria-label="Instagram">{{ icon('instagram') }}</a>@endif
                <a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp', '905391099900')) }}" class="wa" target="_blank" rel="noopener" aria-label="WhatsApp">{{ icon('whatsapp') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
