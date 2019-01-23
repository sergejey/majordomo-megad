<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Это драйвер для датчика датчика освещенности BH1750 для библиотеки I2C-PHP
*/

require_once("mod_i2c_lib.php");

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

function get_lux()
{
	i2c_init();

	// Enable
	i2c_start();
	i2c_send("52");
	i2c_send("A0");
	i2c_send("03");
	i2c_stop();


	// Integration time (100 ms) + Gain
	i2c_start();
	i2c_send("52");
	i2c_send("A1");
	i2c_send("10");
	i2c_stop();


	// Get values
	i2c_start();
	i2c_send("52");
	i2c_send("B4");
	i2c_stop();

	// Reading data
	i2c_start();
	i2c_send("53");

	$ch0_l = i2c_read();
	$ch0_h = i2c_read();
	$ch1_l = i2c_read();
	$ch1_h = i2c_read(1);

	//echo "$ch0_l - $ch0_h - $ch1_l - $ch1_h\n";

	if ( strlen($ch0_l) == 1 )
	$ch0_l = "0$ch0_l";
	if ( strlen($ch0_h) == 1 )
	$ch0_h = "0$ch0_h";
	if ( strlen($ch1_l) == 1 )
	$ch1_l = "0$ch1_l";
	if ( strlen($ch1_h) == 1 )
	$ch1_h = "0$ch1_h";

	$ch0_l = hexdec($ch0_l);
	$ch0_h = hexdec($ch0_h);
	$ch1_l = hexdec($ch1_l);
	$ch1_h = hexdec($ch1_h);


	$ch0_raw = ($ch0_h << 8) | $ch0_l;
	$ch1_raw = ($ch1_h << 8) | $ch1_l;

	i2c_stop();

	//echo "Raw CH0: $ch0_raw\n";
	//echo "Raw CH1: $ch1_raw\n";

	/*
	LUX_COEFB = 1.64  # CH0 coefficient
	LUX_COEFC = 0.59  # CH1 coefficient A
	LUX_COEFD = 0.86  # CH2 coefficient B
	*/

	$cpl = 100 * 25 / 408;
	$lux1 = ($ch0_raw - (1.64 * $ch1_raw)) / $cpl;
	$lux2 = ((0.59 * $ch0_raw) - (0.86 * $ch1_raw)) / $cpl;
	//echo "Lux1: $lux1\n";
	//echo "Lux2: $lux2\n";
	$lux = max($lux1, $lux2);
	//echo "Lux: $lux\n";

	return $lux;

}

