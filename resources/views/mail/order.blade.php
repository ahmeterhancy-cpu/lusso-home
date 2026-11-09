<!DOCTYPE html>
<html lang="tr">
<body style="margin:0;background:#efefef;font-family:Arial,Helvetica,sans-serif;color:#4c4c4c">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#efefef;padding:30px 0"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;max-width:600px">
    <tr><td style="background:#37383c;padding:25px;text-align:center"><img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept" width="160"></td></tr>
    <tr><td style="padding:30px">
        @if ($forAdmin)
            <h2 style="color:#1b1919;margin:0 0 15px">Yeni sipariş: {{ $order->number }}</h2>
            <p>{{ $order->first_name }} {{ $order->last_name }} · {{ $order->phone }} · {{ $order->email }}<br>{{ $order->address }}, {{ $order->city }}</p>
            @if ($order->note)<p><strong>Not:</strong> {{ $order->note }}</p>@endif
        @else
            <h2 style="color:#1b1919;margin:0 0 15px">Teşekkürler {{ $order->first_name }}!</h2>
            <p>{{ $order->number }} numaralı siparişiniz alındı. Hazırlandığında sizi bilgilendireceğiz.</p>
        @endif
        <p><strong>Ödeme:</strong> {{ $order->methodLabel() }} — {{ $order->payment_status === 'paid' ? 'Ödendi' : 'Ödeme bekleniyor' }}</p>
        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px">
            @foreach ($order->items as $item)
                <tr style="border-bottom:1px solid #eee"><td>{{ $item->name }} × {{ $item->quantity }}</td><td align="right">{{ money($item->total) }}</td></tr>
            @endforeach
            @foreach ((array) $order->discount_lines as $d)
                <tr><td>{{ $d['label'] }}</td><td align="right" style="color:#459647">−{{ money($d['amount']) }}</td></tr>
            @endforeach
            <tr><td>Kargo</td><td align="right">{{ (float) $order->shipping > 0 ? money($order->shipping) : 'Ücretsiz' }}</td></tr>
            <tr><td><strong>Toplam</strong></td><td align="right" style="color:#db8700"><strong>{{ money($order->total) }}</strong></td></tr>
        </table>
        @if (! $forAdmin && $order->payment_method === 'havale')
            <p style="background:#f7f7f7;padding:15px;margin-top:20px">{!! nl2br(e(setting('bank_info', 'Banka bilgileri için bizimle iletişime geçin.'))) !!}<br>Açıklamaya <strong>{{ $order->number }}</strong> yazınız.</p>
        @endif
        @unless ($forAdmin)
            <p style="margin-top:25px"><a href="{{ route('order.thanks', ['order' => $order->number, 't' => $order->track_token]) }}" style="color:#db8700">Siparişinizi görüntüleyin</a></p>
        @endunless
    </td></tr>
    <tr><td style="background:#37383c;color:rgba(255,255,255,.6);padding:15px;text-align:center;font-size:12px">Lusso Home Concept · {{ setting('support_phone', '+90 539 109 99 00') }} · info@lussohomeconcept.com</td></tr>
</table>
</td></tr></table>
</body>
</html>
