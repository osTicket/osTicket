<?php
/*********************************************************************
    ajax.i18n.php

    Callbacks to get internaltionalized pieces for osticket

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('!');

class i18nAjaxAPI extends AjaxController {
    function getLanguageFile($lang, $key) {
        global $cfg;

        $i18n = new Internationalization($lang);
        switch ($key) {
        case 'js':
            $data = $i18n->getTemplate('js/redactor.js')->getRawData();
            $data .= $i18n->getTemplate('js/jquery.ui.datepicker.js')->getRawData();
            // Strings from various javascript files
            $data .= $i18n->getTemplate('js/osticket-strings.js')->getRawData();
            header('Content-Type: text/javascript; charset=UTF-8');
            break;
        default:
            Http::response(404, 'No such i18n data');
        }

        Http::cacheable(md5($data), $cfg->lastModified());
        echo $data;
    }
}
?>
