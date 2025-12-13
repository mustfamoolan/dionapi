<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule notification commands
Schedule::command('notifications:check-overdue-debts')->hourly();
Schedule::command('notifications:check-debts-due-soon')->dailyAt('09:00');
Schedule::command('notifications:check-low-stock')->everySixHours();
Schedule::command('notifications:check-expired-subscriptions')->dailyAt('08:00');
