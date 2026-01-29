<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('daily:upload')->dailyAt('02:00');
