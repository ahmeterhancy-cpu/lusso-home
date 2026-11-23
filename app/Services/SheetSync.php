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

    /**
     * Panelde tanımlı dosyalar (ad + bağlantı). Eski tek bağlantı ayarı (sheet_url) da okunur.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public static function files(): array
    {
        $files = [];
        foreach ((array) json_decode((string) setting('sheet_files'), true) as $i => $f) {
            $url = trim((string) ($f['url'] ?? ''));
            if ($url !== '') {
                $files[] = ['name' => trim((string) ($f['name'] ?? '')) ?: 'Dosya '.($i + 1), 'url' => $url];
            }
        }
        if (! $files && ($url = trim((string) setting('sheet_url'))) !== '') {
            $files[] = ['name' => 'Dosya 1', 'url' => $url];
        }

        return $files;
    }

    /** Ayarlarda kayıtlı bütün dosyaları sırayla çalıştırır. $force: süre dolmamış / kapalı olsa da çalıştır. */
    public function run(bool $force = false): ?array
    {
        $files = self::files();
        if (! $files || (! $force && setting('sheet_enabled') !== '1')) {
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
        $skip = \App\Models\Category::sheetSyncExcludedIds();
        $total = ['updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'notFound' => [], 'errors' => []];
        $perFile = [];
        $anyOk = false;
        foreach ($files as $file) {
            $multi = count($files) > 1;
            $prefix = $multi ? $file['name'].': ' : '';
            try {
                $result = $this->importFile($file['url'], $skip);
                $fileOk = empty($result['errors']) || $result['updated'] > 0;
                $line = $this->describe($result);
            } catch (\Throwable $e) {
                $result = ['updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'notFound' => [], 'errors' => [$e->getMessage()]];
                $fileOk = false;
                $line = 'HATA: '.$e->getMessage();
                Log::warning('[Drive senkron] '.$file['name'].': '.$e->getMessage());
            }
            $anyOk = $anyOk || $fileOk;
            $perFile[] = ['name' => $file['name'], 'ok' => $fileOk, 'summary' => $line];
            foreach (['updated', 'unchanged', 'skipped'] as $k) {
                $total[$k] += $result[$k];
            }
            foreach (['notFound', 'errors'] as $k) {
                foreach ($result[$k] as $m) {
                    $total[$k][] = $prefix.$m;
                }
            }
        }

        $failed = count(array_filter($perFile, fn ($f) => ! $f['ok']));
        if (count($files) === 1) {
            $summary = $perFile[0]['summary'];
            $ok = $perFile[0]['ok'];
        } else {
            $summary = count($files).' dosya: '.$this->describe($total).($failed ? " ($failed dosyada hata)" : '');
            $ok = $anyOk && ! $failed;
        }

        Setting::put([
            'sheet_last_run' => $started->toDateTimeString(),
            'sheet_last_result' => json_encode([
                'ok' => $ok, 'at' => $started->format('d.m.Y H:i'), 'summary' => $summary, 'files' => count($files) > 1 ? $perFile : [],
                'notFound' => array_slice($total['notFound'], 0, 50), 'errors' => array_slice($total['errors'], 0, 50),
            ], JSON_UNESCAPED_UNICODE),
        ]);
        \App\Models\ActivityLog::record('custom', null, 'Drive fiyat/stok senkronu: '.$summary);

        return $total + ['summary' => $summary, 'ok' => $ok, 'files' => $perFile];
    }

    /** Tek dosyayı indirip içe aktarır. */
    private function importFile(string $url, array $skip): array
    {
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
        try {
            return $this->csv->import($tmp, $skip);
        } finally {
            @unlink($tmp);
        }
    }

    private function describe(array $r): string
    {
        return "{$r['updated']} ürün güncellendi, {$r['unchanged']} değişmedi"
            .($r['skipped'] ? ', '.$r['skipped'].' ürünün stoğu kategorisi Drive stok güncellemesine kapalı olduğu için değiştirilmedi (fiyatı güncellendi)' : '')
            .($r['notFound'] ? ', '.count($r['notFound']).' stok kodu sitede bulunamadı' : '')
            .($r['errors'] ? ', '.count($r['errors']).' uyarı' : '').'.';
    }
}
