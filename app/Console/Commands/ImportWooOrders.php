<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Eski WooCommerce müşterilerini ve siparişlerini aktarır.
 *
 * Kaynak: storage/app/woo/customers.json ve orders.json (eski panelden salt okunur
 * REST ile alınan, alanları kısaltılmış dökümler). Tekrar çalıştırılabilir:
 * kayıtlar wc_id ile eşleşir, var olanlar güncellenir.
 *
 * Eski şifreler REST ile alınamaz: müşteriler "Şifremi unuttum" ile yeni şifre belirler.
 * Personel hesapları (yönetici, mağaza müdürü) aktarılmaz.
 */
class ImportWooOrders extends Command
{
    protected $signature = 'lusso:aktar-siparis {--klasor=woo : storage/app altındaki döküm klasörü} {--deneme : Yazmadan yalnız say}';

    protected $description = 'Eski WooCommerce müşteri ve siparişlerini aktarır';

    private const STATUS = [
        'completed' => 'completed', 'cancelled' => 'cancelled', 'processing' => 'processing',
        'on-hold' => 'pending', 'pending' => 'pending', 'refunded' => 'cancelled', 'failed' => 'failed',
    ];

    private const METHOD = ['bacs' => 'havale', 'cod' => 'kapida', 'tiko' => 'tiko', 'payment_paynet' => 'paynet'];

    private const STAFF = ['administrator', 'shop_manager', 'editor', 'author'];

    public function handle(): int
    {
        $dir = storage_path('app/'.trim($this->option('klasor'), '/'));
        $customers = $this->load($dir.'/customers.json');
        $orders = $this->load($dir.'/orders.json');
        if ($customers === null || $orders === null) {
            return self::FAILURE;
        }
        $dry = (bool) $this->option('deneme');

        // Sipariş kayıtları model olaylarını tetiklemesin (işlem günlüğü / e-posta).
        $result = Model::withoutEvents(fn () => DB::transaction(fn () => [
            $this->importUsers($customers, $dry),
            $this->importOrders($orders, $dry),
        ]));

        [[$uNew, $uUpd, $uStaff], [$oNew, $oUpd, $linked, $unmatched]] = $result;
        $this->table(['', 'Yeni', 'Güncellenen', 'Not'], [
            ['Müşteri', $uNew, $uUpd, $uStaff.' personel hesabı atlandı'],
            ['Sipariş', $oNew, $oUpd, $linked.' sipariş bir üyeye bağlandı; '.$unmatched.' kalemin ürünü bulunamadı'],
        ]);
        if ($dry) {
            $this->warn('Deneme: hiçbir şey kaydedilmedi.');
        }

        return self::SUCCESS;
    }

    private function load(string $file): ?array
    {
        if (! is_file($file)) {
            $this->error("Dosya yok: $file");

            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data)) {
            $this->error("Okunamadı: $file");

            return null;
        }

        return $data;
    }

    /** @return array{int,int,int} */
    private function importUsers(array $rows, bool $dry): array
    {
        $new = $upd = $staff = 0;
        foreach ($rows as $c) {
            if (in_array($c['role'] ?? '', self::STAFF, true)) {
                $staff++;

                continue;
            }
            $email = mb_strtolower(trim((string) ($c['em'] ?? '')));
            if ($email === '') {
                continue;
            }
            $name = trim(($c['fn'] ?? '').' '.($c['ln'] ?? '')) ?: ($c['un'] ?? Str::before($email, '@'));
            $address = trim(implode(' ', array_filter([$c['a1'] ?? null, $c['a2'] ?? null])));

            $user = User::where('wc_id', $c['id'])->first() ?? User::where('email', $email)->first();
            if ($user) {
                // Var olan hesapta yalnız boş alanları doldur, yetkiye dokunma.
                $user->wc_id = $c['id'];
                $user->phone = $user->phone ?: ($c['ph'] ?? null);
                $user->address = $user->address ?: ($address ?: null);
                $user->city = $user->city ?: ($c['city'] ?? null);
                if (! $dry) {
                    $user->save();
                }
                $upd++;

                continue;
            }

            $user = new User;
            $user->forceFill([
                'wc_id' => $c['id'],
                'name' => Str::limit($name, 120, ''),
                'email' => $email,
                'password' => Str::random(48), // eski şifre alınamaz; "Şifremi unuttum" ile yenilenir
                'phone' => $c['ph'] ?? null,
                'address' => $address ?: null,
                'city' => $c['city'] ?? null,
                'is_admin' => false,
                'created_at' => $this->date($c['c'] ?? null) ?? now(),
            ]);
            if (! $dry) {
                $user->save();
            }
            $new++;
        }

        return [$new, $upd, $staff];
    }

    /** @return array{int,int,int,int} */
    private function importOrders(array $rows, bool $dry): array
    {
        $new = $upd = $linked = $unmatched = 0;
        $productIds = Product::whereNotNull('wc_id')->pluck('id', 'wc_id')->all();
        // Eski sitede silinmiş ürünler için yedek eşleşme: stok kodu, sonra birebir ad
        $bySku = Product::whereNotNull('sku')->where('sku', '!=', '')->pluck('id', 'sku')->all();
        $byName = Product::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower(trim($n)) => $id])->all();
        $usersByWc = User::whereNotNull('wc_id')->pluck('id', 'wc_id')->all();
        $usersByEmail = User::pluck('id', 'email')->mapWithKeys(fn ($id, $e) => [mb_strtolower($e) => $id])->all();

        foreach ($rows as $o) {
            $b = $o['b'] ?? [];
            $s = $o['s'] ?? $b;
            $email = mb_strtolower(trim((string) ($b['em'] ?? '')));
            $userId = ($o['cid'] ?? 0) ? ($usersByWc[$o['cid']] ?? null) : null;
            $userId ??= $email !== '' ? ($usersByEmail[$email] ?? null) : null;
            $linked += $userId ? 1 : 0;

            $status = self::STATUS[$o['st']] ?? 'pending';
            $created = $this->date($o['c'] ?? null) ?? now();
            $paidAt = $status === 'completed' ? ($this->date($o['paid'] ?? null) ?? $this->date($o['done'] ?? null) ?? $created) : null;

            $items = [];
            $subtotal = 0.0;
            foreach ($o['li'] ?? [] as [$wcProduct, $name, $sku, $qty, $lineSub, $lineTotal]) {
                $qty = max(1, (int) $qty);
                $name = html_entity_decode((string) $name, ENT_QUOTES | ENT_HTML5);
                $productId = $productIds[$wcProduct] ?? ($sku !== '' ? ($bySku[$sku] ?? null) : null) ?? ($byName[mb_strtolower(trim($name))] ?? null);
                $unmatched += $productId ? 0 : 1;
                $subtotal += (float) $lineSub;
                $items[] = [
                    'product_id' => $productId,
                    'name' => Str::limit($name, 250, ''),
                    'sku' => $sku !== '' ? Str::limit($sku, 100, '') : null,
                    'price' => round((float) $lineSub / $qty, 2),
                    'quantity' => $qty,
                    'total' => (float) $lineTotal,
                ];
            }

            $note = ['Eski siteden aktarıldı (WooCommerce sipariş #'.$o['no'].').'];
            if (! isset(self::METHOD[$o['pm']])) {
                $note[] = 'Ödeme yöntemi: '.$o['pm'];
            }
            if (! empty($o['tx'])) {
                $note[] = 'Ödeme işlem no: '.$o['tx'];
            }
            foreach ($o['fees'] ?? [] as [$feeName, $feeTotal]) {
                $note[] = 'Ek ücret: '.$feeName.' '.number_format((float) $feeTotal, 2, ',', '.').' ₺';
            }
            if (! empty($b['co'])) {
                $note[] = 'Firma: '.$b['co'];
            }

            $attrs = [
                'number' => (string) $o['no'],
                'source' => 'eski',
                'user_id' => $userId,
                'status' => $status,
                'payment_method' => self::METHOD[$o['pm']] ?? Str::limit((string) $o['pm'], 20, ''),
                'payment_status' => $paidAt ? 'paid' : 'unpaid',
                'payment_ref' => $o['tx'] ?? null,
                'first_name' => Str::limit(trim((string) ($b['fn'] ?? '')) ?: '-', 250, ''),
                'last_name' => Str::limit(trim((string) ($b['ln'] ?? '')), 250, ''),
                'email' => $email ?: '-',
                'phone' => Str::limit((string) ($b['ph'] ?? ''), 40, ''),
                'address' => Str::limit(trim(implode(' ', array_filter([$s['a1'] ?? null, $s['a2'] ?? null]))), 250, ''),
                'city' => Str::limit((string) (($s['city'] ?? '') ?: ($s['st'] ?? '') ?: ($b['city'] ?? '')), 80, ''),
                'note' => $o['note'] ?? null,
                'admin_note' => implode("\n", $note),
                'subtotal' => round($subtotal, 2),
                'discount' => (float) ($o['disc'] ?? 0),
                'shipping' => (float) ($o['ship'] ?? 0),
                'total' => (float) $o['tot'],
                'paid_at' => $paidAt,
                'created_at' => $created,
                'updated_at' => $this->date($o['m'] ?? null) ?? $created,
            ];

            $order = Order::where('wc_id', $o['id'])->first();
            if ($dry) {
                $order ? $upd++ : $new++;

                continue;
            }
            if ($order) {
                $order->forceFill($attrs)->save();
                $order->items()->delete();
                $upd++;
            } else {
                $order = (new Order)->forceFill($attrs + ['wc_id' => $o['id'], 'track_token' => Str::random(32)]);
                $order->save();
                $new++;
            }
            $order->items()->createMany($items);
        }

        return [$new, $upd, $linked, $unmatched];
    }

    private function date(?string $gmt): ?Carbon
    {
        return $gmt ? Carbon::parse($gmt, 'UTC')->setTimezone(config('app.timezone')) : null;
    }
}
