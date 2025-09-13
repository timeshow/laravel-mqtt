<?php
declare(strict_types=1);
namespace TimeShow\Mqtt\Facades;

use Illuminate\Support\Facades\Facade;

class Mqtt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Mqtt';
    }
}
