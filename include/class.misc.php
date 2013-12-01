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

	function randCode($count=8, $chars=false) {
        $chars = $chars ? $chars
            : 'abcdefghijklmnopqrstuvwzyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-.';
        $data = '';
        $m = strlen($chars) - 1;
        for ($i=0; $i < $count; $i++)
            $data .= $chars[mt_rand(0,$m)];
        return $data;
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
    function randNumber($len=6,$start=false,$end=false) {

        $start=(!$len && $start)?$start:str_pad(1,$len,"0",STR_PAD_RIGHT);
        $end=(!$len && $end)?$end:str_pad(9,$len,"9",STR_PAD_RIGHT);

        return mt_rand($start,$end);
    }

    /* misc date helpers...this will go away once we move to php 5 */
    function db2gmtime($var){
        global $cfg;
        if(!$var) return;

        $dbtime=is_int($var)?$var:strtotime($var);
        return $dbtime-($cfg->getDBTZoffset()*3600);
    }

    //Take user time or gmtime and return db (mysql) time.
    function dbtime($var=null){
         global $cfg;

        if(is_null($var) || !$var)
            $time=Misc::gmtime(); //gm time.
        else{ //user time to GM.
            $time=is_int($var)?$var:strtotime($var);
            $offset=$_SESSION['TZ_OFFSET']+($_SESSION['TZ_DST']?date('I',$time):0);
            $time=$time-($offset*3600);
        }
        //gm to db time
        return $time+($cfg->getDBTZoffset()*3600);
    }

    /*Helper get GM time based on timezone offset*/
    function gmtime() {
        return time()-date('Z');
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

        $hr =is_null($hr)?0:$hr;
        $min =is_null($min)?0:$min;

        //normalize;
        if($hr>=24)
            $hr=$hr%24;
        elseif($hr<0)
            $hr=0;

        if($min>=45)
            $min=45;
        elseif($min>=30)
            $min=30;
        elseif($min>=15)
            $min=15;
        else
            $min=0;

        ob_start();
        echo sprintf('<select name="%s" id="%s">',$name,$name);
        echo '<option value="" selected>'.__('Time').'</option>';
        $format = $cfg->getTimeFormat();
        for($i=23; $i>=0; $i--) {
            for($minute=45; $minute>=0; $minute-=15) {
                $sel=($hr==$i && $min==$minute)?'selected="selected"':'';
                $_minute=str_pad($minute, 2, '0',STR_PAD_LEFT);
                $_hour=str_pad($i, 2, '0',STR_PAD_LEFT);
                $disp = date($format, $i*3600 + $minute*60);
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

class FuzzyHash {
    var $hashes = array();
    var $original;

    static function fromText($text) {
        return self::fromStringAndTokens($text, array(
            "\n", ">"));
    }

    static function fromHtml($html) {
        return self::fromStringAndTokens($html, array(
            //'<br[^>]*>', '<p[^>]*>', '<div[^>]*>', '<blockquote[^>]*>'));
            '<[^>]+>'));
    }

    static function fromStringAndTokens($text, $tokens, $length=3) {
        // TODO: Standardize line endings in $text
        $text = str_replace("\r", "", $text);
        $sections = preg_split('~'.implode('|', $tokens).'~', $text, -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        $fuzzy = new FuzzyHash();
        foreach ($sections as $s) {
            if (isset($bucket))
                $bucket[0] .= trim($s[0]);
            else
                $bucket = array(trim($s[0]), $s[1]);
            // Ensure that bucket meets minimum length
            if (strlen($bucket[0]) < $length) continue;
            $hash = base64_encode(substr(md5($bucket[0], true), -$length));
            $fuzzy->hashes[] = array($hash, $bucket);
            unset($bucket);
        }
        $fuzzy->original = $text;
        return $fuzzy;
    }

    function toString() {
        $hashes = array();
        foreach ($this->hashes as $info)
            $hashes[] = $info[0];
        return implode('', $hashes);
    }
    function __toString() {
        return $this->toString();
    }

    function getSlices($other) {
        $cleaned = array();
        $foreign = str_split($other, 4);
        foreach ($this->hashes as $info) {
            list($h, list(, $start)) = $info;
            if (isset($current))
                $current['stops'][] = $start;
            if (in_array($h, $foreign)) {
                // This hash is in the list (match). Set the stop point at
                // the first char of the first unwanted block
                if (!isset($current) || isset($current['keep'])) {
                    if (isset($current))
                        $cleaned[] = $current;
                    $current = array('start'=>$start, 'stops'=>array(),
                        'drop'=>true);
                }
                // Drop all hashes between the current location and the
                // matched hash from the foreign list
                while (array_shift($foreign) != $h);
            }
            else {
                // No match. This block was not in the original text
                if (!isset($current) || isset($current['drop'])) {
                    if (isset($current))
                        $cleaned[] = $current;
                    $current = array('start'=>$start, 'stops'=>array(),
                        'keep'=>true);
                }
            }
        }
        $cleaned[] = $current;
        return $cleaned;
    }

    function getScore($other) {
        $slices = $this->getSlices($other);
        $matched = 0;

        // For now, we will define the score as the characters matched
        // divided by the total length of the text.
        foreach ($slices as $s) {
            if (isset($s['keep']) && $s['keep']) {
                if ($s['stops'])
                    $matched += array_pop($s['stops']) - $s['start'];
                else
                    $matched += strlen($this->original) - $s['start'];
            }
        }
        return 1 - ($matched / strlen($this->original));
    }

    /**
     * Use the digest (string value) from another fuzzy hash to remove data
     * from the original text used to create this hash.
     *
     * Parameters:
     * $other - (string) digest value from a FuzzyHash object. @see
     *      ::toString()
     * $window - (int|default:0) number of sections to keep from a removed
     *      section preceeding a kept section. In other words, if text is to
     *      be removed between two kept sections, keep this number of blocks
     *      at the end of the section to-be-removed.
     */
    function remove($other, $window=0) {
        $cleaned = $this->getSlices($other);
        // Copy the original text to the cleaned
        $output = '';
        foreach ($cleaned as $c) {
            if (isset($c['drop']) && count($c['stops']) > $window)
                continue;
            // TODO: If the previous section was 'drop'ed, perhaps start
            // with the last couple of lines from it before starting this
            // 'keep'ed section. This can be easily accomplished by changing
            // the 'start' value with one of the last 'stops' values from
            // the previous section
            if ($c['stops'])
                $output .= substr($this->original, $c['start'],
                    array_pop($c['stops']) - $c['start']);
            else
                $output .= substr($this->original, $c['start']);
        }
        return $output;
    }
}

/*
 * Extends the fuzzy hash by creating several different types of FuzzyHash
 * instances (by breaking the text in different ways). In doing so, the text
 * can be auto-sensed as text or html (with help for faster processing).
 *
 * When this object is converted to a string, all the fuzzy hashes are
 * represented, so the returned, modified text can be automatically tested
 * against all fuzzy hashes and match methods to automatically detect the
 * best match.
 */
class SmartFuzzyHash {
    var $hashes = array();

    # Filter through htmLawed
    const SCRUB_SAFE_HTML = 1;
    # Remove auto links like <a href="text">text</a>
    const SCRUB_AUTO_LINKS = 2;

    function addText($text) {
        $this->hashes[] = FuzzyHash::fromText($text);
    }

    function addHtml($html, $scrubMask=-1) {
        if ($scrubMask & self::SCRUB_SAFE_HTML)
            $html = Format::safe_html($html);

        $this->hashes[] = FuzzyHash::fromHtml($html);

        // Some mail clients will turn things that look like links into
        // links. This can cause mismatches and can be easily undone
        if ($scrubMask & self::SCRUB_AUTO_LINKS) {
            $T = preg_replace(
                '`<a href="(mailto:|https?://)?((https?://)?[^"]+)">\2</a>`',
                '$2', $html);
            $this->hashes[] = FuzzyHash::fromHtml($T);
        }
    }

    static function fromString($repr) {
    }

    function remove($other, $typeHint=false) {
        $scores = array();
        foreach (explode(',', $other) as $l)
            foreach ($this->hashes as $h)
                $scores[] = array($h->getScore($l), $h, $l);

        usort($scores, function($a,$b) { return $a[0] - $b[0]; });
        $best = array_shift($scores);

        $cleaned = $best[1]->remove($best[2]);
        return $this->clean($cleaned);
    }

    function clean($what) {
        // TODO: Get type hint from best score
        // Sanitize
        $cleaned = Format::safe_html($what);
        // Remove trailing breaks
        $cleaned = preg_replace(
            array('`<br ?/?>$`', '`<div[^>]*><br ?/?></div>$`'),
            array('',''),
            rtrim($cleaned));
        return $cleaned;
    }
}

?>
