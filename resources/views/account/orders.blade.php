@extends('account._layout')

@section('title', 'Siparişler')

@section('account')
    @if ($orders->isEmpty())
        <div class="alert alert-info">Henüz bir sipariş vermediniz. <a href="{{ route('shop') }}">Mağazaya göz atın</a></div>
    @else
        @include('account._orders-table')
        {{ $orders->links() }}
    @endif
@endsection
