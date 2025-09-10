<?php

namespace TimeShow\Mtqq\Facades;

use Illuminate\Support\Facades\Facade;

class Mqtt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Mqtt';
    }
}
