<?php
/*********************************************************************
    main.inc.php

    Master include file which must be included at the start of every file.
    The brain of the whole sytem. Don't monkey with it.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

    #Disable direct access.
    if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__))) die('kwaheri rafiki!');

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

    #Set Dir constants
    define('ROOT_DIR',str_replace('\\\\', '/', realpath(dirname(__FILE__))).'/'); #Get real path for root dir ---linux and windows
    define('INCLUDE_DIR',ROOT_DIR.'include/'); //Change this if include is moved outside the web path.
    define('PEAR_DIR',INCLUDE_DIR.'pear/');
    define('SETUP_DIR',INCLUDE_DIR.'setup/');

    define('UPGRADE_DIR', INCLUDE_DIR.'upgrader/');
    define('I18N_DIR', INCLUDE_DIR.'i18n/');

    require(INCLUDE_DIR.'class.misc.php');

    // Determine the path in the URI used as the base of the osTicket
    // installation
    if (!defined('ROOT_PATH'))
        define('ROOT_PATH', Misc::siteRootPath(realpath(dirname(__file__))).'/'); //root path. Damn directories

    /*############## Do NOT monkey with anything else beyond this point UNLESS you really know what you are doing ##############*/

    #Current version && schema signature (Changes from version to version)
    define('THIS_VERSION','1.7.0+'); //Shown on admin panel
    #load config info
    $configfile='';
    if(file_exists(ROOT_DIR.'ostconfig.php')) //Old installs prior to v 1.6 RC5
        $configfile=ROOT_DIR.'ostconfig.php';
    elseif(file_exists(INCLUDE_DIR.'settings.php')) { //OLD config file.. v 1.6 RC5
        $configfile=INCLUDE_DIR.'settings.php';
        //Die gracefully on upgraded v1.6 RC5 installation - otherwise script dies with confusing message.
        if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']), 'settings.php'))
            die('Please rename config file include/settings.php to include/ost-config.php to continue!');
    } elseif(file_exists(INCLUDE_DIR.'ost-config.php')) //NEW config file v 1.6 stable ++
        $configfile=INCLUDE_DIR.'ost-config.php';
    elseif(file_exists(ROOT_DIR.'setup/'))
        header('Location: '.ROOT_PATH.'setup/');

    if(!$configfile || !file_exists($configfile)) die('<b>Error loading settings. Contact admin.</b>');

    require($configfile);
    define('CONFIG_FILE',$configfile); //used in admin.php to check perm.

   //Path separator
    if(!defined('PATH_SEPARATOR')){
        if(strpos($_ENV['OS'],'Win')!==false || !strcasecmp(substr(PHP_OS, 0, 3),'WIN'))
            define('PATH_SEPARATOR', ';' ); //Windows
        else
            define('PATH_SEPARATOR',':'); //Linux
    }

    //Set include paths. Overwrite the default paths.
    ini_set('include_path', './'.PATH_SEPARATOR.INCLUDE_DIR.PATH_SEPARATOR.PEAR_DIR);


    #include required files
    require(INCLUDE_DIR.'class.osticket.php');
    require(INCLUDE_DIR.'class.ostsession.php');
    require(INCLUDE_DIR.'class.usersession.php');
    require(INCLUDE_DIR.'class.pagenate.php'); //Pagenate helper!
    require(INCLUDE_DIR.'class.log.php');
    require(INCLUDE_DIR.'class.crypto.php');
    require(INCLUDE_DIR.'class.timezone.php');
    require(INCLUDE_DIR.'class.http.php');
    require(INCLUDE_DIR.'class.signal.php');
    require(INCLUDE_DIR.'class.nav.php');
    require(INCLUDE_DIR.'class.page.php');
    require(INCLUDE_DIR.'class.format.php'); //format helpers
    require(INCLUDE_DIR.'class.validator.php'); //Class to help with basic form input validation...please help improve it.
    require(INCLUDE_DIR.'class.mailer.php');
    if (extension_loaded('mysqli'))
        require_once INCLUDE_DIR.'mysqli.php';
    else
        require(INCLUDE_DIR.'mysql.php');

    #CURRENT EXECUTING SCRIPT.
    define('THISPAGE', Misc::currentURL());
    define('THISURI', $_SERVER['REQUEST_URI']);

    # This is to support old installations. with no secret salt.
    if(!defined('SECRET_SALT')) define('SECRET_SALT',md5(TABLE_PREFIX.ADMIN_EMAIL));

    #Session related
    define('SESSION_SECRET', MD5(SECRET_SALT)); //Not that useful anymore...
    define('SESSION_TTL', 86400); // Default 24 hours

    define('DEFAULT_MAX_FILE_UPLOADS',ini_get('max_file_uploads')?ini_get('max_file_uploads'):5);
    define('DEFAULT_PRIORITY_ID',1);

    define('EXT_TICKET_ID_LEN',6); //Ticket create. when you start getting collisions. Applies only on random ticket ids.

    #Tables being used sytem wide
    define('CONFIG_TABLE',TABLE_PREFIX.'config');
    define('SYSLOG_TABLE',TABLE_PREFIX.'syslog');
    define('SESSION_TABLE',TABLE_PREFIX.'session');
    define('FILE_TABLE',TABLE_PREFIX.'file');
    define('FILE_CHUNK_TABLE',TABLE_PREFIX.'file_chunk');

    define('STAFF_TABLE',TABLE_PREFIX.'staff');
    define('DEPT_TABLE',TABLE_PREFIX.'department');
    define('TOPIC_TABLE',TABLE_PREFIX.'help_topic');
    define('GROUP_TABLE',TABLE_PREFIX.'groups');
    define('GROUP_DEPT_TABLE', TABLE_PREFIX.'group_dept_access');
    define('TEAM_TABLE',TABLE_PREFIX.'team');
    define('TEAM_MEMBER_TABLE',TABLE_PREFIX.'team_member');

    define('PAGE_TABLE', TABLE_PREFIX.'page');

    define('FAQ_TABLE',TABLE_PREFIX.'faq');
    define('FAQ_ATTACHMENT_TABLE',TABLE_PREFIX.'faq_attachment');
    define('FAQ_TOPIC_TABLE',TABLE_PREFIX.'faq_topic');
    define('FAQ_CATEGORY_TABLE',TABLE_PREFIX.'faq_category');
    define('CANNED_TABLE',TABLE_PREFIX.'canned_response');
    define('CANNED_ATTACHMENT_TABLE',TABLE_PREFIX.'canned_attachment');

    define('TICKET_TABLE',TABLE_PREFIX.'ticket');
    define('TICKET_THREAD_TABLE',TABLE_PREFIX.'ticket_thread');
    define('TICKET_ATTACHMENT_TABLE',TABLE_PREFIX.'ticket_attachment');
    define('TICKET_PRIORITY_TABLE',TABLE_PREFIX.'ticket_priority');
    define('PRIORITY_TABLE',TICKET_PRIORITY_TABLE);
    define('TICKET_LOCK_TABLE',TABLE_PREFIX.'ticket_lock');
    define('TICKET_EVENT_TABLE',TABLE_PREFIX.'ticket_event');
    define('TICKET_EMAIL_INFO_TABLE',TABLE_PREFIX.'ticket_email_info');

    define('EMAIL_TABLE',TABLE_PREFIX.'email');
    define('EMAIL_TEMPLATE_GRP_TABLE',TABLE_PREFIX.'email_template_group');
    define('EMAIL_TEMPLATE_TABLE',TABLE_PREFIX.'email_template');

    define('FILTER_TABLE',TABLE_PREFIX.'filter');
    define('FILTER_RULE_TABLE',TABLE_PREFIX.'filter_rule');

    define('BANLIST_TABLE',TABLE_PREFIX.'email_banlist'); //Not in use anymore....as of v 1.7

    define('SLA_TABLE',TABLE_PREFIX.'sla');

    define('API_KEY_TABLE',TABLE_PREFIX.'api_key');
    define('TIMEZONE_TABLE',TABLE_PREFIX.'timezone');

    #Global override
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        // Take the left-most item for X-Forwarded-For
        $_SERVER['REMOTE_ADDR'] = array_pop(
            explode(',', trim($_SERVER['HTTP_X_FORWARDED_FOR'])));

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
        $ferror='Unable to connect to the database -'.db_connect_error();
    }elseif(!db_select_database(DBNAME)) {
        $ferror='Unknown or invalid database '.DBNAME;
    } elseif(!($ost=osTicket::start()) || !($cfg = $ost->getConfig())) {
        $ferror='Unable to load config info from DB. Get tech support.';
    }

    if($ferror) { //Fatal error
        //try alerting admin using email in config file
        $msg=$ferror."\n\n".THISPAGE;
        Mailer::sendmail(ADMIN_EMAIL, 'osTicket Fatal Error', $msg, sprintf('"osTicket Alerts"<%s>', ADMIN_EMAIL));
        //Display generic error to the user
        die("<b>Fatal Error:</b> Contact system administrator.");
        exit;
    }

    //Init
    $session = $ost->getSession();

    //System defaults we might want to make global//
    #pagenation default - user can override it!
    define('DEFAULT_PAGE_LIMIT', $cfg->getPageSize()?$cfg->getPageSize():25);

    #Cleanup magic quotes crap.
    if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        $_POST=Format::strip_slashes($_POST);
        $_GET=Format::strip_slashes($_GET);
        $_REQUEST=Format::strip_slashes($_REQUEST);
    }
?>
