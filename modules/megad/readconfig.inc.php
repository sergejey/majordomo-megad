<?php

$record = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . (int)$id . "'");

$url = BASE_URL . '/modules/megad/megad-cfg.php';

if (is_dir(ROOT . 'cms/cached/')) {
    $config_file=ROOT . 'cms/cached/megad.cfg';
} else {
    $config_file=ROOT . 'cached/megad.cfg';
}

$url .= '?ip=' . urlencode($record['IP']) . '&read-conf=' . urlencode($config_file) . '&p=' . urlencode($record['PASSWORD']);
$data = getURL($url, 0);

if (!preg_match('/OK/', $data) && $this->config['API_IP']) {
    $url .= '&local-ip=' . $this->config['API_IP'];
    $data = getURL($url, 0);
}

if (preg_match('/OK/', $data)) {
    $record['CONFIG'] = LoadFile($config_file);
    if (preg_match('/mdid=(.+?)&/is', $record['CONFIG'], $m)) {
        $tmp = explode("\n", $m[1]);
        $record['MDID'] = $tmp[0];
    }

    SQLUpdate('megaddevices', $record);

    $device_type = $record['TYPE'];

    //process config
    if (preg_match_all('/pn=(\d+)&(.+?)\\n' . '/is', $record['CONFIG'], $m)) {
        $total = count($m[2]);

        $additional_ports=array();

        $port=array();
        $port['TYPE']=101; // direct command
        $port['NUM']=100;
        $additional_ports[]=$port;

        if ($device_type == '7I7O') {
            $port=array();
            $port['TYPE']=100; // int temp sensor
            $port['NUM']=100;
            $additional_ports[]=$port;
        }

        for ($i = 0; $i < $total; $i++) {
            $port = $m[1][$i];
            $line = $m[2][$i];
            $type = '';
            $command = '';

            /*
            if (preg_match('/pty=(\d+)/', $line, $m2)) {
                $type = (int)$m2[1];
            } elseif (preg_match('/ecmd=/', $line)) {
                $type = 0; // input
            } else {
                $type = 1; // output
            }
            */
            if (preg_match('/pty=(\d+)/', $line, $m2)) {
                if ($m2[1]=='0') $command='input';
                if ($m2[1]=='1') $command='output';
                if ($m2[1]=='2') $command='adc';
                if ($m2[1]=='3') $command='dsen';
            } elseif (preg_match('/ecmd=/', $line)) {
                $command = 'input';
            } else {
                $command = 'output';
            }

            /*
            if ($device_type == '7I7O' && ($port == 14 || $port == 15)) {
                $type = 2; // ADC
            }
            */

            if ($command !== '') {
                //echo $port.':'.$type."<br/>";
                $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $port . "' AND COMMAND='".$command."'");
                $prop['COMMAND'] = $command;
                $prop['NUM'] = $port;
                $prop['DEVICE_ID'] = $record['ID'];
                if (preg_match('/ecmd=(.*?)\&/', $line, $m3)) {
                    $prop['ECMD'] = $m3[1];
                }
                if (preg_match('/eth=(.*?)\&/', $line, $m3)) {
                    $prop['ETH'] = $m3[1];
                }
                if (preg_match('/m=(\d+)/', $line, $m3)) {
                    $prop['MODE'] = $m3[1];
                }
                if (preg_match('/d=(\d+)/', $line, $m3)) {
                    $prop['DEF'] = $m3[1];
                }
                if (preg_match('/misc=(.*?)\&/', $line, $m3)) {
                    $prop['MISC'] = $m3[1];
                }
                if (!$prop['ID']) {
                    $prop['ID'] = SQLInsert('megadproperties', $prop);
                } else {
                    SQLUpdate('megadproperties', $prop);
                }
            }
        }

        foreach($additional_ports as $k=>$v) {
            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND TYPE='" . $port['TYPE'] . "'");
            if (!$prop['ID']) {
             $prop=array();
             $prop['TYPE']=$port['TYPE'];
             $prop['NUM'] = $port['NUM'];
             $prop['DEVICE_ID'] = $record['ID'];
             $prop['ID'] = SQLInsert('megadproperties', $prop);
            }
        }

        $this->readValues($record['ID']);
    }
}