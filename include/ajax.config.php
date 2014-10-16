<?php
/*********************************************************************
    ajax.content.php

    AJAX interface for content fetching...allowed methods.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.ajax.php';

if(!defined('INCLUDE_DIR')) die('!');

class ConfigAjaxAPI extends AjaxController {

    //config info UI might need.
    function scp() {
        global $cfg;

        $lang = Internationalization::getCurrentLanguage();
        $info = Internationalization::getLanguageInfo($lang);
        list($sl, $locale) = explode('_', $lang);

        $rtl = false;
        foreach (Internationalization::availableLanguages() as $info) {
            if (isset($info['direction']))
                $rtl = true;
        }

        $primary = $cfg->getPrimaryLanguage();
        $primary_info = Internationalization::getLanguageInfo($primary);
        list($primary_sl, $primary_locale) = explode('_', $primary);

        $config=array(
              'lock_time'       => ($cfg->getLockTime()*3600),
              'html_thread'     => (bool) $cfg->isHtmlThreadEnabled(),
              'date_format'     => ($cfg->getDateFormat()),
              'lang'            => $lang,
              'short_lang'      => $sl,
              'has_rtl'         => $rtl,
              'lang_flag'       => strtolower($info['flag'] ?: $locale ?: $sl),
              'primary_lang_flag' => strtolower($primary_info['flag'] ?: $primary_locale ?: $primary_sl),
              'primary_language' => $primary,
              'secondary_languages' => $cfg->getSecondaryLanguages(),
        );
        return $this->json_encode($config);
    }

    function client($headers=true) {
        global $cfg;

        $lang = Internationalization::getCurrentLanguage();
        list($sl, $locale) = explode('_', $lang);

        $rtl = false;
        foreach (Internationalization::availableLanguages() as $info) {
            if (isset($info['direction']))
                $rtl = true;
        }

        $config=array(
            'html_thread'     => (bool) $cfg->isHtmlThreadEnabled(),
            'lang'            => $lang,
            'short_lang'      => $sl,
            'has_rtl'         => $rtl,
            'primary_language' => $cfg->getPrimaryLanguage(),
            'secondary_languages' => $cfg->getSecondaryLanguages(),
        );

        $config = $this->json_encode($config);
        if ($headers) {
            Http::cacheable(md5($config), $cfg->lastModified());
            header('Content-Type: application/json; charset=UTF-8');
        }

        return $config;
    }

    function templateLinks() {
        $links = $this->json_encode(array(
            array('name'=>'Select ...', 'url'=> false),
            array('name'=>'Agent Ticket Link', 'url'=> '%{ticket.staff_link}'),
            array('name'=>'Agent Login Page', 'url'=> '%{url}/scp'),
            array('name'=>'End-User Ticket Link', 'url'=> '%{recipient.ticket_link}'),
            array('name'=>'End-User Login Page', 'url'=> '%{url}/login.php'),
        ));

        Http::cacheable(md5($links), filemtime(__file__));
        header('Content-Type: application/json; charset=UTF-8');

        return $links;
    }
}
?>
