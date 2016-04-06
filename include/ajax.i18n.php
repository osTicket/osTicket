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

    function getTranslations($tag) {
        $t = CustomDataTranslation::allTranslations($tag);
        $phrases = array();
        $lm = 0;
        foreach ($t as $translation) {
            $phrases[$translation->lang] = $translation->text;
            $lm = max($lm, strtotime($translation->updated));
        }
        $json = JsonDataEncoder::encode($phrases) ?: '{}';
        //Http::cacheable(md5($json), $lm);

        return $json;
    }

    function updateTranslations($tag) {
        global $thisstaff, $cfg;

        if (!$thisstaff)
            Http::response(403, "Agent login required");
        if (!$_POST)
            Http::response(422, "No translations found to update");

        $t = CustomDataTranslation::allTranslations($tag);
        $phrases = array();
        foreach ($t as $translation) {
            $phrases[$translation->lang] = $translation;
        }
        foreach ($_POST as $lang => $phrase) {
            if (isset($phrases[$lang])) {
                $p = $phrases[$lang];
                if (!$phrase) {
                    $p->delete();
                }
                else {
                    // Avoid XSS injection
                    $p->text = trim(Format::striptags($phrase));
                    $p->agent_id = $thisstaff->getId();
                }
            }
            elseif (in_array($lang, $cfg->getSecondaryLanguages())) {
                if (!$phrase)
                    continue;
                $phrases[$lang] = CustomDataTranslation::create(array(
                    'lang'          => $lang,
                    'text'          => $phrase,
                    'object_hash'   => $tag,
                    'type'          => 'phrase',
                    'agent_id'      => $thisstaff->getId(),
                    'updated'       => new SqlFunction('NOW'),
                ));
            }
            else {
                Http::response(400,
                    sprintf("%s: Must be a secondary language", $lang));
            }
        }
        // Commit.
        foreach ($phrases as $p)
            if (!$p->save())
                Http::response(500, sprintf("%s: Unable to commit language"));
    }

    function getConfiguredLanguages() {
        global $cfg;

        $primary = $cfg->getPrimaryLanguage();
        $info = Internationalization::getLanguageInfo($primary);
        $langs = array(
            $primary => array(
                'name' => Internationalization::getLanguageDescription($primary),
                'flag' => strtolower($info['flag']),
                'direction' => $info['direction'] ?: 'ltr',
            ),
        );

        foreach ($cfg->getSecondaryLanguages() as $l) {
            $info = Internationalization::getLanguageInfo($l);
            $langs[$l] = array(
                'name' => Internationalization::getLanguageDescription($l),
                'flag' => strtolower($info['flag']),
                'direction' => $info['direction'] ?: 'ltr',
            );
        }
        $json = JsonDataEncoder::encode($langs);
        Http::cacheable(md5($json), $cfg->lastModified());

        return $json;
    }

    function getSecondaryLanguages() {
        global $cfg;

        $langs = array();
        foreach ($cfg->getSecondaryLanguages() as $l) {
            $info = Internationalization::getLanguageInfo($l);
            $langs[$l] = array(
                'name' => Internationalization::getLanguageDescription($l),
                'flag' => strtolower($info['flag']),
                'direction' => $info['direction'] ?: 'ltr',
            );
        }
        $json = JsonDataEncoder::encode($langs);
        Http::cacheable(md5($json), $cfg->lastModified());

        return $json;
    }
}
?>
