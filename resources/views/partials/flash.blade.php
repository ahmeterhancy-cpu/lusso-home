@foreach (['success' => 'alert-success', 'error' => 'alert-error', 'info' => 'alert-info'] as $key => $cls)
    @if (session($key))
        <div class="alert {{ $cls }}">{{ session($key) }}</div>
    @endif
@endforeach
@if ($errors->any())
    <div class="alert alert-error">
        <ul>@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
@endif
