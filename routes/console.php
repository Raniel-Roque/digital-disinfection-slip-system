<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cleanup tasks
Schedule::command('clean:attachments')
    ->daily()
    ->at('00:00')
    ->description('Clean up old attachments based on retention period (preserves logos)');

Schedule::command('clean:logs')
    ->daily()
    ->at('00:00')
    ->description('Clean up old logs based on retention period');

Schedule::command('clean:resolved-reports')
    ->daily()
    ->at('00:00')
    ->description('Clean up old resolved reports based on retention period');
