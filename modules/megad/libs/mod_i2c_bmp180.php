<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Это драйвер для датчика атмосферного давления BMP180 для библиотеки I2C-PHP
*/

require_once("mod_i2c_lib.php");
$b5 = 0;

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

function get_calibration()
{
	$cal_regaddr = array("AC1" => "AA", "AC2" => "AC", "AC3" => "AE", "AC4" => "B0", "AC5" => "B2", "AC6" => "B4", "B1" => "B6", "B2" => "B8", "MB" => "BA", "MC" => "BC", "MD" => "BE" );
	$cfg_data = "";

	echo "Reading calibration data ";

	i2c_init();
	i2c_start();

	foreach ( $cal_regaddr as $key => $value )
	{
		i2c_send("EE");
		i2c_send($value);
		i2c_stop();
		i2c_start();
		i2c_send("EF");
		$msb = i2c_read();
		$lsb = i2c_read(1);
		if ( strlen($msb) == 1 )
		$msb = "0$msb";
		if ( strlen($lsb) == 1 )
		$lsb = "0$lsb";
	
		if ( $key == "AC4" || $key == "AC5" || $key == "AC6" )
		{
			$val = hexdec($msb);
			$val = $val << 8;
			$val = $val + hexdec($lsb);
		}
		else
                $val = hexdecs($msb.$lsb);

		//echo "$key => MSB: $msb; LSB: $lsb; Val: $val\n";
		$cfg_data .= $val.";";
		echo ".";
	}
	echo " done\n";

	$cfg = fopen("mod_i2c_bmp180.cfg", "w");
	fwrite($cfg, $cfg_data);
	fclose($cfg);

}

function get_temperature()
{
	global $b5;

	if ( !file_exists("mod_i2c_bmp180.cfg") )
	get_calibration();

	$cfg = explode(";", file_get_contents("mod_i2c_bmp180.cfg"));

	i2c_init();
	i2c_start();

	i2c_send("EE");
	i2c_send("F4");
	i2c_send("2E");
	i2c_stop();
	i2c_start();
	i2c_send("EE");
	i2c_send("F6");
	i2c_stop();
	i2c_start();
	i2c_send("EF");
	$msb = i2c_read();
	$lsb = i2c_read(1);
	i2c_stop();

	if ( strlen($msb) == 1 )
	$msb = "0$msb";
	if ( strlen($lsb) == 1 )
	$lsb = "0$lsb";

	$ut = hexdec($msb.$lsb);
	//echo "MSB: $msb; LSB: $lsb; UT: $ut\n";
	$x1 = round(($ut - $cfg[5]) * $cfg[4] / 32768);
	$x2 = round($cfg[9] * 2048 / ($x1 + $cfg[10]));
	$b5 = $x1 + $x2;
	$t = round(($b5 + 8) / 16);
	//echo "temp: $t ($x1, $x2, $b5)\n";
	return $t / 10;
}

function get_pressure()
{
	global $b5;

	if ( !file_exists("mod_i2c_bmp180.cfg") )
	get_calibration();

	$cfg = explode(";", file_get_contents("mod_i2c_bmp180.cfg"));

	get_temperature();
	$oss = 1;

	i2c_init();
	i2c_start();

	i2c_send("EE");
	i2c_send("F4");
	i2c_send("74");
	i2c_stop();
	//usleep(50000);
	i2c_start();
	i2c_send("EE");
	i2c_send("F6");
	i2c_stop();
	i2c_start();
	i2c_send("EF");
	$msb = i2c_read();
	$lsb = i2c_read();
	$xlsb = i2c_read(1);
	i2c_stop();

	if ( strlen($msb) == 1 )
	$msb = "0$msb";
	if ( strlen($lsb) == 1 )
	$lsb = "0$lsb";
	if ( strlen($xlsb) == 1 )
	$xlsb = "0$xlsb";
	$up = hexdec($msb.$lsb.$xlsb);
	$up = $up >> (8 - $oss);
	//echo "MSB: $msb; LSB: $lsb; XLSB: $xlsb; UP: $up\n";

	$b6 = $b5 - 4000;
	$x1 = round(($cfg[7] * ($b6 * $b6 / 4096)) / 2048);
	$x2 = round($cfg[1] * $b6 / 2048);
	$x3 = $x1 + $x2;
	$b3 = ($cfg[0] * 4 + $x3);
	$b3 = $b3 << $oss;
	$b3 = round(($b3 + 2)/4);
	$x1 = round($cfg[2] * $b6 / 8192);
	$x2 = round(($cfg[6] * ($b6 * $b6 / 4096)) / 65536);
	$x3 = round((($x1 + $x2) + 2) / 4);
	$b4 = round($cfg[3] * ($x3 + 32768) / 32768);
	$temp = 50000 >> $oss;
	$b7 = ($up - $b3) * ($temp);
	if ($b7 < 2147483648 )
	$p = round(($b7 * 2) / $b4);
	else
	$p = round(($b7 / $b4) * 2);
	$x1 = round(($p / 256) * ($p / 256));
	$x1 = round(($x1 * 3038) / 65536);
	$x2 = round((-7357 * $p) / 65536);
	$p = round($p + ($x1 + $x2 + 3791) / 16);
	$p_mm = round($p / 133.322, 2);

	return ($p_mm);

	//$p0 = $p / pow(1 - 70/44330, 5.255);
	//$p_mm = round($p / 133.322, 2);
	//$p0_mm = round($p0 / 133.322, 2);
	//echo "Pa: $p; Pa_mm: $p_mm / $p0_mm\n";

}

