<?php

use App\Console\Commands\NotifyOverdueTasks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Once a day at 08:00, in the timezone `config('app.timezone')` sets.
 *
 * `withoutOverlapping` only stops a scheduled run starting on top of another
 * scheduled run. It knows nothing about a sweep started by hand, so it is not what
 * keeps a task out of two emails: the row locks in the sweep's claim do that.
 */
Schedule::command(NotifyOverdueTasks::class)
    ->dailyAt(NotifyOverdueTasks::DAILY_RUN_TIME)
    ->withoutOverlapping();
