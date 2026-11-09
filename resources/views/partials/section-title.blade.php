@if (! empty($bordered))
    <div class="title-wrapper bordered">
        <span class="subtitle">#MyLussoLife</span>
        <div class="liner"><span class="line"></span><h4 class="title">{{ $title }}</h4><span class="line"></span></div>
    </div>
@else
    <div class="title-wrapper">
        <span class="subtitle">#MyLussoLife</span>
        <h4 class="title">{{ $title }}</h4>
    </div>
@endif
