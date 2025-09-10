# timeshow/laravel-mqtt

It is a mqtt plugin package wrapper for the Laravel and allows you to connect to an MQTT broker where you can publish messages and subscribe to topics.

## Version Compatibility

| Laravel | Package      |
|:--------|:-------------|
| 9.0     | dev          |
| 10.0^   | last version |

## Install

``` php
$ composer require timeshow/laravel-mqtt
```

The package will register itself through Laravel auto discovery of packages.
Registered will be the service provider as well as an `Mqtt` facade.
Add the `MqttServiceProvider` to your `config/app.php`:

``` bash
//providers
'providers' => [
    // ...
    TimeShow\Mqtt\MqttServiceProvider::class,
]
    
//aliases
'aliases' => [
    //...
    'Mqtt' => TimeShow\Mqtt\Facades\Mqtt::class,    
]

```

After installing the package, you should publish the configuration file using

``` bash 
php artisan vendor:publish --provider="TimeShow\Mqtt\MqttServiceProvider"
```


Configure(.env)
``` php

MQTT_HOST=your_emqx_server_address
MQTT_USERNAME=your_username
MQTT_PASSWORD=your_password
MQTT_PORT=1883

```

#### Publishing topic

``` php
use Timeshow\Mqtt\Mqtt;

public function SendMsgViaMqtt($topic, $message)
{
        $mqtt = new Mqtt();
        $client_id = Auth::user()->id;
        $output = $mqtt->ConnectAndPublish($topic, $message, $client_id);

        if ($output === true)
        {
            return "published";
        }
        
        return "Failed";
}
```

#### Publishing topic using Facade

``` php
use Mqtt;

public function SendMsgViaMqtt($topic, $message)
{
        $client_id = Auth::user()->id;
        
        $output = Mqtt::ConnectAndPublish($topic, $message, $client_id);

        if ($output === true)
        {
            return "published";
        }

        return "Failed";
}
```

#### Subscribing topic

``` php
use Timeshow\Mqtt\Mqtt;

public function SubscribetoTopic($topic)
    {
        $mqtt = new Mqtt();
        $client_id = Auth::user()->id;
        $mqtt->ConnectAndSubscribe($topic, function($topic, $msg){
            echo "Msg Received: \n";
            echo "Topic: {$topic}\n\n";
            echo "\t$msg\n\n";
        }, $client_id);


    }
```
#### Subscribing topic using Facade
#### You can also subscribe to multiple topics using the same function $topic can be array of topics e.g ['topic1', 'topic2']

``` php
use Mqtt;

public function SubscribetoTopic($topic)
    {

       Mqtt::ConnectAndSubscribe($topic, function($topic, $msg){
            echo "Msg Received: \n";
            echo "Topic: {$topic}\n\n";
            echo "\t$msg\n\n";
        },$client_id);


    }
```

#### Publishing topic using Helper method

``` php

public function SendMsgViaMqtt($topic, $message)
{
        $client_id = Auth::user()->id;
        
        $output = connectToPublish($topic, $message, $client_id);

        if ($output === true)
        {
            return "published";
        }

        return "Failed";
}
```

#### Subscribing topic using Helper method
#### You can also subscribe to multiple topics using the same function $topic can be array of topics e.g ['topic1', 'topic2']

``` php

public function SubscribetoTopic($topic)
{
  return connectToSubscribe($topic,$client_id);
}
```

