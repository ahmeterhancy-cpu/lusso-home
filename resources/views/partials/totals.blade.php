{{-- Tutar dökümü. $q: PricingService::quote() sonucu --}}
<div class="totals-row"><span>Ara Toplam</span><span>{{ money($q['subtotal']) }}</span></div>
@foreach ($q['discounts'] as $d)
    <div class="totals-row discount"><span>{{ $d['label'] }}</span><span>−{{ money($d['amount']) }}</span></div>
@endforeach
<div class="totals-row"><span>Kargo</span><span>{{ $q['shipping'] > 0 ? money($q['shipping']) : 'Ücretsiz' }}</span></div>
<div class="totals-row total"><span>Toplam</span><span>{{ money($q['total']) }}</span></div>
