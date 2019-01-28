<?php

$record = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . (int)$id . "'");


$url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?cmd=all';
if ($all) {
    $stateData = $all;
} else {
    $stateData = getURL($url, 0);
}

//echo $stateData;exit;
$commands = array();
$states = explode(';', $stateData);
$total = count($states);
for ($i = 0; $i < $total; $i++) {
    $matched=0;
    /*
    $prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$record['ID']."' AND NUM='".$i."'");
    $type=(int)$prop['TYPE'];
    $mode=(int)$prop['MODE'];
    $cmd=(int)$prop['COMMAND'];
    */
    $current_prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$record['ID']."' AND NUM='".$i."'");

    if ($states[$i] == 'ON') {
        $cmd = array('NUM' => $i, 'VALUE' => 1, 'COMMAND' => 'output');
        $commands[] = $cmd;
        $matched=1;
    }
    if ($states[$i] == 'OFF') {
        $cmd = array('NUM' => $i, 'VALUE' => 0, 'COMMAND' => 'output');
        $commands[] = $cmd;
        $matched=1;
    }
    if (preg_match('/(ON|OFF)\/(\d+)/', $states[$i], $m)) {
        if ($m[1] == 'ON') {
            $cmd = array('NUM' => $i, 'VALUE' => 1, 'COMMAND' => 'input');
         } else {
            $cmd = array('NUM' => $i, 'VALUE' => 0, 'COMMAND' => 'input');
         }
        $commands[] = $cmd;
        $matched=1;
        $cmd = array('NUM' => $i, 'VALUE' => $m[2], 'COMMAND' => 'counter');
        $commands[] = $cmd;
        $matched=1;
    }
    if (preg_match_all('/(temp|hum):([\d\.]+)/',$states[$i],$m)) {
        $totalm=count($m[1]);
        for($im=0;$im<$totalm;$im++) {
            if ($m[1][$im]=='temp') {
                $cmd = array('NUM' => $i, 'VALUE' => $m[2][$im], 'COMMAND' => 'temperature','INDEX'=>$im);
                $commands[] = $cmd;
                $matched=1;
            } else {
                $cmd = array('NUM' => $i, 'VALUE' => $m[2][$im], 'COMMAND' => 'humidity','INDEX'=>$im);
                $commands[] = $cmd;
                $matched=1;
            }
        }
    }
    if (!$matched) {
        if ($current_prop['ID']) {
            $cmd = array('NUM' => $i, 'VALUE' => $states[$i], 'COMMAND' => $current_prop['COMMAND']);
            $commands[] = $cmd;
        }
    }
    //echo $stateData;exit;

}


//internal temp sensor data
$prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND COMMAND='inttemp'");
if ($prop['ID']) {
    $stateData = getURL('http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?tget=1', 0);
    if ($stateData != '') {
        $commands[] = array('NUM' => 0, 'COMMAND' => 'inttemp', 'VALUE' => $stateData);
    }
}

if ($_GET['debug']) {
    dprint($url."\n".$stateData,false);
    dprint($commands);
}

foreach ($commands as $command) {
    $this->processCommand($record['ID'],$command);
}