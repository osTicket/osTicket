<?php
/*********************************************************************
    class.i18n.php

    Internationalization and localization helpers for osTicket

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR.'class.error.php';
require_once INCLUDE_DIR.'class.yaml.php';

class Internationalization {

    // Languages in order of decreasing priority. Always use en_US as a
    // fallback
    var $langs = array('en_US');

    function __construct($language=false) {
        global $cfg;

        if ($cfg && ($lang = $cfg->getPrimaryLanguage()))
            array_unshift($this->langs, $language);

        // Detect language filesystem path, case insensitively
        if ($language && ($info = self::getLanguageInfo($language))) {
            array_unshift($this->langs, $info['code']);
        }
    }

    function getTemplate($path) {
        return new DataTemplate($path, $this->langs);
    }

    /**
     * Loads data from the I18N_DIR for the target language into the
     * database. This is intended to be done at the time of installation;
     * however, care should be taken in this process to ensure that the
     * process could be repeated if an administrator wanted to change the
     * system language and reload the data.
     */
    function loadDefaultData() {
        # notrans -- do not translate the contents of this array
        $models = array(
            'department.yaml' =>    'Dept::__create',
            'sla.yaml' =>           'SLA::__create',
            'form.yaml' =>          'DynamicForm::create',
            'list.yaml' =>          'DynamicList::create',
            // Note that department, sla, and forms are required for
            // help_topic
            'help_topic.yaml' =>    'Topic::__create',
            'filter.yaml' =>        'Filter::create',
            'team.yaml' =>          'Team::__create',
            // Organization
            'organization.yaml' =>  'Organization::__create',
            // Ticket
            'ticket_status.yaml' => 'TicketStatus::__create',
            // Role
            'role.yaml' =>          'Role::__create',
            'file.yaml' =>          'AttachmentFile::__create',
            'sequence.yaml' =>      'Sequence::__create',
        );

        $errors = array();
        foreach ($models as $yaml=>$m) {
            if ($objects = $this->getTemplate($yaml)->getData()) {
                foreach ($objects as $o) {
                    if ($m && is_callable($m))
                        @call_user_func_array($m, array($o, &$errors));
                    // TODO: Add a warning to the success page for errors
                    //       found here
                    $errors = array();
                }
            }
        }

        // Priorities
        $priorities = $this->getTemplate('priority.yaml')->getData();
        foreach ($priorities as $name=>$info) {
            $sql = 'INSERT INTO '.PRIORITY_TABLE
                .' SET priority='.db_input($name)
                .', priority_id='.db_input($info['priority_id'])
                .', priority_desc='.db_input($info['priority_desc'])
                .', priority_color='.db_input($info['priority_color'])
                .', priority_urgency='.db_input($info['priority_urgency']);
            db_query($sql);
        }

        // Configuration
        require_once INCLUDE_DIR.'class.config.php';
        if (($tpl = $this->getTemplate('config.yaml'))
                && ($data = $tpl->getData())) {
            foreach ($data as $section=>$items) {
                $_config = new Config($section);
                foreach ($items as $key=>$value)
                    $_config->set($key, $value);
            }
        }

        // Load core config
        $_config = new OsticketConfig();

        // Determine reasonable default max_file_size
        $max_size = Format::filesize2bytes(strtoupper(ini_get('upload_max_filesize')));
        $val = ((int) $max_size/2);
        $po2 = 1;
        while( $po2 < $val ) $po2 <<= 1;

        $_config->set('max_file_size', $po2);

        // Pages and content
        foreach (array('landing','thank-you','offline',
                'registration-staff', 'pwreset-staff', 'banner-staff',
                'registration-client', 'pwreset-client', 'banner-client',
                'registration-confirm', 'registration-thanks',
                'access-link') as $type) {
            $tpl = $this->getTemplate("templates/page/{$type}.yaml");
            if (!($page = $tpl->getData()))
                continue;
            $sql = 'INSERT INTO '.PAGE_TABLE.' SET type='.db_input($type)
                .', name='.db_input($page['name'])
                .', body='.db_input($page['body'])
                .', notes='.db_input($page['notes'])
                .', created=NOW(), updated=NOW(), isactive=1';
            if (db_query($sql) && ($id = db_insert_id())
                    && in_array($type, array('landing', 'thank-you', 'offline')))
                $_config->set("{$type}_page_id", $id);
        }
        // Default Language
        $_config->set('system_language', $this->langs[0]);

        // Canned response examples
        if (($tpl = $this->getTemplate('templates/premade.yaml'))
                && ($canned = $tpl->getData())) {
            foreach ($canned as $c) {
                $c['isenabled'] = 1;
                if (!($premade = Canned::create($c)) || !$premade->save())
                    continue;
                if (isset($c['attachments'])) {
                    $premade->attachments->upload($c['attachments']);
                }
            }
        }

        // Email templates
        // TODO: Lookup tpl_id
        if ($objects = $this->getTemplate('email_template_group.yaml')->getData()) {
            foreach ($objects as $o) {
                $o['lang_id'] = $this->langs[0];
                $tpl = EmailTemplateGroup::create($o, $errors);
            }
        }
        // This shouldn't be necessary
        $tpl = EmailTemplateGroup::lookup(1);
        foreach ($tpl::$all_names as $name=>$info) {
            if (($tp = $this->getTemplate("templates/email/$name.yaml"))
                    && ($t = $tp->getData())) {
                $t['tpl_id'] = $tpl->getId();
                $t['code_name'] = $name;
                $id = EmailTemplate::create($t, $errors);
                if ($id && ($template = EmailTemplate::lookup($id))
                        && ($ids = Draft::getAttachmentIds($t['body'])))
                    $template->attachments->upload($ids, true);
            }
        }
    }

    static function getLanguageDescription($lang) {
        global $thisstaff, $thisclient;

        $langs = self::availableLanguages();
        $lang = strtolower($lang);
        if (isset($langs[$lang])) {
            $info = &$langs[$lang];
            if (!isset($info['desc'])) {
                if (extension_loaded('intl')) {
                    $lang = self::getCurrentLanguage();
                    list($simple_lang,) = explode('_', $lang);
                    $info['desc'] = sprintf("%s%s",
                        // Display the localized name of the language
                        Locale::getDisplayName($info['code'], $info['code']),
                        // If the major language differes from the user's,
                        // display the language in the user's language
                        (strpos($simple_lang, $info['lang']) === false
                            ? sprintf(' (%s)', Locale::getDisplayName($info['code'], $lang)) : '')
                    );
                }
                else {
                    $info['desc'] = sprintf("%s%s (%s)",
                        $info['nativeName'],
                        $info['locale'] ? sprintf(' - %s', $info['locale']) : '',
                        $info['name']);
                }
            }
            return $info['desc'];
        }
        else
            return $lang;
    }

    static function getLanguageInfo($lang) {
        $langs = self::availableLanguages();
        return @$langs[strtolower($lang)] ?: array();
    }

    static function availableLanguages($base=I18N_DIR) {
        static $cache = false;
        if ($cache) return $cache;

        $langs = (include I18N_DIR . 'langs.php');

        // Consider all subdirectories and .phar files in the base dir
        $dirs = glob(I18N_DIR . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $phars = glob(I18N_DIR . '*.phar', GLOB_NOSORT) ?: array();

        $installed = array();
        foreach (array_merge($dirs, $phars) as $f) {
            $base = basename($f, '.phar');
            @list($code, $locale) = explode('_', $base);
            if (isset($langs[$code])) {
                $installed[strtolower($base)] =
                    $langs[$code] + array(
                    'lang' => $code,
                    'locale' => $locale,
                    'path' => $f,
                    'phar' => substr($f, -5) == '.phar',
                    'code' => $base,
                );
                $installed[strtolower($base)]['flag'] = strtolower(
                    $langs[$code]['flag'] ?: $locale ?: $code
                );
            }
        }
        ksort($installed);

        return $cache = $installed;
    }

    static function isLanguageInstalled($code) {
        $langs = self::availableLanguages();
        return isset($langs[strtolower($code)]);
    }

    static function isLanguageEnabled($code) {
        $langs = self::getConfiguredSystemLanguages();
        return isset($langs[$code]);
    }

    static function getConfiguredSystemLanguages() {
        global $cfg;
        static $langs;

        if (!$cfg)
            return self::availableLanguages();

        if (!isset($langs)) {
            $langs = array();
            $pri = $cfg->getPrimaryLanguage();
            if ($info = self::getLanguageInfo($pri))
                $langs = array($pri => $info);

            // Honor sorting preference of ::availableLanguages()
            foreach ($cfg->getSecondaryLanguages() as $l) {
                if ($info = self::getLanguageInfo($l))
                    $langs[$l] = $info;
            }
        }
        return $langs;
    }

    // TODO: Move this to the REQUEST class or some middleware when that
    // exists.
    // Algorithm borrowed from Drupal 7 (locale.inc)
    static function getDefaultLanguage() {
        global $cfg;
        static $lang;

        if (isset($lang))
            return $lang;

        if (empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
            return $cfg ? $cfg->getPrimaryLanguage() : 'en_US';

        $languages = self::getConfiguredSystemLanguages();

        // The Accept-Language header contains information about the
        // language preferences configured in the user's browser / operating
        // system. RFC 2616 (section 14.4) defines the Accept-Language
        // header as follows:
        //   Accept-Language = "Accept-Language" ":"
        //                  1#( language-range [ ";" "q" "=" qvalue ] )
        //   language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
        // Samples: "hu, en-us;q=0.66, en;q=0.33", "hu,en-us;q=0.5"
        $browser_langcodes = array();
        $matches = array();
        if (preg_match_all('@(?<=[, ]|^)([a-zA-Z-]+|\*)(?:;q=([0-9.]+))?(?:$|\s*,\s*)@',
            trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches, PREG_SET_ORDER)) {
          foreach ($matches as $match) {
            // We can safely use strtolower() here, tags are ASCII.
            // RFC2616 mandates that the decimal part is no more than three
            // digits, so we multiply the qvalue by 1000 to avoid floating
            // point comparisons.
            $langcode = strtolower($match[1]);
            $qvalue = isset($match[2]) ? (float) $match[2] : 1;
            $browser_langcodes[$langcode] = (int) ($qvalue * 1000);
          }
        }

        // We should take pristine values from the HTTP headers, but
        // Internet Explorer from version 7 sends only specific language
        // tags (eg. fr-CA) without the corresponding generic tag (fr)
        // unless explicitly configured. In that case, we assume that the
        // lowest value of the specific tags is the value of the generic
        // language to be as close to the HTTP 1.1 spec as possible.
        //
        // References:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
        // http://blogs.msdn.com/b/ie/archive/2006/10/17/accept-language-header-for-internet-explorer-7.aspx
        asort($browser_langcodes);
        foreach ($browser_langcodes as $langcode => $qvalue) {
          $generic_tag = strtok($langcode, '-');
          if (!isset($browser_langcodes[$generic_tag])) {
            $browser_langcodes[$generic_tag] = $qvalue;
          }
        }

        // Find the enabled language with the greatest qvalue, following the rules
        // of RFC 2616 (section 14.4). If several languages have the same qvalue,
        // prefer the one with the greatest weight.
        $best_match_langcode = FALSE;
        $max_qvalue = 0;
        foreach ($languages as $langcode => $language) {
          // Language tags are case insensitive (RFC2616, sec 3.10).
          // We use _ as the location separator
          $langcode = str_replace('_','-',strtolower($langcode));

          // If nothing matches below, the default qvalue is the one of the wildcard
          // language, if set, or is 0 (which will never match).
          $qvalue = isset($browser_langcodes['*']) ? $browser_langcodes['*'] : 0;

          // Find the longest possible prefix of the browser-supplied language
          // ('the language-range') that matches this site language ('the language tag').
          $prefix = $langcode;
          do {
            if (isset($browser_langcodes[$prefix])) {
              $qvalue = $browser_langcodes[$prefix];
              break;
            }
          } while ($prefix = substr($prefix, 0, strrpos($prefix, '-')));

          // Find the best match.
          if ($qvalue > $max_qvalue) {
            $best_match_langcode = $language['code'];
            $max_qvalue = $qvalue;
          }
        }

        return $lang = self::isLanguageInstalled($best_match_langcode)
            ? $best_match_langcode
            : $cfg->getPrimaryLanguage();
    }

    static function getCurrentLanguage($user=false) {
        global $thisstaff, $thisclient;

        $user = $user ?: $thisstaff ?: $thisclient;
        if ($user && method_exists($user, 'getLanguage'))
            if (($lang = $user->getLanguage()) && self::isLanguageEnabled($lang))
                return $lang;

        // Support the flag buttons for guests
        if ((!$user || $user != $thisstaff) && $_SESSION['::lang'])
            return $_SESSION['::lang'];

        return self::getDefaultLanguage();
    }

    static function getCurrentLocale($user=false) {
        global $thisstaff, $cfg;

        if ($user) {
            return self::getCurrentLanguage($user);
        }
        // FIXME: Move this majic elsewhere - see upgrade bug note in
        // class.staff.php
        if ($thisstaff) {
            return $thisstaff->getLocale()
                ?: self::getCurrentLanguage($thisstaff);
        }

        if (!($locale = $cfg->getDefaultLocale()))
            $locale = self::getCurrentLanguage();

        return $locale;
    }

    static function rfc1766($what) {
        if (is_array($what))
            return array_map(array(get_called_class(), 'rfc1766'), $what);

        $lr = explode('_', $what);
        if (isset($lr[1]))
            $lr[1] = strtoupper($lr[1]);
        return implode('-', $lr);
    }

    static function getTtfFonts() {
        if (!class_exists('Phar'))
            return;
        $fonts = $subs = array();
        foreach (self::availableLanguages() as $code=>$info) {
            if (!$info['phar'] || !isset($info['fonts']))
                continue;
            foreach ($info['fonts'] as $simple => $collection) {
                foreach ($collection as $type => $name) {
                    list($name, $url) = $name;
                    $ttffile = 'phar://' . $info['path'] . '/fonts/' . $name;
                    if (file_exists($ttffile))
                        $fonts[$simple][$type] = $ttffile;
                }
                if (@$collection[':sub'])
                    $subs[] = $simple;
            }
        }
        $rv = array($fonts, $subs);
        Signal::send('config.ttfonts', null, $rv);
        return $rv;
    }

    static function setCurrentLanguage($lang) {
        if (!self::isLanguageInstalled($lang))
            return false;

        $_SESSION['::lang'] = $lang ?: null;
        return true;
    }

    static function allLocales() {
        $locales = array();
        if (class_exists('ResourceBundle')) {
            $current_lang = self::getCurrentLanguage();
            $langs = array();
            foreach (self::getConfiguredSystemLanguages() as $code=>$info) {
                list($lang,) = explode('_', $code, 2);
                $langs[$lang] = true;
            }
            foreach (ResourceBundle::getLocales('') as $code) {
                list($lang,) = explode('_', $code, 2);
                if (isset($langs[$lang])) {
                    $locales[$code] = Locale::getDisplayName($code, $current_lang);
                }
            }
        }
        return $locales;
    }

    static function sortKeyedList($list, $case=false) {
        global $cfg;

        if ($cfg && function_exists('collator_create')) {
            $coll = Collator::create($cfg->getPrimaryLanguage());
            if (!$case)
                $coll->setStrength(Collator::TERTIARY);
            // UASORT is necessary to preserve the keys
            uasort($list, function($a, $b) use ($coll) {
                return $coll->compare($a, $b); });
        }
        else {
            if (!$case)
                uasort($list, function($a, $b) {
                    return strcmp(mb_strtoupper($a), mb_strtoupper($b)); });
            else
                // Really only works on ascii names
                asort($list);
        }
        return $list;
    }

    static function bootstrap() {

        require_once INCLUDE_DIR . 'class.translation.php';

        $domain = 'messages';
        TextDomain::setDefaultDomain($domain);
        TextDomain::lookup()->setPath(I18N_DIR);

        // Set the default locale to UTF-8. It will be changed by
        // ::setLocaleForUser() later for web requests. See #2910
        TextDomain::setLocale(LC_ALL, 'en_US.UTF-8');

        // User-specific translations
        function _N($msgid, $plural, $n) {
            return TextDomain::lookup()->getTranslation()
                ->ngettext($msgid, $plural, is_numeric($n) ? $n : 1);
        }

        // System-specific translations
        function _S($msgid) {
            global $cfg;
            return __($msgid);
        }
        function _NS($msgid, $plural, $count) {
            global $cfg;
        }

        // Phrases with separate contexts
        function _P($context, $msgid) {
            return TextDomain::lookup()->getTranslation()
                ->pgettext($context, $msgid);
        }
        function _NP($context, $singular, $plural, $n) {
            return TextDomain::lookup()->getTranslation()
                ->npgettext($context, $singular, $plural, is_numeric($n) ? $n : 1);
        }

        // Language-specific translations
        function _L($msgid, $locale) {
            return TextDomain::lookup()->getTranslation($locale)
                ->translate($msgid);
        }
        function _NL($msgid, $plural, $n, $locale) {
            return TextDomain::lookup()->getTranslation($locale)
                ->ngettext($msgid, $plural, is_numeric($n) ? $n : 1);
        }
    }
}

class DataTemplate {
    // Base folder for default data and templates
    var $base = I18N_DIR;

    var $filepath;
    var $data;

    /**
     * Searches for the files matching the template in the order of the
     * received languages. Once matched, the language is captured so that
     * template itself does not have to keep track of the language for which
     * it is defined.
     */
    function __construct($path, $langs=array('en_US')) {
        foreach ($langs as $l) {
            if (file_exists("{$this->base}/$l/$path")) {
                $this->lang = $l;
                $this->filepath = Misc::realpath("{$this->base}/$l/$path");
                break;
            }
            elseif (class_exists('Phar')
                    && Phar::isValidPharFilename("{$this->base}/$l.phar")
                    && file_exists("phar://{$this->base}/$l.phar/$path")) {
                $this->lang = $l;
                $this->filepath = "phar://{$this->base}/$l.phar/$path";
                break;
            }
        }
    }

    function getData() {
        if (!isset($this->data) && $this->filepath)
            $this->data = YamlDataParser::load($this->filepath);
            // TODO: If there was a parsing error, attempt to try the next
            //       language in the list of requested languages
        return $this->data;
    }

    function getRawData() {
        if (!isset($this->data) && $this->filepath)
            return file_get_contents($this->filepath);
            // TODO: If there was a parsing error, attempt to try the next
            //       language in the list of requested languages
        return false;
    }

    function getLang() {
        return $this->lang;
    }
}

?>
