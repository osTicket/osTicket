<?php

require_once INCLUDE_DIR . 'class.format.php';

class i18n_Compiler extends Module {

    var $prologue = "Manages translation files from Crowdin";

    var $arguments = array(
        "command" => array(
            'help' => "Action to be performed.",
            "options" => array(
                'list' =>       'Show list of available translations',
                'build' =>      'Compile a language pack',
                'similar' =>    'Find very similar strings',
                'make-pot' =>   'Build the PO file for gettext translations',
                'sign' =>       'Sign a language pack',
            ),
        ),
        'file(s)' => array(
            'required' => false,
            'help' => 'File(s) to be signed, used with `sign`',
        ),
    );

    var $options = array(
        "key" => array('-k','--key','metavar'=>'API-KEY',
            'help'=>'Crowdin project API key. This can be omitted if
            CROWDIN_API_KEY is defined in the ost-config.php file'),
        "lang" => array('-L', '--lang', 'metavar'=>'code',
            'help'=>'Language code (used for building)'),
        'file' => array('-f', '--file', 'metavar'=>'FILE',
            'help' => "Language pack to be signed"),
        'pkey' => array('-P', '--pkey', 'metavar'=>'key-file',
            'help' => 'Private key for signing'),
        'root' => array('-R', '--root', 'matavar'=>'path',
            'help' => 'Specify a root folder for `make-pot`'),
        'domain' => array('-D', '--domain', 'metavar'=>'name',
            'default' => '',
            'help' => 'Add a domain to the path/context of PO strings'),
        'dns' => array('-d', '--dns', 'default' => false, 'metavar' => 'zone-id',
            'help' => 'Write signature to DNS (via this AWS HostedZoneId)'),
        'zlib' => array('-z', '--zlib', 'default' => false,
            'action' => 'store_true', 'help' => 'Compress PHAR with zlib'),
        'bzip2' => array('-j', '--bzip2', 'default' => false,
            'action' => 'store_true', 'help' => 'Compress PHAR with bzip2'),
        'branch' => array('-b', '--branch', 'help' => 'Use a Crowdin branch
            (other than the root)'),
    );

    var $epilog = "Note: If updating DNS, you will need to set
        AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY in the AWS credentials
        profile in your home folder or in your environment. See AWS
        configuration  docs for more information";

    static $project = 'osticket-official';
    static $crowdin_api_url = 'http://i18n.osticket.com/api/project/{project}/{command}';

    function _http_get($url) {
        $this->stdout->write(">>> Downloading $url\n");
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

        $url = str_replace(array('{command}', '{project}'),
            array($command, self::$project),
            self::$crowdin_api_url);

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

        function get_osticket_root_path() { return ROOT_DIR; }
        require_once(ROOT_DIR.'setup/test/tests/class.test.php');

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
            $this->_build($options['lang'], $options);
            break;
        case 'similar':
            $this->find_similar($options);
            break;
        case 'make-pot':
            $this->_make_pot($options);
            break;
        case 'sign':
            if (count($args) < 2)
                $this->fail('Specify a language pack to sign');
            foreach (range(1, count($args)-1, 1) as $i) {
                $plugin = $args[$i];
                if (!is_file($args[$i]))
                    $this->fail($args[$i].': No such file');
                $this->_sign($args[$i], $options);
            }
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

    function _build($lang, $options) {
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
        $phar->startBuffering();
        if ($options['zlib'])
            $phar->compress(Phar::GZ, 'phar');
        if ($options['bzip2'])
            $phar->compress(Phar::BZ2, 'phar');

        $po_file = false;

        $branch = false;
        if ($options['branch'])
            $branch = trim($options['branch'], '/') . '/';
        for ($i=0; $i<$zip->numFiles; $i++) {
            $info = $zip->statIndex($i);
            if ($branch && strpos($info['name'], $branch) !== 0) {
                // Skip files not part of the named branch
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if (!$contents)
                continue;
            if (fnmatch('*/messages*.po', $info['name']) !== false) {
                $po_file = $contents;
                // Don't add the PO file as-is to the PHAR file
                continue;
            }
            elseif (!$branch && !file_exists(I18N_DIR . 'en_US/' . $info['name'])) {
                // Skip files in (other) branches
                continue;
            }
            $phar->addFromString($info['name'], $contents);
        }

        // TODO: Add i18n extras (like fonts)
        // Redactor language pack
        //
        $langs = array($lang);
        if (strpos($lang, '_') !== false) {
            @list($short) = explode('_', $lang);
            $langs[] = $short;
        }
        foreach ($langs as $l) {
            list($code, $js) = $this->_http_get(
                sprintf('https://imperavi.com/download/redactor/langs/%s/',
                    strtolower($l)));
            if ($code == 200 && strlen($js) > 100) {
                $phar->addFromString('js/redactor.js', $js);
                break;
            }
        }
        if ($code != 200)
            $this->stderr->write($lang . ": Unable to fetch Redactor language file\n");

        // JQuery UI Datepicker
        // http://jquery-ui.googlecode.com/svn/tags/latest/ui/i18n/jquery.ui.datepicker-de.js
        foreach ($langs as $l) {
            list($code, $js) = $this->_http_get(
                'http://jquery-ui.googlecode.com/svn/tags/latest/ui/i18n/jquery.ui.datepicker-'
                    .str_replace('_','-',$l).'.js');
            // If locale-specific version is not available, use the base
            // language version (de if de_CH is not available)
            if ($code == 200)
                break;
        }
        if ($code == 200)
            $phar->addFromString('js/jquery.ui.datepicker.js', $js);
        else
            $this->stderr->write(str_replace('_','-',$lang)
                .": Unable to fetch jQuery UI Datepicker locale file\n");

        // True type fonts for PDF printing
        $langs = (include I18N_DIR . 'langs.php');
        $info = $langs[$lang];
        if (isset($info['fonts'])) {
            foreach ($info['fonts'] as $name => $types) {
                foreach ($types as $code => $fullname) {
                    list($ttf, $url) = $fullname;
                    if (!$url)
                        continue;
                    list($code, $file) = $this->_http_get($url);
                    if ($code == 200)
                        $phar->addFromString('fonts/'.$ttf, $file);
                    else
                        $this->stderr->write(
                            "*** Unable to fetch $url\n");
                }
            }
        }

        // Add in the messages.mo.php file
        if ($po_file) {
            $pipes = array();
            $msgfmt = proc_open('msgfmt -o- -',
                array(0=>array('pipe','r'), 1=>array('pipe','w')),
                $pipes);
            if (is_resource($msgfmt)) {
                fwrite($pipes[0], $po_file);
                fclose($pipes[0]);
                $mo_input = fopen('php://temp', 'r+b');
                fwrite($mo_input, stream_get_contents($pipes[1]));
                rewind($mo_input);
                require_once INCLUDE_DIR . 'class.translation.php';
                $mo = Translation::buildHashFile($mo_input, false, true);
                $phar->addFromString('LC_MESSAGES/messages.mo.php', $mo);
                fclose($mo_input);
            }
        }

        // Add in translation of javascript strings
        $phrases = array();
        if ($mo && ($js = $this->__getAllJsPhrases())) {
            $mo = (eval (substr($mo, 5))); # Chop off <?php
            foreach ($js as $c) {
                foreach ($c['forms'] as $f) {
                    $phrases[$f] = @$mo[$f] ?: $f;
                }
            }
            $phar->addFromString(
                'js/osticket-strings.js',
                sprintf('(function($){$.oststrings=%s;})(jQuery);',
                    JsonDataEncoder::encode($phrases))
            );
        }

        // Include a manifest
        include_once INCLUDE_DIR . 'class.mailfetch.php';

        $po_header = Mail_Parse::splitHeaders($mo['']);
        $info = array(
            'Build-Date' => date(DATE_RFC822),
            'Phrases-Version' => $po_header['X-Osticket-Major-Version'],
            'Build-Version' => trim(`git describe`),
            'Build-Major-Version' => MAJOR_VERSION,
            'Language' => $po_header['Language'],
            #'Phrases' =>
            #'Translated' =>
            #'Approved' =>
            'Id' => 'lang:' . $lang,
            'Last-Revision' => $po_header['PO-Revision-Date'],
            'Version' => (int)(strtotime($po_header['PO-Revision-Date']) / 10000),
        );
        $phar->addFromString(
            'MANIFEST.php',
            sprintf('<?php return %s;', var_export($info, true)));

        // TODO: Sign files

        // Use a very small stub
        $phar->setStub('<?php __HALT_COMPILER();');
        $phar->setSignatureAlgorithm(Phar::SHA1);
        $phar->stopBuffering();
    }

    function _sign($plugin, $options) {
        if (!file_exists($plugin))
            $this->fail($plugin.': Cannot find file');
        elseif (!file_exists("phar://$plugin/MANIFEST.php"))
            $this->fail($plugin.': Should be a plugin PHAR file');
        $info = (include "phar://$plugin/MANIFEST.php");
        $phar = new Phar($plugin);

        if (!function_exists('openssl_get_privatekey'))
            $this->fail('OpenSSL extension required for signing');
        if (!$options['pkey'] || !file_exists($options['pkey']))
            $this->fail('Signing private key (-P) required');
        $private = openssl_get_privatekey(
                file_get_contents($options['pkey']));
        if (!$private)
            $this->fail('Unable to read private key');
        $signature = $phar->getSignature();
        $seal = '';
        openssl_sign($signature['hash'], $seal, $private,
            OPENSSL_ALGO_SHA1);
        if (!$seal)
            $this->fail('Unable to generate verify signature');

        $this->stdout->write(sprintf("Signature: %s\n",
            strtolower($signature['hash'])));
        $seal =
            sprintf('"v=1; i=%s; s=%s; V=%s;"',
            $info['Id'], base64_encode($seal), $info['Version']);

        if ($options['dns']) {
            if (!is_file(INCLUDE_DIR . 'aws.phar'))
                $this->fail('Unable to include AWS phar file. Download to INCLUDE_DIR');
            require_once INCLUDE_DIR . 'aws.phar';

            $aws = Aws\Common\Aws::factory(array());
            $client = $aws->get('Route53');

            try {
            $resp = $client->changeResourceRecordSets(array(
                'HostedZoneId' => $options['dns'],
                'ChangeBatch' => array(
                    'Changes' => array(
                        array(
                            'Action' => 'CREATE',
                            'ResourceRecordSet' => array(
                                'Name' => "{$signature['hash']}.updates.osticket.com.",
                                'Type' => 'TXT',
                                'TTL' => 172800,
                                'ResourceRecords' => array(
                                    array(
                                        'Value' => $seal,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ));
            $this->stdout->write(sprintf('%s: %s', $resp['ChangeInfo']['Comment'],
                $resp['ChangeInfo']['Status']));
            }
            catch (Exception $ex) {
                $this->stdout->write("Seal: $seal\n");
                $this->fail('!! AWS Update Failed: '.$ex->getMessage());
            }
        }
        else {
            $this->stdout->write("Seal: $seal\n");
        }
    }

    function __read_next_string($tokens) {
        $string = array();

        while (list(,$T) = each($tokens)) {
            switch ($T[0]) {
                case T_CONSTANT_ENCAPSED_STRING:
                    // Strip leading and trailing ' and " chars
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
                    $string['comments'][] = $text;
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
                case '[':
                    // Not intended to be translated — array index
                    return null;
                default:
                    return array($string, $T);
            }
        }
    }
    function __read_args($tokens, $proto=false) {
        $args = array('forms'=>array());
        $arg = null;
        $proto = $proto ?: array('forms'=>1);

        while (list($string,$T) = $this->__read_next_string($tokens)) {
            // Add context and forms
            if (isset($proto['context']) && !isset($args['context'])) {
                $args['context'] = $string['form'];
            }
            elseif (count($args['forms']) < $proto['forms'] && $string) {
                if (isset($string['constant']) && !$string['constant']) {
                    throw new Exception($string['form'] . ': Untranslatable string');
                }
                $args['forms'][] = $string['form'];
            }
            elseif ($string) {
                $this->stderr->write(sprintf("%s: %s: Too many arguments\n",
                    $string['line'] ?: '?', $string['form']));
            }

            // Add usage and comment info
            if (!isset($args['line']) && isset($string['line']))
                $args['line'] = $string['line'];
            if (isset($string['comments']))
                $args['comments'] = array_merge(
                    @$args['comments'] ?: array(), $string['comments']);

            // Handle the terminating token from ::__read_next_string()
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
            case T_VARIABLE:
                if ($funcdef)
                    break;
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
                $translate = false;
                $hints = array();
                if (preg_match('`^/\*+\s*@(\w+)`m', $T[1])) {
                    foreach (preg_split('`,\s*`m', $T[1]) as $command) {
                        $command = trim($command, " \n\r\t\"*/\\");
                        @list($command, $args) = explode(' ', $command, 2);
                        switch ($command) {
                        case '@context':
                            $hints['context'] = trim($args, " \"*\n\t\r");
                        case '@trans':
                            $translate = true;
                        default:
                            continue;
                        }
                    }
                }
                if ($translate) {
                    // Find the next textual token
                    list($S, $T) = $this->__read_next_string($tokens);
                    $string = array('forms'=>array($S['form']), 'line'=>$S['line'])
                        + $hints;
                    if (isset($S['comments']))
                        $string['comments'] = $S['comments'];
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
        // Preserve embedded newlines -- preserve up to on
        $string = preg_replace("`\n`u", "\\n\n", $string);
        // Word-wrap long lines
        $string = rtrim(preg_replace('/(?=[\s\p{Ps}])(.{1,76})(\s|$|(\p{Ps}))/uS',
            "$1$2\n", $string), "\n");
        $strings = array_filter(explode("\n", $string));

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
            '"X-Osticket-Major-Version: '.MAJOR_VERSION.'\n"',
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
                        if ($line = trim($line))
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
            if (isset($S['context'])) {
                print "msgctxt ";
                $this->__write_string($S['context']);
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

    function find_strings($options) {
        error_reporting(E_ALL);
        $funcs = array(
            '__'    => array('forms'=>1),
            '$__'   => array('forms'=>1),
            '_S'    => array('forms'=>1),
            '_N'    => array('forms'=>2),
            '$_N'   => array('forms'=>2),
            '_NS'   => array('forms'=>2),
            '_P'    => array('context'=>1, 'forms'=>1),
            '_NP'   => array('context'=>1, 'forms'=>2),
            // This is an error
            '_'     => array('forms'=>0),
        );
        $root = realpath($options['root'] ?: ROOT_DIR);
        $domain = $options['domain'] ? '('.$options['domain'].')/' : '';
        $files = Test::getAllScripts(true, $root);
        $strings = array();
        foreach ($files as $f) {
            $F = str_replace($root.'/', $domain, $f);
            $this->stderr->write("$F\n");
            $tokens = new ArrayObject(token_get_all(fread(fopen($f, 'r'), filesize($f))));
            foreach ($this->__find_strings($tokens, $funcs, 1) as $call) {
                self::__addString($strings, $call, $F);
            }
        }
        return array_merge($strings, $this->__getAllJsPhrases($root));
    }

    function _make_pot($options) {
        $strings = $this->find_strings($options);
        $this->__write_pot($strings);
    }

    function find_similar($options) {
        $strings = $this->find_strings($options);
        $roots = array();
        foreach ($strings as $root=>$S) {
            $roots[] = array(substr(mb_strtoupper($root), 0, 255), $S['usage'][0], $root);
        }
        sort($roots);
        $length = count($roots);
        foreach ($roots as $idx=>$root) {
            list($phrase, $usage, $orig) = $root;
            $i = $idx;
            $similar = ((int) strlen($phrase) * 0.1) ?: 1;
            while (++$i < $length) {
                list($other, $other_usage, $other_orig) = $roots[$i];
                if (levenshtein($phrase, $other) < $similar) {
                    $this->stdout->write(sprintf(
                        "'%s' (%s) and '%s' (%s)\n",
                       $orig, $usage, $other_orig, $other_usage
                    )); 
                }
            }
        }
    }

    static function __addString(&$strings, $call, $file=false) {
        if (!($forms = @$call['forms']))
            // Transation of non-constant
            return;
        $primary = $forms[0];
        // Normalize the $primary string
        $primary = preg_replace(array("`\\\(['$])`", '`(?<!\\\)"`'), array("$1", '\"'), $primary);
        if (isset($call['context']))
            $primary = $call['context'] . "\x04" . $primary;
        if (!isset($strings[$primary])) {
            $strings[$primary] = array('forms' => $forms);
        }
        $E = &$strings[$primary];

        if (isset($call['line']) && $file)
            $E['usage'][] = "{$file}:{$call['line']}";
        if (isset($call['flags']))
            $E['flags'] = array_unique(array_merge(@$E['flags'] ?: array(), $call['flags']));
        if (isset($call['comments']))
            $E['comments'] = array_merge(@$E['comments'] ?: array(), $call['comments']);
        if (isset($call['context']))
            $E['context'] = $call['context'];
    }

    function __getAllJsPhrases($root=ROOT_DIR) {
        $strings = array();
        $root = rtrim($root, '/') . '/';
        $funcs = array('__'=>array('forms'=>1));
        foreach (glob_recursive($root . "*.js") as $s) {
            $script = file_get_contents($s);
            $s = str_replace($root, '', $s);
            $this->stderr->write($s."\n");
            $calls = array();
            preg_match_all('/(?:function\s+)?__\(\s*[^\'"]*(([\'"])(?:(?<!\\\\)\2|.)+\2)\s*[^)]*\)/',
                $script, $calls, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach ($calls as $c) {
                if (!($call = $this->__find_strings(token_get_all('<?php '.$c[0][0]), $funcs, 0)))
                    continue;
                $call = $call[0];

                list($lhs) = str_split($script, $c[1][1]);
                $call['line'] = strlen($lhs) - strlen(str_replace("\n", "", $lhs)) + 1;

                self::__addString($strings, $call, $s);
            }
        }
        return $strings;
    }
}

Module::register('i18n', 'i18n_Compiler');
?>
