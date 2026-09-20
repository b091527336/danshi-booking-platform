<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('dbp:status', function () {
    $this->info('Danshi Booking Platform is ready.');
})->purpose('Check the DBP application status');
