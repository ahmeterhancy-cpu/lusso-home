<?php
// Logodan 64x64 favicon üretir: php tools/favicon.php
$im = imagecreatetruecolor(64, 64);
imagefill($im, 0, 0, imagecolorallocate($im, 0x37, 0x38, 0x3c));
$logo = imagecreatefrompng(__DIR__.'/../public/img/logo.png');
// Logodaki ilk harf "L" bölgesi
imagecopyresampled($im, $logo, 6, 6, 30, 25, 52, 52, 110, 110);
imagepng($im, __DIR__.'/../public/img/favicon.png');
echo "public/img/favicon.png yazıldı\n";
