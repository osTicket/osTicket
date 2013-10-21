<?php

/*
 * This file provides the function "t" and "st" which shall be used like "printf" and "sprintf"
 * for all text output on this site.
 * It uses the lang.php files in the i18n/xx_XX/ directory which contain
 * a key_value array. If the key is not translated, it is used as it is.
 */

//check for the "lang" url-attribute and change language if set.
if (isset($_GET["lang"]))
{
    localizer::getInstance()->setLanguage($_GET["lang"]);
}

/**
 * Use this function like printf to provide translated content in templates.
 * If you need the result as string, use "st" instead.
 * @param string $format 
 * @param type $args
 * @param type $_
 */
function t($format, $args = null, $_ = null)
{
    echo st($format, $args, $_);
}

/**
 * Use this function like sprintf to provide translated content in templates.
 * If you directly want to echo the string, use "t" instead.
 * @param type $format
 * @param type $args
 * @param type $_
 * @return type
 */
function st($format, $args = null, $_ = null)
{
    return localizer::getInstance()->translate($format, $args, $_);
}

class localizer
{
    /**
     *
     * @var localizer
     */
    static $instance;
    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new localizer();
        }
        return self::$instance;
    }
    
    public function __construct()
    {
        $lang = "en_US";
        if (isset($_SESSION["lang"]))
        {
            $lang = $_SESSION["lang"];
        }
        $this->setLanguage($lang);
    }
    
    private $translations = array();
    
    public function setLanguage($lang)
    {
        $file = INCLUDE_DIR."i18n/".$lang."/lang.php";
        if (is_file($file))
        {
            include $file;
        }
        else
        {
            $this->translations = array();
        }
        $_SESSION["lang"] = $lang;
    }
    
    public function getLanguages()
    {
        $result = array();
        $dir = new DirectoryIterator(INCLUDE_DIR.'i18n');
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) 
            {
                $lang = $fileinfo->getFilename();
                if (is_file(INCLUDE_DIR."i18n/".$lang."/lang.php"))
                {
                    $result []= $lang;
                }
            }
        }
        return $result;
    }
    
    public function translate($format, $args = null, $_ = null)
    {
        if (isset($this->translations[$format]))
        {
            $format = $this->translations[$format];
        }
        return sprintf($format, $args, $_);
    }
}
?>
