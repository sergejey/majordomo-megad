<?php

include_once("./config.php");
include_once("./lib/loader.php");
include_once(DIR_MODULES."application.class.php");

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME); 

include_once("./load_settings.php");
include_once(DIR_MODULES.'megad/megad.class.php');
$megad=new megad();
$megad->processRequest();

$db->Disconnect(); 

