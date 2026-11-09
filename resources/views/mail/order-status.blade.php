<!DOCTYPE html>
<html lang="tr">
<body style="margin:0;background:#efefef;font-family:Arial,Helvetica,sans-serif;color:#4c4c4c">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#efefef;padding:30px 0"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;max-width:600px">
    <tr><td style="background:#37383c;padding:25px;text-align:center"><img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept" width="160"></td></tr>
    <tr><td style="padding:30px">
        <h2 style="color:#1b1919;margin:0 0 15px">Merhaba {{ $order->first_name }},</h2>
        <p><strong>{{ $order->number }}</strong> numaralı siparişinizin durumu güncellendi:</p>
        <p style="font-size:20px;color:#db8700;margin:20px 0"><strong>{{ $order->statusLabel() }}</strong></p>
        @if ($order->tracking_no)<p>Kargo takip numarası: <strong>{{ $order->tracking_no }}</strong></p>@endif
        @if ($note)<p style="background:#f7f7f7;padding:15px">{!! nl2br(e($note)) !!}</p>@endif
        <p style="margin-top:25px"><a href="{{ route('order.thanks', ['order' => $order->number, 't' => $order->track_token]) }}" style="color:#db8700">Siparişinizi görüntüleyin</a></p>
    </td></tr>
    <tr><td style="background:#37383c;color:rgba(255,255,255,.6);padding:15px;text-align:center;font-size:12px">Lusso Home Concept · {{ setting('support_phone', '+90 539 109 99 00') }} · info@lussohomeconcept.com</td></tr>
</table>
</td></tr></table>
</body>
</html>
