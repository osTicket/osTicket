<?php

require_once dirname(__file__) . "/class.module.php";
require_once dirname(__file__) . "/../cli.inc.php";
require_once INCLUDE_DIR . 'class.format.php';

class i18n_Compiler extends Module {

    var $prologue = "Manages translation files from Crowdin";

    var $arguments = array(
        "command" => "Action to be performed.
            list    - Show list of available translations
            make-pot - Build the PO file for gettext translations"
    );

    var $options = array(
        "key" => array('-k','--key','metavar'=>'API-KEY',
            'help'=>'Crowdin project API key. This can be omitted if
            CROWDIN_API_KEY is defined in the ost-config.php file'),
        "lang" => array('-L', '--lang', 'metavar'=>'code',
            'help'=>'Language code (used for building)'),
    );

    static $crowdin_api_url = 'http://i18n.osticket.com/api/project/osticket-official/{command}';

    function _http_get($url) {
        #curl post
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket/'.THIS_VERSION);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result=curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($code, $result);
    }

    function _request($command, $args=array()) {

        $url = str_replace('{command}', $command, self::$crowdin_api_url);

        $args += array('key' => $this->key);
        foreach ($args as &$a)
            $a = urlencode($a);
        unset($a);
        $url .= '?' . Format::array_implode('=', '&', $args);

        return $this->_http_get($url);
    }

    function run($args, $options) {
        $this->key = $options['key'];
        if (!$this->key && defined('CROWDIN_API_KEY'))
            $this->key = CROWDIN_API_KEY;

        switch (strtolower($args['command'])) {
        case 'list':
            if (!$this->key)
                $this->fail('API key is required');
            $this->_list();
            break;
        case 'build':
            if (!$this->key)
                $this->fail('API key is required');
            if (!$options['lang'])
                $this->fail('Language code is required. See `list`');
            $this->_build($options['lang']);
            break;
        case 'make-pot':
            $this->_make_pot();
            break;
        }
    }

    function _list() {
        error_reporting(E_ALL);
        list($code, $body) = $this->_request('status');
        $d = new DOMDocument();
        $d->loadXML($body);

        $xp = new DOMXpath($d);
        foreach ($xp->query('//language') as $c) {
            $name = $code = '';
            foreach ($c->childNodes as $n) {
                switch (strtolower($n->nodeName)) {
                case 'name':
                    $name = $n->textContent;
                    break;
                case 'code':
                    $code = $n->textContent;
                    break;
                }
            }
            if (!$code)
                continue;
            $this->stdout->write(sprintf("%s (%s)\n", $code, $name));
        }
    }

    function _build($lang) {
        list($code, $zip) = $this->_request("download/$lang.zip");

        if ($code !== 200)
            $this->fail('Language is not available'."\n");

        $temp = tempnam('/tmp', 'osticket-cli');
        $f = fopen($temp, 'w');
        fwrite($f, $zip);
        fclose($f);
        $zip = new ZipArchive();
        $zip->open($temp);
        unlink($temp);

        $lang = str_replace('-','_',$lang);
        @unlink(I18N_DIR."$lang.phar");
        $phar = new Phar(I18N_DIR."$lang.phar");

        for ($i=0; $i<$zip->numFiles; $i++) {
            $info = $zip->statIndex($i);
            $phar->addFromString($info['name'], $zip->getFromIndex($i));
        }

        // TODO: Add i18n extras (like fonts)

        // TODO: Sign files
    }

    function __read_next_string($tokens) {
        $string = array();

        while (list(,$T) = each($tokens)) {
            switch ($T[0]) {
                case T_CONSTANT_ENCAPSED_STRING:
                    // String leading and trailing ' and " chars
                    $string['form'] = preg_replace(array("`^{$T[1][0]}`","`{$T[1][0]}$`"),array("",""), $T[1]);
                    $string['line'] = $T[2];
                    break;
                case T_DOC_COMMENT:
                case T_COMMENT:
                    switch ($T[1][0]) {
                    case '/':
                        if ($T[1][1] == '*')
                            $text = trim($T[1], '/* ');
                        else
                            $text = ltrim($T[1], '/ ');
                        break;
                    case '#':
                        $text = ltrim($T[1], '# ');
                    }
                    $string['comment'] = $text;
                    break;
                case T_WHITESPACE:
                    // noop
                    continue;
                case T_STRING_VARNAME:
                case T_NUM_STRING:
                case T_ENCAPSED_AND_WHITESPACE:
                case '.':
                    $string['constant'] = false;
                    break;
                default:
                    return array($string, $T);
            }
        }
    }
    function __read_args($tokens, $constants=1) {
        $args = array('forms'=>array());
        $arg = null;

        while (list($string,$T) = $this->__read_next_string($tokens)) {
            if (count($args['forms']) < $constants && $string) {
                if (isset($string['constant']) && !$string['constant']) {
                    throw new Exception($string['form'] . ': Untranslatable string');
                }
                $args['forms'][] = $string['form'];
                $args['line'] = $string['line'];
                if (isset($string['comment']))
                    $args['comments'][] = $string['comment'];
            }

            switch ($T[0]) {
            case ')':
                return $args;
            }
        }
    }

    function __get_func_args($tokens, $args) {
        while (list(,$T) = each($tokens)) {
            switch ($T[0]) {
            case T_WHITESPACE:
                continue;
            case '(':
                return $this->__read_args($tokens, $args);
            default:
                // Not a function call
                return false;
            }
        }
    }
    function __find_strings($tokens, $funcs, $parens=0) {
        $T_funcs = array();
        $funcdef = false;
        while (list(,$T) = each($tokens)) {
            switch ($T[0]) {
            case T_STRING:
                if ($funcdef)
                    break;;
                if ($T[1] == 'sprintf') {
                    foreach ($this->__find_strings($tokens, $funcs) as $i=>$f) {
                        // Only the first on gets the php-format flag
                        if ($i == 0)
                            $f['flags'] = array('php-format');
                        $T_funcs[] = $f;
                    }
                    break;
                }
                if (!isset($funcs[$T[1]]))
                    continue;
                $constants = $funcs[$T[1]];
                if ($info = $this->__get_func_args($tokens, $constants))
                    $T_funcs[] = $info;
                break;
            case T_COMMENT:
            case T_DOC_COMMENT:
                if (strpos($T[1], '* trans *') !== false) {
                    // Find the next textual token
                    list($S, $T) = $this->__read_next_string($tokens);
                    $string = array('forms'=>array($S['form']), 'line'=>$S['line']);
                    if (isset($S['comment']))
                        $string['comments'][] = $S['comment'];
                    $T_funcs[] = $string;
                }
                break;
            // Track function definitions of the gettext functions
            case T_FUNCTION:
                $funcdef = true;
                break;
            case '{';
                $funcdef = false;
            case '(':
                $parens++;
                break;
            case ')':
                // End of scope?
                if (--$parens == 0)
                    return $T_funcs;
            }
        }
        return $T_funcs;
    }

    function __write_string($string) {
        // Unescape single quote (') and escape unescaped double quotes (")
        $string = preg_replace(array("`\\\(['$])`", '`(?<!\\\)"`'), array("$1", '\"'), $string);
        // Preserve embedded newlines
        $string = str_replace("\n", "\\n\n", $string);
        // Word-wrap long lines
        $string = rtrim(preg_replace('/(?=[\s\p{Ps}])(.{1,76})(\s|$|(\p{Ps}))/uS',
            "$1$2\n", $string), "\n");
        $strings = explode("\n", $string);

        if (count($strings) > 1)
            array_unshift($strings, "");
        foreach ($strings as $line) {
            print "\"{$line}\"\n";
        }
    }
    function __write_pot_header() {
        $lines = array(
            'msgid ""',
            'msgstr ""',
            '"Project-Id-Version: osTicket '.trim(`git describe`).'\n"',
            '"POT-Create-Date: '.date('Y-m-d H:i O').'\n"',
            '"Report-Msgid-Bugs-To: support@osticket.com\n"',
            '"Language: en_US\n"',
            '"MIME-Version: 1.0\n"',
            '"Content-Type: text/plain; charset=UTF-8\n"',
            '"Content-Transfer-Encoding: 8bit\n"',
            '"X-Generator: osTicket i18n CLI\n"',
        );
        print implode("\n", $lines);
        print "\n";
    }
    function __write_pot($strings) {
        $this->__write_pot_header();
        foreach ($strings as $S) {
            print "\n";
            if ($c = @$S['comments']) {
                foreach ($c as $comment) {
                    foreach (explode("\n", $comment) as $line) {
                        $line = trim($line);
                        print "#. {$line}\n";
                    }
                }
            }
            foreach ($S['usage'] as $ref) {
                print "#: ".$ref."\n";
            }
            if ($f = @$S['flags']) {
                print "#, ".implode(', ', $f)."\n";
            }
            print "msgid ";
            $this->__write_string($S['forms'][0]);
            if (count($S['forms']) == 2) {
                print "msgid_plural ";
                $this->__write_string($S['forms'][1]);
                print 'msgstr[0] ""'."\n";
                print 'msgstr[1] ""'."\n";
            }
            else {
                print 'msgstr ""'."\n";
            }
        }
    }

    function _make_pot() {
        error_reporting(E_ALL);
        $funcs = array('__'=>1, '_N'=>2);
        function get_osticket_root_path() { return ROOT_DIR; }
        require_once(ROOT_DIR.'setup/test/tests/class.test.php');
        $files = Test::getAllScripts();
        $strings = array();
        foreach ($files as $f) {
            $F = str_replace(ROOT_DIR, '', $f);
            $this->stderr->write("$F\n");
            $tokens = new ArrayObject(token_get_all(fread(fopen($f, 'r'), filesize($f))));
            foreach ($this->__find_strings($tokens, $funcs, 1) as $calls) {
                if (!($forms = $calls['forms']))
                    // Transation of non-constant
                    continue;
                $primary = $forms[0];
                if (!isset($strings[$primary])) {
                    $strings[$primary] = array('forms' => $forms);
                }
                $E = &$strings[$primary];

                if (isset($calls['line']))
                    $E['usage'][] = "{$F}:{$calls['line']}";
                if (isset($calls['flags']))
                    $E['flags'] = array_unique(array_merge(@$E['flags'] ?: array(), $calls['flags']));
                if (isset($calls['comments']))
                    $E['comments'] = array_merge(@$E['comments'] ?: array(), $calls['comments']);
            }
        }
        $this->__write_pot($strings);
    }
}

Module::register('i18n', 'i18n_Compiler');
?>
