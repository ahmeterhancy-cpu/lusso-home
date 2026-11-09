@extends('layouts.app')

@section('title', 'Sipariş Takibi')
@section('page_title')
    @include('partials.page-title', ['title' => 'Sipariş Takibi'])
@endsection

@section('content')
<div class="container main-page" style="max-width:760px">
    @include('partials.flash')
    @if ($order)
        @include('account._order-detail')
        <a href="{{ route('track') }}" class="btn">Başka bir sipariş sorgula</a>
    @else
        <p>Siparişinizin durumunu öğrenmek için sipariş numaranızı ve sipariş verirken kullandığınız e-posta adresini girin.</p>
        <form method="post" action="{{ route('track') }}">
            @csrf
            <div class="form-grid">
                <div class="form-row"><label for="number">Sipariş numarası</label><input id="number" type="text" name="number" value="{{ old('number') }}" placeholder="LHC-…" required></div>
                <div class="form-row"><label for="email">E-posta adresi</label><input id="email" type="email" name="email" value="{{ old('email') }}" required></div>
            </div>
            <button type="submit" class="btn btn-black">Sorgula</button>
        </form>
    @endif
</div>
@endsection
