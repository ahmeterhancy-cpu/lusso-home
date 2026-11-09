<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Drive / Google E-Tablo'daki fiyat-stok dosyasından otomatik güncelleme.
 *
 * Dosya "Bağlantıya sahip herkes → Görüntüleyen" olarak paylaşılır, bağlantı panele
 * yapıştırılır. Desteklenen bağlantılar:
 *   - Google E-Tablo:  docs.google.com/spreadsheets/d/<ID>/edit#gid=<GID>  → CSV olarak indirilir
 *   - Drive dosyası:   drive.google.com/file/d/<ID>/view                   → .xlsx/.csv indirilir
 *   - Doğrudan CSV/XLSX adresi (ör. "Web'de yayınla" bağlantısı)
 */
class SheetSync
{
    public function __construct(private ProductCsv $csv) {}

    public function downloadUrl(string $url): string
    {
        $url = trim($url);
        if (preg_match('~docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)~', $url, $m)) {
            if (str_contains($url, '/pub')) {
                return $url; // "Web'de yayınla" bağlantısı zaten indirilebilir
            }
            $gid = preg_match('~[#&?]gid=(\d+)~', $url, $g) ? $g[1] : '0';

            return "https://docs.google.com/spreadsheets/d/{$m[1]}/export?format=csv&gid={$gid}";
        }
        if (preg_match('~drive\.google\.com/(?:file/d/|open\?id=|uc\?(?:.*&)?id=)([a-zA-Z0-9_-]+)~', $url, $m)) {
            return "https://drive.google.com/uc?export=download&id={$m[1]}";
        }

        return $url;
    }

    /** Ayarlarda kayıtlı dosyayı çalıştırır. $force: süre dolmamış olsa da çalıştır. */
    public function run(bool $force = false): ?array
    {
        $url = (string) setting('sheet_url');
        if ($url === '' || (! $force && setting('sheet_enabled') !== '1')) {
            return null;
        }
        if (! $force) {
            $every = max(5, (int) setting('sheet_interval', 60));
            $last = setting('sheet_last_run');
            if ($last && now()->diffInMinutes(\Illuminate\Support\Carbon::parse($last)) < $every) {
                return null;
            }
        }

        $started = now();
        try {
            $res = Http::timeout(60)->withOptions(['allow_redirects' => ['max' => 5]])->get($this->downloadUrl($url));
            if (! $res->successful()) {
                throw new \RuntimeException('Dosya indirilemedi (HTTP '.$res->status().'). Paylaşım ayarı "Bağlantıya sahip herkes" olmalı.');
            }
            $body = $res->body();
            if (str_contains(strtolower(substr($body, 0, 500)), '<html')) {
                throw new \RuntimeException('Dosya yerine bir Google sayfası geldi. Dosyanın paylaşımını "Bağlantıya sahip herkes → Görüntüleyen" yapın.');
            }
            $tmp = tempnam(sys_get_temp_dir(), 'lusso-sheet-');
            file_put_contents($tmp, $body);
            $result = $this->csv->import($tmp);
            @unlink($tmp);
            $summary = "{$result['updated']} ürün güncellendi, {$result['unchanged']} değişmedi"
                .($result['notFound'] ? ', '.count($result['notFound']).' stok kodu sitede bulunamadı' : '')
                .($result['errors'] ? ', '.count($result['errors']).' uyarı' : '').'.';
            $ok = empty($result['errors']) || $result['updated'] > 0;
        } catch (\Throwable $e) {
            $result = ['updated' => 0, 'unchanged' => 0, 'notFound' => [], 'errors' => [$e->getMessage()]];
            $summary = 'HATA: '.$e->getMessage();
            $ok = false;
            Log::warning('[Drive senkron] '.$e->getMessage());
        }

        Setting::put([
            'sheet_last_run' => $started->toDateTimeString(),
            'sheet_last_result' => json_encode([
                'ok' => $ok, 'at' => $started->format('d.m.Y H:i'), 'summary' => $summary,
                'notFound' => array_slice($result['notFound'], 0, 30), 'errors' => array_slice($result['errors'], 0, 30),
            ], JSON_UNESCAPED_UNICODE),
        ]);
        \App\Models\ActivityLog::record('custom', null, 'Drive fiyat/stok senkronu: '.$summary);

        return $result + ['summary' => $summary, 'ok' => $ok];
    }
}
