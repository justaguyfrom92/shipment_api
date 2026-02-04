<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('shipment:create',
[
	'--start-date' => now()->toDateTimeString(),
])->dailyAt('01:00')
->sendOutputTo(storage_path('logs/commands.log'));

Schedule::command('daily:upload')->dailyAt('02:00')
->sendOutputTo(storage_path('logs/commands.log'));
