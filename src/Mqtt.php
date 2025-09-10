<?php
declare(strict_types=1);
namespace TimeShow\Mqtt;

use TimeShow\Mqtt\Services\MqttService;

class Mqtt
{
    protected $host = null;
    protected $username = null;
    protected $cert_file = null;
    protected $local_cert = null;
    protected $local_pk = null;
    protected $password = null;
    protected $port = null;
    protected $timeout = 0;
    protected $debug = null;
    protected $qos = 0;
    protected $retain = 0;


    public function __construct()
    {
        $this->host         = config('mqtt.host');
        $this->username     = config('mqtt.username');
        $this->password     = config('mqtt.password');
        $this->cert_file    = config('mqtt.certfile');
        $this->local_cert   = config('mqtt.localcert');
        $this->local_pk     = config('mqtt.localpk');
        $this->port         = config('mqtt.port');
        $this->timeout      = config('mqtt.timeout');
        $this->debug        = config('mqtt.debug');
        $this->qos          = config('mqtt.qos');
        $this->retain       = config('mqtt.retain');
    }

    public function ConnectAndPublish($topic, $msg, $client_id=null, $retain=null) : bool
    {
        $id = empty($client_id) ?  rand(0,999) : $client_id;

        $client = new MqttService($this->host, $this->port, $this->timeout, $id, $this->cert_file, $this->local_cert, $this->local_pk, $this->debug);

        $retain = empty($retain) ?  $this->retain : $retain;

        if ($client->connect(true, null, $this->username, $this->password))
        {
            $client->publish($topic,$msg, $this->qos, $retain);
            $client->close();

            return true;
        }

        return false;

    }


    public function ConnectAndSubscribe($topic, $proc, $client_id=null) : bool
    {
        $id = empty($client_id) ?  rand(0,999) : $client_id;

        $client = new MqttService($this->host, $this->port, $this->timeout, $id, $this->cert_file, $this->local_cert, $this->local_pk, $this->debug);

        if ($client->connect(true, null, $this->username, $this->password))
        {
            $topicData = ['qos' => $this->qos];
            $topics = is_array($topic) ? $topic : [$topic];

            foreach ($topics as $topicName) {
                $topicData[$topicName] = ["qos" => 0, "function" => $proc];
            }

            $client->subscribe($topicData, $this->qos);

            while($client->proc())
            {

            }

            $client->close();

            return true;
        }

        return false;
    }


}