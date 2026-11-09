<?php
// Dosyası diskte olmayan ürün görsellerini storage/app/gorsel-listesi.txt'ye yazar
// (tools/gorsel-indir.sh bu listeyi indirir). Kullanım: php tools/eksik-gorseller.php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$out = [];
foreach (App\Models\ProductImage::query()->whereNotNull('source_url')->cursor() as $img) {
    $f = public_path('medya/'.$img->path);
    if (! is_file($f) || filesize($f) === 0) {
        $out[] = $img->source_url."\t".$img->path;
    }
}
file_put_contents(storage_path('app/gorsel-listesi.txt'), implode("\n", $out).($out ? "\n" : ''));
echo count($out)." eksik görsel listeye yazıldı\n";
