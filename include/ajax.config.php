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
        global $cfg, $thisstaff;

        $lang = Internationalization::getCurrentLanguage();
        $info = Internationalization::getLanguageInfo($lang);
        list($sl, $locale) = explode('_', $lang);

        $rtl = false;
        foreach (Internationalization::getConfiguredSystemLanguages() as $info) {
            if (isset($info['direction']))
                $rtl = true;
        }

        $primary = $cfg->getPrimaryLanguage();
        $primary_info = Internationalization::getLanguageInfo($primary);
        list($primary_sl, $primary_locale) = explode('_', $primary);

        $config=array(
              'lock_time'       => $cfg->getTicketLockMode() == Lock::MODE_DISABLED ? 0 : ($cfg->getLockTime()*60),
              'html_thread'     => (bool) $cfg->isRichTextEnabled(),
              'date_format'     => Format::dtfmt_php2js($cfg->getDateFormat(true)),
              'lang'            => $lang,
              'short_lang'      => $sl,
              'has_rtl'         => $rtl,
              'lang_flag'       => strtolower($info['flag'] ?: $locale ?: $sl),
              'primary_lang_flag' => strtolower($primary_info['flag'] ?: $primary_locale ?: $primary_sl),
              'primary_language' => Internationalization::rfc1766($primary),
              'secondary_languages' => $cfg->getSecondaryLanguages(),
              'page_size'       => $thisstaff->getPageLimit() ?: PAGE_LIMIT,
              'path'            => ROOT_PATH,
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
            'html_thread'     => (bool) $cfg->isRichTextEnabled(),
            'lang'            => $lang,
            'short_lang'      => $sl,
            'has_rtl'         => $rtl,
            'primary_language' => Internationalization::rfc1766($cfg->getPrimaryLanguage()),
            'secondary_languages' => $cfg->getSecondaryLanguages(),
            'path'            => ROOT_PATH,
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

        Http::cacheable(md5($links));
        header('Content-Type: application/json; charset=UTF-8');

        return $links;
    }

    /**
     * Ajax: GET /config/date-format?format=<format>
     *
     * Formats the user's current date and time according to the given
     * format in INTL codes.
     *
     * Get-Arguments:
     * format - (string) format string used to format the current date and
     *      time (from the user's perspective)
     *
     * Returns:
     * (string) Current sequence number, optionally formatted
     *
     * Throws:
     * 403 - Not logged in
     * 400 - ?format missing
     */
    function dateFormat() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!isset($_GET['format']))
            Http::response(400, '?format is required');

        return Format::htmlchars(Format::__formatDate(
            Misc::gmtime(), $_GET['format'], false, null, null, '', 'UTC'
        ));
    }
}
?>
