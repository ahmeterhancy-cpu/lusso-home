<?php
// public/medya/urunler altındaki dosyaların gerçekten görsel olduğunu doğrular
// (eski sunucu hata sayfası ya da yarım dosya döndürmüş olabilir). Kullanım: php tools/gorsel-kontrol.php
$d = __DIR__.'/../public/medya/urunler';
$n = $small = $bytes = 0;
$bad = [];
foreach (scandir($d) as $f) {
    if ($f[0] === '.') {
        continue;
    }
    $p = "$d/$f";
    $n++;
    $s = filesize($p);
    $bytes += $s;
    if ($s < 1024) {
        $small++;
    }
    $h = bin2hex((string) file_get_contents($p, false, null, 0, 4));
    if (! preg_match('/^(ffd8|89504e47|52494646|47494638)/', $h)) {
        $bad[] = "$f ($h)";
    }
}
echo "toplam $n dosya, 1KB altı $small, görsel olmayan ".count($bad).', boyut '.round($bytes / 1048576)." MB\n";
echo implode("\n", array_slice($bad, 0, 10)).($bad ? "\n" : '');
