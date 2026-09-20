<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('dbp:status', function () {
    $this->info('Danshi Booking Platform is ready.');
})->purpose('Check the DBP application status');

Schedule::command('tablesit:sync-bookings')
    ->everyFifteenMinutes()
    ->withoutOverlapping(20);
