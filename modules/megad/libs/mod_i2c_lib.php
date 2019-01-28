<?
/*
* Copyright (c) 2016, Andrey_B
* http://ab-log.ru
* Подробнее см. LICENSE.txt или http://www.gnu.org/licenses/
*/

/*
Библиотека I2C-PHP для MegaD-328
Ver: 1.1 (2016-02-21)
*/


define("HIGH", 1);
define("LOW", 0);

function i2c_init()
{
	i2c_dir(SCL, "OUT");
	i2c_dir(SDA, "OUT");
	file_get_contents(MD."cmd=".SCL.":".HIGH.";".SDA.":".HIGH);
}

function i2c_start()
{
	file_get_contents(MD."cmd=".SDA.":".LOW.";".SCL.":".LOW);
}

function i2c_stop()
{
	//file_get_contents(MD."cmd=".SCL.":".HIGH);
	//file_get_contents(MD."cmd=".SCL.":".LOW);

	file_get_contents(MD."cmd=".SDA.":".LOW.";".SCL.":".HIGH.";".SDA.":".HIGH);

}

function i2c_dir($port, $dir)
{
	if ( $dir == "OUT" )
	$dir = 1;
	else
	$dir = 0;
	file_get_contents(MD."pt=$port&dir=$dir");
}

function i2c_send($data)
{
	if ( defined("V") && V == 2 )
	{
		//if ( V == 2 )
		file_get_contents(MD."pt=".SDA."&i2c=".hexdec($data)."&scl=".SCL.":1;".SCL.":0;");
		//file_get_contents(MD."pt=11&i2c=".hexdec($data)."&scl=10:1;10:0;");
	}
	else
	{
		$data_bin = decbin(hexdec($data));
		$len = strlen($data_bin);
		for ( $i = 8; $i > $len; $i-- )
		$data_bin = "0".$data_bin;

		$old_bit = 3;
		$cmd = "";
		$cnt = 0;

		for ( $i = 0; $i < 8; $i++ )
		{
			// Вариант 1
			//file_get_contents(MD."cmd=".SDA.":".$data_bin[$i]);
			//file_get_contents(MD."cmd=".SCL.":".HIGH);
			//file_get_contents(MD."cmd=".SCL.":".LOW);
			//file_get_contents(MD."cmd=".SDA.":".LOW);
			
			// Вариант 2
			file_get_contents(MD."cmd=".SDA.":".$data_bin[$i].";".SCL.":".HIGH.";".SCL.":".LOW.";".SDA.":".LOW);

			/*
			// Вариант 3 - отправляем сразу 2 бита
			$cmd .= SDA.":".$data_bin[$i].";".SCL.":".HIGH.";".SCL.":".LOW.";".SDA.":".LOW.";";
			$cnt++;
			if ( $cnt == 2 )
			{
				file_get_contents(MD."cmd=$cmd");
				$cnt = 0;
				$cmd = "";
			}
			*/

		}

		// Вариант 1
		//i2c_dir(SDA, "IN");
		//file_get_contents(MD."cmd=".SCL.":".HIGH);
		//$ack = file_get_contents(MD."pt=".SDA."&cmd=get")."\n";
		//file_get_contents(MD."cmd=".SCL.":".LOW);

		//echo $ack;

		// Вариант 2
		file_get_contents(MD."pt=".SDA."&dir=0&cmd=".SCL.":".HIGH.";".SCL.":".LOW);
		$ack = file_get_contents(MD."pt=".SDA."&cmd=get&dir=1");

		return $ack;
	}
}

function i2c_read($nack = 0)
{
	$data_bits = "";
	$bit = "";

	file_get_contents(MD."pt=".SDA."&dir=0&cmd=".SCL.":".LOW);
	//file_get_contents(MD."cmd=".SCL.":".LOW);

	for ( $i = 0; $i < 8; $i++ )
	{
		file_get_contents(MD."cmd=".SCL.":".HIGH);
		$bit = file_get_contents(MD."pt=".SDA."&cmd=get");

		if ( $bit == "ON" )
		$data_bits .= "0";
		else
		$data_bits .= "1";

		file_get_contents(MD."cmd=".SCL.":".LOW);
	}

	i2c_dir(SDA, "OUT");
	file_get_contents(MD."cmd=".SCL.":".HIGH);
	if ( $nack == 1 )
	file_get_contents(MD."cmd=".SDA.":".HIGH);
	//file_get_contents(MD."cmd=".SCL.":".LOW);
	//i2c_dir(SDA, "IN");
	file_get_contents(MD."pt=".SDA."&dir=0&cmd=".SCL.":".LOW);

	//return dechex(bindec($data_bits))." - $data_bits\n";
	return dechex(bindec($data_bits));

}

