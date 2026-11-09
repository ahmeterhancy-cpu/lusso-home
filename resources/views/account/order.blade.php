@extends('account._layout')

@section('title', 'Sipariş '.$order->number)

@section('account')
    @include('account._order-detail')
@endsection
