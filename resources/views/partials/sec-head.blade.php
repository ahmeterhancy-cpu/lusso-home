{{-- Bölüm başlığı: solda küçük üst yazı + serif başlık, sağda "tümünü gör" bağlantısı --}}
<div class="sec-head">
    <div>
        @if (($eyebrow ?? site('subtitle')) !== false)@php($eb = $eyebrow ?? site('subtitle'))<p class="eyebrow" @if (str_starts_with((string) $eb, '#')) lang="en" @endif>{{ $eb }}</p>@endif
        <h2 class="serif">{{ $title }}</h2>
    </div>
    @if (! empty($link))
        <a href="{{ $link }}" class="sec-link">{{ $linkText ?? 'Tümünü gör' }} {{ icon('arrow-right') }}</a>
    @endif
</div>
