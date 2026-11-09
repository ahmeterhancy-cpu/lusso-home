<?php
// Koyu zeminli logodan saydam zeminli sürüm üretir (panel/e-posta gibi açık zeminler için).
// php tools/logo-acik.php
$src = imagecreatefrompng(__DIR__.'/../public/img/logo.png');
$w = imagesx($src);
$h = imagesy($src);
$out = imagecreatetruecolor($w, $h);
imagesavealpha($out, true);
imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
// Zemin rengi: sol üst köşe
$bg = imagecolorat($src, 2, 2);
[$br, $bgc, $bb] = [($bg >> 16) & 255, ($bg >> 8) & 255, $bg & 255];
for ($x = 0; $x < $w; $x++) {
    for ($y = 0; $y < $h; $y++) {
        $c = imagecolorat($src, $x, $y);
        [$r, $g, $b] = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
        $d = sqrt(($r - $br) ** 2 + ($g - $bgc) ** 2 + ($b - $bb) ** 2);
        if ($d < 18) {
            continue; // zemin: saydam
        }
        // kenar yumuşatma: zemine yakın pikseller kısmen saydam
        $alpha = $d < 90 ? (int) round(127 * (1 - ($d - 18) / 72)) : 0;
        imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, $alpha));
    }
}
// Boş kenarları kırp (panelde logo küçük görünmesin)
$cropped = imagecropauto($out, IMG_CROP_TRANSPARENT);
if ($cropped) {
    imagesavealpha($cropped, true);
    $out = $cropped;
}
imagepng($out, __DIR__.'/../public/img/logo-acik.png');
echo "public/img/logo-acik.png yazıldı\n";
