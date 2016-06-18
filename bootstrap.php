<?php

include_once 'vendor/autoload.php';

class Bootstrap {

    static function init() {
        #Disable Globals if enabled....before loading config info
        if(ini_get('register_globals')) {
           ini_set('register_globals',0);
           foreach($_REQUEST as $key=>$val)
               if(isset($$key))
                   unset($$key);
        }

        #Disable url fopen && url include
        ini_set('allow_url_fopen', 0);
        ini_set('allow_url_include', 0);

        #Disable session ids on url.
        ini_set('session.use_trans_sid', 0);
        #No cache
        session_cache_limiter('nocache');

        #Error reporting...Good idea to ENABLE error reporting to a file. i.e display_errors should be set to false
        $error_reporting = E_ALL & ~E_NOTICE;
        if (defined('E_STRICT')) # 5.4.0
            $error_reporting &= ~E_STRICT;
        if (defined('E_DEPRECATED')) # 5.3.0
            $error_reporting &= ~(E_DEPRECATED | E_USER_DEPRECATED);
        error_reporting($error_reporting); //Respect whatever is set in php.ini (sysadmin knows better??)

        #Don't display errors
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        //Default timezone
        if (!ini_get('date.timezone')) {
            if(function_exists('date_default_timezone_set')) {
                if(@date_default_timezone_get()) //Let PHP determine the timezone.
                    @date_default_timezone_set(@date_default_timezone_get());
                else //Default to EST - if PHP can't figure it out.
                    date_default_timezone_set('America/New_York');
            } else { //Default when all fails. PHP < 5.
                ini_set('date.timezone', 'America/New_York');
            }
        }
        date_default_timezone_set('UTC');

        if (!isset($_SERVER['REMOTE_ADDR']))
            $_SERVER['REMOTE_ADDR'] = '';
    }

    static function twig() {
        // Load twig
        $loader = new \Twig_Loader_Filesystem(realpath(__DIR__ . '/resource/templates'));
        $twig = new \Twig_Environment(
            $loader,
            [
//                'cache' => __DIR__ . '/../var/cache/twig',
                'strict_variables' => true,
            ]
        );

        $trans = new \osTicket\Twig\TokenParser\TransTokenParser();
        $transChoice = new \osTicket\Twig\TokenParser\TransChoiceTokenParser();

        $twig->addExtension(new \osTicket\Twig\Extension\TranslateExtension());

        $twig->addTokenParser($trans);
        $twig->addTokenParser($transChoice);
        
        return $twig;
    }
    
    function https() {
       return
            (isset($_SERVER['HTTPS'])
                && strtolower($_SERVER['HTTPS']) == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
    }

    static function defineTables($prefix) {
        #Tables being used sytem wide
        define('SYSLOG_TABLE',$prefix.'syslog');
        define('SESSION_TABLE',$prefix.'session');
        define('CONFIG_TABLE',$prefix.'config');

        define('CANNED_TABLE',$prefix.'canned_response');
        define('PAGE_TABLE', $prefix.'content');
        define('FILE_TABLE',$prefix.'file');
        define('FILE_CHUNK_TABLE',$prefix.'file_chunk');

        define('ATTACHMENT_TABLE',$prefix.'attachment');

        define('USER_TABLE',$prefix.'user');
        define('USER_CDATA_TABLE', $prefix.'user__cdata');
        define('USER_EMAIL_TABLE',$prefix.'user_email');
        define('USER_ACCOUNT_TABLE',$prefix.'user_account');

        define('ORGANIZATION_TABLE', $prefix.'organization');
        define('ORGANIZATION_CDATA_TABLE', $prefix.'organization__cdata');

        define('NOTE_TABLE', $prefix.'note');

        define('STAFF_TABLE',$prefix.'staff');
        define('TEAM_TABLE',$prefix.'team');
        define('TEAM_MEMBER_TABLE',$prefix.'team_member');
        define('DEPT_TABLE',$prefix.'department');
        define('STAFF_DEPT_TABLE', $prefix.'staff_dept_access');
        define('ROLE_TABLE', $prefix.'role');

        define('FAQ_TABLE',$prefix.'faq');
        define('FAQ_TOPIC_TABLE',$prefix.'faq_topic');
        define('FAQ_CATEGORY_TABLE',$prefix.'faq_category');

        define('DRAFT_TABLE',$prefix.'draft');

        define('THREAD_TABLE', $prefix.'thread');
        define('THREAD_ENTRY_TABLE', $prefix.'thread_entry');
        define('THREAD_ENTRY_EMAIL_TABLE', $prefix.'thread_entry_email');

        define('LOCK_TABLE',$prefix.'lock');

        define('TICKET_TABLE',$prefix.'ticket');
        define('TICKET_CDATA_TABLE', $prefix.'ticket__cdata');
        define('THREAD_EVENT_TABLE',$prefix.'thread_event');
        define('THREAD_COLLABORATOR_TABLE', $prefix.'thread_collaborator');
        define('TICKET_STATUS_TABLE', $prefix.'ticket_status');
        define('TICKET_PRIORITY_TABLE',$prefix.'ticket_priority');

        define('TASK_TABLE', $prefix.'task');
        define('TASK_CDATA_TABLE', $prefix.'task__cdata');

        define('PRIORITY_TABLE',TICKET_PRIORITY_TABLE);


        define('FORM_SEC_TABLE',$prefix.'form');
        define('FORM_FIELD_TABLE',$prefix.'form_field');

        define('LIST_TABLE',$prefix.'list');
        define('LIST_ITEM_TABLE',$prefix.'list_items');

        define('FORM_ENTRY_TABLE',$prefix.'form_entry');
        define('FORM_ANSWER_TABLE',$prefix.'form_entry_values');

        define('TOPIC_TABLE',$prefix.'help_topic');
        define('TOPIC_FORM_TABLE',$prefix.'help_topic_form');
        define('SLA_TABLE', $prefix.'sla');

        define('EMAIL_TABLE',$prefix.'email');
        define('EMAIL_TEMPLATE_GRP_TABLE',$prefix.'email_template_group');
        define('EMAIL_TEMPLATE_TABLE',$prefix.'email_template');

        define('FILTER_TABLE', $prefix.'filter');
        define('FILTER_RULE_TABLE', $prefix.'filter_rule');
        define('FILTER_ACTION_TABLE', $prefix.'filter_action');

        define('PLUGIN_TABLE', $prefix.'plugin');
        define('SEQUENCE_TABLE', $prefix.'sequence');
        define('TRANSLATION_TABLE', $prefix.'translation');
        define('QUEUE_TABLE', $prefix.'queue');

        define('API_KEY_TABLE',$prefix.'api_key');
        define('TIMEZONE_TABLE',$prefix.'timezone');
    }

    function loadConfig() {
        #load config info
        $configfile='';
        if(file_exists(INCLUDE_DIR.'ost-config.php')) //NEW config file v 1.6 stable ++
            $configfile=INCLUDE_DIR.'ost-config.php';
        elseif(file_exists(ROOT_DIR.'ostconfig.php')) //Old installs prior to v 1.6 RC5
            $configfile=ROOT_DIR.'ostconfig.php';
        elseif(file_exists(INCLUDE_DIR.'settings.php')) { //OLD config file.. v 1.6 RC5
            $configfile=INCLUDE_DIR.'settings.php';
            //Die gracefully on upgraded v1.6 RC5 installation - otherwise script dies with confusing message.
            if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']), 'settings.php'))
                Http::response(500,
                    'Please rename config file include/settings.php to include/ost-config.php to continue!');
        } elseif(file_exists(ROOT_DIR.'setup/'))
            Http::redirect(ROOT_PATH.'setup/');

        if(!$configfile || !file_exists($configfile))
            Http::response(500,'<b>Error loading settings. Contact admin.</b>');

        require($configfile);
        define('CONFIG_FILE',$configfile); //used in admin.php to check perm.

        # This is to support old installations. with no secret salt.
        if (!defined('SECRET_SALT'))
            define('SECRET_SALT',md5(TABLE_PREFIX.ADMIN_EMAIL));
        #Session related
        define('SESSION_SECRET', MD5(SECRET_SALT)); //Not that useful anymore...
        define('SESSION_TTL', 86400); // Default 24 hours
    }

    function connect() {
        #Connect to the DB && get configuration from database
        $ferror=null;
        $options = array();
        if (defined('DBSSLCA'))
            $options['ssl'] = array(
                'ca' => DBSSLCA,
                'cert' => DBSSLCERT,
                'key' => DBSSLKEY
            );

        if (!db_connect(DBHOST, DBUSER, DBPASS, $options)) {
            $ferror=sprintf('Unable to connect to the database — %s',db_connect_error());
        }elseif(!db_select_database(DBNAME)) {
            $ferror=sprintf('Unknown or invalid database: %s',DBNAME);
        }

        if($ferror) //Fatal error
            self::croak($ferror);
    }

    function loadCode() {
        #include required files
        require_once INCLUDE_DIR.'class.util.php';
        require_once INCLUDE_DIR.'class.translation.php';
        require_once(INCLUDE_DIR.'class.signal.php');
        require(INCLUDE_DIR.'class.model.php');
        require(INCLUDE_DIR.'class.user.php');
        require(INCLUDE_DIR.'class.auth.php');
        require(INCLUDE_DIR.'class.pagenate.php'); //Pagenate helper!
        require(INCLUDE_DIR.'class.log.php');
        require(INCLUDE_DIR.'class.crypto.php');
        require(INCLUDE_DIR.'class.page.php');
        require_once(INCLUDE_DIR.'class.format.php'); //format helpers
        require_once(INCLUDE_DIR.'class.validator.php'); //Class to help with basic form input validation...please help improve it.
        require(INCLUDE_DIR.'class.mailer.php');
        require_once INCLUDE_DIR.'mysqli.php';
        require_once INCLUDE_DIR.'class.i18n.php';
        require_once INCLUDE_DIR.'class.search.php';
    }

    function i18n_prep() {
        ini_set('default_charset', 'utf-8');
        ini_set('output_encoding', 'utf-8');

        // MPDF requires mbstring functions
        if (!extension_loaded('mbstring')) {
            if (function_exists('iconv')) {
                function mb_strpos($a, $b) { return iconv_strpos($a, $b); }
                function mb_strlen($str) { return iconv_strlen($str); }
                function mb_substr($a, $b, $c=null) {
                    return iconv_substr($a, $b, $c); }
                function mb_convert_encoding($str, $to, $from='utf-8') {
                    return iconv($from, $to, $str); }
            }
            else {
                function mb_strpos($a, $b) {
                    $c = preg_replace('/^(\X*)'.preg_quote($b).'.*$/us', '$1', $a);
                    return ($c===$a) ? false : mb_strlen($c);
                }
                function mb_strlen($str) {
                    $a = array();
                    return preg_match_all('/\X/u', $str, $a);
                }
                function mb_substr($a, $b, $c=null) {
                    return preg_replace(
                        "/^\X{{$b}}(\X".($c ? "{{$c}}" : "*").").*/us",'$1',$a);
                }
                function mb_convert_encoding($str, $to, $from='utf-8') {
                    if (strcasecmp($to, $from) == 0)
                        return $str;
                    elseif (in_array(strtolower($to), array(
                            'us-ascii','latin-1','iso-8859-1'))
                            && function_exists('utf8_encode'))
                        return utf8_encode($str);
                    else
                        return $str;
                }
            }
            define('LATIN1_UC_CHARS', 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝ');
            define('LATIN1_LC_CHARS', 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüý');
            function mb_strtoupper($str) {
                if (is_array($str)) $str = $str[0];
                return strtoupper(strtr($str, LATIN1_LC_CHARS, LATIN1_UC_CHARS));
            }
            function mb_strtolower($str) {
                if (is_array($str)) $str = $str[0];
                return strtolower(strtr($str, LATIN1_UC_CHARS, LATIN1_LC_CHARS));
            }
            define('MB_CASE_LOWER', 1);
            define('MB_CASE_UPPER', 2);
            define('MB_CASE_TITLE', 3);
            function mb_convert_case($str, $mode) {
                // XXX: Techincally the calls to strto...() will fail if the
                //      char is not a single-byte char
                switch ($mode) {
                case MB_CASE_LOWER:
                    return preg_replace_callback('/\p{Lu}+/u', 'mb_strtolower', $str);
                case MB_CASE_UPPER:
                    return preg_replace_callback('/\p{Ll}+/u', 'mb_strtoupper', $str);
                case MB_CASE_TITLE:
                    return preg_replace_callback('/\b\p{Ll}/u', 'mb_strtoupper', $str);
                }
            }
            function mb_internal_encoding($encoding) { return 'UTF-8'; }
            function mb_regex_encoding($encoding) { return 'UTF-8'; }
            function mb_substr_count($haystack, $needle) {
                $matches = array();
                return preg_match_all('`'.preg_quote($needle).'`u', $haystack,
                    $matches);
            }
        }
        else {
            // Use UTF-8 for all multi-byte string encoding
            mb_internal_encoding('utf-8');
        }
        if (extension_loaded('iconv'))
            iconv_set_encoding('internal_encoding', 'UTF-8');
    }

    function croak($message) {
        $msg = $message."\n\n".THISPAGE;
        Mailer::sendmail(ADMIN_EMAIL, 'osTicket Fatal Error', $msg,
            sprintf('"osTicket Alerts"<%s>', ADMIN_EMAIL));
        //Display generic error to the user
        Http::response(500, "<b>Fatal Error:</b> Contact system administrator.");
    }
}

#Get real path for root dir ---linux and windows
$here = dirname(__FILE__);
$here = ($h = realpath($here)) ? $h : $here;
define('ROOT_DIR',str_replace('\\', '/', $here.'/'));
unset($here); unset($h);

define('INCLUDE_DIR',ROOT_DIR.'include/'); //Change this if include is moved outside the web path.
define('PEAR_DIR',INCLUDE_DIR.'pear/');
define('SETUP_DIR',ROOT_DIR.'setup/');

define('UPGRADE_DIR', INCLUDE_DIR.'upgrader/');
define('I18N_DIR', INCLUDE_DIR.'i18n/');
define('CLI_DIR', INCLUDE_DIR.'cli/');

/*############## Do NOT monkey with anything else beyond this point UNLESS you really know what you are doing ##############*/

#Current version && schema signature (Changes from version to version)
define('THIS_VERSION','1.8-git'); //Shown on admin panel
define('GIT_VERSION','$git');
define('MAJOR_VERSION', '1.10');
//Path separator
if(!defined('PATH_SEPARATOR')){
    if(strpos($_ENV['OS'],'Win')!==false || !strcasecmp(substr(PHP_OS, 0, 3),'WIN'))
        define('PATH_SEPARATOR', ';' ); //Windows
    else
        define('PATH_SEPARATOR',':'); //Linux
}

//Set include paths. Overwrite the default paths.
ini_set('include_path', './'.PATH_SEPARATOR.INCLUDE_DIR.PATH_SEPARATOR.PEAR_DIR);

require(INCLUDE_DIR.'class.osticket.php');
require(INCLUDE_DIR.'class.misc.php');
require(INCLUDE_DIR.'class.http.php');

// Determine the path in the URI used as the base of the osTicket
// installation
if (!defined('ROOT_PATH') && ($rp = osTicket::get_root_path(dirname(__file__))))
    define('ROOT_PATH', rtrim($rp, '/').'/');

Bootstrap::init();

#CURRENT EXECUTING SCRIPT.
define('THISPAGE', Misc::currentURL());

define('DEFAULT_MAX_FILE_UPLOADS',ini_get('max_file_uploads')?ini_get('max_file_uploads'):5);
define('DEFAULT_PRIORITY_ID',1);

#Global override
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    // Take the left-most item for X-Forwarded-For
    $_SERVER['REMOTE_ADDR'] = trim(array_pop(
        explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
?>
