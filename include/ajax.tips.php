<?php
/*********************************************************************
    ajax.tips.php

    AJAX interface for help popovers. Initially written to allow
    translations of the installer help popups

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(!defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.i18n.php');

class HelpTipAjaxAPI extends AjaxController {
    function getTipsJson($namespace, $lang=false) {
        global $ost, $thisstaff;

        $lang = Internationalization::getCurrentLanguage();

        $i18n = new Internationalization($lang);
        $tips = $i18n->getTemplate("help/tips/$namespace.yaml");

        if (!$tips || !($data = $tips->getData()))
            Http::response(404, 'Help content not available');

        // Translate links to the root path of this installation
        foreach ($data as $tip=>&$info) {
            if ($ost)
                $info = $ost->replaceTemplateVariables($info, array(
                    'config'=>$ost->getConfig()));
            if (isset($info['links']))
                foreach ($info['links'] as &$l)
                    if ($l['href'][0] == '/')
                        $l['href'] = ROOT_PATH.substr($l['href'],1);
        }

        return $this->json_encode($data);
    }

    function getTipsJsonForLang($lang, $namespace) {
        return $this->getTipsJson($namespace, $lang);
    }
}

?>
