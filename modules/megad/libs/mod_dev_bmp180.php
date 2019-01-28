<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Скрипт для работы с датчиком атмосферного давления BMP180
Использует драйвер BMP180 и библиотеку I2C-PHP
*/

define("SCL", "8");
define("SDA", "9");
define("MD", "http://192.168.0.14/sec/?");
// Вариант реализации I2C: 1 - полностью программный; 2 - частично аппаратный (прошивка 3.43beta1 и выше)
//define("V", "2");
require_once("mod_i2c_bmp180.php");
echo get_pressure()."\n";
