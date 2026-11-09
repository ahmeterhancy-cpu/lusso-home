<?php
// Görsel dosyasının sonuna kadar inip inmediğini, biçimin bitiş işaretine bakarak anlar.
// Yarım inen JPEG tarayıcıda alt kısmı gri görünür ama başı sağlam olduğu için ilk baytlara bakan kontrol yakalayamaz.
function gorsel_tam_mi(string $f): bool
{
    $size = filesize($f);
    if ($size < 16) {
        return false;
    }
    $fh = fopen($f, 'rb');
    $head = fread($fh, 12);
    fseek($fh, -32, SEEK_END);
    $tail = fread($fh, 32);
    fclose($fh);

    if (str_starts_with($head, "\xFF\xD8")) {
        return str_contains($tail, "\xFF\xD9");
    }
    if (str_starts_with($head, 'RIFF')) {
        return unpack('V', substr($head, 4, 4))[1] + 8 <= $size;
    }
    if (str_starts_with($head, "\x89PNG")) {
        return str_contains($tail, 'IEND');
    }

    return true;
}
