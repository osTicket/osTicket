<?php
/*
 * Base32 encoder/decoder
 *
 * Jared Hancock <jared@osticket.com>
 * Copyright (c) osTicket.com
 */


class Base32 {

    /**
     * encode a binary string
     *
     * @param    $inString   Binary string to base32 encode
     * @return   $outString  Base32 encoded $inString
     *
     * Original code from
     * http://www.phpkode.com/source/p/moodle/moodle/lib/base32.php. Optimized
     * to double performance
     */

    static function encode($inString)
    {
        $outString = "";
        $compBits = "";
        static $BASE32_TABLE = array(
            '00000' => 'a', '00001' => 'b', '00010' => 'c', '00011' => 'd',
            '00100' => 'e', '00101' => 'f', '00110' => 'g', '00111' => 'h',
            '01000' => 'i', '01001' => 'j', '01010' => 'k', '01011' => 'l',
            '01100' => 'm', '01101' => 'n', '01110' => 'o', '01111' => 'p',
            '10000' => 'q', '10001' => 'r', '10010' => 's', '10011' => 't',
            '10100' => 'u', '10101' => 'v', '10110' => 'w', '10111' => 'x',
            '11000' => 'y', '11001' => 'z', '11010' => '0', '11011' => '1',
            '11100' => '2', '11101' => '3', '11110' => '4', '11111' => '5');

        /* Turn the compressed string into a string that represents the bits as 0 and 1. */
        for ($i = 0, $k = strlen($inString); $i < $k; $i++) {
            $compBits .= str_pad(decbin(ord($inString[$i])), 8, '0', STR_PAD_LEFT);
        }

        /* Pad the value with enough 0's to make it a multiple of 5 */
        if ((($len = strlen($compBits)) % 5) != 0) {
            $compBits = str_pad($compBits, $len+(5-($len % 5)), '0', STR_PAD_RIGHT);
        }

        /* Create an array by chunking it every 5 chars */
        $fiveBitsArray = str_split($compBits, 5);

        /* Look-up each chunk and add it to $outstring */
        foreach ($fiveBitsArray as $fiveBitsString) {
            $outString .= $BASE32_TABLE[$fiveBitsString];
        }

        return $outString;
    }



    /**
     * decode to a binary string
     *
     * @param    $inString   String to base32 decode
     *
     * @return   $outString  Base32 decoded $inString
     *
     * @access   private
     *
     */

    static function decode($inString) {
        /* declaration */
        $deCompBits = '';
        $outString = '';

        static $BASE32_TABLE = array(
            'a' => '00000', 'b' => '00001', 'c' => '00010', 'd' => '00011',
            'e' => '00100', 'f' => '00101', 'g' => '00110', 'h' => '00111',
            'i' => '01000', 'j' => '01001', 'k' => '01010', 'l' => '01011',
            'm' => '01100', 'n' => '01101', 'o' => '01110', 'p' => '01111',
            'q' => '10000', 'r' => '10001', 's' => '10010', 't' => '10011',
            'u' => '10100', 'v' => '10101', 'w' => '10110', 'x' => '10111',
            'y' => '11000', 'z' => '11001', '0' => '11010', '1' => '11011',
            '2' => '11100', '3' => '11101', '4' => '11110', '5' => '11111');

        /* Step 1 */
        $inputCheck = strlen($inString) % 8;
        if(($inputCheck == 1)||($inputCheck == 3)||($inputCheck == 6)) {
            trigger_error('input to Base32Decode was a bad mod length: '.$inputCheck);
            return false;
        }

        /* $deCompBits is a string that represents the bits as 0 and 1.*/
        for ($i = 0, $k = strlen($inString); $i < $k; $i++) {
            $inChar = $inString[$i];
            if(isset($BASE32_TABLE[$inChar])) {
                $deCompBits .= $BASE32_TABLE[$inChar];
            } else {
                trigger_error('input to Base32Decode had a bad character: '.$inChar);
                return false;
            }
        }

        /* Break the decompressed string into octets for returning */
        foreach (str_split($deCompBits, 8) as $chunk) {
            if (strlen($chunk) != 8) {
                // Ensure correct padding
                if (substr_count($chunk, '1')>0) {
                    trigger_error('found non-zero padding in Base32Decode');
                    return false;
                }
                break;
            }
            $outString .= chr(bindec($chunk));
        }

        return $outString;
    }
}

?>
