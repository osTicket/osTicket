<?php
/*********************************************************************
    class.installer.php

    osTicket Intaller - installs the latest version.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR.'class.migrater.php';
require_once INCLUDE_DIR.'class.setup.php';
require_once INCLUDE_DIR.'class.i18n.php';

class Installer extends SetupWizard {

    var $config;

    function Installer($configfile) {
        $this->config =$configfile;
        $this->errors=array();
    }

    function getConfigFile() {
        return $this->config;
    }

    function config_exists() {
        return ($this->getConfigFile() && file_exists($this->getConfigFile()));
    }

    function config_writable() {
        return ($this->getConfigFile() && is_writable($this->getConfigFile()));
    }

    function check_config() {
        return ($this->config_exists() && $this->config_writable());
    }

    //XXX: Latest version insall logic...no carry over.
    function install($vars) {

        $this->errors=$f=array();

        $f['name']          = array('type'=>'string',   'required'=>1, 'error'=>__('Name required'));
        $f['email']         = array('type'=>'email',    'required'=>1, 'error'=>__('Valid email required'));
        $f['fname']         = array('type'=>'string',   'required'=>1, 'error'=>__('First name required'));
        $f['lname']         = array('type'=>'string',   'required'=>1, 'error'=>__('Last name required'));
        $f['admin_email']   = array('type'=>'email',    'required'=>1, 'error'=>__('Valid email required'));
        $f['username']      = array('type'=>'username', 'required'=>1, 'error'=>__('Username required'));
        $f['passwd']        = array('type'=>'password', 'required'=>1, 'error'=>__('Password required'));
        $f['passwd2']       = array('type'=>'password', 'required'=>1, 'error'=>__('Confirm Password'));
        $f['prefix']        = array('type'=>'string',   'required'=>1, 'error'=>__('Table prefix required'));
        $f['dbhost']        = array('type'=>'string',   'required'=>1, 'error'=>__('Host name required'));
        $f['dbname']        = array('type'=>'string',   'required'=>1, 'error'=>__('Database name required'));
        $f['dbuser']        = array('type'=>'string',   'required'=>1, 'error'=>__('Username required'));
        $f['dbpass']        = array('type'=>'string',   'required'=>1, 'error'=>__('Password required'));

        $vars = array_map('trim', $vars);

        if(!Validator::process($f,$vars,$this->errors) && !$this->errors['err'])
            $this->errors['err']=__('Missing or invalid data - correct the errors and try again.');


        //Staff's email can't be same as system emails.
        if($vars['admin_email'] && $vars['email'] && !strcasecmp($vars['admin_email'],$vars['email']))
            $this->errors['admin_email']=__('Conflicts with system email above');
        //Admin's pass confirmation.
        if(!$this->errors && strcasecmp($vars['passwd'],$vars['passwd2']))
            $this->errors['passwd2']=__('Password(s) do not match');
        //Check table prefix underscore required at the end!
        if($vars['prefix'] && substr($vars['prefix'], -1)!='_')
            $this->errors['prefix']=__('Bad prefix. Must have underscore (_) at the end. e.g \'ost_\'');

        //Make sure admin username is not very predictable. XXX: feels dirty but necessary
        if(!$this->errors['username'] && in_array(strtolower($vars['username']),array('admin','admins','username','osticket')))
            $this->errors['username']=__('Bad username');

        // Support port number specified in the hostname with a colon (:)
        list($host, $port) = explode(':', $vars['dbhost']);
        if ($port && is_numeric($port) && ($port < 1 || $port > 65535))
            $this->errors['db'] = __('Invalid database port number');

        //MYSQL: Connect to the DB and check the version & database (create database if it doesn't exist!)
        if(!$this->errors) {
            if(!db_connect($vars['dbhost'],$vars['dbuser'],$vars['dbpass']))
                $this->errors['db']=sprintf(__('Unable to connect to MySQL server: %s'), db_connect_error());
            elseif(explode('.', db_version()) < explode('.', $this->getMySQLVersion()))
                $this->errors['db']=sprintf(__('osTicket requires MySQL %s or later!'),$this->getMySQLVersion());
            elseif(!db_select_database($vars['dbname']) && !db_create_database($vars['dbname'])) {
                $this->errors['dbname']=__("Database doesn't exist");
                $this->errors['db']=__('Unable to create the database.');
            } elseif(!db_select_database($vars['dbname'])) {
                $this->errors['dbname']=__('Unable to select the database');
            } else {
                //Abort if we have another installation (or table) with same prefix.
                $sql = 'SELECT * FROM `'.$vars['prefix'].'config` LIMIT 1';
                if(db_query($sql, false)) {
                    $this->errors['err'] = __('We have a problem - another installation with same table prefix exists!');
                    $this->errors['prefix'] = __('Prefix already in-use');
                } else {
                    //Try changing charset and collation of the DB - no bigie if we fail.
                    db_query('ALTER DATABASE '.$vars['dbname'].' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', false);
                }
            }
        }

        //bailout on errors.
        if($this->errors) return false;

        /*************** We're ready to install ************************/
        define('ADMIN_EMAIL',$vars['admin_email']); //Needed to report SQL errors during install.
        define('TABLE_PREFIX',$vars['prefix']); //Table prefix
        Bootstrap::defineTables(TABLE_PREFIX);
        Bootstrap::loadCode();

        $debug = true; // Change it to false to squelch SQL errors.

        //Last minute checks.
        if(!file_exists($this->getConfigFile()) || !($configFile=file_get_contents($this->getConfigFile())))
            $this->errors['err']=__('Unable to read config file. Permission denied! (#2)');
        elseif(!($fp = @fopen($this->getConfigFile(),'r+')))
            $this->errors['err']=__('Unable to open config file for writing. Permission denied! (#3)');

        else {
            $streams = DatabaseMigrater::getUpgradeStreams(INCLUDE_DIR.'upgrader/streams/');
            foreach ($streams as $stream=>$signature) {
                $schemaFile = INC_DIR."streams/$stream/install-mysql.sql";
                if (!file_exists($schemaFile) || !($fp2 = fopen($schemaFile, 'rb')))
                    $this->errors['err'] = sprintf(
                        __('%s: Internal Error - please make sure your download is the latest (#1)'),
                        $stream);
                elseif (
                        // TODO: Make the hash algo configurable in the streams
                        //       configuration ( core : md5 )
                        !($hash = md5(fread($fp2, filesize($schemaFile))))
                        || strcasecmp($signature, $hash))
                    $this->errors['err'] = sprintf(
                        __('%s: Unknown or invalid schema signature (%s .. %s)'),
                        $stream,
                        $signature, $hash);
                elseif (!$this->load_sql_file($schemaFile, $vars['prefix'], true, $debug))
                    $this->errors['err'] = sprintf(
                        __('%s: Error parsing SQL schema! Get help from developers (#4)'),
                        $stream);
            }
        }

        if(!$this->errors) {

            // TODO: Use language selected from install worksheet
            $i18n = new Internationalization($vars['lang_id']);
            $i18n->loadDefaultData();

            Signal::send('system.install', $this);

            $sql='SELECT `id` FROM '.TABLE_PREFIX.'sla ORDER BY `id` LIMIT 1';
            $sla_id_1 = db_result(db_query($sql, false));

            $sql='SELECT `dept_id` FROM '.TABLE_PREFIX.'department ORDER BY `dept_id` LIMIT 1';
            $dept_id_1 = db_result(db_query($sql, false));

            $sql='SELECT `tpl_id` FROM '.TABLE_PREFIX.'email_template_group ORDER BY `tpl_id` LIMIT 1';
            $template_id_1 = db_result(db_query($sql, false));

            $sql='SELECT `group_id` FROM '.TABLE_PREFIX.'groups ORDER BY `group_id` LIMIT 1';
            $group_id_1 = db_result(db_query($sql, false));

            $sql='SELECT `value` FROM '.TABLE_PREFIX.'config WHERE namespace=\'core\' and `key`=\'default_timezone_id\' LIMIT 1';
            $default_timezone = db_result(db_query($sql, false));

            //Create admin user.
            $sql='INSERT INTO '.TABLE_PREFIX.'staff SET created=NOW() '
                .", isactive=1, isadmin=1, group_id='$group_id_1', dept_id='$dept_id_1'"
                .", timezone_id='$default_timezone', max_page_size=25"
                .', email='.db_input($vars['admin_email'])
                .', firstname='.db_input($vars['fname'])
                .', lastname='.db_input($vars['lname'])
                .', username='.db_input($vars['username'])
                .', passwd='.db_input(Passwd::hash($vars['passwd']));
            if(!db_query($sql, false) || !($uid=db_insert_id()))
                $this->errors['err']=__('Unable to create admin user (#6)');
        }

        if(!$this->errors) {
            //Create default emails!
            $email = $vars['email'];
            list(,$domain)=explode('@',$vars['email']);
            $sql='INSERT INTO '.TABLE_PREFIX.'email (`name`,`email`,`created`,`updated`) VALUES '
                    ." ('Support','$email',NOW(),NOW())"
                    .",('osTicket Alerts','alerts@$domain',NOW(),NOW())"
                    .",('','noreply@$domain',NOW(),NOW())";
            $support_email_id = db_query($sql, false) ? db_insert_id() : 0;


            $sql='SELECT `email_id` FROM '.TABLE_PREFIX."email WHERE `email`='alerts@$domain' LIMIT 1";
            $alert_email_id = db_result(db_query($sql, false));

            //Create config settings---default settings!
            $defaults = array(
                'default_email_id'=>$support_email_id,
                'alert_email_id'=>$alert_email_id,
                'default_dept_id'=>$dept_id_1, 'default_sla_id'=>$sla_id_1,
                'default_template_id'=>$template_id_1,
                'admin_email'=>$vars['admin_email'],
                'schema_signature'=>$streams['core'],
                'helpdesk_url'=>URL,
                'helpdesk_title'=>$vars['name']);
            $config = new Config('core');
            if (!$config->updateAll($defaults))
                $this->errors['err']=__('Unable to create config settings').' (#7)';

            // Set company name
            require_once(INCLUDE_DIR.'class.company.php');
            $company = new Company();
            $company->getForm()->setAnswer('name', $vars['name']);
            $company->getForm()->save();

			foreach ($streams as $stream=>$signature) {
				if ($stream != 'core') {
                    $config = new Config($stream);
                    if (!$config->update('schema_signature', $signature))
                        $this->errors['err']=__('Unable to create config settings').' (#8)';
				}
			}
        }

        if($this->errors) return false; //Abort on internal errors.


        //Rewrite the config file - MUST be done last to allow for installer recovery.
        $configFile= str_replace("define('OSTINSTALLED',FALSE);","define('OSTINSTALLED',TRUE);",$configFile);
        $configFile= str_replace('%ADMIN-EMAIL',$vars['admin_email'],$configFile);
        $configFile= str_replace('%CONFIG-DBHOST',$vars['dbhost'],$configFile);
        $configFile= str_replace('%CONFIG-DBNAME',$vars['dbname'],$configFile);
        $configFile= str_replace('%CONFIG-DBUSER',$vars['dbuser'],$configFile);
        $configFile= str_replace('%CONFIG-DBPASS',$vars['dbpass'],$configFile);
        $configFile= str_replace('%CONFIG-PREFIX',$vars['prefix'],$configFile);
        $configFile= str_replace('%CONFIG-SIRI',Misc::randCode(32),$configFile);
        if(!$fp || !ftruncate($fp,0) || !fwrite($fp,$configFile)) {
            $this->errors['err']=__('Unable to write to config file. Permission denied! (#5)');
            return false;
        }
        @fclose($fp);

        /************* Make the system happy ***********************/

        $sql='UPDATE '.TABLE_PREFIX."email SET dept_id=$dept_id_1";
        db_query($sql, false);

        global $cfg;
        $cfg = new OsticketConfig();

        //Create a ticket to make the system warm and happy.
        $errors = array();
        $ticket_vars = $i18n->getTemplate('templates/ticket/installed.yaml')
            ->getData();
        $ticket = Ticket::create($ticket_vars, $errors, 'api', false, false);

        if ($ticket
                && ($org = Organization::objects()->order_by('id')->one())) {

            $user=User::lookup($ticket->getOwnerId());
            $user->setOrganization($org);
        }

        //TODO: create another personalized ticket and assign to admin??

        //Log a message.
        $msg=__("Congratulations osTicket basic installation completed!\n\nThank you for choosing osTicket!");
        $sql='INSERT INTO '.TABLE_PREFIX.'syslog SET created=NOW(), updated=NOW(), log_type="Debug" '
            .', title="osTicket installed!"'
            .', log='.db_input($msg)
            .', ip_address='.db_input($_SERVER['REMOTE_ADDR']);
        db_query($sql, false);

        return true;
    }
}
?>
