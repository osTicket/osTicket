<?php
/*********************************************************************
    class.format.php

    Collection of helper function used for formatting

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/


class Format {


    function file_size($bytes) {

        if(!is_numeric($bytes))
            return $bytes;
        if($bytes<1024)
            return $bytes.' bytes';
        if($bytes < (900<<10))
            return round(($bytes/1024),1).' kb';

        return round(($bytes/1048576),1).' mb';
    }

    /* encode text into desired encoding - taking into accout charset when available. */
    function encode($text, $charset=null, $encoding='utf-8') {

        //Try auto-detecting charset/encoding
        if (!$charset && function_exists('mb_detect_encoding'))
            $charset = mb_detect_encoding($text);

        // Cleanup - incorrect, bogus, or ambiguous charsets
        // ISO-8859-1 is assumed for empty charset.
        if (!$charset || in_array(strtolower(trim($charset)),
                array('default','x-user-defined','iso','us-ascii')))
            $charset = 'ISO-8859-1';

        $original = $text;
        if (function_exists('iconv'))
            $text = iconv($charset, $encoding.'//IGNORE', $text);
        elseif (function_exists('mb_convert_encoding'))
            $text = mb_convert_encoding($text, $encoding, $charset);
        elseif (!strcasecmp($encoding, 'utf-8')
                && function_exists('utf8_encode')
                && !strcasecmp($charset, 'ISO-8859-1'))
            $text = utf8_encode($text);

        // If $text is false, then we have a (likely) invalid charset, use
        // the original text and assume 8-bit (latin-1 / iso-8859-1)
        // encoding
        return (!$text && $original) ? $original : $text;
    }

    //Wrapper for utf-8 encoding.
    function utf8encode($text, $charset=null) {
        return Format::encode($text, $charset, 'utf-8');
    }

    function mimedecode($text, $encoding='UTF-8') {

        if(function_exists('imap_mime_header_decode')
                && ($parts = imap_mime_header_decode($text))) {
            $str ='';
            foreach ($parts as $part)
                $str.= Format::encode($part->text, $part->charset, $encoding);

            $text = $str;
        } elseif(function_exists('iconv_mime_decode')) {
            $text = iconv_mime_decode($text, 0, $encoding);
        } elseif(!strcasecmp($encoding, 'utf-8') && function_exists('imap_utf8')) {
            $text = imap_utf8($text);
        }

        return $text;
    }

    /**
     * Decodes filenames given in the content-disposition header according
     * to RFC5987, such as filename*=utf-8''filename.png. Note that the
     * language sub-component is defined in RFC5646, and that the filename
     * is URL encoded (in the charset specified)
     */
    function decodeRfc5987($filename) {
        $match = array();
        if (preg_match("/([\w!#$%&+^_`{}~-]+)'([\w-]*)'(.*)$/",
                $filename, $match))
            // XXX: Currently we don't care about the language component.
            //      The  encoding hint is sufficient.
            return self::utf8encode(urldecode($match[3]), $match[1]);
        else
            return $filename;
    }

    /**
     * Json Encoder
     *
     */
    function json_encode($what) {
        require_once (INCLUDE_DIR.'class.json.php');
        return JsonDataEncoder::encode($what);
    }

	function phone($phone) {

		$stripped= preg_replace("/[^0-9]/", "", $phone);
		if(strlen($stripped) == 7)
			return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2",$stripped);
		elseif(strlen($stripped) == 10)
			return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3",$stripped);
		else
			return $phone;
	}

    function truncate($string,$len,$hard=false) {

        if(!$len || $len>strlen($string))
            return $string;

        $string = substr($string,0,$len);

        return $hard?$string:(substr($string,0,strrpos($string,' ')).' ...');
    }

    function strip_slashes($var) {
        return is_array($var)?array_map(array('Format','strip_slashes'),$var):stripslashes($var);
    }

    function wrap($text, $len=75) {
        return $len ? wordwrap($text, $len, "\n", true) : $text;
    }

    function html($html, $config=array('balance'=>1)) {
        require_once(INCLUDE_DIR.'htmLawed.php');
        $spec = false;
        if (isset($config['spec']))
            $spec = $config['spec'];
        return htmLawed($html, $config, $spec);
    }

    function html2text($html, $width=74, $tidy=true) {


        # Tidy html: decode, balance, sanitize tags
        if($tidy)
            $html = Format::html(Format::htmldecode($html), array('balance' => 1));

        # See if advanced html2text is available (requires xml extension)
        if (function_exists('convert_html_to_text')
                && extension_loaded('dom'))
            return convert_html_to_text($html, $width);

        # Try simple html2text  - insert line breaks after new line tags.
        $html = preg_replace(
                array(':<br ?/?\>:i', ':(</div>)\s*:i', ':(</p>)\s*:i'),
                array("\n", "$1\n", "$1\n\n"),
                $html);

        # Strip tags, decode html chars and wrap resulting text.
        return Format::wrap(
                Format::htmldecode( Format::striptags($html, false)),
                $width);
    }

    static function __html_cleanup($el, $attributes=0) {
        static $eE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1,
            'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1);
        // Clean unexpected class values
        if (isset($attributes['class'])) {
            $classes = explode(' ', $attributes['class']);
            foreach ($classes as $i=>$a)
                // Unset all unsupported style classes -- anything but M$
                if (strpos($a, 'Mso') !== 0)
                    unset($classes[$i]);
            if ($classes)
                $attributes['class'] = implode(' ', $classes);
            else
                unset($attributes['class']);
        }
        // Clean browser-specific style attributes
        if (isset($attributes['style'])) {
            $styles = preg_split('/;\s*/S', html_entity_decode($attributes['style']));
            $props = array();
            foreach ($styles as $i=>&$s) {
                @list($prop, $val) = explode(':', $s);
                if (isset($props[$prop])) {
                    unset($styles[$i]);
                    continue;
                }
                $props[$prop] = true;
                // Remove unset or browser-specific style rules
                if (!$val || !$prop || $prop[0] == '-' || substr($prop, 0, 4) == 'mso-')
                    unset($styles[$i]);
                // Remove quotes of properties without enclosed space
                if (!strpos($val, ' '))
                    $val = str_replace('"','', $val);
                else
                    $val = str_replace('"',"'", $val);
                $s = "$prop:".trim($val);
            }
            unset($s);
            if ($styles)
                $attributes['style'] = Format::htmlencode(implode(';', $styles));
            else
                unset($attributes['style']);
        }
        $at = '';
        if (is_array($attributes)) {
            foreach ($attributes as $k=>$v)
                $at .= " $k=\"$v\"";
            return "<{$el}{$at}".(isset($eE[$el])?" /":"").">";
        }
        else {
            return "</{$el}>";
        }
    }

    function safe_html($html) {
        // Remove HEAD and STYLE sections
        $html = preg_replace(
            array(':<(head|style|script).+?</\1>:is', # <head> and <style> sections
                  ':<!\[[^]<]+\]>:',            # <![if !mso]> and friends
                  ':<!DOCTYPE[^>]+>:',          # <!DOCTYPE ... >
                  ':<\?[^>]+>:',                # <?xml version="1.0" ... >
            ),
            array('', '', '', ''),
            $html);
        $config = array(
            'safe' => 1, //Exclude applet, embed, iframe, object and script tags.
            'balance' => 1, //balance and close unclosed tags.
            'comment' => 1, //Remove html comments (OUTLOOK LOVE THEM)
            'tidy' => -1,
            'deny_attribute' => 'id',
            'schemes' => 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https; src: cid, http, https, data',
            'hook_tag' => function($e, $a=0) { return Format::__html_cleanup($e, $a); },
            'elements' => '*+iframe',
            'spec' => 'iframe=-*,height,width,type,src(match="`^(https?:)?//(www\.)?(youtube|dailymotion|vimeo)\.com/`i"),frameborder; div=data-mid',
        );

        return Format::html($html, $config);
    }

    function localizeInlineImages($text) {
        // Change image.php urls back to content-id's
        return preg_replace('/image\\.php\\?h=([\\w.-]{32})\\w{32}/',
            'cid:$1', $text);
    }

    function sanitize($text, $striptags=false) {

        //balance and neutralize unsafe tags.
        $text = Format::safe_html($text);

        $text = self::localizeInlineImages($text);

        //If requested - strip tags with decoding disabled.
        return $striptags?Format::striptags($text, false):$text;
    }

    function htmlchars($var) {
        return Format::htmlencode($var);
    }

    function htmlencode($var) {

        if (is_array($var))
            return array_map(array('Format', 'htmlencode'), $var);

        $flags = ENT_COMPAT;
        if (phpversion() >= '5.4.0')
            $flags |= ENT_HTML401;

        try {
            return htmlentities( (string) $var, $flags, 'UTF-8', false);
        } catch(Exception $e) {
            return $var;
        }
    }

    function htmldecode($var) {

        if(is_array($var))
            return array_map(array('Format','htmldecode'), $var);

        $flags = ENT_COMPAT;
        if (phpversion() >= '5.4.0')
            $flags |= ENT_HTML401;

        return html_entity_decode($var, $flags, 'UTF-8');
    }

    function input($var) {
        return Format::htmlencode($var);
    }

    //Format text for display..
    function display($text, $inline_images=true) {
        // Make showing offsite images optional
        $text = preg_replace_callback('/<img ([^>]*)(src="http[^"]+")([^>]*)\/>/',
            function($match) {
                // Drop embedded classes -- they don't refer to ours
                $match = preg_replace('/class="[^"]*"/', '', $match);
                return sprintf('<span %s class="non-local-image" data-%s %s></span>',
                    $match[1], $match[2], $match[3]);
            },
            $text);

        //make urls clickable.
        $text = Format::clickableurls($text);

        if ($inline_images)
            return self::viewableImages($text);

        return $text;
    }

    function striptags($var, $decode=true) {

        if(is_array($var))
            return array_map(array('Format','striptags'), $var, array_fill(0, count($var), $decode));

        return strip_tags($decode?Format::htmldecode($var):$var);
    }

    //make urls clickable. Mainly for display
    function clickableurls($text, $trampoline=true) {
        global $ost;

        $token = $ost->getLinkToken();

        // Find all text between tags
        $text = preg_replace_callback(':^[^<]+|>[^<]+:',
            function($match) use ($token, $trampoline) {
                // Scan for things that look like URLs
                return preg_replace_callback(
                    '`(?<!>)(((f|ht)tp(s?)://|(?<!//)www\.)([-+~%/.\w]+)(?:[-?#+=&;%@.\w]*)?)'
                   .'|(\b[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,4})`',
                    function ($match) use ($token, $trampoline) {
                        if ($match[1]) {
                            while (in_array(substr($match[1], -1),
                                    array('.','?','-',':',';'))) {
                                $match[9] = substr($match[1], -1) . $match[9];
                                $match[1] = substr($match[1], 0, strlen($match[1])-1);
                            }
                            if (strpos($match[2], '//') === false) {
                                $match[1] = 'http://' . $match[1];
                            }
                            if ($trampoline)
                                return '<a href="l.php?url='.urlencode($match[1])
                                    .sprintf('&auth=%s" target="_blank">', $token)
                                    .$match[1].'</a>'.$match[9];
                            else
                                return sprintf('<a href="%s">%s</a>%s',
                                    $match[1], $match[1], $match[9]);
                        } elseif ($match[6]) {
                            return sprintf('<a href="mailto:%1$s" target="_blank">%1$s</a>',
                                $match[6]);
                        }
                    },
                    $match[0]);
            },
            $text);

        // Now change @href and @src attributes to come back through our
        // system as well
        $config = array(
            'hook_tag' => function($e, $a=0) use ($token) {
                static $eE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1,
                    'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1);
                if ($e == 'a' && $a) {
                    if (isset($a['href'])
                            && strpos($a['href'], 'mailto:') !== 0
                            && strpos($a['href'], 'l.php?') === false)
                        $a['href'] = 'l.php?url='.urlencode($a['href'])
                            .'&amp;auth='.$token;
                    // ALL link targets open in a new tab
                    $a['target'] = '_blank';
                    $a['class'] = 'no-pjax';
                }
                // Images which are external are rewritten to <div
                // data-src='url...'/>
                elseif ($e == 'span' && $a && isset($a['data-src']))
                    $a['data-src'] = 'l.php?url='.urlencode($a['data-src'])
                        .'&amp;auth='.$token;
                // URLs for videos need to route too
                elseif ($e == 'iframe' && $a && isset($a['src']))
                    $a['src'] = 'l.php?url='.urlencode($a['src'])
                        .'&amp;auth='.$token;
                $at = '';
                if (is_array($a)) {
                    foreach ($a as $k=>$v)
                        $at .= " $k=\"$v\"";
                    return "<{$e}{$at}".(isset($eE[$e])?" /":"").">";
                }
                else {
                    return "</{$e}>";
                }
            },
            'schemes' => 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https; src: cid, http, https, data',
            'elements' => '*+iframe',
            'spec' => 'span=data-src,width,height',
        );
        return Format::html($text, $config);
    }

    function stripEmptyLines($string) {
        return preg_replace("/\n{3,}/", "\n\n", trim($string));
    }


    function viewableImages($html, $script='image.php') {
        return preg_replace_callback('/"cid:([\w._-]{32})"/',
        function($match) use ($script) {
            $hash = $match[1];
            if (!($file = AttachmentFile::lookup($hash)))
                return $match[0];
            return sprintf('"%s?h=%s" data-cid="%s"',
                $script, $file->getDownloadHash(), $match[1]);
        }, $html);
    }


    /**
     * Thanks, http://us2.php.net/manual/en/function.implode.php
     * Implode an array with the key and value pair giving
     * a glue, a separator between pairs and the array
     * to implode.
     * @param string $glue The glue between key and value
     * @param string $separator Separator between pairs
     * @param array $array The array to implode
     * @return string The imploded array
    */
    function array_implode( $glue, $separator, $array ) {

        if ( !is_array( $array ) ) return $array;

        $string = array();
        foreach ( $array as $key => $val ) {
            if ( is_array( $val ) )
                $val = implode( ',', $val );

            $string[] = "{$key}{$glue}{$val}";
        }

        return implode( $separator, $string );
    }

    /* elapsed time */
    function elapsedTime($sec) {

        if(!$sec || !is_numeric($sec)) return "";

        $days = floor($sec / 86400);
        $hrs = floor(bcmod($sec,86400)/3600);
        $mins = round(bcmod(bcmod($sec,86400),3600)/60);
        if($days > 0) $tstring = $days . 'd,';
        if($hrs > 0) $tstring = $tstring . $hrs . 'h,';
        $tstring =$tstring . $mins . 'm';

        return $tstring;
    }

    /* Dates helpers...most of this crap will change once we move to PHP 5*/
    function db_date($time) {
        global $cfg;
        return Format::userdate($cfg->getDateFormat(), Misc::db2gmtime($time));
    }

    function db_datetime($time) {
        global $cfg;
        return Format::userdate($cfg->getDateTimeFormat(), Misc::db2gmtime($time));
    }

    function db_daydatetime($time) {
        global $cfg;
        return Format::userdate($cfg->getDayDateTimeFormat(), Misc::db2gmtime($time));
    }

    function userdate($format, $gmtime) {
        return Format::date($format, $gmtime, $_SESSION['TZ_OFFSET'], $_SESSION['TZ_DST']);
    }

    function date($format, $gmtimestamp, $offset=0, $daylight=false){

        if(!$gmtimestamp || !is_numeric($gmtimestamp))
            return "";

        $offset+=$daylight?date('I', $gmtimestamp):0; //Daylight savings crap.

        return date($format, ($gmtimestamp+ ($offset*3600)));
    }

    // Thanks, http://stackoverflow.com/a/2955878/1025836
    /* static */
    function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\p{L}\p{N}]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // lowercase
        $text = strtolower($text);

        return (empty($text)) ? 'n-a' : $text;
    }

    /**
     * Parse RFC 2397 formatted data strings. Format according to the RFC
     * should look something like:
     *
     * data:[type/subtype][;charset=utf-8][;base64],data
     *
     * Parameters:
     * $data - (string) RFC2397 formatted data string
     * $output_encoding - (string:optional) Character set the input data
     *      should be encoded to.
     * $always_convert - (bool|default:true) If the input data string does
     *      not specify an input encding, assume iso-8859-1. If this flag is
     *      set, the output will always be transcoded to the declared
     *      output_encoding, if set.
     *
     * Returs:
     * array (data=>parsed and transcoded data string, type=>MIME type
     * declared in the data string or text/plain otherwise)
     *
     * References:
     * http://www.ietf.org/rfc/rfc2397.txt
     */
    function parseRfc2397($data, $output_encoding=false, $always_convert=true) {
        if (substr($data, 0, 5) != "data:")
            return array('data'=>$data, 'type'=>'text/plain');

        $data = substr($data, 5);
        list($meta, $contents) = explode(",", $data, 2);
        if ($meta)
            list($type, $extra) = explode(";", $meta, 2);
        else
            $extra = '';
        if (!isset($type) || !$type)
            $type = 'text/plain';

        $parameters = explode(";", $extra);

        # Handle 'charset' hint in $extra, such as
        # data:text/plain;charset=iso-8859-1,Blah
        # Convert to utf-8 since it's the encoding scheme for the database.
        $charset = ($always_convert) ? 'iso-8859-1' : false;
        foreach ($parameters as $p) {
            list($param, $value) = explode('=', $extra);
            if ($param == 'charset')
                $charset = $value;
            elseif ($param == 'base64')
                $contents = base64_decode($contents);
        }
        if ($output_encoding && $charset)
            $contents = Format::encode($contents, $charset, $output_encoding);

        return array(
            'data' => $contents,
            'type' => $type
        );
    }

}
?>
