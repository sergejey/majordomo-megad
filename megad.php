<?php
include_once("./config.php");
include_once("./lib/loader.php");
include_once(DIR_MODULES . "application.class.php");

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once("./load_settings.php");
include_once(DIR_MODULES . 'megad/megad.class.php');
$megad = new megad();
$megad->getConfig();
if ($megad->config['API_DEBUG']) {
    DebMes("Request: " . $_SERVER['REQUEST_URI'] . " (" . $_SERVER['REMOTE_ADDR'] . ")", 'megad');
}

$result = $megad->processRequest();
if ($megad->config['API_DEBUG']) {
    DebMes("Result: " . $result, 'megad');
}

$db->Disconnect();

@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
@ini_set('implicit_flush', 1);
@ob_implicit_flush(true);
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
header('X-Accel-Buffering: no');
header('Content-Length: '.strlen($result));
echo $result;
flush();