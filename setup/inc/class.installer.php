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
require_once INCLUDE_DIR.'class.setup.php';

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

        $f['name']          = array('type'=>'string',   'required'=>1, 'error'=>'Name required');
        $f['email']         = array('type'=>'email',    'required'=>1, 'error'=>'Valid email required');
        $f['fname']         = array('type'=>'string',   'required'=>1, 'error'=>'First name required');
        $f['lname']         = array('type'=>'string',   'required'=>1, 'error'=>'Last name required');
        $f['admin_email']   = array('type'=>'email',    'required'=>1, 'error'=>'Valid email required');
        $f['username']      = array('type'=>'username', 'required'=>1, 'error'=>'Username required');
        $f['passwd']        = array('type'=>'password', 'required'=>1, 'error'=>'Password required');
        $f['passwd2']       = array('type'=>'string',   'required'=>1, 'error'=>'Confirm password');
        $f['prefix']        = array('type'=>'string',   'required'=>1, 'error'=>'Table prefix required');
        $f['dbhost']        = array('type'=>'string',   'required'=>1, 'error'=>'Hostname required');
        $f['dbname']        = array('type'=>'string',   'required'=>1, 'error'=>'Database name required');
        $f['dbuser']        = array('type'=>'string',   'required'=>1, 'error'=>'Username required');
        $f['dbpass']        = array('type'=>'string',   'required'=>1, 'error'=>'password required');


        if(!Validator::process($f,$vars,$this->errors) && !$this->errors['err'])
            $this->errors['err']='Missing or invalid data - correct the errors and try again.';


        //Staff's email can't be same as system emails.
        if($vars['admin_email'] && $vars['email'] && !strcasecmp($vars['admin_email'],$vars['email']))
            $this->errors['admin_email']='Conflicts with system email above';
        //Admin's pass confirmation.
        if(!$this->errors && strcasecmp($vars['passwd'],$vars['passwd2']))
            $this->errors['passwd2']='passwords to not match!';
        //Check table prefix underscore required at the end!
        if($vars['prefix'] && substr($vars['prefix'], -1)!='_')
            $this->errors['prefix']='Bad prefix. Must have underscore (_) at the end. e.g \'ost_\'';

        //Make sure admin username is not very predictable. XXX: feels dirty but necessary
        if(!$this->errors['username'] && in_array(strtolower($vars['username']),array('admin','admins','username','osticket')))
            $this->errors['username']='Bad username';

        //MYSQL: Connect to the DB and check the version & database (create database if it doesn't exist!)
        if(!$this->errors) {
            if(!db_connect($vars['dbhost'],$vars['dbuser'],$vars['dbpass']))
                $this->errors['db']='Unable to connect to MySQL server. '.db_connect_error();
            elseif(db_version()< $this->getMySQLVersion())
                $this->errors['db']=sprintf('osTicket requires MySQL %s or better!',$this->getMySQLVersion());
            elseif(!db_select_database($vars['dbname']) && !db_create_database($vars['dbname'])) {
                $this->errors['dbname']='Database doesn\'t exist';
                $this->errors['db']='Unable to create the database.';
            } elseif(!db_select_database($vars['dbname'])) {
                $this->errors['dbname']='Unable to select the database';
            } else {
                //Abort if we have another installation (or table) with same prefix.
                $sql = 'SELECT * FROM `'.$vars['prefix'].'config` LIMIT 1';
                if(db_query($sql, false)) {
                    $this->errors['err'] = 'We have a problem - another installation with same table prefix exists!';
                    $this->errors['prefix'] = 'Prefix already in-use';
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
        define('PREFIX',$vars['prefix']); //Table prefix

        $schemaFile =INC_DIR.'sql/osTicket-mysql.sql'; //DB dump.
        $debug = true; //XXX:Change it to true to show SQL errors.

        //Last minute checks.
        if(!file_exists($schemaFile) || !($fp = fopen($schemaFile, 'rb')))
            $this->errors['err']='Internal Error - please make sure your download is the latest (#1)';
        elseif(
                !($signature=trim(file_get_contents("$schemaFile.md5")))
                || !($hash=md5(fread($fp, filesize($schemaFile))))
                || strcasecmp($signature, $hash))
            $this->errors['err']='Unknown or invalid schema signature ('
                .$signature.' .. '.$hash.')';
        elseif(!file_exists($this->getConfigFile()) || !($configFile=file_get_contents($this->getConfigFile())))
            $this->errors['err']='Unable to read config file. Permission denied! (#2)';
        elseif(!($fp = @fopen($this->getConfigFile(),'r+')))
            $this->errors['err']='Unable to open config file for writing. Permission denied! (#3)';
        elseif(!$this->load_sql_file($schemaFile,$vars['prefix'], true, $debug))
            $this->errors['err']='Error parsing SQL schema! Get help from developers (#4)';

        $sql='SELECT `id` FROM '.PREFIX.'sla ORDER BY `id` LIMIT 1';
        $sla_id_1 = db_result(db_query($sql, false), 0);

        $sql='SELECT `dept_id` FROM '.PREFIX.'department ORDER BY `dept_id` LIMIT 1';
        $dept_id_1 = db_result(db_query($sql, false), 0);

        $sql='SELECT `tpl_id` FROM '.PREFIX.'email_template_group ORDER BY `tpl_id` LIMIT 1';
        $template_id_1 = db_result(db_query($sql, false), 0);

        $sql='SELECT `group_id` FROM '.PREFIX.'groups ORDER BY `group_id` LIMIT 1';
        $group_id_1 = db_result(db_query($sql, false), 0);

        $sql='SELECT `id` FROM '.PREFIX.'timezone WHERE offset=-5.0 LIMIT 1';
        $eastern_timezone = db_result(db_query($sql, false), 0);

        if(!$this->errors) {
            //Create admin user.
            $sql='INSERT INTO '.PREFIX.'staff SET created=NOW() '
                .", isactive=1, isadmin=1, group_id=$group_id_1, dept_id=$dept_id_1"
                .", timezone_id=$eastern_timezone, max_page_size=25"
                .', email='.db_input($vars['admin_email'])
                .', firstname='.db_input($vars['fname'])
                .', lastname='.db_input($vars['lname'])
                .', username='.db_input($vars['username'])
                .', passwd='.db_input(Passwd::hash($vars['passwd']));
            if(!db_query($sql, false) || !($uid=db_insert_id()))
                $this->errors['err']='Unable to create admin user (#6)';
        }

        if(!$this->errors) {
            //Create default emails!
            $email = $vars['email'];
            list(,$domain)=explode('@',$vars['email']);
            $sql='INSERT INTO '.PREFIX.'email (`name`,`email`,`created`,`updated`) VALUES '
                    ." ('Support','$email',NOW(),NOW())"
                    .",('osTicket Alerts','alerts@$domain',NOW(),NOW())"
                    .",('','noreply@$domain',NOW(),NOW())";
            $support_email_id = db_query($sql, false) ? db_insert_id() : 0;


            $sql='SELECT `email_id` FROM '.PREFIX."email WHERE `email`='alerts@$domain' LIMIT 1";
            $alert_email_id = db_result(db_query($sql, false), 0);

            //Create config settings---default settings!
            //XXX: rename ostversion  helpdesk_* ??
			$defaults = array('isonline'=>'0', 'default_email_id'=>$support_email_id,
				'alert_email_id'=>$alert_email_id, 'default_dept_id'=>$dept_id_1, 'default_sla_id'=>$sla_id_1,
				'default_timezone_id'=>$eastern_timezone, 'default_template_id'=>$template_id_1,
				'admin_email'=>db_input($vars['admin_email']),
				'schema_signature'=>db_input($signature),
				'helpdesk_url'=>db_input(URL),
				'helpdesk_title'=>db_input($vars['name']));
			foreach ($defaults as $key=>$value) {
				$sql='UPDATE '.PREFIX.'config SET updated=NOW(), value='.$value
					.' WHERE namespace="core" AND `key`='.db_input($key);
	            if(!db_query($sql, false))
	                $this->errors['err']='Unable to create config settings (#7)';
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
        $configFile= str_replace('%CONFIG-SIRI',Misc::randcode(32),$configFile);
        if(!$fp || !ftruncate($fp,0) || !fwrite($fp,$configFile)) {
            $this->errors['err']='Unable to write to config file. Permission denied! (#5)';
            return false;
        }
        @fclose($fp);

        /************* Make the system happy ***********************/

        $sql='UPDATE '.PREFIX."email SET dept_id=$dept_id_1";
        db_query($sql, false);
        $sql='UPDATE '.PREFIX."department SET email_id=$support_email_id"
            .", autoresp_email_id=$support_email_id";
        db_query($sql, false);

        //Create a ticket to make the system warm and happy.
        $sql='INSERT INTO '.PREFIX.'ticket SET created=NOW(), status="open", source="Web" '
            ." ,priority_id=0, dept_id=$dept_id_1, topic_id=0 "
            .' ,ticketID='.db_input(Misc::randNumber(6))
            .' ,email="support@osticket.com" '
            .' ,name="osTicket Support" '
            .' ,subject="osTicket Installed!"';
        if(db_query($sql, false) && ($tid=db_insert_id())) {
            if(!($msg=file_get_contents(INC_DIR.'msg/installed.txt')))
                $msg='Congratulations and Thank you for choosing osTicket!';

            $sql='INSERT INTO '.PREFIX.'ticket_thread SET created=NOW()'
                .', source="Web" '
                .', thread_type="M" '
                .', ticket_id='.db_input($tid)
                .', title='.db_input('osTicket Installed')
                .', body='.db_input($msg);
            db_query($sql, false);
        }
        //TODO: create another personalized ticket and assign to admin??

        //Log a message.
        $msg="Congratulations osTicket basic installation completed!\n\nThank you for choosing osTicket!";
        $sql='INSERT INTO '.PREFIX.'syslog SET created=NOW(), updated=NOW(), log_type="Debug" '
            .', title="osTicket installed!"'
            .', log='.db_input($msg)
            .', ip_address='.db_input($_SERVER['REMOTE_ADDR']);
        db_query($sql, false);

        return true;
    }
}
?>
