<?php
declare(strict_types=1);
namespace TimeShow\Mqtt\Services;

class MqttService
{
    private $socket;
    private $msgid = 1;
    public $keepalive = 10;
    public $timesinceping;
    public $topics = array();
    public $debug = false;
    public $address;
    public $port;
    public $clientid;
    public $will;
    private $username;
    private $password;
    public $cafile;
    public $localcert;
    public $localpk;

    function __construct($address, $port, $timeout, $clientId, $cafile = NULL, $localCert = NULL, $localPk = NULL, $debug=false){
        $this->debug = $debug;
        $this->broker($address, $port, $timeout, $clientId, $cafile, $localCert, $localPk);
    }

    /* sets the broker details */
    function broker($address, $port, $timeout, $clientid, $cafile = NULL, $localcert = NULL, $localpk = NULL){
        $this->address = $address;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->clientid = $clientid;
        $this->cafile = $cafile;
        $this->localcert = $localcert;
        $this->localpk = $localpk;
    }

    function connect_auto($clean = true, $will = NULL, $username = NULL, $password = NULL){
        while($this->connect($clean, $will, $username, $password)==false){
            sleep(10);
        }
        return true;
    }


    function connect($clean = true, $will = NULL, $username = NULL, $password = NULL){

        if($will) $this->will = $will;
        if($username) $this->username = $username;
        if($password) $this->password = $password;
        if ($this->cafile) {
            $sslOptions = ["ssl" => [
                "verify_peer_name" => true,
                "cafile" => $this->cafile
            ]];
            if( $this->localcert && $this->localpk )
            {
                $sslOptions["ssl"]["local_cert"] = $this->localcert;
                $sslOptions["ssl"]["local_pk"] = $this->localpk;
            }
            $socketContext = stream_context_create($sslOptions);
            $this->socket = stream_socket_client("tls://" . $this->address . ":" . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $socketContext);
        } else {
            $this->socket = stream_socket_client("tcp://" . $this->address . ":" . $this->port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT);
        }
        if (!$this->socket ) {
            if($this->debug) error_log("stream_socket_create() $errno, $errstr \n");
            return false;
        }
        stream_set_timeout($this->socket, 5);
        stream_set_blocking($this->socket, 0);
        $i = 0;
        $buffer = "";
        $buffer .= chr(0x00); $i++;
        $buffer .= chr(0x06); $i++;
        $buffer .= chr(0x4d); $i++;
        $buffer .= chr(0x51); $i++;
        $buffer .= chr(0x49); $i++;
        $buffer .= chr(0x73); $i++;
        $buffer .= chr(0x64); $i++;
        $buffer .= chr(0x70); $i++;
        $buffer .= chr(0x03); $i++;
        //No Will
        $var = 0;
        if($clean) $var+=2;
        //Add will info to header
        if($this->will != NULL){
            $var += 4; // Set will flag
            $var += ($this->will['qos'] << 3); //Set will qos
            if($this->will['retain'])	$var += 32; //Set will retain
        }
        if($this->username != NULL) $var += 128;	//Add username to header
        if($this->password != NULL) $var += 64;	//Add password to header
        $buffer .= chr($var); $i++;
        //Keep alive
        $buffer .= chr($this->keepalive >> 8); $i++;
        $buffer .= chr($this->keepalive & 0xff); $i++;
        $buffer .= $this->strwritestring($this->clientid,$i);
        //Adding will to payload
        if($this->will != NULL){
            $buffer .= $this->strwritestring($this->will['topic'],$i);
            $buffer .= $this->strwritestring($this->will['content'],$i);
        }
        if($this->username) $buffer .= $this->strwritestring($this->username,$i);
        if($this->password) $buffer .= $this->strwritestring($this->password,$i);
        $head = "  ";
        $head[0] = chr(0x10);
        $head[1] = chr($i);
        fwrite($this->socket, $head, 2);
        fwrite($this->socket,  $buffer);
        $string = $this->read(4);
        if(ord($string[0])>>4 == 2 && $string[3] == chr(0)){
            if($this->debug) echo "Connected to Broker\n";
        }else{
            error_log(sprintf("Connection failed! (Error: 0x%02x 0x%02x)\n",
                ord($string[0]),ord($string[3])));
            return false;
        }
        $this->timesinceping = time();
        return true;
    }

    function read($int = 8192, $nb = false){
        //	print_r(socket_get_status($this->socket));

        $string="";
        $togo = $int;

        if($nb){
            return fread($this->socket, $togo);
        }

        while (!feof($this->socket) && $togo>0) {
            $fread = fread($this->socket, $togo);
            $string .= $fread;
            $togo = $int - strlen($string);
        }




        return $string;
    }

    function subscribe($topics, $qos = 0){
        $i = 0;
        $buffer = "";
        $id = $this->msgid;
        $buffer .= chr($id >> 8);  $i++;
        $buffer .= chr($id % 256);  $i++;
        foreach($topics as $key => $topic){
            if (!empty($topic))
            {
                $buffer .= $this->strwritestring($key,$i);
                $callback = func_get_args()[0];
                $buffer .= chr($callback["qos"]);
                $i++;
                $this->topics[$key] = $topic;
            }
        }
        $cmd = 0x80;
        //$qos
        $cmd +=	($qos << 1);
        $head = chr($cmd);
        $head .= chr($i);

        fwrite($this->socket, $head, 2);
        fwrite($this->socket, $buffer, $i);
        $string = $this->read(2);

        $bytes = ord(substr($string,1,1));
        $string = $this->read($bytes);
    }

    function ping(){
        $head = chr(0xc0);
        $head .= chr(0x00);
        fwrite($this->socket, $head, 2);
        if($this->debug) echo "ping sent\n";
    }

    function disconnect(){
        $head = " ";
        $head[0] = chr(0xe0);
        $head[1] = chr(0x00);
        fwrite($this->socket, $head, 2);
    }

    function close(){
        $this->disconnect();
        stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
    }

    function publish($topic, $content, $qos = 0, $retain = 0){
        $i = 0;
        $buffer = "";
        $buffer .= $this->strwritestring($topic,$i);
        //$buffer .= $this->strwritestring($content,$i);
        if($qos){
            $id = $this->msgid++;
            $buffer .= chr($id >> 8);  $i++;
            $buffer .= chr($id % 256);  $i++;
        }
        $buffer .= $content;
        $i+=strlen($content);
        $head = " ";
        $cmd = 0x30;
        if($qos) $cmd += $qos << 1;
        if($retain) $cmd += 1;
        $head[0] = chr($cmd);
        $head .= $this->setmsglength($i);
        fwrite($this->socket, $head, strlen($head));
        fwrite($this->socket, $buffer, $i);
    }

    function message($msg){
        $tlen = (ord($msg[0])<<8) + ord($msg[1]);
        $topic = substr($msg,2,$tlen);
        $msg = substr($msg,($tlen+2));
        $found = 0;
        foreach($this->topics as $key=>$top){
            if( preg_match("/^".str_replace("#",".*",
                    str_replace("+","[^\/]*",
                        str_replace("/","\/",
                            str_replace("$",'\$',
                                $key))))."$/",$topic) ){
                if(is_callable($top['function'])){
                    call_user_func($top['function'],$topic,$msg);
                    $found = 1;
                }
            }
        }
        if($this->debug && !$found) echo "msg received but no match in subscriptions\n";
    }

    function proc( $loop = true){
        if(1){
            $sockets = array($this->socket);
            $w = $e = NULL;
            $cmd = 0;

            //$byte = fgetc($this->socket);
            if(feof($this->socket)){
                if($this->debug) echo "eof receive going to reconnect for good measure\n";
                fclose($this->socket);
                $this->connect_auto(false);
                if(count($this->topics))
                    $this->subscribe($this->topics);
            }

            $byte = $this->read(1, true);

            if(!strlen($byte)){
                if($loop){
                    usleep(100000);
                }

            }else{

                $cmd = (int)(ord($byte)/16);
                if($this->debug) echo "Receive: $cmd\n";
                $multiplier = 1;
                $value = 0;
                do{
                    $digit = ord($this->read(1));
                    $value += ($digit & 127) * $multiplier;
                    $multiplier *= 128;
                }while (($digit & 128) != 0);
                if($this->debug) echo "Fetching: $value\n";

                if($value)
                    $string = $this->read($value);

                if($cmd){
                    switch($cmd){
                        case 3:
                            $this->message($string);
                            break;
                    }
                    $this->timesinceping = time();
                }
            }
            if($this->timesinceping < (time() - $this->keepalive )){
                if($this->debug) echo "not found something so ping\n";
                $this->ping();
            }

            if($this->timesinceping<(time()-($this->keepalive*2))){
                if($this->debug) echo "not seen a package in a while, disconnecting\n";
                fclose($this->socket);
                $this->connect_auto(false);
                if(count($this->topics))
                    $this->subscribe($this->topics);
            }
        }
        return 1;
    }

    function getmsglength(&$msg, &$i){
        $multiplier = 1;
        $value = 0 ;
        do{
            $digit = ord($msg[$i]);
            $value += ($digit & 127) * $multiplier;
            $multiplier *= 128;
            $i++;
        }while (($digit & 128) != 0);
        return $value;
    }

    function setmsglength($len){
        $string = "";
        do{
            $digit = $len % 128;
            $len = $len >> 7;
            // if there are more digits to encode, set the top bit of this digit
            if ( $len > 0 )
                $digit = ($digit | 0x80);
            $string .= chr($digit);
        }while ( $len > 0 );
        return $string;
    }

    function strwritestring($str, &$i){
        $ret = " ";
        $len = strlen($str);
        $msb = $len >> 8;
        $lsb = $len % 256;
        $ret = chr($msb);
        $ret .= chr($lsb);
        $ret .= $str;
        $i += ($len+2);
        return $ret;
    }

    function printstr($string){
        $strlen = strlen($string);
        for($j=0;$j<$strlen;$j++){
            $num = ord($string[$j]);
            if($num > 31)
                $chr = $string[$j]; else $chr = " ";
            printf("%4d: %08b : 0x%02x : %s \n",$j,$num,$num,$chr);
        }
    }

}