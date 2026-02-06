<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup draft settlements every hour
Schedule::command('settlement:cleanup-drafts --hours=24')->hourly();

// Cleanup old split PDF files daily at 2:00 AM
Schedule::command('pdf:cleanup-old-splits --days=30')->dailyAt('02:00');
