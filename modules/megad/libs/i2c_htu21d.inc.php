<?php

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

function get_htu21d_temperature($i2c)
{
    $i2c->i2c_init();
    $i2c->i2c_start();

    $i2c->i2c_send("80");
    $i2c->i2c_send("E3");
    $i2c->i2c_stop();
    $i2c->i2c_start();
    $i2c->i2c_send("81");
    $msb = $i2c->i2c_read();
    $lsb = $i2c->i2c_read();
    $crc = $i2c->i2c_read(1);
    $i2c->i2c_stop();

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

function get_htu21d_humidity($i2c)
{
    $i2c->i2c_init();
    $i2c->i2c_start();

    $i2c->i2c_send("80");
    $i2c->i2c_send("E5");
    $i2c->i2c_stop();
    $i2c->i2c_start();
    $i2c->i2c_send("81");
    $msb = $i2c->i2c_read();
    $lsb = $i2c->i2c_read();
    $crc = $i2c->i2c_read(1);
    $i2c->i2c_stop();

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