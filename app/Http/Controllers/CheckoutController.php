<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\TikoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders, private TikoService $tiko) {}

    public function show()
    {
        $q = $this->cart->quote(auth()->user()?->email);
        if ($q['lines']->isEmpty()) {
            return redirect()->route('cart');
        }
        if ($q['coupon_error']) {
            $this->cart->setCoupon(null);

            return redirect()->route('cart')->with('error', $q['coupon_error'].' Kupon sepetten kaldırıldı.');
        }

        return view('shop.checkout', [
            'lines' => $q['lines'],
            'q' => $q,
            'methods' => $this->methods(),
            'user' => auth()->user(),
        ]);
    }

    /** Panelden kapatılabilen ödeme yöntemleri. */
    private function methods(): array
    {
        $all = Order::METHODS;
        foreach (array_keys($all) as $m) {
            if (setting('pay_'.$m, '1') !== '1') {
                unset($all[$m]);
            }
        }
        if (isset($all['tiko']) && ! $this->tiko->configured() && ! $this->tiko->simulation()) {
            unset($all['tiko']);
        }

        return $all;
    }

    public function store(Request $request)
    {
        $methods = $this->methods();
        $data = $request->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:40',
            'address' => 'required|string|max:250',
            'city' => 'required|string|max:80',
            'note' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:'.implode(',', array_keys($methods)),
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Devam etmek için mesafeli satış sözleşmesini onaylamalısınız.',
        ], [
            'first_name' => 'Ad', 'last_name' => 'Soyad', 'email' => 'E-posta', 'phone' => 'Telefon',
            'address' => 'Adres', 'city' => 'Şehir', 'payment_method' => 'Ödeme yöntemi',
        ]);

        try {
            $order = $this->orders->createFromCart($data, $data['payment_method'], auth()->id());
        } catch (RuntimeException $e) {
            return redirect()->route('cart')->with('error', $e->getMessage());
        }

        if ($order->payment_method === 'tiko') {
            return redirect()->route('tiko.pay', ['order' => $order->number, 't' => $order->track_token]);
        }

        return redirect()->route('order.thanks', ['order' => $order->number, 't' => $order->track_token]);
    }

    private function findOrder(string $number, ?string $token): Order
    {
        $order = Order::where('number', $number)->firstOrFail();
        abort_unless($token && hash_equals($order->track_token, $token), 404);

        return $order;
    }

    public function thanks(Request $request, string $number)
    {
        $order = $this->findOrder($number, $request->query('t'));

        return view('shop.thanks', ['order' => $order->load('items')]);
    }

    /** Kart formu: Tiko'ya doğrudan gönderilir. */
    public function tikoPay(Request $request, string $number)
    {
        $order = $this->findOrder($number, $request->query('t'));
        if ($order->payment_status === 'paid') {
            return redirect()->route('order.thanks', ['order' => $order->number, 't' => $order->track_token]);
        }

        return view('shop.tiko-pay', [
            'order' => $order,
            'simulation' => $this->tiko->simulation(),
            'action' => $this->tiko->simulation() ? route('tiko.simulate', $order->number) : $this->tiko->payUrl(),
            'fields' => $this->tiko->simulation() ? [] : $this->tiko->formFields($order, $request->ip()),
        ]);
    }

    /** Tiko dönüşü (GET veya POST). Dönüşe değil, durum sorgusuna güvenilir. */
    public function tikoReturn(Request $request, string $number)
    {
        $order = Order::where('number', $number)->firstOrFail();
        $back = ['order' => $order->number, 't' => $order->track_token];

        if ($order->payment_status === 'paid') {
            return redirect()->route('order.thanks', $back);
        }

        $result = $this->tiko->queryStatus($order->number);
        Log::info('[Tiko] dönüş', ['order' => $number, 'param' => $request->query('status'), 'state' => $result['state']]);

        if ($result['state'] === 'paid') {
            if (abs($result['amount'] - (float) $order->total) > 0.01) {
                $order->log('Tutar uyuşmazlığı! Tiko: '.$result['amount'].' / sipariş: '.$order->total);

                return redirect()->route('tiko.pay', $back)->with('error', 'Ödeme tutarı doğrulanamadı, lütfen bizimle iletişime geçin.');
            }
            $this->orders->markPaid($order, $result['trans_id'], 'Tiko ile kart ödemesi alındı.');

            return redirect()->route('order.thanks', $back);
        }
        if ($result['state'] === 'pending') {
            $order->log('Tiko sonucu henüz kesinleşmedi (100).');

            return redirect()->route('order.thanks', $back)->with('info', 'Ödemeniz kontrol ediliyor. Onaylandığında e-posta ile bilgilendirileceksiniz.');
        }

        $msg = $result['message'] ?? 'Ödeme tamamlanamadı.';
        if ($result['state'] === 'failed') {
            $order->log('Kart ödemesi reddedildi: '.$msg);
        }

        return redirect()->route('tiko.pay', $back)->with('error', 'Ödeme tamamlanamadı: '.$msg.' Lütfen tekrar deneyin.');
    }

    /** Sunucudan sunucuya bildirim. */
    public function tikoCallback(Request $request)
    {
        $p = $request->all();
        if (! $this->tiko->verifyCallback($p)) {
            Log::warning('[Tiko] callback imzası geçersiz', ['order' => $p['OrderId'] ?? null]);

            return response('INVALID', 400);
        }
        $order = Order::where('number', $p['OrderId'] ?? '')->first();
        if (! $order) {
            return response('NOT FOUND', 404);
        }
        if (($p['Status'] ?? '') === '200' && abs((float) ($p['Amount'] ?? 0) - (float) $order->total) <= 0.01) {
            $this->orders->markPaid($order, (string) ($p['TransId'] ?? ''), 'Tiko bildirimi: ödeme onaylandı.');
        } elseif (($p['Status'] ?? '') !== '100') {
            $order->log('Tiko bildirimi: ödeme başarısız ('.($p['ErrorMsg'] ?? $p['Status'] ?? '?').').');
        }

        return response('OK');
    }

    /** Yerel geliştirme: kimlik bilgisi yokken sahte onay. */
    public function tikoSimulate(Request $request, string $number)
    {
        abort_unless($this->tiko->simulation(), 404);
        $order = Order::where('number', $number)->firstOrFail();
        if ($request->input('result') === 'fail') {
            $order->log('Simülasyon: ödeme reddedildi.');

            return redirect()->route('tiko.pay', ['order' => $order->number, 't' => $order->track_token])->with('error', 'Ödeme tamamlanamadı (simülasyon). Lütfen tekrar deneyin.');
        }
        $this->orders->markPaid($order, 'SIM-'.now()->format('His'), 'Simülasyon: kart ödemesi onaylandı.');

        return redirect()->route('order.thanks', ['order' => $order->number, 't' => $order->track_token]);
    }
}
