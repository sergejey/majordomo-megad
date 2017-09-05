<?php
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
@apache_setenv('no-gzip', 1);   

include_once("./config.php");
include_once("./lib/loader.php");
include_once(DIR_MODULES."application.class.php");

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME); 

include_once("./load_settings.php");
include_once(DIR_MODULES.'megad/megad.class.php');
$megad=new megad();
$megad->getConfig();
if ($megad->config['API_DEBUG']) {
 DebMes("Request: ".$_SERVER['REQUEST_URI']. " (".$_SERVER['REMOTE_ADDR'].")",'megad');
}
$megad->processRequest();

$db->Disconnect(); 

