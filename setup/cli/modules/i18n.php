<?php

require_once dirname(__file__) . "/class.module.php";
require_once dirname(__file__) . "/../cli.inc.php";
require_once INCLUDE_DIR . 'class.format.php';

class i18n_Compiler extends Module {

    var $prologue = "Manages translation files from Crowdin";

    var $arguments = array(
        "command" => "Action to be performed.
            list    - Show list of available translations"
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
}

Module::register('i18n', 'i18n_Compiler');
?>
