<?php
/*********************************************************************
    class.email.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once INCLUDE_DIR.'class.role.php';
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.mailfetch.php');

class Email extends VerySimpleModel {
    static $meta = array(
        'table' => EMAIL_TABLE,
        'pk' => array('email_id'),
        'joins' => array(
            'priority' => array(
                'constraint' => array('priority_id' => 'Priority.priority_id'),
                'null' => true,
            ),
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
                'null' => true,
            ),
            'topic' => array(
                'constraint' => array('topic_id' => 'Topic.topic_id'),
                'null' => true,
            ),
        )
    );

    const PERM_BANLIST = 'emails.banlist';

    static protected $perms = array(
            self::PERM_BANLIST => array(
                'title' =>
                /* @trans */ 'Banlist',
                'desc'  =>
                /* @trans */ 'Ability to add/remove emails from banlist via ticket interface',
                'primary' => true,
            ));


    var $address;
    var $mail_proto;

    function getId() {
        return $this->email_id;
    }

    function __toString() {
        if ($this->name)
            return sprintf('%s <%s>', $this->name, $this->email);

        return $this->email;
    }


    function __onload() {
        $this->mail_proto = $this->get('mail_protocol');
        if ($this->mail_encryption == 'SSL')
            $this->mail_proto .= "/".$this->mail_encryption;

        $this->address=$this->name?($this->name.' <'.$this->email.'>'):$this->email;
    }

    function getEmail() {
        return $this->email;
    }

    function getAddress() {
        return $this->address;
    }

    function getName() {
        return $this->name;
    }

    function getPriorityId() {
        return $this->priority_id;
    }

    function getDeptId() {
        return $this->dept_id;
    }

    function getDept() {
        return $this->dept;
    }

    function getTopicId() {
        return $this->topic_id;
    }

    function getTopic() {
        return $this->topic;
    }

    function autoRespond() {
        return !$this->noautoresp;
    }

    function getPasswd() {
        if (!$this->userpass)
            return '';
        return Crypto::decrypt($this->userpass, SECRET_SALT, $this->userid);
    }

    function getSMTPPasswd() {
        if (!$this->smtp_userpass)
            return '';
        return Crypto::decrypt($this->smtp_userpass, SECRET_SALT, $this->smtp_userid);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        $base = $this->getHashtable();
        $base['mail_proto'] = $this->mail_protocol;
        if ($this->mail_encryption != 'NONE')
          $base['mail_proto'] .= "/{$this->mail_encryption}";
        return $base;
    }

    function getMailAccountInfo() {

        /*NOTE: Do not change any of the tags - otherwise mail fetching will fail */
        $info = array(
                //Mail server info
                'host'  => $this->mail_host,
                'port'  => $this->mail_port,
                'protocol'  => $this->mail_protocol,
                'encryption' => $this->mail_encryption,
                'username'  => $this->userid,
                'password' => Crypto::decrypt($this->userpass, SECRET_SALT, $this->userid),
                //osTicket specific
                'email_id'  => $this->getId(), //Required for email routing to work.
                'max_fetch' => $this->mail_fetchmax,
                'folder' => $this->mail_folder,
                'delete_mail' => $this->mail_delete,
                'archive_folder' => $this->mail_archivefolder
        );

        return $info;
    }

    function isSMTPEnabled() {

        return (
                $this->smtp_active
                    && ($info=$this->getSMTPInfo())
                    && (!$info['auth'] || $info['password'])
                );
    }

    function allowSpoofing() {
        return ($this->smtp_spoofing);
    }

    function getSMTPInfo() {
        $smtpcreds = $this->smtp_auth_creds;
        $username = $smtpcreds ? $this->smtp_userid : $this->userid;
        $passwd = $smtpcreds ? $this->smtp_userpass : $this->userpass;

        $info = array (
                'host' => $this->smtp_host,
                'port' => $this->smtp_port,
                'auth' => (bool) $this->smtp_auth,
                'username' => $username,
                'password' => Crypto::decrypt($passwd, SECRET_SALT, $username)
                );

        return $info;
    }

    function send($to, $subject, $message, $attachments=null, $options=null, $cc=array()) {

        $mailer = new Mailer($this);
        if($attachments)
            $mailer->addAttachments($attachments);

        return $mailer->send($to, $subject, $message, $options, $cc);
    }

    function sendAutoReply($to, $subject, $message, $attachments=null, $options=array()) {
        $options+= array('autoreply' => true);
        return $this->send($to, $subject, $message, $attachments, $options);
    }

    function sendAlert($to, $subject, $message, $attachments=null, $options=array()) {
        $options+= array('notice' => true);
        return $this->send($to, $subject, $message, $attachments, $options);
    }

   function delete() {
        global $cfg;
        //Make sure we are not trying to delete default emails.
        if(!$cfg || $this->getId()==$cfg->getDefaultEmailId() || $this->getId()==$cfg->getAlertEmailId()) //double...double check.
            return 0;

        if (!parent::delete())
            return false;

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        Dept::objects()
            ->filter(array('email_id' => $this->getId()))
            ->update(array(
                'email_id' => $cfg->getDefaultEmailId()
            ));

        Dept::objects()
            ->filter(array('autoresp_email_id' => $this->getId()))
            ->update(array(
                'autoresp_email_id' => 0,
            ));

        return true;
    }


    /******* Static functions ************/

   static function getIdByEmail($email) {
        $qs = static::objects()->filter(Q::any(array(
                        'email'  => $email,
                        'userid' => $email
                        )))
            ->values_flat('email_id');

        $row = $qs->first();
        return $row ? $row[0] : false;
    }

    static function create($vars=false) {
        $inst = new static($vars);
        $inst->created = SqlFunction::NOW();
        return $inst;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors=false) {
        global $cfg;

        // very basic checks
        $vars['cpasswd']=$this->getPasswd(); //Current decrypted password.
        $vars['smtp_cpasswd']=$this->getSMTPPasswd(); // Current decrypted SMTP password.
        $vars['name']=Format::striptags(trim($vars['name']));
        $vars['email']=trim($vars['email']);
        $vars['mail_folder']=Format::striptags(trim($vars['mail_folder']));

        $id = isset($this->email_id) ? $this->getId() : 0;
        if($id && $id!=$vars['id'])
            $errors['err']=__('Get technical help!')
                .' '.__('Internal error occurred');

        if(!$vars['email'] || !Validator::is_email($vars['email'])) {
            $errors['email']=__('Valid email required');
        }elseif(($eid=Email::getIdByEmail($vars['email'])) && $eid!=$id) {
            $errors['email']=__('Email already exists');
        }elseif($cfg && !strcasecmp($cfg->getAdminEmail(), $vars['email'])) {
            $errors['email']=__('Email already used as admin email!');
        }elseif(Staff::getIdByEmail($vars['email'])) { //make sure the email doesn't belong to any of the staff
            $errors['email']=__('Email in use by an agent');
        }

        if(!$vars['name'])
            $errors['name']=__('Email name required');

        $dept = Dept::lookup($vars['dept_id']);
        if($dept && !$dept->isActive())
          $errors['dept_id'] = '';

        $topic = Topic::lookup($vars['topic_id']);
        if($topic && !$topic->isActive())
          $errors['topic_id'] = '';

        // Validate Credentials
        if ($vars['mail_active'] || ($vars['smtp_active'] && $vars['smtp_auth']
                && !$vars['smtp_auth_creds']))
            $errors = self::validateCredentials($vars['userid'], $vars['passwd'], $id, $errors, false);

        if ($vars['smtp_active'] && $vars['smtp_auth'] && $vars['smtp_auth_creds'])
            $errors = self::validateCredentials($vars['smtp_userid'], $vars['smtp_passwd'], $id, $errors, true);

        list($vars['mail_protocol'], $encryption) = explode('/', $vars['mail_proto']);
        $vars['mail_encryption'] = $encryption ?: 'NONE';

        if($vars['mail_active']) {
            //Check pop/imapinfo only when enabled.
            if(!function_exists('imap_open'))
                $errors['mail_active']= __("IMAP doesn't exist. PHP must be compiled with IMAP enabled.");
            if(!$vars['mail_host'])
                $errors['mail_host']=__('Host name required');
            if(!$vars['mail_port'])
                $errors['mail_port']=__('Port required');
            if(!$vars['mail_protocol'])
                $errors['mail_protocol']=__('Select protocol');
            if(!$vars['mail_fetchfreq'] || !is_numeric($vars['mail_fetchfreq']))
                $errors['mail_fetchfreq']=__('Fetch interval required');
            if(!$vars['mail_fetchmax'] || !is_numeric($vars['mail_fetchmax']))
                $errors['mail_fetchmax']=__('Maximum emails required');

            if($vars['mail_protocol'] == 'POP' && !empty($vars['mail_folder']))
                $errors['mail_folder'] = __('POP mail servers do not support folders');

            if(!isset($vars['postfetch']))
                $errors['postfetch']=__('Indicate what to do with fetched emails');
            elseif(!strcasecmp($vars['postfetch'],'archive')) {
                if ($vars['mail_protocol'] == 'POP')
                    $errors['postfetch'] =  __('POP mail servers do not support folders');
                elseif (!$vars['mail_archivefolder'])
                    $errors['postfetch'] = __('Valid folder required');
            }
        }

        if($vars['smtp_active']) {
            if(!$vars['smtp_host'])
                $errors['smtp_host']=__('Host name required');
            if(!$vars['smtp_port'])
                $errors['smtp_port']=__('Port required');
        }

        //abort on errors
        if ($errors)
            return false;

        if(!$errors && ($vars['mail_host'] && $vars['userid'])) {
            $existing = static::objects()
                ->filter(array(
                    'mail_host' => $vars['mail_host'],
                    'userid' => $vars['userid']
                ));

            if ($id)
                $existing->exclude(array('email_id' => $id));

            if ($existing->exists())
                $errors['userid']=$errors['host']=__('Host/userid combination already in use.');
        }

        $passwd = $vars['passwd'] ?: $vars['cpasswd'];
        if(!$errors && $vars['mail_active']) {
            //note: password is unencrypted at this point...MailFetcher expect plain text.
            $fetcher = new MailFetcher(
                    array(
                        'host'  => $vars['mail_host'],
                        'port'  => $vars['mail_port'],
                        'folder' => $vars['mail_folder'],
                        'username'  => $vars['userid'],
                        'password'  => $passwd,
                        'protocol'  => $vars['mail_protocol'],
                        'encryption' => $vars['mail_encryption'])
                    );
            if(!$fetcher->connect()) {
                //$errors['err']='Invalid login. Check '.Format::htmlchars($vars['mail_protocol']).' settings';
                $errors['err']=sprintf(__('Invalid login. Check %s settings'),Format::htmlchars($vars['mail_protocol']));
                $errors['mail']='<br>'.$fetcher->getLastError();
            } elseif ($vars['mail_folder'] && !$fetcher->checkMailbox($vars['mail_folder'],true)) {
                 $errors['mail_folder']=sprintf(__('Invalid or unknown mail folder! >> %s'),$fetcher->getLastError());
                 if(!$errors['mail'])
                     $errors['mail']=__('Invalid or unknown mail folder!');
            }elseif($vars['mail_archivefolder'] && !$fetcher->checkMailbox($vars['mail_archivefolder'],true)) {
                 //$errors['postfetch']='Invalid or unknown mail folder! >> '.$fetcher->getLastError().'';
                 $errors['postfetch']=sprintf(__('Invalid or unknown mail folder! >> %s'),$fetcher->getLastError());
                 if(!$errors['mail'])
                     $errors['mail']=__('Invalid or unknown archive folder!');
            }
        }

        $smtppasswd = $vars['smtp_passwd'] ?: $vars['smtp_cpasswd'];
        if(!$errors && $vars['smtp_active']) { //Check SMTP login only.
            $smtpcreds = $vars['smtp_auth_creds'];
            require_once 'Mail.php'; // PEAR Mail package
            $smtp = mail::factory('smtp',
                    array ('host' => $vars['smtp_host'],
                           'port' => $vars['smtp_port'],
                           'auth' => (bool) $vars['smtp_auth'],
                           'username' => $smtpcreds ? $vars['smtp_userid'] : $vars['userid'],
                           'password' => $smtpcreds ? $smtppasswd : $passwd,
                           'timeout'  =>20,
                           'debug' => false,
                           ));
            $mail = $smtp->connect();
            if(PEAR::isError($mail)) {
                $errors['err']=__('Unable to log in. Check SMTP settings.');
                $errors['smtp']='<br>'.$mail->getMessage();
            }else{
                $smtp->disconnect(); //Thank you, sir!
            }
        }

        if($errors) return false;

        $this->mail_errors = 0;
        $this->mail_lastfetch = null;
        $this->email = Format::sanitize($vars['email']);
        $this->name = Format::striptags($vars['name']);
        $this->dept_id = (int) $vars['dept_id'];
        $this->priority_id = (int) (isset($vars['priority_id']) ? $vars['priority_id'] : 0);
        $this->topic_id = (int) $vars['topic_id'];
        $this->noautoresp = (int) $vars['noautoresp'];
        $this->userid = $vars['userid'];
        $this->mail_active = $vars['mail_active'];
        $this->mail_host = $vars['mail_host'];
        $this->mail_folder = $vars['mail_folder'] ?: null;
        $this->mail_protocol = $vars['mail_protocol'] ?: 'POP';
        $this->mail_encryption = $vars['mail_encryption'];
        $this->mail_port = $vars['mail_port'] ?: 0;
        $this->mail_fetchfreq = $vars['mail_fetchfreq'] ?: 0;
        $this->mail_fetchmax = $vars['mail_fetchmax'] ?: 0;
        $this->smtp_active = $vars['smtp_active'];
        $this->smtp_host = $vars['smtp_host'];
        $this->smtp_port = $vars['smtp_port'] ?: 0;
        $this->smtp_auth = $vars['smtp_auth'];
        $this->smtp_auth_creds = isset($vars['smtp_auth_creds']) ? 1 : 0;
        $this->smtp_userid = $vars['smtp_userid'];
        $this->smtp_spoofing = $vars['smtp_spoofing'];
        $this->notes = Format::sanitize($vars['notes']);

        //Post fetch email handling...
        if ($vars['postfetch'] && !strcasecmp($vars['postfetch'],'delete')) {
            $this->mail_delete = 1;
            $this->mail_archivefolder = null;
        }
        elseif($vars['postfetch'] && !strcasecmp($vars['postfetch'],'archive') && $vars['mail_archivefolder']) {
            $this->mail_delete = 0;
            $this->mail_archivefolder = $vars['mail_archivefolder'];
        }
        else {
            $this->mail_delete = 0;
            $this->mail_archivefolder = null;
        }

        if ($vars['passwd']) //New password - encrypt.
            $this->userpass = Crypto::encrypt($vars['passwd'],SECRET_SALT, $vars['userid']);

        if ($vars['smtp_passwd']) // New SMTP password - encrypt.
            $this->smtp_userpass = Crypto::encrypt($vars['smtp_passwd'], SECRET_SALT, $vars['smtp_userid']);

        if ($this->save())
            return true;

        if ($id) { //update
            $errors['err']=sprintf(__('Unable to update %s.'), __('this email'))
               .' '.__('Internal error occurred');
        }
        else {
            $errors['err']=sprintf(__('Unable to add %s.'), __('this email'))
               .' '.__('Internal error occurred');
        }

        return false;
    }

    static function validateCredentials(?string $username=null, ?string $password=null, ?int $id=null, &$errors, $smtp=false) {
        if (!$username)
            $errors[$smtp ? 'smtp_userid' : 'userid'] = __('Username missing');

        if (!$id && !$password)
            $errors[$smtp ? 'smtp_passwd' : 'passwd'] = __('Password Required');
        elseif ($password && $username
                && !Crypto::encrypt($password, SECRET_SALT, $username))
            $errors[$smtp ? 'smtp_passwd' : 'passwd'] = sprintf('%s - %s', __('Unable to encrypt password'), __('Get technical help!'));

        return $errors;
    }

    static function getPermissions() {
        return self::$perms;
    }

    static function getAddresses($options=array(), $flat=true) {
        $objects = static::objects();
        if ($options['smtp'])
            $objects = $objects->filter(array('smtp_active'=>true));

        if ($options['depts'])
            $objects = $objects->filter(array('dept_id__in'=>$options['depts']));

        if (!$flat)
            return $objects;

        $addresses = array();
        foreach ($objects->values_flat('email_id', 'email') as $row) {
            list($id, $email) = $row;
            $addresses[$id] = $email;
        }
        return $addresses;
    }
}
RolePermission::register(/* @trans */ 'Miscellaneous', Email::getPermissions());
?>
