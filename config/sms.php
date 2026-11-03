<?php

return [

    /*
    | Segundos sin contacto tras los cuales un dispositivo (ESP32) se considera desconectado.
    */
    'device_online_seconds' => (int) env('SMS_DEVICE_ONLINE_SECONDS', 120),

    /*
    | Zona horaria en la que se interpretan fecha_envio / hora_envio de los mensajes.
    */
    'timezone' => env('SMS_TIMEZONE', 'America/Mexico_City'),

];
