<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('shipment:create',['--start-date' => now()->toDateTimeString()])
	->dailyAt('01:00')
	->appendOutputTo(storage_path('logs/commands.log'))
	->onSuccess(function ()
	{
		file_put_contents(storage_path('logs/test.log'), 'ran at ' . now() . "\n", FILE_APPEND);
	})
	->onFailure(function ()
        {
                file_put_contents(storage_path('logs/test.log'), 'error at ' . now() . "\n", FILE_APPEND);
        });


Schedule::command('daily:upload')
	//->dailyAt('12:46')
	->appendOutputTo(storage_path('logs/commands.log'))
	->onSuccess(function ()
        {
                file_put_contents(storage_path('logs/test.log'), 'ran at ' . now() . "\n", FILE_APPEND);
        })
	->onFailure(function ()
        {
                file_put_contents(storage_path('logs/test.log'), 'error at ' . now() . "\n", FILE_APPEND);
        });
