<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Скрипт для отображение данных на дисплее OLED с контроллером SSD1306
Использует драйвер SSD1306 и библиотеку I2C-PHP
*/

define("SCL", "8");
define("SDA", "9");
define("MD", "http://192.168.0.14/sec/?");

// Вариант реализации I2C: 1 - полностью программный; 2 - частично аппаратный (прошивка 3.43beta1 и выше)
define("V", "2");

require_once("mod_i2c_ssd1306.php");

ssd1306_init();
ssd1306_clear_display();

/*** Отображение текста ***/
ssd1306_write_text("Температура: 24.62", "verdana_10", 0, 0); //
//ssd1306_write_text("22.82 ", "verdana_10", 84, 0);
ssd1306_write_text("Влажность: 47%", "verdana_10", 0, 2);
ssd1306_write_text("   ХОРОШЕГО ДНЯ!", "mistral_10", 0, 5);

/*** Отображение графики ***/
//ssd1306_draw_pic("ab_log_logo");

?>