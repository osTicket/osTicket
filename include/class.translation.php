<?php
/*********************************************************************
    class.gettext.php

    This implements a `Translation` class that is loosely based on the PHP
    gettext pure-php module. It includes some code from the project and some
    code which is based in part at least on the PHP gettext project.

    This extension to the PHP gettext extension using a specially crafted MO
    file which is a PHP hash array. The file can be built using a utility
    method in this class.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    PHP gettext extension is copyrighted separately:
    ---------------
    Copyright (c) 2003, 2009 Danilo Segan <danilo@kvota.net>.
    Copyright (c) 2005 Nico Kaiser <nico@siriux.net>

    This file is part of PHP-gettext.

    PHP-gettext is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    PHP-gettext is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PHP-gettext; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    ---------------

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

/**
 * Provides a simple gettext replacement that works independently from
 * the system's gettext abilities.
 * It can read MO files and use them for translating strings.
 * The files are passed to gettext_reader as a Stream (see streams.php)
 *
 * This version has the ability to cache all strings and translations to
 * speed up the string lookup.
 * While the cache is enabled by default, it can be switched off with the
 * second parameter in the constructor (e.g. whenusing very large MO files
 * that you don't want to keep in memory)
 */
class gettext_reader {
  //public:
   var $error = 0; // public variable that holds error code (0 if no error)

   //private:
  var $BYTEORDER = 0;        // 0: low endian, 1: big endian
  var $STREAM = NULL;
  var $short_circuit = false;
  var $enable_cache = false;
  var $originals = NULL;      // offset of original table
  var $translations = NULL;    // offset of translation table
  var $pluralheader = NULL;    // cache header field for plural forms
  var $total = 0;          // total string count
  var $table_originals = NULL;  // table for original strings (offsets)
  var $table_translations = NULL;  // table for translated strings (offsets)
  var $cache_translations = NULL;  // original -> translation mapping


  /* Methods */


  /**
   * Reads a 32bit Integer from the Stream
   *
   * @access private
   * @return Integer from the Stream
   */
  function readint() {
      if ($this->BYTEORDER == 0) {
        // low endian
        $input=unpack('V', $this->STREAM->read(4));
        return array_shift($input);
      } else {
        // big endian
        $input=unpack('N', $this->STREAM->read(4));
        return array_shift($input);
      }
    }

  function read($bytes) {
    return $this->STREAM->read($bytes);
  }

  /**
   * Reads an array of Integers from the Stream
   *
   * @param int count How many elements should be read
   * @return Array of Integers
   */
  function readintarray($count) {
    if ($this->BYTEORDER == 0) {
        // low endian
        return unpack('V'.$count, $this->STREAM->read(4 * $count));
      } else {
        // big endian
        return unpack('N'.$count, $this->STREAM->read(4 * $count));
      }
  }

  /**
   * Constructor
   *
   * @param object Reader the StreamReader object
   * @param boolean enable_cache Enable or disable caching of strings (default on)
   */
  function __construct($Reader, $enable_cache = true) {
    // If there isn't a StreamReader, turn on short circuit mode.
    if (! $Reader || isset($Reader->error) ) {
      $this->short_circuit = true;
      return;
    }

    // Caching can be turned off
    $this->enable_cache = $enable_cache;

    $MAGIC1 = "\x95\x04\x12\xde";
    $MAGIC2 = "\xde\x12\x04\x95";

    $this->STREAM = $Reader;
    $magic = $this->read(4);
    if ($magic == $MAGIC1) {
      $this->BYTEORDER = 1;
    } elseif ($magic == $MAGIC2) {
      $this->BYTEORDER = 0;
    } else {
      $this->error = 1; // not MO file
      return false;
    }

    // FIXME: Do we care about revision? We should.
    $this->revision = $this->readint();

    $this->total = $this->readint();
    $this->originals = $this->readint();
    $this->translations = $this->readint();
  }

  /**
   * Loads the translation tables from the MO file into the cache
   * If caching is enabled, also loads all strings into a cache
   * to speed up translation lookups
   *
   * @access private
   */
  function load_tables() {
    if (is_array($this->cache_translations) &&
      is_array($this->table_originals) &&
      is_array($this->table_translations))
      return;

    /* get original and translations tables */
    if (!is_array($this->table_originals)) {
      $this->STREAM->seekto($this->originals);
      $this->table_originals = $this->readintarray($this->total * 2);
    }
    if (!is_array($this->table_translations)) {
      $this->STREAM->seekto($this->translations);
      $this->table_translations = $this->readintarray($this->total * 2);
    }

    if ($this->enable_cache) {
      $this->cache_translations = array ();
      /* read all strings in the cache */
      for ($i = 0; $i < $this->total; $i++) {
        $this->STREAM->seekto($this->table_originals[$i * 2 + 2]);
        $original = $this->STREAM->read($this->table_originals[$i * 2 + 1]);
        $this->STREAM->seekto($this->table_translations[$i * 2 + 2]);
        $translation = $this->STREAM->read($this->table_translations[$i * 2 + 1]);
        $this->cache_translations[$original] = $translation;
      }
    }
  }

  /**
   * Returns a string from the "originals" table
   *
   * @access private
   * @param int num Offset number of original string
   * @return string Requested string if found, otherwise ''
   */
  function get_original_string($num) {
    $length = $this->table_originals[$num * 2 + 1];
    $offset = $this->table_originals[$num * 2 + 2];
    if (! $length)
      return '';
    $this->STREAM->seekto($offset);
    $data = $this->STREAM->read($length);
    return (string)$data;
  }

  /**
   * Returns a string from the "translations" table
   *
   * @access private
   * @param int num Offset number of original string
   * @return string Requested string if found, otherwise ''
   */
  function get_translation_string($num) {
    $length = $this->table_translations[$num * 2 + 1];
    $offset = $this->table_translations[$num * 2 + 2];
    if (! $length)
      return '';
    $this->STREAM->seekto($offset);
    $data = $this->STREAM->read($length);
    return (string)$data;
  }

  /**
   * Binary search for string
   *
   * @access private
   * @param string string
   * @param int start (internally used in recursive function)
   * @param int end (internally used in recursive function)
   * @return int string number (offset in originals table)
   */
  function find_string($string, $start = -1, $end = -1) {
    if (($start == -1) or ($end == -1)) {
      // find_string is called with only one parameter, set start end end
      $start = 0;
      $end = $this->total;
    }
    if (abs($start - $end) <= 1) {
      // We're done, now we either found the string, or it doesn't exist
      $txt = $this->get_original_string($start);
      if ($string == $txt)
        return $start;
      else
        return -1;
    } else if ($start > $end) {
      // start > end -> turn around and start over
      return $this->find_string($string, $end, $start);
    } else {
      // Divide table in two parts
      $half = (int)(($start + $end) / 2);
      $cmp = strcmp($string, $this->get_original_string($half));
      if ($cmp == 0)
        // string is exactly in the middle => return it
        return $half;
      else if ($cmp < 0)
        // The string is in the upper half
        return $this->find_string($string, $start, $half);
      else
        // The string is in the lower half
        return $this->find_string($string, $half, $end);
    }
  }

  /**
   * Translates a string
   *
   * @access public
   * @param string string to be translated
   * @return string translated string (or original, if not found)
   */
  function translate($string) {
    if ($this->short_circuit)
      return $string;
    $this->load_tables();

    if ($this->enable_cache) {
      // Caching enabled, get translated string from cache
      if (array_key_exists($string, $this->cache_translations))
        return $this->cache_translations[$string];
      else
        return $string;
    } else {
      // Caching not enabled, try to find string
      $num = $this->find_string($string);
      if ($num == -1)
        return $string;
      else
        return $this->get_translation_string($num);
    }
  }

  /**
   * Sanitize plural form expression for use in PHP eval call.
   *
   * @access private
   * @return string sanitized plural form expression
   */
  function sanitize_plural_expression($expr) {
    // Get rid of disallowed characters.
    $expr = preg_replace('@[^a-zA-Z0-9_:;\(\)\?\|\&=!<>+*/\%-]@', '', $expr);

    // Add parenthesis for tertiary '?' operator.
    $expr .= ';';
    $res = '';
    $p = 0;
    for ($i = 0, $k = strlen($expr); $i < $k; $i++) {
      $ch = $expr[$i];
      switch ($ch) {
      case '?':
        $res .= ' ? (';
        $p++;
        break;
      case ':':
        $res .= ') : (';
        break;
      case ';':
        $res .= str_repeat( ')', $p) . ';';
        $p = 0;
        break;
      default:
        $res .= $ch;
      }
    }
    return $res;
  }

  /**
   * Parse full PO header and extract only plural forms line.
   *
   * @access private
   * @return string verbatim plural form header field
   */
  function extract_plural_forms_header_from_po_header($header) {
    $regs = array();
    if (preg_match("/(^|\n)plural-forms: ([^\n]*)\n/i", $header, $regs))
      $expr = $regs[2];
    else
      $expr = "nplurals=2; plural=n == 1 ? 0 : 1;";
    return $expr;
  }

  /**
   * Get possible plural forms from MO header
   *
   * @access private
   * @return string plural form header
   */
  function get_plural_forms() {
    // lets assume message number 0 is header
    // this is true, right?
    $this->load_tables();

    // cache header field for plural forms
    if (! is_string($this->pluralheader)) {
      if ($this->enable_cache) {
        $header = $this->cache_translations[""];
      } else {
        $header = $this->get_translation_string(0);
      }
      $expr = $this->extract_plural_forms_header_from_po_header($header);
      $this->pluralheader = $this->sanitize_plural_expression($expr);
    }
    return $this->pluralheader;
  }

  /**
   * Detects which plural form to take
   *
   * @access private
   * @param n count
   * @return int array index of the right plural form
   */
  function select_string($n) {
      // Expression reads
      // nplurals=X; plural= n != 1
      if (!isset($this->plural_expression)) {
          $matches = array();
          if (!preg_match('`nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*(.+$)`',
                  $this->get_plural_forms(), $matches))
              return 1;

          $this->plural_expression = create_function('$n',
              sprintf('return %s;', str_replace('n', '($n)', $matches[2])));
          $this->plural_total = (int) $matches[1];
      }
      $func = $this->plural_expression;
      $plural = $func($n);
      return ($plural > $this->plural_total)
          ? $this->plural_total - 1
          : $plural;
  }

  /**
   * Plural version of gettext
   *
   * @access public
   * @param string single
   * @param string plural
   * @param string number
   * @return translated plural form
   */
  function ngettext($single, $plural, $number) {
    if ($this->short_circuit) {
      if ($number != 1)
        return $plural;
      else
        return $single;
    }

    // find out the appropriate form
    $select = $this->select_string($number);

    // this should contains all strings separated by NULLs
    $key = $single . chr(0) . $plural;


    if ($this->enable_cache) {
      if (! array_key_exists($key, $this->cache_translations)) {
        return ($number != 1) ? $plural : $single;
      } else {
        $result = $this->cache_translations[$key];
        $list = explode(chr(0), $result);
        return $list[$select];
      }
    } else {
      $num = $this->find_string($key);
      if ($num == -1) {
        return ($number != 1) ? $plural : $single;
      } else {
        $result = $this->get_translation_string($num);
        $list = explode(chr(0), $result);
        return $list[$select];
      }
    }
  }

  function pgettext($context, $msgid) {
    $key = $context . chr(4) . $msgid;
    $ret = $this->translate($key);
    if (strpos($ret, "\004") !== FALSE) {
      return $msgid;
    } else {
      return $ret;
    }
  }

  function npgettext($context, $singular, $plural, $number) {
    $key = $context . chr(4) . $singular;
    $ret = $this->ngettext($key, $plural, $number);
    if (strpos($ret, "\004") !== FALSE) {
      return $singular;
    } else {
      return $ret;
    }

  }
}

class FileReader {
  var $_pos;
  var $_fd;
  var $_length;

  function __construct($filename) {
    if (is_resource($filename)) {
        $this->_length = strlen(stream_get_contents($filename));
        rewind($filename);
        $this->_fd = $filename;
    }
    elseif (file_exists($filename)) {

      $this->_length=filesize($filename);
      $this->_fd = fopen($filename,'rb');
      if (!$this->_fd) {
        $this->error = 3; // Cannot read file, probably permissions
        return false;
      }
    } else {
      $this->error = 2; // File doesn't exist
      return false;
    }
    $this->_pos = 0;
  }

  function read($bytes) {
    if ($bytes) {
      fseek($this->_fd, $this->_pos);

      // PHP 5.1.1 does not read more than 8192 bytes in one fread()
      // the discussions at PHP Bugs suggest it's the intended behaviour
      $data = '';
      while ($bytes > 0) {
        $chunk  = fread($this->_fd, $bytes);
        $data  .= $chunk;
        $bytes -= strlen($chunk);
      }
      $this->_pos = ftell($this->_fd);

      return $data;
    } else return '';
  }

  function seekto($pos) {
    fseek($this->_fd, $pos);
    $this->_pos = ftell($this->_fd);
    return $this->_pos;
  }

  function currentpos() {
    return $this->_pos;
  }

  function length() {
    return $this->_length;
  }

  function close() {
    fclose($this->_fd);
  }

}

/**
 * Class: Translation
 *
 * This class is strongly based on the gettext_reader class. It makes use of
 * a few simple optimizations for the context of osTicket
 *
 *    * The language packs are pre-compiled and distributed (which means
 *      they can be customized).
 *    * The MO file will always be processed by PHP code
 *    * osTicket uses utf-8 output exclusively (for web traffic anyway)
 *
 * These allow us to optimize the MO file for the osTicket project
 * specifically and make enough of an optimization to allow using a pure-PHP
 * source gettext library implementation which should be roughly the same
 * performance as the libc gettext library.
 */
class Translation extends gettext_reader implements Serializable {

    var $charset;

    const META_HEADER = 0;

    function __construct($reader, $charset=false) {
        if (!$reader)
            return $this->short_circuit = true;

        // Just load the cache
        if (!is_string($reader))
            throw new RuntimeException('Programming Error: Expected filename for translation source');
        $this->STREAM = $reader;

        $this->enable_cache = true;
        $this->charset = $charset;
        $this->encode = $charset && strcasecmp($charset, 'utf-8') !== 0;
        $this->load_tables();
    }

    function load_tables() {
        if (isset($this->cache_translations))
            return;

        $this->cache_translations = (include $this->STREAM);
    }

    function translate($string) {
        if ($this->short_circuit)
            return $string;

        // Caching enabled, get translated string from cache
        if (isset($this->cache_translations[$string]))
            $string = $this->cache_translations[$string];

        if (!$this->encode)
            return $string;

        return Charset::transcode($string, 'utf-8', $this->charset);
    }

    static function buildHashFile($mofile, $outfile=false, $return=false) {
        if (!$outfile) {
            $stream = fopen('php://stdout', 'w');
        }
        elseif (is_string($outfile)) {
            $stream = fopen($outfile, 'w');
        }
        elseif (is_resource($outfile)) {
            $stream = $outfile;
        }

        if (!$stream)
            throw new InvalidArgumentException(
                'Expected a filename or valid resource');

        if (!$mofile instanceof FileReader)
            $mofile = new FileReader($mofile);

        $reader = new parent($mofile, true);

        if ($reader->short_circuit || $reader->error)
            throw new Exception('Unable to initialize MO input file');

        $reader->load_tables();

        // Get basic table
        if (!($table = $reader->cache_translations))
            throw new Exception('Unable to read translations from file');

        // Transcode the table to UTF-8
        $header = $table[""];
        $info = array();
        preg_match('/^content-type: (.*)$/im', $header, $info);
        $charset = false;
        if ($content_type = $info[1]) {
            // Find the charset property
            $settings = explode(';', $content_type);
            foreach ($settings as $v) {
                @list($prop, $value) = explode('=', trim($v), 2);
                if (strtolower($prop) == 'charset') {
                    $charset = trim($value);
                    break;
                }
            }
        }
        if ($charset && strcasecmp($charset, 'utf-8') !== 0) {
            foreach ($table as $orig=>$trans) {
                $table[Charset::utf8($orig, $charset)] =
                    Charset::utf8($trans, $charset);
                unset($table[$orig]);
            }
        }

        // Add in some meta-data
        $table[self::META_HEADER] = array(
            'Revision' => $reader->revision,      // From the MO
            'Total-Strings' => $reader->total,    // From the MO
            'Table-Size' => count($table),      // Sanity check for later
            'Build-Timestamp' => gmdate(DATE_RFC822),
            'Format-Version' => 'A',            // Support future formats
            'Encoding' => 'UTF-8',
        );

        // Serialize the PHP array and write to output
        $contents = sprintf('<?php return %s;', var_export($table, true));
        if ($return)
            return $contents;
        else
            fwrite($stream, $contents);
    }

    static function resurrect($key) {
        if (!function_exists('apcu_fetch'))
            return false;

        $success = true;
        if (($translation = apcu_fetch($key, $success)) && $success)
            return $translation;
    }
    function cache($key) {
        if (function_exists('apcu_add'))
            apcu_add($key, $this);
    }


    function serialize() {
        return serialize(array($this->charset, $this->encode, $this->cache_translations));
    }
    function unserialize($what) {
        list($this->charset, $this->encode, $this->cache_translations)
            = unserialize($what);
        $this->short_circuit = ! $this->enable_cache
            = 0 < $this->cache_translations ? count($this->cache_translations) : 1;
    }
}

if (!defined('LC_MESSAGES')) {
    define('LC_ALL', 0);
    define('LC_CTYPE', 1);
    define('LC_NUMERIC', 2);
    define('LC_TIME', 3);
    define('LC_COLLATE', 4);
    define('LC_MONETARY', 5);
    define('LC_MESSAGES', 6);
}

class TextDomain {
    var $l10n = array();
    var $path;
    var $codeset;
    var $domain;

    static $registry;
    static $default_domain = 'messages';
    static $current_locale = '';
    static $LC_CATEGORIES = array(
        LC_ALL => 'LC_ALL',
        LC_CTYPE => 'LC_CTYPE',
        LC_NUMERIC => 'LC_NUMERIC',
        LC_TIME => 'LC_TIME',
        LC_COLLATE => 'LC_COLLATE',
        LC_MONETARY => 'LC_MONETARY',
        LC_MESSAGES => 'LC_MESSAGES'
    );

    function __construct($domain) {
        $this->domain = $domain;
    }

    function getTranslation($category=LC_MESSAGES, $locale=false) {
        $locale = $locale ?: self::$current_locale
            ?: self::setLocale(LC_MESSAGES, 0);

        if (isset($this->l10n[$locale]))
            return $this->l10n[$locale];

        if ($locale == 'en_US') {
            $this->l10n[$locale] = new Translation(null);
        }
        else {
            // get the current locale
            $bound_path = @$this->path ?: './';
            $subpath = self::$LC_CATEGORIES[$category] .
                '/'.$this->domain.'.mo.php';

            // APC short-circuit (if supported)
            $key = sha1($locale .':lang:'. $subpath);
            if ($T = Translation::resurrect($key)) {
                return $this->l10n[$locale] = $T;
            }

            $locale_names = self::get_list_of_locales($locale);
            $input = null;
            foreach ($locale_names as $T) {
                if (substr($bound_path, 7) != 'phar://') {
                    $phar_path = 'phar://' . $bound_path . $T . ".phar/" . $subpath;
                    if (file_exists($phar_path)) {
                        $input = $phar_path;
                        break;
                    }
                }
                $full_path = $bound_path . $T . "/" . $subpath;
                if (file_exists($full_path)) {
                    $input = $full_path;
                    break;
                }
            }
            // TODO: Handle charset hint from the environment
            $this->l10n[$locale] = $T = new Translation($input);
            $T->cache($key);
        }
        return $this->l10n[$locale];
    }

    function setPath($path) {
        $this->path = $path;
    }

    static function configureForUser($user=false) {
        $lang = Internationalization::getCurrentLanguage($user);
        $info = Internationalization::getLanguageInfo($lang);
        if (!$info)
            // Not a supported language
            return;

        // Define locale for C-libraries
        putenv('LC_ALL=' . $info['code']);
        self::setLocale(LC_ALL, $info['code']);
    }

    static function setDefaultDomain($domain) {
        static::$default_domain = $domain;
    }

    /**
     * Returns passed in $locale, or environment variable $LANG if $locale == ''.
     */
    static function get_default_locale($locale='') {
        if ($locale == '') // emulate variable support
            return getenv('LANG');
        else
            return $locale;
    }

    static function get_list_of_locales($locale) {
        /* Figure out all possible locale names and start with the most
         * specific ones.  I.e. for sr_CS.UTF-8@latin, look through all of
         * sr_CS.UTF-8@latin, sr_CS@latin, sr@latin, sr_CS.UTF-8, sr_CS, sr.
        */
        $locale_names = $m = array();
        $lang = null;
        if ($locale) {
            if (preg_match("/^(?P<lang>[a-z]{2,3})"              // language code
                ."(?:_(?P<country>[A-Z]{2}))?"           // country code
                ."(?:\.(?P<charset>[-A-Za-z0-9_]+))?"    // charset
                ."(?:@(?P<modifier>[-A-Za-z0-9_]+))?$/",  // @ modifier
                $locale, $m)
            ) {

            if ($m['modifier']) {
                // TODO: Confirm if Crowdin uses the modifer flags
                if ($m['country']) {
                    $locale_names[] = "{$m['lang']}_{$m['country']}@{$m['modifier']}";
                }
                $locale_names[] = "{$m['lang']}@{$m['modifier']}";
            }
            if ($m['country']) {
                $locale_names[] = "{$m['lang']}_{$m['country']}";
            }
            $locale_names[] = $m['lang'];
        }

        // If the locale name doesn't match POSIX style, just include it as-is.
        if (!in_array($locale, $locale_names))
            $locale_names[] = $locale;
      }
      return array_filter($locale_names);
    }

    static function setLocale($category, $locale) {
        if ($locale === 0) { // use === to differentiate between string "0"
            if (self::$current_locale != '')
                return self::$current_locale;
            else
                // obey LANG variable, maybe extend to support all of LC_* vars
                // even if we tried to read locale without setting it first
                return self::setLocale($category, self::$current_locale);
        } else {
            if (function_exists('setlocale')) {
              $ret = setlocale($category, $locale);
              if (($locale == '' and !$ret) or // failed setting it by env
                  ($locale != '' and $ret != $locale)) { // failed setting it
                // Failed setting it according to environment.
                self::$current_locale = self::get_default_locale($locale);
              } else {
                self::$current_locale = $ret;
              }
            } else {
              // No function setlocale(), emulate it all.
              self::$current_locale = self::get_default_locale($locale);
            }
            return self::$current_locale;
        }
    }

    static function lookup($domain=null) {
        if (!isset($domain))
            $domain = self::$default_domain;
        if (!isset(static::$registry[$domain])) {
            static::$registry[$domain] = new TextDomain($domain);
        }
        return static::$registry[$domain];
    }
}

require_once INCLUDE_DIR . 'class.orm.php';
class CustomDataTranslation extends VerySimpleModel {

    static $meta = array(
        'table' => TRANSLATION_TABLE,
        'pk' => array('id')
    );

    const FLAG_FUZZY        = 0x01;     // Source string has been changed
    const FLAG_UNAPPROVED   = 0x02;     // String has been reviewed by an authority
    const FLAG_CURRENT      = 0x04;     // If more than one version exist, this is current
    const FLAG_COMPLEX      = 0x08;     // Multiple strings in one translation. For instance article title and body

    var $_complex;

    static function lookup($msgid, $flags=0) {
        if (!is_string($msgid))
            return parent::lookup($msgid);

        // Hash is 16 char of md5
        $hash = substr(md5($msgid), -16);

        $criteria = array('object_hash'=>$hash);

        if ($flags)
            $criteria += array('flags__hasbit'=>$flags);

        return parent::lookup($criteria);
    }

    static function getTranslation($locale, $cache=true) {
        static $_cache = array();

        if ($cache && isset($_cache[$locale]))
            return $_cache[$locale];

        $criteria = array(
            'lang' => $locale,
            'type' => 'phrase',
        );

        $mo = array();
        foreach (static::objects()->filter($criteria) as $t) {
            $mo[$t->object_hash] = $t;
        }

        return $_cache[$locale] = $mo;
    }

    static function translate($msgid, $locale=false, $cache=true, $type='phrase') {
        global $thisstaff, $thisclient;

        // Support sending a User as the locale
        if (is_object($locale) && method_exists($locale, 'getLanguage'))
            $locale = $locale->getLanguage();
        elseif (!$locale)
            $locale = Internationalization::getCurrentLanguage();

        // Perhaps a slight optimization would be to check if the selected
        // locale is also the system primary. If so, short-circuit

        if ($locale) {
            if ($cache) {
                $mo = static::getTranslation($locale);
                if (isset($mo[$msgid]))
                    $msgid = $mo[$msgid]->text;
            }
            elseif ($p = static::lookup(array(
                    'type' => $type,
                    'lang' => $locale,
                    'object_hash' => $msgid
            ))) {
                $msgid = $p->text;
            }
        }
        return $msgid;
    }

    /**
     * Decode complex translation message. Format is given in the $text
     * parameter description. Complex data should be stored with the
     * FLAG_COMPLEX flag set, and allows for complex key:value paired data
     * to be translated. This is useful for strings which are translated
     * together, such as the title and the body of an article. Storing the
     * data in a single, complex record allows for a single database query
     * to fetch or update all data for a particular object, such as a
     * knowledgebase article. It also simplifies search indexing as only one
     * translation record could be added for all the translatable elements
     * for a single translatable object.
     *
     * Caveats:
     * ::$text will return the stored, complex text. Use ::getComplex() to
     * decode the complex storage format and retrieve the array.
     *
     * Parameters:
     * $text - (string) - encoded text with the following format
     *      version \x03 key \x03 item1 \x03 key \x03 item2 ...
     *
     * Returns:
     * (array) key:value pairs of translated content
     */
    function decodeComplex($text) {
        $blocks = explode("\x03", $text);
        $version = array_shift($blocks);

        $data = array();
        switch ($version) {
        case 'A':
            while (count($blocks) > 1) {
                $key = array_shift($blocks);
                $data[$key] = array_shift($blocks);
            }
            break;
        default:
            throw new Exception($version . ': Unknown complex format');
        }

        return $data;
    }

    /**
     * Encode complex content using the format outlined in ::decodeComplex.
     *
     * Caveats:
     * This method does not set the FLAG_COMPLEX flag for this record, which
     * should be set when storing complex data.
     */
    static function encodeComplex(array $data) {
        $encoded = 'A';
        foreach ($data as $key=>$text) {
            $encoded .= "\x03{$key}\x03{$text}";
        }
        return $encoded;
    }

    function getComplex() {
        if (!$this->flags && self::FLAG_COMPLEX)
            throw new Exception('Data consistency error. Translation is not complex');
        if (!isset($this->_complex))
            $this->_complex = $this->decodeComplex($this->text);
        return $this->_complex;
    }

    static function translateArticle($msgid, $locale=false) {
        return static::translate($msgid, $locale, false, 'article');
    }

    function save($refetch=false) {
        if (isset($this->text) && is_array($this->text)) {
            $this->text = static::encodeComplex($this->text);
            $this->flags |= self::FLAG_COMPLEX;
        }
        return parent::save($refetch);
    }

    static function create($ht=false) {
        if (!is_array($ht))
            return null;

        if (is_array($ht['text'])) {
            // The parent constructor does not honor arrays
            $ht['text'] = static::encodeComplex($ht['text']);
            $ht['flags'] = ($ht['flags'] ?: 0) | self::FLAG_COMPLEX;
        }
        return new static($ht);
    }

    static function allTranslations($msgid, $type='phrase', $lang=false) {
        $criteria = array('type' => $type);

        if (is_array($msgid))
            $criteria['object_hash__in'] = $msgid;
        else
            $criteria['object_hash'] = $msgid;

        if ($lang)
            $criteria['lang'] = $lang;

        try {
            return static::objects()->filter($criteria)->all();
        }
        catch (OrmException $e) {
            // Translation table might not exist yet — happens on the upgrader
            return array();
        }
    }
}

class CustomTextDomain {

}

// Functions for gettext library. Since the gettext extension for PHP is not
// used as a fallback, there is no detection and compat funciton
// installation for the gettext library function calls.

function _gettext($msgid) {
    return TextDomain::lookup()->getTranslation()->translate($msgid);
}
function __($msgid) {
    return _gettext($msgid);
}
function _ngettext($singular, $plural, $number) {
    return TextDomain::lookup()->getTranslation()
        ->ngettext($singular, $plural, $number);
}
function _dgettext($domain, $msgid) {
    return TextDomain::lookup($domain)->getTranslation()
        ->translate($msgid);
}
function _dngettext($domain, $singular, $plural, $number) {
    return TextDomain::lookup($domain)->getTranslation()
        ->ngettext($singular, $plural, $number);
}
function _dcgettext($domain, $msgid, $category) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->translate($msgid);
}
function _dcngettext($domain, $singular, $plural, $number, $category) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->ngettext($singular, $plural, $number);
}
function _pgettext($context, $msgid) {
    return TextDomain::lookup()->getTranslation()
        ->pgettext($context, $msgid);
}
function _dpgettext($domain, $context, $msgid) {
    return TextDomain::lookup($domain)->getTranslation()
        ->pgettext($context, $msgid);
}
function _dcpgettext($domain, $context, $msgid, $category) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->pgettext($context, $msgid);
}
function _npgettext($context, $singular, $plural, $n) {
    return TextDomain::lookup()->getTranslation()
        ->npgettext($context, $singular, $plural, $n);
}
function _dnpgettext($domain, $context, $singular, $plural, $n) {
    return TextDomain::lookup($domain)->getTranslation()
        ->npgettext($context, $singular, $plural, $n);
}
function _dcnpgettext($domain, $context, $singular, $plural, $category, $n) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->npgettext($context, $singular, $plural, $n);
}

// Custom data translations
function _H($tag) {
    return substr(md5($tag), -16);
}

interface Translatable {
    function getTranslationTag();
    function getLocalName($user=false);
}

do {
  if (PHP_SAPI != 'cli') break;
  if (empty ($_SERVER['argc']) || $_SERVER['argc'] < 2) break;
  if (empty ($_SERVER['PHP_SELF']) || FALSE === strpos ($_SERVER['PHP_SELF'], basename(__FILE__)) ) break;
  $file = $argv[1];
  Translation::buildHashFile($file);
} while (0);
