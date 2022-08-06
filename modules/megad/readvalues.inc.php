<?php

$record = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . (int)$id . "'");

$url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?cmd=all';
if ($all) {
    $stateData = $all;
} else {
    if ($this->config['API_DEBUG']) {
        DebMes("Reading state:\n" . $url, 'megad');
    }
    $stateData = getURL($url, 0);
    if ($this->config['API_DEBUG']) {
        DebMes("State response:\n" . $stateData, 'megad');
    }
}
if ($_GET['debug']) {
    dprint($url . "\n" . $stateData, false);
}
if ($stateData == '') return;
$commands = array();
$states = explode(';', $stateData);
$total = count($states);

for ($i = 0; $i < $total; $i++) {
    $matched = 0;
    $current_prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND NUM='" . $i . "' AND COMMAND_INDEX=0");
    if (preg_match('/^i2c/', $current_prop['COMMAND'])) continue;

    if ($states[$i] == 'ON') {
        $cmd = array('NUM' => $i, 'VALUE' => 1, 'COMMAND' => 'output');
        $commands[] = $cmd;
        $matched = 1;
    }
    if ($states[$i] == 'OFF') {
        $cmd = array('NUM' => $i, 'VALUE' => 0, 'COMMAND' => 'output');
        $commands[] = $cmd;
        $matched = 1;
    }
    if ($states[$i] == 'MCP') {
        $cmd = array('NUM' => $i, 'VALUE' => 'MCP', 'COMMAND' => 'output');
        $commands[] = $cmd;
        $matched = 1;
    }
    if (preg_match('/(ON|OFF)\/(\d+)/', $states[$i], $m)) {
        if ($m[1] == 'ON') {
            $cmd = array('NUM' => $i, 'VALUE' => 1, 'COMMAND' => 'input');
        } else {
            $cmd = array('NUM' => $i, 'VALUE' => 0, 'COMMAND' => 'input');
        }
        $commands[] = $cmd;
        $matched = 1;
        $cmd = array('NUM' => $i, 'VALUE' => $m[2], 'COMMAND' => 'counter');
        $commands[] = $cmd;
        $matched = 1;
    }
    if (preg_match_all('/(temp|hum):([\-\d\.]+)/', $states[$i], $m)) {
        $totalm = count($m[1]);
        for ($im = 0; $im < $totalm; $im++) {
            if ($m[1][$im] == 'temp') {
                $cmd = array('NUM' => $i, 'VALUE' => $m[2][$im], 'COMMAND' => 'temperature', 'INDEX' => 1);
                $commands[] = $cmd;
                $matched = 1;
            } else {
                $cmd = array('NUM' => $i, 'VALUE' => $m[2][$im], 'COMMAND' => 'humidity', 'INDEX' => 1);
                $commands[] = $cmd;
                $matched = 1;
            }
        }
    }
    if (preg_match('/pm1:([\-\d\.]+)/',$states[$i],$m)) {
        $cmd = array('NUM' => $i, 'VALUE' => $m[1], 'COMMAND' => 'pm1', 'INDEX' => 1);
        $commands[] = $cmd;
        $matched = 1;
    }
    if (preg_match('/pm2.5:([\-\d\.]+)/',$states[$i],$m)) {
        $cmd = array('NUM' => $i, 'VALUE' => $m[1], 'COMMAND' => 'pm2.5', 'INDEX' => 1);
        $commands[] = $cmd;
        $matched = 1;
    }
    if (preg_match('/pm10:([\-\d\.]+)/',$states[$i],$m)) {
        $cmd = array('NUM' => $i, 'VALUE' => $m[1], 'COMMAND' => 'pm10', 'INDEX' => 1);
        $commands[] = $cmd;
        $matched = 1;
    }
    if (!$matched) {
        if ($current_prop['ID']) {
            $cmd = array('NUM' => $i, 'VALUE' => $states[$i], 'COMMAND' => $current_prop['COMMAND']);
            $commands[] = $cmd;
        } else {
            $cmd = array('NUM' => $i, 'VALUE' => $states[$i], 'COMMAND' => 'output');
            $commands[] = $cmd;
        }
    }
}


//internal temp sensor data
$prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND COMMAND='inttemp'");
if (!$quick && $prop['ID']) {
    $url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?tget=1';
    $stateData = getURL($url, 0);
    if (is_numeric($stateData)) {
        $commands[] = array('NUM' => 0, 'COMMAND' => 'inttemp', 'VALUE' => $stateData);
    }
}


//internal GSM alarm 
$prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND COMMAND='alarm'");
if (!$quick && $prop['ID']) {
    $url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?cf=1';
    $config = getURL($url, 0);

    $preg = preg_match_all('/Mode: (.+?)<br>/m', $config, $m, PREG_SET_ORDER, 0);
    $mode = $m[0][1];

    if ($mode == 'disarm') {
        $stateData = '0';
    }
    if ($mode == '<b>ARM</b>') {
        $stateData = '1';
    }


    if (($stateData != '') && (strlen($stateData)) < 20) {
        //echo "arm: ".$stateData."<br>";
        $commands[] = array('NUM' => 100, 'COMMAND' => 'alarm', 'VALUE' => $stateData);
    }
}


$i2c_properties = SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND COMMAND LIKE 'i2c%' ORDER BY NUM");
if ($stateData != '' && $i2c_properties[0]['ID']) {
    foreach ($i2c_properties as $property) {
        if (!$quick && $property['COMMAND'] == 'i2c_htu21d_sda') {
            include_once(DIR_MODULES . $this->name . '/libs/i2c_com.class.php');
            include_once(DIR_MODULES . $this->name . '/libs/i2c_functions.inc.php');
            $sda = $property['NUM'];
            $scl = $property['ADD_NUM'];
            if (!$scl || !$sda) continue;
            $url ='http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$sda.'&scl='.$scl.'&i2c_dev=htu21d&i2c_par=1';
            $temperature = getURL($url);
            if (is_numeric($temperature)) {
                $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'temperature', 'INDEX' => 1, 'VALUE' => $temperature);
                $url ='http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$sda.'&scl='.$scl.'&i2c_dev=htu21d';
                sleep(1);
                $humidity = getURL($url);
                if (is_numeric($humidity)) {
                    $hum_compensated = round($humidity + (25 - $temperature) * -0.15, 2);
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'humidity', 'INDEX' => 1, 'VALUE' => $hum_compensated);
                }
            }
            /*
            $i2c_com = new i2c_com('http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?', $scl, $sda, $record['I2C_VERSION']);
            include_once(DIR_MODULES . $this->name . '/libs/i2c_htu21d.inc.php');
            $temperature = get_htu21d_temperature($i2c_com);
            dprint($temperature);
            if (is_numeric($temperature)) {
                $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'temperature', 'INDEX' => 1, 'VALUE' => $temperature);
                $humidity = get_htu21d_humidity($i2c_com);
                if (is_numeric($humidity)) {
                    $hum_compensated = round($humidity + (25 - $temperature) * -0.15, 2);
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'humidity', 'INDEX' => 1, 'VALUE' => $hum_compensated);
                }
            }
            */
        } elseif (!$quick && $property['COMMAND'] == 'i2c_ptsensor') {
            $sda = $property['NUM'];
            $scl = $property['ADD_NUM'];
            if (!$scl || !$sda) continue;
            $url ='http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$sda.'&scl='.$scl.'&i2c_dev=ptsensor&i2c_par=1';
            $data = getURL($url);
            sleep(1);
            $url ='http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$sda.'&scl='.$scl.'&i2c_dev=ptsensor&i2c_par=2';
            $data = getURL($url);
            if ($data!='') {
                $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => $property['COMMAND'], 'VALUE' => $data);
            }
        } elseif (!$quick && $property['COMMAND'] == 'i2c_htu21d') {
            include_once(DIR_MODULES . $this->name . '/libs/i2c_com.class.php');
            include_once(DIR_MODULES . $this->name . '/libs/i2c_functions.inc.php');
            $sda = $property['ADD_NUM'];
            $scl = $property['NUM'];
            if (!$scl || !$sda) continue;
            $i2c_com = new i2c_com('http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?', $scl, $sda, $record['I2C_VERSION']);
            include_once(DIR_MODULES . $this->name . '/libs/i2c_htu21d.inc.php');
            $temperature = get_htu21d_temperature($i2c_com);
            if (is_numeric($temperature)) {
                $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'temperature', 'INDEX' => 1, 'VALUE' => $temperature);
                $humidity = get_htu21d_humidity($i2c_com);
                if (is_numeric($humidity)) {
                    $hum_compensated = round($humidity + (25 - $temperature) * -0.15, 2);
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'humidity', 'INDEX' => 1, 'VALUE' => $hum_compensated);
                }
            }
        } elseif ($property['COMMAND'] == 'i2c_16pwm_sda') {
            $url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$property['NUM'].'&cmd=get';
            $data = getURL($url);
            $ar = explode(';',$data);
            $totalc = count($ar);
            if ($totalc==16) {
                for($ic=0;$ic<$totalc;$ic++) {
                    $v = (int)$ar[$ic];
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'output', 'INDEX' => ($ic+1), 'VALUE' => $v);
                }
            }
        } elseif ($property['COMMAND'] == 'i2c_16i_xt_sda') {
            $url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$property['NUM'].'&cmd=get';
            $data = getURL($url);
            $ar = explode(';',$data);
            $totalc = count($ar);
            if ($totalc==16) {
                for($ic=0;$ic<$totalc;$ic++) {
                    if ($ar[$ic]=='ON') {
                        $v=1;
                    } else {
                        $v=0;
                    }
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'input', 'INDEX' => ($ic+1), 'VALUE' => $v);
                }
            }
        } elseif ($property['COMMAND'] == 'i2c_16ir_xt_sda') {
            $url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$property['NUM'].'&cmd=get';
            $data = getURL($url);
            $ar = explode(';',$data);
            $totalc = count($ar);
            if ($totalc==16) {
                for($ic=0;$ic<$totalc;$ic++) {
                    if ($ar[$ic]=='ON') {
                        $v=1;
                    } else {
                        $v=0;
                    }
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'output', 'INDEX' => ($ic+1), 'VALUE' => $v);
                }
            }
        } elseif ($property['COMMAND'] == 'i2c_16i_xt') {
            $url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?pt='.$property['ADD_NUM'].'&cmd=get';
            $data = getURL($url);
            $ar = explode(';',$data);
            $totalc = count($ar);
            if ($totalc==16) {
                for($ic=0;$ic<$totalc;$ic++) {
                    if ($ar[$ic]=='ON') {
                        $v=1;
                    } else {
                        $v=0;
                    }
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'input', 'INDEX' => ($ic+1), 'VALUE' => $v);
                }
            }
        }
    }
}

foreach ($commands as $command) {
    $this->processCommand($record['ID'], $command);
}

if ($_GET['debug']) {
    dprint($commands);
}
