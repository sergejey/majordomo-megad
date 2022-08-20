<?php

$record = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . (int)$id . "'");

$url = BASE_URL . '/modules/megad/megad-cfg-2561.php';

if (is_dir(ROOT . 'cms/cached/')) {
    $config_file = ROOT . 'cms/cached/megad.cfg';
} else {
    $config_file = ROOT . 'cached/megad.cfg';
}
@unlink($config_file);

$url .= '?ip=' . urlencode($record['IP']) . '&read-conf=' . urlencode($config_file) . '&p=' . urlencode($record['PASSWORD']);

$local_ip = '';
if ($this->config['API_IP']) {
    $local_ip = $this->config['API_IP'];
} else {
    $local_ip = getLocalIp();
}

$data = getURL($url . '&local-ip=' . $local_ip, 0);
if (!preg_match('/OK/', $data) && $local_ip) {
    $data = getURL($url, 0);
}
//dprint($data,false);
//dprint(LoadFile($config_file));

if ($this->config['API_DEBUG']) {
    DebMes("Config request:\n" . $url, 'megad');
    DebMes("Config response:\n" . $data, 'megad');
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
        for ($i = 0; $i < $total; $i++) {
            $port = $m[1][$i];
            $line = $m[2][$i];
            $type = '';
            $command = '';

            if (preg_match('/pty=(\d+)/', $line, $m2)) {
                if ($m2[1] == '0') $command = 'input';
                if ($m2[1] == '1') $command = 'output';
                if ($m2[1] == '2') $command = 'adc';
                if ($m2[1] == '3') $command = 'dsen';
            } elseif (preg_match('/ecmd=/', $line)) {
                $command = 'input';
            } else {
                $command = 'output';
            }

            if ($command !== '') {
                $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $port . "' AND COMMAND='" . $command . "'");
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
        $this->readValues($record['ID']);
    }

    if (preg_match_all('/gsm=(.+?)' . '/is', $record['CONFIG'], $m)) {

        if ($m[1][0]) {
            $command = 'alarm';
            $port = 100;
        }


        if (($command !== '') && ($m[1][0] <> '&')) {
            //echo $port.':'.$type."<br/>";
            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $port . "' AND COMMAND='" . $command . "'");
            $prop['COMMAND'] = $command;
            $prop['NUM'] = $port;
            $prop['COMMENT'] = 'GSM ALARM STATUS';
            $prop['DEVICE_ID'] = $record['ID'];
            $prop['CURRENT_VALUE'] = $m[1][0];
            $prop['CURRENT_VALUE_STRING'] = $m[1][0];
            if (preg_match('/m=(\d+)/', $line, $m3)) {
                $prop['MODE'] = $m[1][0];
            }
            if (!$prop['ID']) {
                $prop['ID'] = SQLInsert('megadproperties', $prop);
            } else {
                SQLUpdate('megadproperties', $prop);
            }
        }

    }


    if (preg_match_all('/smst=(.+?)' . '/is', $record['CONFIG'], $m)) {

        if ($m[1][0]) {
            $command = 'counter';
            $port = 100;
        }

        if (($command !== '') && ($m[1][0] <> '&')) {
            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $port . "' AND COMMAND='" . $command . "'");
            $prop['COMMAND'] = $command;
            $prop['NUM'] = $port;
            $prop['COMMENT'] = 'GSM SMS TIMEOUT';
            $prop['DEVICE_ID'] = $record['ID'];
            $prop['CURRENT_VALUE'] = $m[1][0];
            $prop['CURRENT_VALUE_STRING'] = $m[1][0];
            if (preg_match('/m=(\d+)/', $line, $m3)) {
                $prop['MODE'] = $m[1][0];
            }
            if (!$prop['ID']) {
                $prop['ID'] = SQLInsert('megadproperties', $prop);
            } else {
                SQLUpdate('megadproperties', $prop);
            }
        }

    }


    if (preg_match_all('/gsm_num=(.+?)' . '&/is', $record['CONFIG'], $m)) {
        if ($m[1][0]) {
            $command = 'raw';
            $port = 100;
        }

        if ($command !== '') {
            //echo $port.':'.$type."<br/>";
            $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $port . "' AND COMMAND='" . $command . "'");
            $prop['COMMAND'] = $command;
            $prop['NUM'] = $port;
            $prop['COMMENT'] = 'GSM ALARM PHONE';
            $prop['DEVICE_ID'] = $record['ID'];
            $prop['CURRENT_VALUE'] = $m[1][0];
            $prop['CURRENT_VALUE_STRING'] = $m[1][0];
            if (preg_match('/m=(\d+)/', $line, $m3)) {
                $prop['MODE'] = $m[1][0];
            }
            if (!$prop['ID']) {
                $prop['ID'] = SQLInsert('megadproperties', $prop);
            } else {
                SQLUpdate('megadproperties', $prop);
            }
        }

        if ($m[1][0]) {
            $command = 'alarmwrn';
            $port = 100;
        }
        $prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $port . "' AND COMMAND='" . $command . "'");
        $prop['COMMAND'] = $command;
        $prop['NUM'] = $port;
        $prop['COMMENT'] = 'GSM WARNING ALARM STATUS';
        $prop['DEVICE_ID'] = $record['ID'];
        $prop['CURRENT_VALUE'] = '0';
        $prop['CURRENT_VALUE_STRING'] = '0';
        if (preg_match('/m=(\d+)/', $line, $m3)) {
            $prop['MODE'] = $m[1][0];
        }
        if (!$prop['ID']) {
            $prop['ID'] = SQLInsert('megadproperties', $prop);
        } else {
            SQLUpdate('megadproperties', $prop);
        }

    }
}
