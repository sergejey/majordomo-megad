<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Это драйвер для датчика температуры и влажности HTU21D для библиотеки I2C-PHP
*/

require_once("mod_i2c_lib.php");

function check_htu21d_crc($raw_data, $crc)
{
	$remainder = $raw_data << 8;
	//$remainder |= $crc; //Add on the check value
 	$divsor = 0x988000;

	for ( $i = 0 ; $i < 16 ; $i++)
	{
		if ( $remainder & 1<<(23 - $i) )
		$remainder ^= $divsor;
		$divsor >>= 1;
	}

	//echo dechex($remainder)."\n";
	return dechex($remainder);
}

function hexdecs($hex)
{
    // ignore non hex characters
    $hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
   
    // converted decimal value:
    $dec = hexdec($hex);
   
    // maximum decimal value based on length of hex + 1:
    //   number of bits in hex number is 8 bits for each 2 hex -> max = 2^n
    //   use 'pow(2,n)' since '1 << n' is only for integers and therefore limited to integer size.
    $max = pow(2, 4 * (strlen($hex) + (strlen($hex) % 2)));
   
    // complement = maximum - converted hex:
    $_dec = $max - $dec;
   
    // if dec value is larger than its complement we have a negative value (first bit is set)
    return $dec >= $_dec ? -$_dec : $dec;
}

function get_htu21d_temperature()
{
	i2c_init();
	i2c_start();

	i2c_send("80");
	i2c_send("E3");
	i2c_stop();
	i2c_start();
	i2c_send("81");
	$msb = i2c_read();
	$lsb = i2c_read();
	$crc = i2c_read(1);
	i2c_stop();

	if ( strlen($msb) == 1 )
	$msb = "0$msb";
	if ( strlen($lsb) == 1 )
	$lsb = "0$lsb";
	//echo $msb.".".$lsb.".$crc\n";
	$msb = hexdec($msb);
	$lsb = hexdec($lsb);
	//$raw_temp = ($msb << 8) | ($lsb & 0b11111100);
	$raw_temp = ($msb << 8) | $lsb;

	if ( check_htu21d_crc($raw_temp, $crc) != $crc )
	return "CRC error - $crc";
	else
	{
		$raw_temp &= 0xFFFC;
		$temperature = number_format(round(-46.85 + 175.72 * ($raw_temp / 65536), 2), 2); 
		return $temperature;
	}
}

function get_htu21d_humidity()
{
	i2c_init();
	i2c_start();

	i2c_send("80");
	i2c_send("E5");
	i2c_stop();
	i2c_start();
	i2c_send("81");
	$msb = i2c_read();
	$lsb = i2c_read();
	$crc = i2c_read(1);
	i2c_stop();

	//$msb = "7C";
	//$lsb = "80";

	if ( strlen($msb) == 1 )
	$msb = "0$msb";
	if ( strlen($lsb) == 1 )
	$lsb = "0$lsb";
	//echo $msb.".".$lsb.".$crc\n";
	$msb = hexdec($msb);
	$lsb = hexdec($lsb);
	$raw_hum = ($msb << 8) | $lsb;

	if ( check_htu21d_crc($raw_hum, $crc) != $crc )
	return "CRC error - $crc";
	else
	{

		$raw_hum &= 0xFFFC;
		$humidity = number_format(round(-6 + (125 * ($raw_hum / 65536)), 2), 2);
		if ( $humidity > 100 )
		$humidity = 100;
		return $humidity;
	}

}

