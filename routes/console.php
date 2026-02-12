<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup draft settlements every hour
Schedule::command('settlement:cleanup-drafts --hours=24')->hourly();

// Cleanup old split PDF files on the 10th of every month at 2:00 AM
Schedule::command('pdf:cleanup-old-splits --days=60')->monthlyOn(10, '02:00');

// Backup database setiap hari Kamis pukul 2:00 AM
Schedule::command('backup:database')->weeklyOn(4, '02:00');
