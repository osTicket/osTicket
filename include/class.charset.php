<?php
/*********************************************************************
    class.charset.php

    Charset util class

    Copyright (c) 2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Charset {

    const UTF8 = 'utf-8';

    // Cleanup invalid charsets
    // Thanks in part to https://github.com/mikel/mail/commit/88457e
    static function normalize($charset) {

        $match = array();
        switch (true) {
        // Windows charsets - force correct format
        case preg_match('`^Windows-?(\d+)$`i', $charset, $match):
            return 'Windows-'.$match[1];
        // ks_c_5601-1987: Korean alias for cp949
        case preg_match('`^ks_c_5601-1987`i', $charset):
            return 'cp949';
        case preg_match('`^iso-?(\S+)$`i', $charset, $match):
            return "ISO-".$match[1];
        // GBK superceded gb2312 and is backward compatible
        case preg_match('`^gb2312`i', $charset):
            return 'GBK';
        // Incorrect, bogus, ambiguous or empty charsets
        // ISO-8859-1 is assumed
        case preg_match('`^(default|x-user-defined|iso|us-ascii)$`i', $charset):
        case preg_match('`^\s*$`', $charset):
            return 'ISO-8859-1';
        }

        // Hmmmm
        return $charset;
    }

    // Translate characters ($text) from one encoding ($from) to another ($to)
    static function transcode($text, $from, $to) {

        //Try auto-detecting charset/encoding
        if (!$from && function_exists('mb_detect_encoding'))
            $from = mb_detect_encoding($text);

        // Normalize bogus or ambiguous charsets
        $from = self::normalize(trim($from));
        $to = self::normalize(trim($to));

        $original = $text;
        if (function_exists('iconv'))
            $text = iconv($from, $to.'//IGNORE', $text);
        elseif (function_exists('mb_convert_encoding'))
            $text = mb_convert_encoding($text, $to, $from);
        elseif (!strcasecmp($to, 'utf-8')
                && function_exists('utf8_encode')
                && !strcasecmp($from, 'ISO-8859-1'))
            $text = utf8_encode($text);

        // If $text is false, then we have a (likely) invalid charset, use
        // the original text and assume 8-bit (latin-1 / iso-8859-1)
        // encoding
        return (!$text && $original) ? $original : $text;
    }

    //Wrapper for utf-8 transcoding.
    function utf8($text, $charset=null) {
        return self::transcode($text, $charset, self::UTF8);
    }
}
?>
