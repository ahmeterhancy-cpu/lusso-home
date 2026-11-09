{{-- Koyu sayfa başlığı şeridi + iz yolu. $title, $crumbs = [[ad, adres|null], ...] --}}
<div class="page-title">
    <div class="container">
        <h1>{{ $title }}</h1>
        <div class="breadcrumbs">
            <a href="{{ route('home') }}">{{ icon('home') }}Ana Sayfa</a>
            @foreach ($crumbs ?? [[$title, null]] as [$name, $url])
                <span class="sep">/</span>
                @if ($url)<a href="{{ $url }}">{{ $name }}</a>@else<span>{{ $name }}</span>@endif
            @endforeach
        </div>
    </div>
</div>
