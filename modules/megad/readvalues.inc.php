<?php


$record = SQLSelectOne("SELECT * FROM megaddevices WHERE ID='" . (int)$id . "'");


$url = 'http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?cmd=all';
if ($all) {
    $stateData = $all;
} else {
    $stateData = getURL($url, 0);
}

if ($_GET['debug']) {
    dprint($url."\n".$stateData,false);



 }

//debmes($stateData, 'megadrx');

if ($stateData=='') return;

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
    $current_prop=SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='".$record['ID']."' AND NUM='".$i."' AND COMMAND_INDEX=0");
    if (preg_match('/^i2c/',$current_prop['COMMAND'])) continue;

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
    if (preg_match_all('/(temp|hum):([\-\d\.]+)/',$states[$i],$m)) {
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
if (!$quick && $prop['ID']) {
$url='http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?tget=1';
    $stateData = getURL($url, 0);
    dprint($url."\n".$stateData,false);

    if ($stateData != '') {
        $commands[] = array('NUM' => 0, 'COMMAND' => 'inttemp', 'VALUE' => $stateData);
    }
}


//internal GSM alarm 
$prop = SQLSelectOne("SELECT * FROM megadproperties WHERE DEVICE_ID='" . $record['ID'] . "' AND COMMAND='alarm'");
if (!$quick && $prop['ID']) {
$url='http://' . $record['IP'] . '/' . $record['PASSWORD'] . '/?cf=1';
$config=getURL($url, 0);
// dprint($url."\n".$config,false);

$preg=preg_match_all('/Mode: (.+?)<br>/m', $config,$m, PREG_SET_ORDER, 0);
//debmes($m, 'megad');
$mode=$m[0][1];
//debmes($mode, 'megad');
//unset ($stateData);

if ($mode=='disarm')        {$stateData='0';
}
if ($mode=='<b>ARM</b>')    {$stateData='1';}






//}
//$stateData = 'comming';
    if (($stateData != '')&&(strlen($stateData))<20) {
//        echo "arm: ".$stateData."<br>";
        $commands[] = array('NUM' => 100, 'COMMAND' => 'alarm', 'VALUE' => $stateData);
//debmes($commands, 'megad');
    }
}




$i2c_properties=SQLSelect("SELECT * FROM megadproperties WHERE DEVICE_ID='".$record['ID']."' AND COMMAND LIKE 'i2c%' ORDER BY NUM");
if (!$quick && $stateData!='' && $i2c_properties[0]['ID']) {
    include_once(DIR_MODULES.$this->name.'/libs/i2c_com.class.php');
    include_once(DIR_MODULES.$this->name.'/libs/i2c_functions.inc.php');
    foreach($i2c_properties as $property) {
        $scl=$property['NUM'];
        $sda=$property['ADD_NUM'];
        $i2c_com=new i2c_com('http://'.$record['IP'].'/'.$record['PASSWORD'].'/?',$scl,$sda,$record['I2C_VERSION']);
        if ($property['COMMAND']=='i2c_htu21d') {
            include_once(DIR_MODULES.$this->name.'/libs/i2c_htu21d.inc.php');
            $temperature = get_htu21d_temperature($i2c_com);
            if (is_numeric($temperature)) {
                $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'temperature','INDEX'=>1, 'VALUE' => $temperature);
                $humidity = get_htu21d_humidity($i2c_com);
                if (is_numeric($humidity)) {
                    $hum_compensated = round($humidity + (25 - $temperature) * -0.15,2);
                    $commands[] = array('NUM' => $property['NUM'], 'COMMAND' => 'humidity','INDEX'=>1, 'VALUE' => $hum_compensated);
                }
            }
        }
    }
}

//debmes('123', 'megad');
//debmes($commands, 'megad');
//debmes('1count commands '.count($commands), 'megad');
//debmes($commands, 'megad');
//debmes('2count commands '.count($commands), 'megad');




foreach ($commands as $command) {
    $this->processCommand($record['ID'],$command);
//debmes('processCommand '.$record['ID'].":".$command, 'megad');

}

if ($_GET['debug']) {    dprint($commands);}
