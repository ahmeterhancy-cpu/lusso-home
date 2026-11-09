<?php

namespace App\Console\Commands;

use App\Services\SheetSync;
use Illuminate\Console\Command;

class SheetSyncCommand extends Command
{
    protected $signature = 'urun:senkron {--zorla : Süre dolmamış veya kapalı olsa da çalıştır}';

    protected $description = 'Google Drive / E-Tablo dosyasından fiyat ve stokları günceller';

    public function handle(SheetSync $sync): int
    {
        $r = $sync->run((bool) $this->option('zorla'));
        $this->line($r ? $r['summary'] : 'Atlandı (kapalı ya da süresi gelmedi).');

        return ($r['ok'] ?? true) ? self::SUCCESS : self::FAILURE;
    }
}
