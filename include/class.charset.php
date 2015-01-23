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

    // Cleanup invalid charsets
    // Thanks in part to https://github.com/mikel/mail/commit/88457e
    function normalize($charset) {

        $match = array();
        switch (true) {
        // Windows charsets - force correct format
        case preg_match('`^Windows-?(\d+)$`', $charset, $match):
            return 'Windows-'.$match[1];
        // ks_c_5601-1987: Korean alias for cp949
        case preg_match('`^ks_c_5601-1987`', $charset):
            return 'cp949';
        // Incorrect, bogus, ambiguous or empty charsets
        // ISO-8859-1 is assumed
        case preg_match('`^(default|x-user-defined|iso|us-ascii)`', $charset):
        case preg_match('`^\s*$`', $charset):
            return 'ISO-8859-1';
        }

        // Hmmmm
        return $charset;
    }
}
?>
