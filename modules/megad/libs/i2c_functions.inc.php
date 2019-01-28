<?php

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