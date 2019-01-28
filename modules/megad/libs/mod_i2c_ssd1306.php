<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Драйвер для OLED дисплеев с контроллером SSD1306 и библиотеку I2C-PHP
*/

require_once("mod_i2c_lib.php");

function ssd1306_init()
{
	i2c_stop();
	i2c_init();
	i2c_start();

	i2c_send("78");
	i2c_send("00");

	i2c_send("AF"); // Display ON

	i2c_send("D5"); // Display Clock ?
	i2c_send("80"); // Default 80

	i2c_send("81"); // Contrast
	i2c_send("EE"); 

	//echo i2c_send("D4");
	//echo i2c_send("80");

	//echo i2c_send("A8"); // Multiplex Ratio
	//echo i2c_send("3F"); // Default 3F

	//echo i2c_send("D3"); // Set Display Offset Смещенеи по вертикали.
	//echo i2c_send("00"); // Default 00

	//echo i2c_send("40"); // Set Display Start Line - Смещение по вертикали. Default 40

	i2c_send("8D"); // Charge Pump (иначе не включится!)
	i2c_send("14");
	i2c_send("AF"); // Display ON

	i2c_send("A1"); // Set Segment Re-map // Default A0 слева направо или справа на лево
	i2c_send("C8"); // Set COM Output // Default C0 сверху вниз или снизу вверх

	//echo i2c_send("DA"); // Set COM Pins
	//echo i2c_send("12");

	i2c_send("A6");

	//i2c_send("20");
	//i2c_send("00");

	i2c_stop();
}

function ssd1306_clear_display()
{
	i2c_start();
	i2c_send("78");
	i2c_send("00");
	i2c_send("20");
	i2c_send("00");
	i2c_send("21");
	i2c_send("00");
	i2c_send("7F");
	i2c_send("22");
	i2c_send("00");
	i2c_send("07");

	i2c_stop();
	i2c_start();

	i2c_send("78");
	i2c_send("40");

	for ( $i = 0; $i < 1024; $i++ )
	i2c_send("00");

	i2c_stop();
}

function ssd1306_write_text($string, $font = "default", $col = 0, $page = 0)
{
	$my_string = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
	include("libs/ssd1306/mod_ssd1306_fonts.php");
	$my_font = $$font;

	i2c_start();
	i2c_send("78");
	i2c_send("00");
	i2c_send("20");
	i2c_send("41");
	i2c_send("21");
	i2c_send(dechex($col));
	i2c_send("7F");
	i2c_send("22");
	i2c_send(dechex($page));
	i2c_send(dechex($page + 1));

	i2c_stop();
	i2c_start();
	i2c_send("78");
	i2c_send("40");

	for ( $j = 0; $j < count($my_string); $j++ )
	{
		$flag = 1;
		//echo $my_string[$j]."\n";
		for ( $i = 0; $i < count($my_font[$my_string[$j]]); $i++ )
		{
			i2c_send(dechex($my_font[$my_string[$j]][$i + $flag]));
			$flag = $flag * -1;
		}
		i2c_send("00");
		i2c_send("00");
	}

	i2c_stop();
}

function ssd1306_draw_pic($pic = "ab_log_logo")
{
	i2c_stop();

	include("libs/ssd1306/mod_ssd1306_pics.php");
	$my_pic = $$pic;

	i2c_start();
	i2c_send("78");
	i2c_send("00");
	i2c_send("20");
	i2c_send("00");
	i2c_send("21");
	i2c_send("00");
	i2c_send("7F");
	i2c_send("22");
	i2c_send("00");
	i2c_send("07");

	i2c_stop();
	i2c_start();
	i2c_send("78");
	i2c_send("40");

	for ( $j = 0; $j < 1024; $j++ )
	i2c_send(dechex($my_pic[$j]));

	i2c_stop();
}

?>
