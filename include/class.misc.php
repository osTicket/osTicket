<?php
/*********************************************************************
    class.misc.php

    Misc collection of useful generic helper functions.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Misc {

	function randCode($len=8, $chars=false) {
        $chars = $chars ?: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_=';

        // Determine the number of bits we need
        $char_count = strlen($chars);
        $bits_per_char = ceil(log($char_count, 2));
        $bytes = ceil(4 * $len / floor(32 / $bits_per_char));
        // Pad to 4 byte boundary
        $bytes += (4 - ($bytes % 4)) % 4;

        // Fetch some random data blocks
        $data = Crypto::random($bytes);

        $mask = (1 << $bits_per_char) - 1;
        $loops = (int) (32 / $bits_per_char);
        $output = '';
        $ints = unpack('V*', $data);
        foreach ($ints as $int) {
            for ($i = $loops; $i > 0; $i--) {
                $output .= $chars[($int & $mask) % $char_count];
                $int >>= $bits_per_char;
            }
        }
        return substr($output, 0, $len);
	}

    function __rand_seed($value=0) {
        // Form a 32-bit figure for the random seed with the lower 16-bits
        // the microseconds of the current time, and the upper 16-bits from
        // received value
        $seed = ((int) $value % 65535) << 16;
        $seed += (int) ((double) microtime() * 1000000) % 65535;
        mt_srand($seed);
    }

    /* Helper used to generate ticket IDs */
    function randNumber($len=6) {
        $number = '';
        for ($i=0; $i<$len; $i++) {
            $min = ($i == 0) ? 1 : 0;
            $number .= mt_rand($min, 9);
        }

        return (int) $number;
    }

    /* misc date helpers...this will go away once we move to php 5 */
    function db2gmtime($var){
        static $dbtz;
        global $cfg;

        if (!$var || !$cfg)
            return;

        if (!isset($dbtz))
            $dbtz = new DateTimeZone($cfg->getDbTimezone());

        $dbtime = is_int($var) ? $var : strtotime($var);
        $D = DateTime::createFromFormat('U', $dbtime);
        if (!$D)
            // This happens e.g. from negative timestamps
            return $var;

        return $dbtime - $dbtz->getOffset($D);
    }

    // Take user's time and return GMT time.
    function user2gmtime($timestamp=null, $user=null) {
        global $cfg;

        $tz = new DateTimeZone($cfg->getTimezone($user));

        if ($timestamp && is_int($timestamp)) {
            if (!($date = DateTime::createFromFormat('U', $timestamp)))
                return $timestamp;

            return $timestamp - $tz->getOffset($date);
        }

        $date = new DateTime($timestamp ?: 'now', $tz);
        return $date ? $date->getTimestamp() : $timestamp;
    }

    //Take user time or gmtime and return db (mysql) time.
    function dbtime($var=null){
        static $dbtz;
        global $cfg;

        if (is_null($var) || !$var) {
            // Default timezone is set to UTC
            $time = time();
        } else {
            // User time to UTC
            $time = self::user2gmtime($var);
        }

        if (!isset($dbtz)) {
            $dbtz = new DateTimeZone($cfg->getDbTimezone());
        }
        // UTC to db time
        $D = DateTime::createFromFormat('U', $time);
        return $time + $dbtz->getOffset($D);
    }

    /*Helper get GM time based on timezone offset*/
    function gmtime($time=false, $user=false) {
        global $cfg;

        $tz = new DateTimeZone($user ? $cfg->getDbTimezone($user) : 'UTC');

       if ($time && is_numeric($time))
          $time = DateTime::createFromFormat('U', $time);
        elseif (!($time = new DateTime($time ?: 'now'))) {
            // Old standard
            return time() - date('Z');
        }

        return $time->getTimestamp() - $tz->getOffset($time);
    }

    /* Needed because of PHP 4 support */
    function micro_time() {
        list($usec, $sec) = explode(" ", microtime());

        return ((float)$usec + (float)$sec);
    }

    //Current page
    function currentURL() {

        $str = 'http';
        if ($_SERVER['HTTPS'] == 'on') {
            $str .='s';
        }
        $str .= '://';
        if (!isset($_SERVER['REQUEST_URI'])) { //IIS???
            $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1 );
            if (isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING'];
            }
        }
        if ($_SERVER['SERVER_PORT']!=80) {
            $str .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
        } else {
            $str .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }

        return $str;
    }

    function timeDropdown($hr=null, $min =null,$name='time') {
        global $cfg;

        //normalize;
        if ($hr >= 24)
            $hr = $hr%24;
        elseif ($hr < 0)
            $hr = 0;
        elseif ($hr)
            $hr = (int) $hr;
        else  // Default to 5pm
            $hr = 17;

        if ($min >= 45)
            $min = 45;
        elseif ($min >= 30)
            $min = 30;
        elseif ($min >= 15)
            $min = 15;
        else
            $min = 0;

        $time = Misc::user2gmtime(mktime(0,0,0));
        ob_start();
        echo sprintf('<select name="%s" id="%s" style="display:inline-block;width:auto">',$name,$name);
        echo '<option value="" selected="selected">&mdash;'.__('Time').'&mdash;</option>';
        for($i=23; $i>=0; $i--) {
            for ($minute=45; $minute>=0; $minute-=15) {
                $sel=($hr===$i && $min===$minute) ? 'selected="selected"' : '';
                $_minute=str_pad($minute, 2, '0',STR_PAD_LEFT);
                $_hour=str_pad($i, 2, '0',STR_PAD_LEFT);
                $disp = Format::time($time + ($i*3600 + $minute*60 + 1), false);
                echo sprintf('<option value="%s:%s" %s>%s</option>',$_hour,$_minute,$sel,$disp);
            }
        }
        echo '</select>';
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    function realpath($path) {
        $rp = realpath($path);
        return $rp ? $rp : $path;
    }

}
?>
