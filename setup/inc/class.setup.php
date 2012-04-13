<?php
/*********************************************************************
    class.setup.php

    osTicket setup wizard.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

Class SetupWizard {

    //Mimimum requirements
    var $prereq = array('php'   => '4.3',
                        'mysql' => '4.4');

    //Version info - same as the latest version.
    var $version ='1.7-dpr2';
    var $version_verbose='1.7 DPR 2';

    //Errors
    var $errors=array();

    function SetupWizard(){
        $this->errors=array();
    }

    function load_sql_file($file, $prefix, $abort=true, $debug=false) {
        
        if(!file_exists($file) || !($schema=file_get_contents($file)))
            return $this->abort('Error accessing SQL file '.basename($file));

        return $this->load_sql($schema, $prefix, $abort, $debug);
    }

    /*
        load SQL schema - assumes MySQL && existing connection
        */
    function load_sql($schema, $prefix, $abort=true, $debug=false) {

        # Strip comments and remarks
        $schema=preg_replace('%^\s*(#|--).*$%m','',$schema);
        # Replace table prefis
        $schema = str_replace('%TABLE_PREFIX%',$prefix, $schema);
        # Split by semicolons - and cleanup 
        if(!($statements = array_filter(array_map('trim', @explode(';', $schema)))))
            return $this->abort('Error parsing SQL schema');


        @mysql_query('SET SESSION SQL_MODE =""');
        foreach($statements as $k=>$sql) {
            if(!mysql_query($sql)) {
                if($debug) echo "[$sql]=>".mysql_error();
                if($abort)
                    return $this->abort("[$sql] - ".mysql_error());
            }
        }

        return true;
    }

    function getVersion() {
        return $this->version;
    }

    function getVersionVerbose() {
        return $this->version_verbose;
    }

    function getPHPVersion() {
        return $this->prereq['php'];
    }

    function getMySQLVersion() {
        return $this->prereq['mysql'];
    }

    function check_php() {
        return (version_compare(PHP_VERSION,$this->getPHPVersion())>=0);
    }

    function check_mysql() {
        return (extension_loaded('mysql'));
    }

    function check_prereq() {
        return ($this->check_php() && $this->check_mysql());
    }

    /*
        @error is a mixed var.
    */
    function abort($error) {
       
        $this->onError($error);

        return false; // Always false... It's an abort.
    }

    function setError($error) {
    
        if($error && is_array($error))
            $this->errors = array_merge($this->errors,$error);
        elseif($error)
            $this->errors[] = $error;
    }

    function getErrors(){

        return $this->errors;
    }

    function onError($error) {
       return $this->setError($error);
    }
}

class Upgrader extends SetupWizard {

    var $prefix;
    var $sqldir;
    var $signature;

    function Upgrader($signature, $prefix, $sqldir) {

        $this->signature = $signature;
        $this->shash = substr($signature, 0, 8);
        $this->prefix = $prefix;
        $this->sqldir = $sqldir;
        $this->errors = array();

        //Init persistent state of upgrade.
        $this->state = &$_SESSION['ost_upgrader'][$this->getShash()]['state'];

        //Init the task Manager.
        if(!isset($_SESSION['ost_upgrader'][$this->getShash()]))
            $_SESSION['ost_upgrader'][$this->getShash()]['tasks']=array();

        //Tasks to perform - saved on the session.
        $this->tasks = &$_SESSION['ost_upgrader'][$this->getShash()]['tasks'];
    }

    function onError($error) {
        $this->setError($error);
        $this->setState('aborted');
    }

    function isUpgradable() {
        return (!$this->isAborted() && $this->getNextPatch());
    }

    function isAborted() {
        return !strcasecmp($this->getState(), 'aborted');
    }

    function getSchemaSignature() {
        return $this->signature;
    }

    function getShash() {
        return $this->shash;
    }

    function getTablePrefix() {
        return $this->prefix;
    }

    function getSQLDir() {
        return $this->sqldir;
    }

    function getState() {
        return $this->state;
    }

    function setState($state) {
        $this->state = $state;
    }

    function getNextPatch() {

        if(!($patch=glob($this->getSQLDir().$this->getShash().'-*.patch.sql')))
            return null;

        return $patch[0];
    }

    function getThisPatch() {
                
        if(!($patch=glob($this->getSQLDir().'*-'.$this->getShash().'.patch.sql')))
            return null;

        return $patch[0];

    }

    function getNextVersion() {
        if(!$patch=$this->getNextPatch())
            return '(Latest)';

        if(preg_match('/\*(.*)\*/', file_get_contents($patch), $matches))
            $info=$matches[0];
        else
            $info=substr(basename($patch), 9, 8);

        return $info;
    }

    function getNextAction() {

        $action='Upgrade osTicket to '.$this->getVersion();
        if($this->getNumPendingTasks() && ($task=$this->getNextTask())) {
            $action = $task['desc'];
            if($task['status']) //Progress report... 
                $action.=' ('.$task['status'].')';
        } elseif($this->isUpgradable() && ($nextversion = $this->getNextVersion())) {
            $action = "Upgrade to $nextversion";
        }

        return $action;
    }

    function getNextStepInfo() {

        if(($patches=$this->getPatches()))
            return $patches[0];
        
        if(($hooks=$this->getScriptedHooks()))
            return $hooks[0]['desc'];

        return null;
    }

    function getPatches($ToSignature) {
     
        $signature = $this->getSignature();
        $patches = array();
        while(($patch=glob($this->getSQLDir().substr($signature,0,8).'-*.patch.sql')) && $i++) {
            $patches = array_merge($patches, $patch);
            if(!($signature=substr(basename($patch[0]), 10, 8)) || !strncasecmp($signature, $ToSignature, 8))
                break;
        }

        return $patches;
    }

    function getNumPendingTasks() {

        return count($this->getPendingTasks());
    }

    function getPendingTasks() {

        $pending=array();
        if(($tasks=$this->getTasks())) {
            foreach($tasks as $k => $task) {
                if(!$task['done'])
                    $pending[$k] = $task;
            }  
        }
        
        return $pending;
    }

    function getTasks() {
       return $this->tasks;
    }

    function getNextTask() {

        if(!($tasks=$this->getPendingTasks()))
            return null;

        return current($tasks);
    }

    function removeTask($tId) {

        if(isset($this->tasks[$tId]))
            unset($this->tasks[$tId]);

        return (!$this->tasks[$tId]);
    }

    function setTaskStatus($tId, $status) {
        if(isset($this->tasks[$tId]))
            $this->tasks[$tId]['status'] = $status;
    }

    function doTasks() {

        if(!($tasks=$this->getPendingTasks()))
            return true; //Nothing to do.

        foreach($tasks as $k => $task) {
            if(call_user_func(array($this, $task['func']), $k)===0) {
                $this->tasks[$k]['done'] = true;
            } else { //Task has pending items to process.
                break;
            }
        }

        return (!$this->getPendingTasks());
    }
    
    function upgrade() {

        if($this->getPendingTasks() || !($patch=$this->getNextPatch()))
            return false;

        if(!$this->load_sql_file($patch, $this->getTablePrefix()))
            return false;

        //TODO: Log the upgrade

        //Load up post install tasks.
        $shash = substr(basename($patch), 9, 8); 
        $phash = substr(basename($patch), 0, 17); 
        
        $tasks=array();
        $tasks[] = array('func' => 'sometask',
                         'desc' => 'Some Task.... blah');
        switch($phash) { //Add  patch specific scripted tasks.
            case 'xxxx': //V1.6 ST- 1.7 *
                $tasks[] = array('func' => 'migrateAttachments2DB',
                                 'desc' => 'Migrating attachments to database, it might take a while depending on the number of files.');
                break;
        }
        
        $tasks[] = array('func' => 'cleanup',
                         'desc' => 'Post-upgrade cleanup!');
        
        
        
        //Load up tasks - NOTE: writing directly to the session - back to the future majic.
        $_SESSION['ost_upgrader'][$shash]['tasks'] = $tasks;
        $_SESSION['ost_upgrader'][$shash]['state'] = 'upgrade';

        //clear previous patch info - 
        unset($_SESSION['ost_upgrader'][$this->getSHash()]);

        return true;
    }

    /************* TASKS **********************/
    function sometask($tId) {
        
        $this->setTaskStatus($tId, 'Doing... '.time(). ' #'.$_SESSION['sometask']);

        sleep(2);
        $_SESSION['sometask']+=1;
        if($_SESSION['sometask']<4)
            return 22;

        $_SESSION['sometask']=0;

        return 0;  //Change to 1 for testing...
    }

    function cleanup($tId=0) {

        $file=$this->getSQLDir().$this->getSchemaSignature().'-cleanup.sql';
        if(!file_exists($file)) //No cleanup script.
            return 0;

        //We have a cleanup script  ::XXX: Don't abort on error? 
        if($this->load_sql_file($file, $this->getTablePrefix(), false, true))
            return 0;

        //XXX: ???
        return false;
    }
    

    function migrateAttachments2DB($tId=0) {
        echo "Process attachments here - $tId";
        return 0;
    }
}

/*
   Installer class - latest version.
   */
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
                $this->errors['db']='Unable to connect to MySQL server. Possibly invalid login info.';
            elseif(db_version()< $this->getMySQLVersion())
                $this->errors['db']=sprintf('osTicket requires MySQL %s or better!',$this->getMySQLVersion());
            elseif(!db_select_database($vars['dbname']) && !db_create_database($vars['dbname'])) {
                $this->errors['dbname']='Database doesn\'t exist';
                $this->errors['db']='Unable to create the database.';
            } elseif(!db_select_database($vars['dbname'])) {
                $this->errors['dbname']='Unable to select the database';
            }
        }

        //bailout on errors.
        if($this->errors) return false;

        /*************** We're ready to install ************************/
        define('ADMIN_EMAIL',$vars['admin_email']); //Needed to report SQL errors during install.
        define('PREFIX',$vars['prefix']); //Table prefix

        $schemaFile =INC_DIR.'sql/osticket-v1.7-mysql.sql'; //DB dump.
        $debug = true; //XXX:Change it to true to show SQL errors.

        //Last minute checks.
        if(!file_exists($schemaFile))
            $this->errors['err']='Internal Error - please make sure your download is the latest (#1)';
        elseif(!file_exists($this->getConfigFile()) || !($configFile=file_get_contents($this->getConfigFile())))
            $this->errors['err']='Unable to read config file. Permission denied! (#2)';
        elseif(!($fp = @fopen($this->getConfigFile(),'r+')))
            $this->errors['err']='Unable to open config file for writing. Permission denied! (#3)';
        elseif(!$this->load_sql_file($schemaFile,$vars['prefix'], true, $debug))
            $this->errors['err']='Error parsing SQL schema! Get help from developers (#4)';
              
        if(!$this->errors) {
            //Create admin user.
            $sql='INSERT INTO '.PREFIX.'staff SET created=NOW() '
                .', isactive=1, isadmin=1, group_id=1, dept_id=1, timezone_id=8, max_page_size=25 '
                .', email='.db_input($_POST['admin_email'])
                .', firstname='.db_input($vars['fname'])
                .', lastname='.db_input($vars['lname'])
                .', username='.db_input($vars['username'])
                .', passwd='.db_input(Passwd::hash($vars['passwd']));
            if(!mysql_query($sql) || !($uid=mysql_insert_id()))
                $this->errors['err']='Unable to create admin user (#6)';
        }

        if(!$this->errors) {
            //Create config settings---default settings!
            //XXX: rename ostversion  helpdesk_* ??
            $sql='INSERT INTO '.PREFIX.'config SET updated=NOW(), isonline=0 '
                .', default_email_id=1, alert_email_id=2, default_dept_id=1 '
                .', default_sla_id=1, default_timezone_id=8, default_template_id=1 '
                .', admin_email='.db_input($vars['admin_email'])
                .', schema_signature='.db_input(md5_file($schemaFile))
                .', helpdesk_url='.db_input(URL)
                .', helpdesk_title='.db_input($vars['name']);
            if(!mysql_query($sql) || !($cid=mysql_insert_id()))
                $this->errors['err']='Unable to create config settings (#7)';
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
        //Create default emails!
        $email = $vars['email'];
        list(,$domain)=explode('@',$vars['email']);
        $sql='INSERT INTO '.PREFIX.'email (`email_id`, `dept_id`, `name`,`email`,`created`,`updated`) VALUES '
                ." (1,1,'Support','$email',NOW(),NOW())"
                .",(2,1,'osTicket Alerts','alerts@$domain',NOW(),NOW())"
                .",(3,1,'','noreply@$domain',NOW(),NOW())";
        @mysql_query($sql);
                   
        //Create a ticket to make the system warm and happy.
        $sql='INSERT INTO '.PREFIX.'ticket SET created=NOW(), status="open", source="Web" '
            .' ,priority_id=2, dept_id=1, topic_id=1 '
            .' ,ticketID='.db_input(Misc::randNumber(6))
            .' ,email="support@osticket.com" '
            .' ,name="osTicket Support" '
            .' ,subject="osTicket Installed!"';
        if(mysql_query($sql) && ($tid=mysql_insert_id())) {
            if(!($msg=file_get_contents(INC_DIR.'msg/installed.txt')))
                $msg='Congratulations and Thank you for choosing osTicket!';
                        
            $sql='INSERT INTO '.PREFIX.'ticket_thread SET created=NOW(),source="Web" '
                .', poster="System"'
                .', ticket_id='.db_input($tid)
                .', body='.db_input($msg);
            @mysql_query($sql);
        }
        //TODO: create another personalized ticket and assign to admin??
                    
        //Log a message.
        $msg="Congratulations osTicket basic installation completed!\n\nThank you for choosing osTicket!";
        $sql='INSERT INTO '.PREFIX.'syslog SET created=NOW(),updated=NOW(),log_type="Debug" '
            .', title="osTicket installed!"'
            .', log='.db_input($msg)
            .', ip_address='.db_input($_SERVER['REMOTE_ADDR']);
        @mysql_query($sql);

        return true;
    }
}
?>
