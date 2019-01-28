<?php

if (!defined('HIGH')) define("HIGH", 1);
if (!defined('LOW')) define("LOW", 0);

class i2c_com extends stdClass
{
    public $slc;    
    public $sda;
    public $version;
    public $url;

    function __construct($url, $scl, $sda, $version = 1)
    {
        $this->scl = $scl;
        $this->sda = $sda;
        $this->url = $url;
        $this->version = $version;
    }

    function i2c_request($data) {
        $url = $this->url . $data;
        //dprint($url,false);
        return file_get_contents($this->url . $data);
    }

    function i2c_init()
    {
        $this->i2c_dir($this->scl, "OUT");
        $this->i2c_dir($this->sda, "OUT");
        $this->i2c_request("cmd=" . $this->scl . ":" . HIGH . ";" . $this->sda . ":" . HIGH);
    }

    function i2c_start()
    {
        $this->i2c_request("cmd=" . $this->sda . ":" . LOW . ";" . $this->scl . ":" . LOW);
    }

    function i2c_stop()
    {
        //file_get_contents(MD."cmd=".$this->scl.":".HIGH);
        //file_get_contents(MD."cmd=".$this->scl.":".LOW);

        $this->i2c_request("cmd=" . $this->sda . ":" . LOW . ";" . $this->scl . ":" . HIGH . ";" . $this->sda . ":" . HIGH);

    }

    function i2c_dir($port, $dir)
    {
        if ($dir == "OUT")
            $dir = 1;
        else
            $dir = 0;
        $this->i2c_request("pt=$port&dir=$dir");
    }

    function i2c_send($data)
    {
        if ($this->version == 2) {
            //if ( $this->version == 2 )
            $this->i2c_request("pt=" . $this->sda . "&i2c=" . hexdec($data) . "&scl=" . $this->scl . ":1;" . $this->scl . ":0;");
            //file_get_contents(MD."pt=11&i2c=".hexdec($data)."&scl=10:1;10:0;");
        } else {
            $data_bin = decbin(hexdec($data));
            $len = strlen($data_bin);
            for ($i = 8; $i > $len; $i--)
                $data_bin = "0" . $data_bin;

            $old_bit = 3;
            $cmd = "";
            $cnt = 0;

            for ($i = 0; $i < 8; $i++) {
                // Вариант 1
                //file_get_contents($this->url."cmd=".$this->sda.":".$data_bin[$i]);
                //file_get_contents($this->url."cmd=".$this->scl.":".HIGH);
                //file_get_contents($this->url."cmd=".$this->scl.":".LOW);
                //file_get_contents($this->url."cmd=".$this->sda.":".LOW);

                // Вариант 2
                $this->i2c_request("cmd=" . $this->sda . ":" . $data_bin[$i] . ";" . $this->scl . ":" . HIGH . ";" . $this->scl . ":" . LOW . ";" . $this->sda . ":" . LOW);

                /*
                // Вариант 3 - отправляем сразу 2 бита
                $cmd .= $this->sda.":".$data_bin[$i].";".$this->scl.":".HIGH.";".$this->scl.":".LOW.";".$this->sda.":".LOW.";";
                $cnt++;
                if ( $cnt == 2 )
                {
                    file_get_contents($this->url."cmd=$cmd");
                    $cnt = 0;
                    $cmd = "";
                }
                */

            }

            // Вариант 1
            //i2c_dir($this->sda, "IN");
            //file_get_contents($this->url."cmd=".$this->scl.":".HIGH);
            //$ack = file_get_contents(MD."pt=".$this->sda."&cmd=get")."\n";
            //file_get_contents($this->url."cmd=".$this->scl.":".LOW);

            //echo $ack;

            // Вариант 2
            $this->i2c_request("pt=" . $this->sda . "&dir=0&cmd=" . $this->scl . ":" . HIGH . ";" . $this->scl . ":" . LOW);
            $ack = $this->i2c_request("pt=" . $this->sda . "&cmd=get&dir=1");

            return $ack;
        }
    }

    function i2c_read($nack = 0)
    {
        $data_bits = "";
        $bit = "";

        $this->i2c_request("pt=" . $this->sda . "&dir=0&cmd=" . $this->scl . ":" . LOW);
        //file_get_contents(MD."cmd=".$this->scl.":".LOW);

        for ($i = 0; $i < 8; $i++) {
            $this->i2c_request("cmd=" . $this->scl . ":" . HIGH);
            $bit = $this->i2c_request("pt=" . $this->sda . "&cmd=get");

            if ($bit == "ON")
                $data_bits .= "0";
            else
                $data_bits .= "1";

            $this->i2c_request("cmd=" . $this->scl . ":" . LOW);
        }

        $this->i2c_dir($this->sda, "OUT");
        $this->i2c_request("cmd=" . $this->scl . ":" . HIGH);
        if ($nack == 1)
            $this->i2c_request("cmd=" . $this->sda . ":" . HIGH);
        //file_get_contents(MD."cmd=".$this->scl.":".LOW);
        //i2c_dir($this->sda, "IN");
        $this->i2c_request("pt=" . $this->sda . "&dir=0&cmd=" . $this->scl . ":" . LOW);

        //return dechex(bindec($data_bits))." - $data_bits\n";
        return dechex(bindec($data_bits));

    }
}