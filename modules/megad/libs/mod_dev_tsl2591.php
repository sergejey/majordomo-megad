<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Скрипт для работы с датчиком освещенности TLS2591
Использует драйвер BH1750 и библиотеку I2C-PHP
*/

define("SCL", "34");
define("SDA", "35");
define("MD", "http://192.168.0.14/sec/?");

// Вариант реализации I2C: 1 - полностью программный; 2 - частично аппаратный (прошивка 3.43beta1 и выше)
define("V", "1");

require_once("mod_i2c_tsl2591.php");

$lux = get_lux();

echo "Lux: $lux\n";

?>
