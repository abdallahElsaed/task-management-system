<?php

use App\Console\Commands\NotifyOverdueTasks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * FT-005 BR-1: once a day at 08:00, in the timezone `config('app.timezone')` sets.
 *
 * `withoutOverlapping` guards against a sweep that is still running when the next
 * one is due; a run that starts after one has finished is harmless either way,
 * because reported tasks are never found twice (BR-5, EC-13).
 */
Schedule::command(NotifyOverdueTasks::class)
    ->dailyAt(NotifyOverdueTasks::DAILY_RUN_TIME)
    ->withoutOverlapping();
