<?php
// GEÇİCİ ürün içeriği güncelleme sayfası (sunucuda Terminal yok). İş bitince depodan silinir.
// Anahtar yalnız özetiyle (sha256) burada; anahtarın kendisi depoda yok.
@set_time_limit(900);
header('Content-Type: text/plain; charset=utf-8');
if (! hash_equals('19bcdb4003187e0f4b307906d7a9773c76efdc7a1eed940291dcf51261436e16', hash('sha256', (string) ($_GET['k'] ?? '')))) {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$adim = $_GET['adim'] ?? 'durum';
$say = fn () => 'ürün: '.DB::table('products')->count().', açık: '.DB::table('products')->where('is_active', 1)->count()
    .', görsel: '.DB::table('product_images')->count().', sipariş: '.DB::table('orders')->count().', üye: '.DB::table('users')->count()."\n";
echo 'PHP '.PHP_VERSION." | adım: $adim\n\n";

try {
    if ($adim === 'durum') {
        echo $say();
        $dump = $root.'/database/canli/lusso-guncelleme.sql.gz';
        echo 'Döküm: '.(is_file($dump) ? round(filesize($dump) / 1024).' KB' : 'YOK')."\n";
    } elseif ($adim === 'uygula') {
        echo "Önce: ".$say();
        $pdo = DB::connection()->getPdo();
        $fh = gzopen($root.'/database/canli/lusso-guncelleme.sql.gz', 'rb');
        $buf = '';
        $n = 0;
        while (! gzeof($fh)) {
            $line = gzgets($fh, 1 << 22);
            $buf .= $line;
            if (preg_match('/;\s*$/', $line)) {
                if (trim($buf) !== '') {
                    $pdo->exec($buf);
                    $n++;
                }
                $buf = '';
            }
        }
        gzclose($fh);
        echo "Çalıştırılan ifade: $n\n";
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('view:cache');
        echo "Sonra: ".$say();
    } else {
        echo "Bilinmeyen adım\n";
    }
} catch (Throwable $e) {
    echo 'HATA: '.get_class($e).': '.$e->getMessage()."\n";
}
