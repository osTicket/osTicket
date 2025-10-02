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

include_once INCLUDE_DIR.'class.charset.php';
require_once INCLUDE_DIR.'class.variable.php';

class Format {


    static function file_size($bytes) {

        if(!is_numeric($bytes))
            return $bytes;
        if($bytes<1024)
            return $bytes.' bytes';
        if($bytes < (900<<10))
            return round(($bytes/1024),1).' kb';

        return round(($bytes/1048576),1).' mb';
    }

    static function filesize2bytes($size) {
        switch (substr($size, -1)) {
        case 'M': case 'm': return (int)$size <<= 20;
        case 'K': case 'k': return (int)$size <<= 10;
        case 'G': case 'g': return (int)$size <<= 30;
        }

        return $size;
    }

    static function filename($filename) {
        return preg_replace('/[^a-zA-Z0-9\-\._]/', '-', $filename);
    }

    static function mimedecode($text, $encoding='UTF-8') {
        // Handle poorly or completely un-encoded header values (
        if (function_exists('mb_detect_encoding'))
            if (($src_enc = mb_detect_encoding($text))
                    && (strcasecmp($src_enc, 'ASCII') !== 0))
                return Charset::transcode($text, $src_enc, $encoding);

        if(function_exists('imap_mime_header_decode')
                && ($parts = imap_mime_header_decode($text))) {
            $str ='';
            foreach ($parts as $part)
                $str.= Charset::transcode($part->text, $part->charset, $encoding);

            $text = $str;
        } elseif($text[0] == '=' && function_exists('iconv_mime_decode')) {
            $text = iconv_mime_decode($text, 0, $encoding);
        } elseif(!strcasecmp($encoding, 'utf-8')
                && function_exists('imap_utf8')) {
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
    static function decodeRfc5987($filename) {
        $match = array();
        if (preg_match("/([\w!#$%&+^_`{}~-]+)'([\w-]*)'(.*)$/",
                $filename, $match))
            // XXX: Currently we don't care about the language component.
            //      The  encoding hint is sufficient.
            return Charset::utf8(urldecode($match[3]), $match[1]);
        else
            return $filename;
    }

    /**
     * Json Encoder
     *
     */
    static function json_encode($what) {
        require_once (INCLUDE_DIR.'class.json.php');
        return JsonDataEncoder::encode($what);
    }

	static function phone($phone) {

		$stripped= preg_replace("/[^0-9]/", "", $phone);
		if(strlen($stripped) == 7)
			return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2",$stripped);
		elseif(strlen($stripped) == 10)
			return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3",$stripped);
		else
			return $phone;
	}

    static function mask($str, $start = 0, $length = null, $mask="*") {
        $mask = preg_replace("/\S/", $mask, $str);
        if ($length)
            $str = substr_replace($str, substr($mask, $start, $length), $start, $length);
        else
            $str = substr_replace ($str, substr($mask, $start), $start);

        return $str;
    }

    static function shroud($str, $start=0, $length=null) {
        $str = $length ? substr($str, 0, $length) : $str;
        return self::mask($str, $start, $length);
    }


    static function truncate($string,$len,$hard=false) {

        if(!$len || $len>strlen($string))
            return $string;

        $string = substr($string,0,$len);

        return $hard?$string:(substr($string,0,strrpos($string,' ')).' ...');
    }

    static function strip_slashes($var) {
        return is_array($var)?array_map(array('Format','strip_slashes'),$var):stripslashes($var);
    }

    static function wrap($text, $len=75) {
        return $len ? wordwrap($text, $len, "\n", true) : $text;
    }

    static function html_balance($html, $remove_empty=true) {
        if (!extension_loaded('dom'))
            return $html;

        if (!trim($html))
            return $html;

        $doc = new DomDocument();
        $xhtml = '<?xml encoding="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'
            // Wrap the content in a <div> because libxml would use a <p>
            . "<div>$html</div>";
        $doc->encoding = 'utf-8';
        $doc->preserveWhitespace = false;
        $doc->recover = true;
        if (false === @$doc->loadHTML($xhtml))
            return $html;

        if ($remove_empty) {
            // Remove empty nodes
            $xpath = new DOMXPath($doc);
            static $eE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1,
                    'iframe' => 1, 'hr'=>1, 'img'=>1, 'input'=>1,
                    'isindex'=>1, 'param'=>1, 'div'=>1);
            do {
                $done = true;
                $nodes = $xpath->query('//*[not(text()) and not(node())]');
                foreach ($nodes as $n) {
                    if (isset($eE[$n->nodeName]))
                        continue;
                    $n->parentNode->removeChild($n);
                    $done = false;
                }
            } while (!$done);
        }

        static $phpversion;
        if (!isset($phpversion))
            $phpversion = phpversion();

        $body = $doc->getElementsByTagName('body');
        if (!$body->length)
            return $html;

        if ($phpversion > '5.3.6') {
            $html = $doc->saveHTML($doc->getElementsByTagName('body')->item(0)->firstChild);
        }
        else {
            $html = $doc->saveHTML();
            $html = preg_replace('`^<!DOCTYPE.+?>|<\?xml .+?>|</?html>|</?body>|</?head>|<meta .+?/?>`', '', $html); # <?php
        }
        return preg_replace('`^<div>|</div>$`', '', trim($html));
    }

    static function html($html, $config=array()) {
        require_once(INCLUDE_DIR.'htmLawed.php');
        $spec = false;
        if (isset($config['spec']))
            $spec = $config['spec'];

        // Add in htmLawed defaults
        $config += array(
            'balance' => 1,
        );

        // Attempt to balance using libxml. htmLawed will corrupt HTML with
        // balancing to fix improper HTML at the same time. For instance,
        // some email clients may wrap block elements inside inline
        // elements. htmLawed will change such block elements to inlines to
        // make the HTML correct.
        if ($config['balance'] && extension_loaded('dom')) {
            $html = self::html_balance($html);
            $config['balance'] = 0;
        }

        return htmLawed($html, $config, $spec);
    }

    static function html2text($html, $width=74, $tidy=true) {

        if (!$html)
            return $html;


        # Tidy html: decode, balance, sanitize tags
        if($tidy)
            $html = Format::html(Format::htmldecode($html), array('balance' => 1));

        # See if advanced html2text is available (requires xml extension)
        if (function_exists('convert_html_to_text')
                && extension_loaded('dom')
                && ($text = convert_html_to_text($html, $width)))
                return $text;

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

        // We're dealing with closing tag
        if ($attributes === 0)
            return "</{$el}>";

        // Remove iframe and embed without src (perhaps striped by spec)
        // It would be awesome to rickroll such entry :)
        if (in_array($el, array('iframe', 'embed'))
                && (!isset($attributes['src']) || empty($attributes['src'])))
            return '';

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
                $attributes['style'] = Format::htmlchars(implode(';', $styles));
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

    static function safe_html($html, $options=array()) {
        global $cfg;

        $options = array_merge(array(
                    // Balance html tags
                    'balance' => 1,
                    // Decoding special html char like &lt; and &gt; which
                    // can be used to skip cleaning
                    'decode' => true
                    ),
                $options);

        if ($options['decode'])
            $html = Format::htmldecode($html);

        // Remove HEAD and STYLE sections
        $html = preg_replace(
            array(':<(head|style|script).+?</\1>:is', # <head> and <style> sections
                  ':<!\[[^]<]+\]>:',            # <![if !mso]> and friends
                  ':<!DOCTYPE[^>]+>:',          # <!DOCTYPE ... >
                  ':<\?[^>]+>:',                # <?xml version="1.0" ... >
                  ':<html[^>]+:i',              # drop html attributes
                  ':<(a|span) (name|style)="(mso-bookmark\:)?_MailEndCompose">(.+)?<\/(a|span)>:', # Drop _MailEndCompose
                  ':<div dir=(3D)?"ltr">(.*?)<\/div>(.*):is', # drop Gmail "ltr" attributes
                  ':data-cid="[^"]*":',         # drop image cid attributes
                  '(position:[^!";]+;?)',
            ),
            array('', '', '', '', '<html', '$4', '$2 $3', '', ''),
            $html);

        // HtmLawed specific config only
        $config = array(
            'safe' => 1, //Exclude applet, embed, iframe, object and script tags.
            'balance' => $options['balance'],
            'comment' => 1, //Remove html comments (OUTLOOK LOVE THEM)
            'tidy' => -1,
            'elements' => '*-form-input-button',
            'deny_attribute' => 'id, formaction, action, on*',
            'schemes' => 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https; src: cid, http, https, data',
            'hook_tag' => function($e, $a=0) { return Format::__html_cleanup($e, $a); },
        );

        // iFrame Whitelist
        if ($cfg)
            $whitelist = $cfg->getIframeWhitelist();
        if (!empty($whitelist)) {
            $config['elements'] .= '+iframe';
            $config['spec'] = 'iframe=-*,height,width,type,style,src(match="`^(https?:)?//(www\.)?('
                .implode('|', $whitelist)
                .')(\?|/|#)([^@]*)$`i"),frameborder'.($options['spec'] ? '; '.$options['spec'] : '').',allowfullscreen';
        }

        return Format::html($html, $config);
    }

    static function localizeInlineImages($text) {
        // Change file.php urls back to content-id's
        return preg_replace(
            '`<img src="(?:https?:/)?(?:/[^/"]+)*?/file\\.php\\?(?:\w+=[^&"]+&(?:amp;)?)*?key=([^&]+)[^"]*`',
            '<img src="cid:$1', $text);
    }

    static function sanitize($text, $striptags=false, $spec=false) {
        // Localize inline images before sanitizing content
        $text = self::localizeInlineImages($text);

        //balance and neutralize unsafe tags.
        $text = Format::safe_html($text, array('spec' => $spec));

        //If requested - strip tags with decoding disabled.
        return $striptags?Format::striptags($text, false):$text;
    }

    static function htmlchars($var, $sanitize = false) {
        static $phpversion = null;

        if (is_array($var)) {
            $result = array();
            foreach ($var as $k => $v)
                $result[$k] = self::htmlchars($v, $sanitize);

            return $result;
        }

        if ($sanitize)
            $var = Format::sanitize($var);

        if (!isset($phpversion))
            $phpversion = phpversion();

        $flags = ENT_COMPAT;
        if ($phpversion >= '5.4.0')
            $flags |= ENT_HTML401;

        try {
            return htmlspecialchars( (string) $var, $flags, 'UTF-8', false);
        } catch(Exception $e) {
            return $var;
        }
    }

    static function htmldecode($var) {

        if(is_array($var))
            return array_map(array('Format','htmldecode'), $var);

        $flags = ENT_COMPAT;
        if (phpversion() >= '5.4.0')
            $flags |= ENT_HTML401;

        return htmlspecialchars_decode($var, $flags);
    }

    static function http_query_string(string $query, array $filter = null) {
        $args = [];
        parse_str($query, $args);
        if ($filter && is_array($filter))
            $args = array_diff_key($args, array_flip($filter));
        return http_build_query($args);
    }

    static function input($var) {
        return Format::htmlchars($var);
    }

    //Format text for display..
    static function display($text, $inline_images=true, $balance=true) {
        global $cfg;

        // Exclude external images?
        $exclude = !$cfg->allowExternalImages();
        // Allowed image extensions
        $allowed = array('gif', 'png', 'jpg', 'jpeg');

        // Make showing offsite images optional
        $text = preg_replace_callback('/<img ([^>]*)(src="http[^"]+")([^>]*)\/>/',
            function($match) use ($exclude, $allowed) {
                $m = array();
                // Split the src URL and get the extension
                preg_match('/src="([^"]+)"/', $match[2], $m);
                $part = parse_url($m[1], PHP_URL_PATH);
                $path = explode('.', $part);
                $ext = preg_split('/[^A-Za-z]/', end($path))[0];

                if (!$exclude && in_array($ext, $allowed)) {
                    // Drop embedded classes -- they don't refer to ours
                    $match = preg_replace('/class="[^"]*"/', '', $match);
                    return sprintf('<span %s class="non-local-image" data-%s %s></span>',
                        $match[1], $match[2], $match[3]);
                } else
                    return '';
            },
            $text);

        if ($balance)
            $text = self::html_balance($text, false);

        // make urls clickable.
        $text = Format::clickableurls($text);

        if ($inline_images)
            return self::viewableImages($text);

        return $text;
    }

    static function stripExternalImages($input, $display=false) {
        global $cfg;

        // Allowed Inline External Image Extensions
        $allowed = array('gif', 'png', 'jpg', 'jpeg');
        $exclude = !$cfg->allowExternalImages();
        $local = false;

        $input = preg_replace_callback('/<img ([^>]*)(src="([^"]+)")([^>]*)\/?>/',
            function($match) use ($local, $allowed, $exclude, $display) {
                if (strpos($match[3], 'cid:') !== false)
                    $local = true;

                // Split the src URL and get the extension
                $part = parse_url($match[3], PHP_URL_PATH);
                $path = explode('.', $part);
                $ext = preg_split('/[^A-Za-z]/', end($path))[0];

                if (!$local && (($exclude && $display) || !in_array($ext, $allowed)))
                    return '';
                else
                    return $match[0];
            },
            $input);

        return $input;
    }

    static function striptags($var, $decode=true) {

        if(is_array($var))
            return array_map(array('Format','striptags'), $var, array_fill(0, count($var), $decode));

        return strip_tags($decode?Format::htmldecode($var):$var);
    }

    // Strip all Emoticon/Emoji characters until we support them
    static function strip_emoticons($text) {
        return preg_replace(array(
                '/[\x{1F601}-\x{1F64F}]/u', # Emoticons
                '/[\x{1F680}-\x{1F6C0}]/u', # Transport/Map
                '/[\x{1F600}-\x{1F636}]/u', # Add. Emoticons
                '/[\x{1F681}-\x{1F6C5}]/u', # Add. Transport/Map
                '/[\x{1F30D}-\x{1F567}]/u', # Other
                '/[\x{1F910}-\x{1F999}]/u', # Hands
                '/[\x{1F9D0}-\x{1F9DF}]/u', # Fantasy
                '/[\x{1F9E0}-\x{1F9EF}]/u', # Clothes
                '/[\x{1F6F0}-\x{1F6FF}]/u', # Misc. Transport
                '/[\x{1F6E0}-\x{1F6EF}]/u', # Planes/Boats
                '/[\x{1F6C0}-\x{1F6CF}]/u', # Bed/Bath
                '/[\x{1F9C0}-\x{1F9C2}]/u', # Misc. Food
                '/[\x{1F6D0}-\x{1F6D2}]/u', # Sign/P.O.W./Cart
                '/[\x{1F500}-\x{1F5FF}]/u', # Uncategorized
                '/[\x{1F300}-\x{1F3FF}]/u', # Cyclone/Amphora
                '/[\x{2702}-\x{27B0}]/u',   # Dingbats
                '/[\x{00A9}-\x{00AE}]/u',   # Copyright/Registered
                '/[\x{23F0}-\x{23FF}]/u',   # Clock/Buttons
                '/[\x{23E0}-\x{23EF}]/u',   # More Buttons
                '/[\x{2310}-\x{231F}]/u',   # Hourglass/Watch
                '/[\x{1000B6}]/u',          # Private Use Area (Plane 16)
                '/[\x{2322}-\x{232F}]/u'    # Keyboard
            ), '', $text);
    }

    // Insert </br> tag inside empty <p> tags to ensure proper editor spacing
    static function editor_spacing($text) {
        return preg_replace('/<p><\/p>/', '<p><br></p>', $text);
    }

    //make urls clickable. Mainly for display
    static function clickableurls($text, $target='_blank') {
        global $ost;

        // Find all text between tags
        return preg_replace_callback(':^[^<]+|>[^<]+:',
            function($match) {
                // Scan for things that look like URLs
                return preg_replace_callback(
                    '`(?<!>)(((f|ht)tp(s?)://|(?<!//)www\.)([-+~%/.\w]+)(?:[-?#+=&;%@.\w\[\]\/]*)?)'
                   .'|(\b[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,63})`',
                    function ($match) {
                        if ($match[1]) {
                            while (in_array(substr($match[1], -1),
                                    array('.','?','-',':',';'))) {
                                $match[9] = substr($match[1], -1) . $match[9];
                                $match[1] = substr($match[1], 0, strlen($match[1])-1);
                            }
                            if (strpos($match[2], '//') === false) {
                                $match[1] = 'http://' . $match[1];
                            }

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
    }

    static function stripEmptyLines($string) {
        return preg_replace("/\n{3,}/", "\n\n", trim($string));
    }


    static function viewableImages($html, $options=array(), $format=false) {
        $cids = $images = array();
        $options +=array(
                'disposition' => 'inline');
        $html = preg_replace_callback('/("|&quot;)cid:([\w._-]{32})("|&quot;)/',
        function($match) use ($options, $images) {
            if (!($file = AttachmentFile::lookup($match[2])))
                return $match[0];

            return sprintf('"%s" data-cid="%s"',
                $file->getDownloadUrl($options), $match[2]);
        }, $html);
        return $format ? Format::htmlchars($html, true) : $html;
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
    static function array_implode( $glue, $separator, $array ) {

        if ( !is_array( $array ) ) return $array;

        $string = array();
        foreach ( $array as $key => $val ) {
            if ( is_array( $val ) )
                $val = implode( ',', $val );

            $string[] = "{$key}{$glue}{$val}";
        }

        return implode( $separator, $string );
    }

    static function number($number, $locale=false) {
        if (is_array($number))
            return array_map(array('Format','number'), $number);

        if (!is_numeric($number))
            return $number;

        if (extension_loaded('intl') && class_exists('NumberFormatter')) {
            $nf = NumberFormatter::create($locale ?: Internationalization::getCurrentLocale(),
                NumberFormatter::DECIMAL);
            return $nf->format($number);
        }

        return number_format((int) $number);
    }

    /*
     * Add ORDINAL suffix to a number e.g 1st, 2nd, 3rd etc.
     * TODO: Combine this routine with Format::number and pass in type of
     * formatting.
     */
    static function ordinalsuffix($number, $locale=false) {
        if (is_array($number))
            return array_map(array('Format', 'ordinalsuffix'), $number);

        if (!is_numeric($number))
            return $number;

        if (extension_loaded('intl') && class_exists('NumberFormatter')) {
            $nf = new NumberFormatter($locale ?:
                    Internationalization::getCurrentLocale(),
                    NumberFormatter::ORDINAL);
            return $nf->format($number);
        }

        // Default to English ordinal
        if (!in_array(($number % 100), [11,12,13])) {
            switch ($number % 10) {
            case 1:  return $number.'st';
            case 2:  return $number.'nd';
            case 3:  return $number.'rd';
            }
        }

        return $number.'th';
    }

    /* elapsed time */
    static function elapsedTime($sec) {

        if(!$sec || !is_numeric($sec)) return "";

        $days = floor($sec / 86400);
        $hrs = floor(bcmod($sec,86400)/3600);
        $mins = round(bcmod(bcmod($sec,86400),3600)/60);
        if($days > 0) $tstring = $days . 'd,';
        if($hrs > 0) $tstring = $tstring . $hrs . 'h,';
        $tstring =$tstring . $mins . 'm';

        return $tstring;
    }

    static function __formatDate($timestamp, $format, $fromDb, $dayType, $timeType,
            $strftimeFallback, $timezone, $user=false) {
        global $cfg;
        static $cache;

        if ($timestamp && $fromDb)
            $timestamp = Misc::db2gmtime($timestamp);

        // Make sure timestamp is valid for realz.
        if (!$timestamp || !($datetime = DateTime::createFromFormat('U', $timestamp)))
            return '';

        // Normalize timezone
        if ($timezone)
            $timezone = Format::timezone($timezone);

        // Set the desired timezone (caching since it will be mostly same
        // for most date formatting.
        $timezone = Format::timezone($timezone, $cfg->getTimezone());
        if (isset($cache[$timezone]))
            $tz =  $cache[$timezone];
        else
            $cache[$timezone] = $tz = new DateTimeZone($timezone);

        $datetime->setTimezone($tz);

        // Formmating options
        $options = array(
                'timezone' => $tz->getName(),
                'locale' =>  Internationalization::getCurrentLocale($user),
                'daytype' => $dayType,
                'timetype' => $timeType,
                'strftime' => $strftimeFallback,
                );

        return self::IntDateFormat($datetime, $format, $options);

    }

    // IntDateFormat
    // Format datetime to desired format in accorrding to desired locale
    static function IntDateFormat(DateTime $datetime, $format, $options=array()) {
        global $cfg;

        if (!$datetime instanceof DateTime)
            return '';

        $format = $format ?: $cfg->getDateFormat();
        $timezone = $datetime->getTimeZone();
        // Use IntlDateFormatter if available
        if (class_exists('IntlDateFormatter')) {
            $options += array(
                    'pattern' => $format,
                    'timezone' => $timezone->getName());

            if ($fmt=Internationalization::getIntDateFormatter($options))
                return  $fmt->format($datetime);
        }

        // Fallback to using strftime which is not timezone aware
        // Figure out timezone offset for given timestamp
        $timestamp = $datetime->format('U');
        $time = DateTime::createFromFormat('U', $timestamp, new DateTimeZone('UTC'));
        $timestamp += $timezone->getOffset($time);
        // Change format to strftime format otherwise us a fallback format
        $format = self::getStrftimeFormat($format) ?: $options['strftime']
            ?:  '%x %X';
        if ($cfg && $cfg->isForce24HourTime())
            $format = str_replace('X', 'R', $format);

        // TODO: Deprecated; replace this soon
        return strftime($format, $timestamp);
    }

    // Normalize ambiguous timezones
    static function timezone($tz, $default=false) {

        // Translate ambiguous 'GMT' timezone
        if ($tz == 'GMT')
           return 'Europe/London';

        if (!$tz || !strcmp($tz, '+00:00'))
            $tz = 'UTC';

        if (is_numeric($tz))
            $tz = timezone_name_from_abbr('', $tz, false);
        // Forbid timezone abbreviations like 'CDT'
        elseif ($tz !== 'UTC' && strpos($tz, '/') === false) {
            // Attempt to lookup based on the abbreviation
            if (!($tz = timezone_name_from_abbr($tz)))
                // Abbreviation doesn't point to anything valid
                return $default;
        }

        // SYSTEM does not describe a time zone, ensure we have a valid zone
        // by attempting to create an instance of DateTimeZone()
        try {
            $timezone = new DateTimeZone($tz);
            return $timezone->getName();
        } catch(Exception $ex) {
            return $default;
        }

        return $tz;
    }

    static function parseDateTime($date, $locale=null, $format=false) {
        global $cfg;

        if (!$date)
            return null;

        // Timestamp format?
        if (is_numeric($date))
            return DateTime::createFromFormat('U', $date);

        $datetime = null;
        try {
            $datetime = new DateTime($date);
            $tz = $datetime->getTimezone()->getName();
            if ($tz && $tz[0] == '+' || $tz[0] == '-')
                $tz = (int) $datetime->format('Z');
            elseif ($tz == 'Z')
                $tz = 'UTC';
            $timezone =  new DateTimeZone(Format::timezone($tz) ?: 'UTC');
            $datetime->setTimezone($timezone);
        } catch (Exception $ex) {
            // Fallback using strtotime
            if (($time=strtotime($date)))
                $datetime = DateTime::createFromFormat('U', $time);

        }

        return $datetime;
    }

    static function time($timestamp, $fromDb=true, $format=false, $timezone=false, $user=false) {
        global $cfg;

        return self::__formatDate($timestamp,
            $format ?: $cfg->getTimeFormat(), $fromDb,
            IDF_NONE, IDF_SHORT,
            '%X', $timezone ?: $cfg->getTimezone(), $user);
    }

    static function date($timestamp, $fromDb=true, $format=false, $timezone=false, $user=false) {
        global $cfg;

        return self::__formatDate($timestamp,
            $format ?: $cfg->getDateFormat(), $fromDb,
            IDF_SHORT, IDF_NONE,
            '%x', $timezone ?: $cfg->getTimezone(), $user);
    }

    static function datetime($timestamp, $fromDb=true, $format=false,  $timezone=false, $user=false) {
        global $cfg;

        return self::__formatDate($timestamp,
                $format ?: $cfg->getDateTimeFormat(), $fromDb,
                IDF_SHORT, IDF_SHORT,
                '%x %X', $timezone ?: $cfg->getTimezone(), $user);
    }

    static function daydatetime($timestamp, $fromDb=true, $format=false,  $timezone=false, $user=false) {
        global $cfg;

        return self::__formatDate($timestamp,
                $format ?: $cfg->getDayDateTimeFormat(), $fromDb,
                IDF_FULL, IDF_SHORT,
                '%x %X', $timezone ?: $cfg->getTimezone(), $user);
    }

    static function getStrftimeFormat($format) {
        static $codes, $ids;

        if (!isset($codes)) {
            // This array is flipped because of duplicated formats on the
            // intl side due to slight differences in the libraries
            $codes = array(
            '%d' => 'dd',
            '%a' => 'EEE',
            '%e' => 'd',
            '%A' => 'EEEE',
            '%w' => 'e',
            '%w' => 'c',
            '%z' => 'D',

            '%V' => 'w',

            '%B' => 'MMMM',
            '%m' => 'MM',
            '%b' => 'MMM',

            '%g' => 'Y',
            '%G' => 'Y',
            '%Y' => 'y',
            '%y' => 'yy',

            '%P' => 'a',
            '%l' => 'h',
            '%k' => 'H',
            '%I' => 'hh',
            '%H' => 'HH',
            '%M' => 'mm',
            '%S' => 'ss',

            '%z' => 'ZZZ',
            '%Z' => 'z',
            );

            $flipped = array_flip($codes);
            krsort($flipped);

            // Also establish a list of ids, so we can do a creative replacement
            // without clobbering the common letters in the formats
            $keys = array_keys($flipped);
            $ids = array_combine($keys, array_map('chr', array_flip($keys)));

            // Now create an array from the id codes back to strftime codes
            $codes = array_combine($ids, $flipped);
        }
        // $ids => array(intl => #id)
        // $codes => array(#id => strftime)
        $format = str_replace(array_keys($ids), $ids, $format);
        $format = str_replace($ids, $codes, $format);

        return preg_replace_callback('`[\x00-\x1f]`',
            function($m) use ($ids) {
                return $ids[ord($m[0])];
            },
            $format
        );
    }

    // Translate php date / time formats to js equivalent
    static function dtfmt_php2js($format) {

        $codes = array(
        // Date
        'DD' => 'oo',
        'D' => 'o',
        'EEEE' => 'DD',
        'EEE' => 'D',
        'MMMM' => '||',
        'MMM' => '|',
        'MM' => 'mm',
        'M' =>  'm',
        '||' => 'MM',
        '|' => 'M',
        'yyyy' => 'YY',
        'yyy' => 'YY',
        'yy' =>  'Y',
        'y' => 'yy',
        'YY' =>  'yy',
        'Y' => 'y',
        // Time
        'a' => 'tt',
        'HH' => 'H',
        'H' => 'HH',
        );

        return str_replace(array_keys($codes), array_values($codes), $format);
    }

    // Thanks, http://stackoverflow.com/a/2955878/1025836
    /* static */
    static function slugify($text) {
        // convert special characters to entities
        $text = htmlentities($text, ENT_NOQUOTES, 'UTF-8');

        // removes entity suffixes, leaving only un-accented characters
        $text = preg_replace('~&([A-Za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);~', '$1', $text);
        $text = preg_replace('~&([A-Za-z]{2})(?:lig);~', '$1', $text);

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
    static function parseRfc2397($data, $output_encoding=false, $always_convert=true) {
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
            $contents = Charset::transcode($contents, $charset, $output_encoding);

        return array(
            'data' => $contents,
            'type' => $type
        );
    }

    // Performs Unicode normalization (where possible) and splits words at
    // difficult word boundaries (for far eastern languages)
    static function searchable($text, $lang=false, $length=false) {
        global $cfg;

        if (function_exists('normalizer_normalize')) {
            // Normalize text input :: remove diacritics and such
            $text = normalizer_normalize($text, Normalizer::FORM_C);
        }

        if (false && class_exists('IntlBreakIterator')) {
            // Split by word boundaries
            if ($tokenizer = IntlBreakIterator::createWordInstance(
                    $lang ?: ($cfg ? $cfg->getPrimaryLanguage() : 'en_US'))
            ) {
                $tokenizer->setText($text);
                $tokens = array();
                foreach ($tokenizer as $token)
                    $tokens[] = $token;
                $text = implode(' ', $tokens);
            }
        }
        else {
            // Approximate word boundaries from Unicode chart at
            // http://www.unicode.org/reports/tr29/#Word_Boundaries

            // Punt for now

            // Drop extraneous whitespace
            $text = preg_replace('/(\s)\s+/u', '$1', $text);

            // Drop leading and trailing whitespace
            $text = trim($text);
        }

        if ($length && (str_word_count($text) > $length))
            return null;

        return $text;
    }

    static function relativeTime($to, $from=false, $granularity=1) {
        if (!$to)
            return false;
        $timestamp = $to;
        if (gettype($timestamp) === 'string')
            $timestamp = strtotime($timestamp);
        $from = $from ?: Misc::gmtime();
        if (gettype($timestamp) === 'string')
            $from = strtotime($from);
        $timeDiff = $from - $timestamp;
        $absTimeDiff = abs($timeDiff);

        // Roll back to the nearest multiple of $granularity
        $absTimeDiff -= $absTimeDiff % $granularity;

        // within 2 seconds
        if ($absTimeDiff <= 2) {
          return $timeDiff >= 0 ? __('just now') : __('now');
        }

        // within a minute
        if ($absTimeDiff < 60) {
          return sprintf($timeDiff >= 0 ? __('%d seconds ago') : __('in %d seconds'), $absTimeDiff);
        }

        // within 2 minutes
        if ($absTimeDiff < 120) {
          return sprintf($timeDiff >= 0 ? __('about a minute ago') : __('in about a minute'));
        }

        // within an hour
        if ($absTimeDiff < 3600) {
          return sprintf($timeDiff >= 0 ? __('%d minutes ago') : __('in %d minutes'), $absTimeDiff / 60);
        }

        // within 2 hours
        if ($absTimeDiff < 7200) {
          return ($timeDiff >= 0 ? __('about an hour ago') : __('in about an hour'));
        }

        // within 24 hours
        if ($absTimeDiff < 86400) {
          return sprintf($timeDiff >= 0 ? __('%d hours ago') : __('in %d hours'), $absTimeDiff / 3600);
        }

        // within 29 days
        $days29 = 29 * 86400;
        if ($absTimeDiff < $days29) {
          return sprintf($timeDiff >= 0 ? __('%d days ago') : __('in %d days'), round($absTimeDiff / 86400));
        }

        // within 60 days
        $days60 = 60 * 86400;
        if ($absTimeDiff < $days60) {
          return ($timeDiff >= 0 ? __('about a month ago') : __('in about a month'));
        }

        $currTimeYears = date('Y', $from);
        $timestampYears = date('Y', $timestamp);
        $currTimeMonths = $currTimeYears * 12 + date('n', $from);
        $timestampMonths = $timestampYears * 12 + date('n', $timestamp);

        // within a year
        $monthDiff = $currTimeMonths - $timestampMonths;
        if ($monthDiff < 12 && $monthDiff > -12) {
          return sprintf($monthDiff >= 0 ? __('%d months ago') : __('in %d months'), abs($monthDiff));
        }

        $yearDiff = $currTimeYears - $timestampYears;
        if ($yearDiff < 2 && $yearDiff > -2) {
          return $yearDiff >= 0 ? __('a year ago') : __('in a year');
        }

        return sprintf($yearDiff >= 0 ? __('%d years ago') : __('in %d years'), abs($yearDiff));
    }
}

if (!class_exists('IntlDateFormatter')) {
    define('IDF_NONE', 0);
    define('IDF_SHORT', 1);
    define('IDF_FULL', 2);
}
else {
    define('IDF_NONE', IntlDateFormatter::NONE);
    define('IDF_SHORT', IntlDateFormatter::SHORT);
    define('IDF_FULL', IntlDateFormatter::FULL);
}

class FormattedLocalDate
implements TemplateVariable {

    var $date;
    var $timezone;
    var $datetime;
    var $fromdb;
    var $format;

    function __construct($date,  $options=array()) {

        // Date to be formatted
        $this->datetime = Format::parseDateTime($date);
        $this->date = $this->datetime->getTimestamp();
        // Desired timezone
        if (isset($options['timezone']))
            $this->timezone = $options['timezone'];
        else
            $this->timezone = false;
        // User
        if (isset($options['user']))
            $this->user = $options['user'];
        else
            $this->user = false;

        // DB date or nah?
        if (isset($options['fromdb']))
            $this->fromdb = $options['fromdb'];
        else
            $this->fromdb = true;
        // Desired format
        if (isset($options['format']) && $options['format'])
            $this->format = $options['format'];
    }

    function getDateTime() {
        return $this->datetime;
    }

    function asVar() {
        return $this->getVar($this->format ?: 'long');
    }

    function getVar($what) {
        // TODO: Rebase date format so that locale is discovered HERE.

        switch ($what) {
        case 'short':
            return Format::date($this->date, $this->fromdb, false, $this->timezone, $this->user);
        case 'long':
            return Format::datetime($this->date, $this->fromdb, false, $this->timezone, $this->user);
        case 'time':
            return Format::time($this->date, $this->fromdb, false, $this->timezone, $this->user);
        case 'full':
            return Format::daydatetime($this->date, $this->fromdb, false, $this->timezone, $this->user);
        }
    }

    function __toString() {
        return $this->asVar() ?: '';
    }

    static function getVarScope() {
        return array(
            'full' => 'Expanded date, e.g. day, month dd, yyyy',
            'long' => 'Date and time, e.g. d/m/yyyy hh:mm',
            'short' => 'Date only, e.g. d/m/yyyy',
            'time' => 'Time only, e.g. hh:mm',
        );
    }
}

class FormattedDate
extends FormattedLocalDate {
    function asVar() {
        return $this->getVar('system')->asVar();
    }

    function __toString() {
        global $cfg;

        $timezone = new DatetimeZone($this->timezone ?:
                $cfg->getTimezone());
        $options = array(
                'timezone'  => $timezone->getName(),
                'fromdb'    => $this->fromdb,
                'format'    => $this->format
                );

        $val = (string) new FormattedLocalDate($this->date, $options);
        if ($this->timezone && $this->format == 'long') {
            try {
                $this->datetime->setTimezone($timezone);
                $val = sprintf('%s %s',
                        $val, $this->datetime->format('T'));

            } catch(Exception $ex) {
                // ignore
            }
        }

        return $val;
    }

    function getVar($what, $context=null) {
        global $cfg;

        if ($rv = parent::getVar($what, $context))
            return $rv;

        switch ($what) {
        case 'user':
            // Fetch $recipient from the context and find that user's time zone
            if ($context && ($recipient = $context->getObj('recipient'))) {
                $options = array(
                        'timezone' => $recipient->getTimezone() ?: $cfg->getDefaultTimezone(),
                        'user' => $recipient
                        );
                return new FormattedLocalDate($this->date, $options);
            }
            // Don't resolve the variable until correspondance is sent out
            return false;
        case 'system':
            return new FormattedLocalDate($this->date, array(
                        'timezone' => $cfg->getDefaultTimezone()
                        )
                    );
        }
    }

    function getHumanize() {
        return Format::relativeTime(Misc::db2gmtime($this->date));
    }

    static function getVarScope() {
        return parent::getVarScope() + array(
            'humanize' => 'Humanized time, e.g. about an hour ago',
            'user' => array(
                'class' => 'FormattedLocalDate', 'desc' => "Localize to recipient's time zone and locale"),
            'system' => array(
                'class' => 'FormattedLocalDate', 'desc' => 'Localize to system default time zone'),
        );
    }
}
?>
