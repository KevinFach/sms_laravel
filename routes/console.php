<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Encola los mensajes vencidos cada minuto (cola activa de envíos).
Schedule::command('messages:dispatch')->everyMinute()->withoutOverlapping();

// Confirma contra el proveedor remoto los mensajes ya despachados a canales push.
Schedule::command('messages:sync-remote')->everyMinute()->withoutOverlapping();
