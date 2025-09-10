<?php
/*
|--------------------------------------------------------------------------
| Laravel-Mqtt Config
|--------------------------------------------------------------------------
*/

return [
    'host'      => env('MQTT_HOST', '127.0.0.1'),
    'username'  => env('MQTT_USERNAME', ''),
    'password'  => env('MQTT_PASSWORD', ''),
    'certfile'  => env('MQTT_CERT_FILE', ''),
    'localcert' => env('MQTT_LOCAL_CERT', ''),
    'localpk'   => env('MQTT_LOCAL_PK', ''),
    'port'      => env('MQTT_PORT', '1883'),
    'timeout'   => (int) env('MQTT_TIMEOUT', 60),
    'debug'     => (bool) env('MQTT_DEBUG', false),
    'qos'       => env('MQTT_QOS', 0),
    'retain'    => env('MQTT_RETAIN', 0)
];