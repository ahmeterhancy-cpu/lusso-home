<ul class="main-nav">
    @foreach ($menu as $item)
        <li @class(['active' => url()->current() === $item['url']])>
            <a href="{{ $item['url'] }}">{{ $item['name'] }} @if ($item['children']){{ icon('down') }}@endif</a>
            @if ($item['children'])
                <ul class="sub-menu">
                    @foreach ($item['children'] as $ch)
                        <li><a href="{{ $ch['url'] }}">{{ $ch['name'] }}</a></li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
