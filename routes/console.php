<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('appointments:queue-reminders')
    ->hourly()
    ->timezone(config('app.timezone'));
