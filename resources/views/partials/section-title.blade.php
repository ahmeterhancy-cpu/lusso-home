@if (! empty($bordered))
    <div class="title-wrapper bordered">
        <span class="subtitle">#MyLussoLife</span>
        <div class="liner"><span class="line"></span><h4 class="title">{{ $title }}</h4><span class="line"></span></div>
    </div>
@else
    <div class="title-wrapper">
        @if ($subtitle ?? true)<span class="subtitle">#MyLussoLife</span>@endif
        <h4 class="title">{{ $title }}</h4>
    </div>
@endif
