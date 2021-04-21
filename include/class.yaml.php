<?php
/*********************************************************************
    class.yaml.php

    Parses YAML data files into PHP associative arrays. Useful for initial
    data shipped with osTicket.

    Currently, this module uses the pure-php implementation Spyc, written by
        - Chris Wanstrath
        - Vlad Andersen
    and released under an MIT license. The software is available at
    https://github.com/mustangostang/spyc

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/

require_once "Spyc.php";
require_once "class.error.php";

class YamlDataParser {
    /* static */
    function load($file) {
        if (!file_exists($file)) {
            raise_error("$file: File does not exist", 'YamlParserError');
            return false;
        }
        return Spyc::YAMLLoad($file);
    }
}

class YamlParserError extends BaseError {
    static $title = 'Error parsing YAML document';
}
?>
