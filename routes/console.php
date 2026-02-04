<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('shipment:create',
[
	'--start-date' => now()->toDateTimeString(),
])->dailyAt('01:00')
->appendOutputTo(storage_path('logs/commands.log'));

Schedule::command('daily:upload')->dailyAt('12:00')
->appendOutputTo(storage_path('logs/commands.log'));
