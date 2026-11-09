<?php

use Illuminate\Support\Facades\Schedule;

// Sunucuda tek bir cron yeterli:  * * * * * php /yol/artisan schedule:run
// Drive senkronu her 5 dakikada kontrol eder; asıl aralık paneldeki ayardır.
Schedule::command('urun:senkron')->everyFiveMinutes()->withoutOverlapping(30);
