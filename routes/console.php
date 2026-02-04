<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('shipment:create',['--start-date' => now()->toDateTimeString()])
	->dailyAt('01:00')
	->appendOutputTo(storage_path('logs/scheduled-commands.log'));


Schedule::command('daily:upload')
	->everyMinute()
	//->dailyAt('12:50:00')
	->appendOutputTo(storage_path('logs/schedule-commands.log'));
