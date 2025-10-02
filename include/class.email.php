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
require_once(INCLUDE_DIR.'laminas-mail/vendor/autoload.php');
include_once INCLUDE_DIR.'class.role.php';
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.mail.php');
include_once(INCLUDE_DIR.'class.mailer.php');
include_once(INCLUDE_DIR.'class.oauth2.php');
include_once(INCLUDE_DIR.'class.mailfetch.php');
include_once(INCLUDE_DIR.'class.mailparse.php');
include_once(INCLUDE_DIR.'api.tickets.php');

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
            'mailbox' => array(
                'reverse' => 'MailBoxAccount.account',
                'list' => false,
                'null' => true,
            ),
            'smtp' => array(
                'reverse' => 'SmtpAccount.account',
                'list' => false,
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

    private $stash;
    private $address;

    function getId() {
        return $this->email_id;
    }

    function __toString() {
        return $this->getAddress();
    }

    //  TODO: move stash/restore to a  StashableTrait
    function restore($key, $drop=true) {
        if (($data = $this->stash($key, null)) && $drop)
            $this->stash[$key] = null;
        return $data;
    }

    function stash($key, $data=null) {
        if (!isset($this->stash))
            $this->stash = &$_SESSION[':email'][$this->getId()];

        // If data is null then stash is being pop-ed
        if (!isset($data) && $key && isset($this->stash[$key]))
            return $this->stash[$key];

        // stash data
        if ($key && $data)
            $this->stash[$key] = $data;
    }

    function stashFormData(array $data) {
        $this->stash('formdata', array_filter($data));
    }

    function restoreFormData($drop=true) {
        return $this->restore('formdata', $drop) ?: [];
    }

    function restoreErrors($drop=true) {
        return $this->restore('errors', $drop) ?: [];
    }

    function restoreNotice($drop=true) {
        return $this->restore('notice', $drop);
    }

    function getEmail() {
        return $this->email;
    }

    function getAddress() {
        if (!isset($this->address))
            $this->address = $this->name
            ? sprintf('%s <%s>', $this->name, $this->email)
            : $this->email;

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

    function getHashtable() {
        return $this->ht;
    }

    static function getSupportedAuthTypes() {
        static $auths  = null;
        if (!isset($auths)) {
            $auths = [];
            // OAuth auth
            foreach (Oauth2AuthorizationBackend::allRegistered() as $id => $bk)
                $auths[$id] = $bk->getName();
            // Basic authentication
            $auths['basic'] = sprintf('%s (%s)',
                    __('Basic Authentication'),
                    __('Legacy'));
        }

        return $auths;
    }

    static function getSupportedSMTPAuthTypes() {
        return array_merge([
                'mailbox' => sprintf('%s  %s',
                    __('Same as'), __('Remote Mailbox')),
                'none' => sprintf('%s - %s',
                    __('None'), __('No Authentication Required'))],
                self::getSupportedAuthTypes());
    }

    function getInfo() {
        // Base information mimus objects
        $info = array_filter($this->getHashtable(), function($e) {
                    return !is_object($e);
                });
        // Remote Mailbox Info
        if (($mailbox=$this->getMailBoxAccount()))
            $info = array_merge($info, $mailbox->getInfo());
        // SMTP Account Info
        if (($smtp=$this->getSmtpAccount()))
            $info = array_merge($info, $smtp->getInfo());
        // Restore stahed formdata (if any)
        if ($_SERVER['REQUEST_METHOD'] == 'GET'
                && ($data=$this->restoreFormData()))
            $info = array_merge($info, $data);

        return $info;
    }

    function getMailBoxAccount($autoinit=true) {
        if (!$this->mailbox && isset($this->email_id) && $autoinit)
            $this->mailbox = MailBoxAccount::create([
                    'email_id' => $this->email_id]);

        return $this->mailbox;
    }

    function getSmtpAccount($autoinit=true) {
        if (!$this->smtp && isset($this->email_id) && $autoinit)
            $this->smtp = SmtpAccount::create([
                    'email_id' => $this->email_id]);

        return $this->smtp;
    }

    function getAuthAccount($which) {
        $account = null;
        switch ($which) {
            case 'mailbox':
                $account  = $this->getMailBoxAccount();
                break;
            case 'smtp':
                $account = $this->getSmtpAccount();
                break;
        }
        return $account;
    }

    function send($to, $subject, $message, $attachments=null, $options=null, $cc=array()) {
        $mailer = new osTicket\Mail\Mailer($this);
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

        // Delete email accounts
        if ($this->mailbox)
            $this->mailbox->delete();
        if ($this->smtp)
            $this->smtp->delete();

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

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors=false) {
        global $cfg;

        // very basic checks
        $vars['name'] = Format::striptags(trim($vars['name']));
        $vars['email'] = trim($vars['email']);
        $id = isset($this->email_id) ? $this->getId() : 0;
        if ($id && $id != $vars['id'])
            $errors['err']=__('Get technical help!')
                .' '.__('Internal error occurred');

        if (!$vars['email'] || !Validator::is_email($vars['email'])) {
            $errors['email']=__('Valid email required');
        } elseif (($eid=Email::getIdByEmail($vars['email'])) && $eid != $id) {
            $errors['email']=__('Email already exists');
        } elseif ($cfg && !strcasecmp($cfg->getAdminEmail(), $vars['email'])) {
            $errors['email']=__('Email already used as admin email!');
        } elseif (Staff::getIdByEmail($vars['email'])) { //make sure the email doesn't belong to any of the staff
            $errors['email']=__('Email in use by an agent');
        }

        if (!$vars['name'])
            $errors['name']=__('Email name required');

        /*
         TODO: ???
        $dept = Dept::lookup($vars['dept_id']);
        if($dept && !$dept->isActive())
          $errors['dept_id'] = '';

        $topic = Topic::lookup($vars['topic_id']);
        if($topic && !$topic->isActive())
          $errors['topic_id'] = '';
        */

        // Remote Mailbox Settings
        if (($mailbox = $this->getMailBoxAccount()))
            $mailbox->update($vars, $errors);
        // SMTP Settings
        if (($smtp = $this->getSmtpAccount()))
            $smtp->update($vars, $errors);

        //abort on errors
        if ($errors)
            return false;

        if ($errors) return false;

        // Update basic settings
        $this->email = Format::sanitize($vars['email']);
        $this->name = Format::striptags($vars['name']);
        $this->dept_id = (int) $vars['dept_id'];
        $this->priority_id = (int) (isset($vars['priority_id']) ? $vars['priority_id'] : 0);
        $this->topic_id = (int) $vars['topic_id'];
        $this->noautoresp = (int) $vars['noautoresp'];
        $this->notes = Format::sanitize($vars['notes']);

        if ($this->save())
            return true;

        if ($id) { //update
            $errors['err'] = sprintf(__('Unable to update %s.'), __('this email'))
               .' '.__('Internal error occurred');
        } else {
            $errors['err'] = sprintf(__('Unable to add %s.'), __('this email'))
               .' '.__('Internal error occurred');
        }

        return false;
    }

   static function getIdByEmail($email) {
        $qs = static::objects()->filter(Q::any(array(
                        'email'  => $email,
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

    static function getAddresses($options=array(), $flat=true) {
        $objects = static::objects();
        if ($options['smtp'])
            $objects = $objects->filter(array('smtp__active' => 1));

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

    static function getPermissions() {
        return self::$perms;
    }

    // Supported Remote Mailbox protocols
    static function mailboxProtocols() {
        return [
            'IMAP' => 'IMAP',
            'POP'  => 'POP'];
    }
}
RolePermission::register(/* @trans */ 'Miscellaneous', Email::getPermissions());

class EmailAccount extends VerySimpleModel {
    static $meta = array(
        'table' => EMAIL_ACCOUNT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'email' => array(
                'constraint' => array('email_id' => 'Email.email_id'),
             ),
        ),
    );

    private $bkId;
    private $form;
    private $cred;
    private $config;
    private $instance;
    // If account supports tls or ssl
    private $encryption = false;
    // Account settings
    private $settings;


    public function getAccountSetting($stashed=false) {

        if (!isset($this->settings)) {
            // Set properties to stashed form data (if any and requested)
            if ($stashed && ($info=$this->getInfo())) {
                foreach (['host', 'port', 'protocol'] as $p) {
                    $k = "{$this->type}_$p";
                    if (isset($info[$k]))
                        $this->{$p} = $info[$k];
                }
            }
            $this->settings = new osTicket\Mail\AccountSetting($this);
        }
        return $this->settings;
    }

    public function getHostInfo() {
        return $this->getAccountSetting()->getHostInfo();
    }

    public function getHost() {
        return $this->host;
    }

    public function getPort() {
        return $this->port;
    }

    public function getProtocol() {
        return $this->protocol;
    }

    public function getNumErrors() {
        return $this->num_errors;
    }

    public function isOAuthAuth() {
        return str_starts_with($this->getAuthBk(), 'oauth');
    }

    public function isBasicAuth() {
        return str_starts_with($this->getAuthBk(), 'basic');
    }

    public function isActive() {
        return ($this->active  && $this->hasCredentials());
    }

    // **** Don't use it  ****
    // This routine is depricated and will be removed in the future - OAuth2
    // Plugin uses it to check if the email accout has auth2 backend.
    public function isEnabled() {
        return $this->isOAuthAuth();
    }

    public function isAuthBackendEnabled() {
        return $this->isOAuthAuth()
            ? (($i=$this->getOAuth2Instance()) && $i->isEnabled())
            : true;
    }

    public function isStrict() {
        return $this->getConfig()->getStrictMatching();
    }

    public function checkStrictMatching($token=null) {
        $token ??= $this->getAccessToken();
        return ($token && $token->isMatch(
                $this->getEmail()->getEmail(),
                $this->isStrict()));
    }

    public function shouldAuthorize() {
        // check status and make sure it's oauth
        if (!$this->isAuthBackendEnabled() || !$this->isOAuthAuth())
            return false;

        return (!($cred=$this->getFreshCredentials())
                // Get token with signature match - mismatch means config
                // changed somehow
                || !($token=$cred->getAccessToken($this->getConfigSignature()))
                // Check if expired
                || $token->isExpired()
                // If Strict Matching is enabled ensure the email matches
                // the Resource Owner
                || !$this->checkStrictMatching($token));

    }

    public function getId() {
        return $this->id;
    }

    public function getType() {
        return $this->type;
    }

    public function getAuthBk() {
        return $this->auth_bk;
    }

    public function getAuthId() {
        return $this->auth_id;
    }

    public function getBkId() {
        if  (!isset($this->bkId)) {
            $id = sprintf('%s:%d',
                $this->getAuthBk(), $this->getId());
            if ($this->isOAuthAuth())
                $id .= sprintf(':%d:%b',
                    $this->getAuthId(), $this->isStrict()); #TODO: Remove strict and delegate to email account

            $this->bkId = $id;
        }
        return $this->bkId;
    }

    public function getEmailId() {
        return $this->email_id;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getName() {
        return $this->getEmail()->getName();
    }

    public function getAccessToken() {
        $cred = $this->getFreshCredentials();
        return $cred ? $cred->getAccessToken($this->getConfigSignature()) : null;
    }

    private function getOAuth2Backend($auth=null) {
        $auth = $auth ?: $this->getAuthBk();
        return Oauth2AuthorizationBackend::getBackend($auth);
    }

    public function getOAuth2ConfigDefaults() {
        $email  = $this->getEmail();
        return  [
            'auth_type' => 'autho',
            'auth_name' => $email->getName(),
            'name' => sprintf('%s (%s)',
                    $email->getEmail(), $this->getType()),
            'isactive' => 1,
            'strict_matching' => $this->isStrict(),
            'notes' => sprintf(
                    __('OAuth2 Authorization for %s'), $email->getEmail()),
        ];
    }

    public function getOAuth2ConfigInfo()  {
        $vars = $this->getOAuth2ConfigDefaults();
        if (($i=$this->getOAuth2Instance()))
            $vars = array_merge($vars, $i->getInfo());
        return $vars;
    }

    private function getOAuth2ConfigForm($vars, $auth=null) {
        // Lookup OAuth2 backend & Get basic config form
         if (($bk=$this->getOAuth2Backend($auth)))
             return $bk->getConfigForm(
                     array_merge(
                         $this->getOAuth2ConfigDefaults(),
                         $vars ?: $bk->getDefaults() #nolint
                         ),
                     !strcmp($auth, $this->getAuthBk())
                     ? $this->getAuthId() : null
                     );
    }

    private function getBasicAuthConfigForm($vars, $auth=null) {
        $creds = $this->getCredentialsVars($auth) ?: [];
        if (!$vars &&  $creds) {
            $vars = [
                'username' => $creds['username'],
                'passwd' => $creds['password'],
            ];
        } elseif (!$_POST && !isset($vars['username']) && $this->email)
            $vars['username'] = $this->email->getEmail();

        if (!isset($vars['passwd']) && $_POST && $creds)
            $vars['passwd'] = $creds['password'];

        return new BasicAuthConfigForm($vars);
    }

    public function getOAuth2Instance($bk=null) {
        $bk = $bk ?: $this->getOAuth2Backend();
        if (!isset($this->instance) && $this->getAuthId() && $bk)
            $this->instance = $bk->getPluginInstance($this->getAuthId());

        return $this->instance;
    }

    public function getConfigSignature() {
        if (($i=$this->getOAuth2Instance()))
            return $i->getSignature();
    }

    public function getInfo() {
        $ht = array();
        foreach (static::$vars as $var) {
            if (isset($this->ht[$var]))
                $ht[$this->type.'_'.$var] = $this->ht[$var];
        }
        // Add stashed info (if any)
        if (($data=$this->email->restoreFormData(false)))
            $ht = array_merge($ht, $data);


        return $ht;
    }

    private function getNamespace() {
        return sprintf('email.%d.account.%d',
                 $this->getEmailId(),
                 $this->getId());
    }

    protected function getConfig() {
        if (!isset($this->config))
            $this->config = new EmailAccountConfig($this->getNamespace());
        return $this->config;
    }

    public function getAuthConfigForm($auth, $vars=false) {
        if (!isset($this->form) || strcmp($auth, $this->getAuthBk())) {
            list($type, $provider) = explode(':', $auth);
            switch ($type) {
                case 'oauth2':
                    $this->form = $this->getOAuth2ConfigForm($vars, $auth);
                    break;
                case 'basic':
                     $this->form = $this->getBasicAuthConfigForm($vars,
                             $auth);
                     $setting =  $this->getAccountSetting(true);
                     if (!$setting  || !$setting->isValid())
                         $this->form->setNotice(
                                 __('Host, Port & Protocol Required'));
                    break;
            }

        }
        return $this->form;
    }

    public function saveAuth($auth, $form, &$errors) {
        // Validate the form
        if (!$form->isValid())
            return false;
        $vars = $form->getClean();
        list($type, $provider) = explode(':', $auth);
        switch ($type) {
            case 'basic':
                // Set username and password
                if (!$this->updateCredentials($auth, $vars, $errors)
                    && !isset($errors['err']))
                    $errors['err'] = sprintf('%s %s',
                            __('Error Saving'),
                            __('Authentication'));
                break;
            case 'oauth2':
                // For OAuth we are simply saving configuration -
                // credetials are saved post successful authorization
                // redirect.

                // Lookup OAuth backend
                if (($bk=$this->getOAuth2Backend($auth))) {
                    // Merge form data, post vars and any defaults
                    $vars = array_merge($this->getOAuth2ConfigDefaults(),
                            array_intersect_key($_POST, $this->getOAuth2ConfigDefaults()),
                            $vars);
                    // Update or add OAuth2 instance
                    if ($this->getAuthId()
                            && ($i=$bk->getPluginInstance($this->getAuthId()))) {
                        $vars = array_merge($bk->getDefaults(), $vars); #nolint
                        if ($i->update($vars, $errors)) {
                            // Disable account if backend is changed
                            if (strcasecmp($this->auth_bk, $auth))
                                $this->active = 0;
                            // Auth backend can be changed on update
                            $this->auth_bk = $auth;
                            $this->save();
                            // Update Strict Matching
                            $this->getConfig()->setStrictMatching($_POST['strict_matching'] ? 1 : 0);
                        } elseif (!isset($errors['err'])) {
                            $errors['err'] = sprintf('%s %s',
                                    __('Error Saving'),
                                    __('Authentication'));
                        }
                    } else {
                        // Ask the backend to add OAuth2 instance for this account
                        if (($i=$bk->addPluginInstance($vars, $errors))) { #nolint
                            // Cache instance
                            $this->instance = $i;
                            $this->auth_bk = $auth;
                            $this->auth_id = $i->getId();
                            $this->save();
                        } else {
                            $errors['err'] = __('Error Adding OAuth2 Instance');
                        }
                    }
                }
                break;
             default:
                 $errors['err'] = __('Unknown Authentication Type');
         }
         return !($errors);
    }

    public function logError($error) {
        return $this->logActivity($error);
    }

    public function hasCredentials() {
        return ($this->getFreshCredentials());
    }

    private function getCredentialsVars($auth=null) {
        $vars = [];
        if (($cred = $this->getCredentials($auth)))
            $vars = $cred->toArray();

        return $vars;
    }

    public function getFreshCredentials($auth=null) {
        return $this->getCredentials($auth, true);
    }

    public function getCredentials($auth=null, $refresh=false) {
        // Authentication doesn't match - it's getting reconfigured.
        if ($auth
                && strncasecmp($this->getAuthBk(), $auth, strlen($auth))
                && !in_array($auth, ['none', 'mailbox']))
            return [];

        if (!isset($this->cred) || $refresh)  {
            $this->cred = $cred = null;
            $auth = $auth ?: $this->getAuthBk();
            list($type, $provider) = explode(':', $auth);
            try {
                switch ($type) {
                    case 'mailbox':
                        $cred = $this->getMailBoxCredentials($refresh);
                        break;
                    case 'none':
                        // No authentication required (open replay)
                        $cred = new osTicket\Mail\NoAuthCredentials([
                                'username' => $this->email->getEmail()]);
                        break;
                    case 'basic':
                        $cred = $this->getBasicAuthCredentials();
                        break;
                    case 'oauth2':
                        $cred = $this->getOAuth2AuthCredentials($provider, $refresh);
                        break;
                    default:
                        throw new Exception(sprintf('%s: %s',
                                    $type, __('Unknown Credential Type')));
                }
                // Cache the credentials
                $this->cred = $cred;
            } catch (Exception $ex) {
                // Log the error
                $this->logError(sprintf('%s: %s',
                            __('Credentials'), $ex->getMessage()
                            ));
            }
        }
        return $this->cred;
    }

    private function getMailBoxCredentials($refresh=false) {
        if (($mb=$this->email->getMailBoxAccount())
                && $mb->getAuthBk())
            return $mb->getCredentials($mb->getAuthBk(), $refresh);
    }

    private function getBasicAuthCredentials() {
        if (($c=$this->getConfig())
                && ($creds=$c->toArray())
                && isset($creds['username'])
                && isset($creds['passwd'])) {
            return  new osTicket\Mail\BasicAuthCredentials([
                    'username' => $creds['username'],
                    // Decrypt password
                    'password' => Crypto::decrypt($creds['passwd'],
                        SECRET_SALT,
                        md5($creds['username'].$this->getNamespace()))
            ]);
        }
    }

    private function updateBasicAuthCredentials($vars, &$errors) {
        // Get current credentials - we need to re-encrypt
        // password as username might be changing
        $creds = $this->getCredentialsVars('basic');
        // password change?
        if (!$vars['username']) {
            $errors['username'] = __('Username Required');
        } elseif (!$vars['passwd'] && !$creds['password']) {
            $errors['passwd'] = __('Password Required');
        } elseif (($setting=$this->getAccountSetting(true))
                && !$setting->isValid()) {
            $errors['err'] = implode(', ', $setting->getErrors());
        } elseif ($setting && !$errors) {
            // Validate the credentials
            try {
                $cred = new osTicket\Mail\BasicAuthCredentials([
                        'username' => $vars['username'],
                        'password' => $vars['passwd'] ?:
                            $creds['password'],
                ]);
                if (!$this->validateCredentials($cred))
                    $errors['err'] = __('Invalid Credentials');
            } catch (Exception $ex) {
                 $errors['err'] = $ex->getMessage();
            }
        }

        if (!$errors) {
            // Save credentials and get out of here.
            $info = [
                // username
                'username' => $vars['username'],
                // Encrypt  password
                'passwd'   => Crypto::encrypt($vars['passwd'] ?:
                        $creds['password'],  SECRET_SALT,
                         md5($vars['username'].$this->getNamespace()))
            ];

            if (!$this->getConfig()->updateInfo($info))
                $errors['err'] = sprintf('%s: %s',
                        __('BasicAuth'),
                        __('Error saving credentials'));
        }
        return !count($errors);
    }

    private function getOAuth2AuthCredentials($provider, $refresh=false) {
        if (!($c=$this->getConfig()))
            return false;

        $creds=$c->toArray();
        // Decrypt Access Token
        if ($creds['access_token']) {
            $creds['access_token'] = Crypto::decrypt(
                    $creds['access_token'],
                    SECRET_SALT,
                    md5($creds['resource_owner_email'].$this->getNamespace())
                    );
        }

        // Decrypt Referesh Token
        if ($creds['refresh_token']) {
            $creds['refresh_token'] = Crypto::decrypt(
                    $creds['refresh_token'],
                    SECRET_SALT,
                    md5($creds['resource_owner_email'].$this->getNamespace())
                    );
        }

        try {
            // Init credentials and see of we need to
            // refresh the token
            $errors = [];
            $auth = sprintf('oauth2:%s', $provider);
            $class = 'osTicket\Mail\OAuth2AuthCredentials';
            if (($cred=$class::init($creds))
                    && ($token=$cred->getToken())
                    && ($refresh && $token->isExpired())
                    && ($bk=$this->getOAuth2Backend())
                    && ($info=$bk->refreshAccessToken( #nolint
                            $token->getRefreshToken(),
                            $this->getBkId(), $errors))
                    && isset($info['access_token'])
                    && $this->updateCredentials($auth,
                        // Merge new access token with
                        // already decrypted creds
                        array_merge($creds, $info), $errors
                        )) {
                    return $this->getCredentials($auth, $refresh);
            } elseif ($errors) {
                // Throw an exception with the error
                throw new Exception($errors['refresh_token']
                        ?? __('Referesh Token Expired'));
            }
        } catch (Exception $ex) {
            // rethrow the exception including above.
            throw $ex;
        }
        return $cred;
    }

    private function updateOAuth2AuthCredentials($provider, $vars, &$errors) {
        if (!$vars['access_token']) {
            $errors['access_token'] = __('Access Token Required');
        } elseif (!$vars['resource_owner_email']
                || !Validator::is_email($vars['resource_owner_email'])) {
            $errors['resource_owner_email'] =
                __('Resource Owner Required');

        } elseif (!$errors) {
            // Encrypt Access Token
            $vars['access_token'] = Crypto::encrypt(
                    $vars['access_token'],
                     SECRET_SALT,
                     md5($vars['resource_owner_email'].$this->getNamespace()));
             // Encrypt Referesh Token
            if ($vars['refresh_token']) {
                $vars['refresh_token'] = Crypto::encrypt(
                        $vars['refresh_token'],
                        SECRET_SALT,
                        md5($vars['resource_owner_email'].$this->getNamespace())
                        );
            }
            $vars['config_signature'] = $this->getConfigSignature();
            // TODO: Validate
            if (!$this->getConfig()->updateInfo($vars))
                $errors['err'] = sprintf('oauth2:%s - %s',
                         Format::htmlchars($provider),
                         __('Error saving credentials'));
        }
        return !count($errors);
    }

    public function updateCredentials($auth, $vars, &$errors) {
        if (!$vars || $errors)
            return false;

        list($type, $provider) = explode(':', $auth);
        switch ($type) {
            case 'basic':
                if (!($this->updateBasicAuthCredentials($vars, $errors)))
                    return false;
                break;
            case 'oauth2':
                if (!($this->updateOAuth2AuthCredentials($provider, $vars, $errors)))
                    return false;
                break;
            default:
                 $errors['err'] =  sprintf('%s - %s',
                         __('Unknown Authentication'),
                         Format::htmlchars($auth));
        }

        if ($errors)
            return false;

        // Save the auth backend
        $this->auth_bk = $auth;
        // Clear cached credentials
        $this->creds = null;
        return $this->save();
    }

    /*
     * Destory the account config
     */
    function destroyConfig() {
        return $this->getConfig()->destroy();
    }

    function update($vars, &$errors) {
        return false;
    }

    public function logActivity($error=null, $now=null) {
        if (isset($error)) {
            $this->num_errors += 1;
            $this->last_error_msg = $error;
            $this->last_error = $now ?: SqlFunction::NOW();
        } else {
            $this->num_errors = 0;
            $this->last_error = null;
            $this->last_error_msg = null;
            $this->last_activity =  $now ?: SqlFunction::NOW();
        }
        return $this->save();
    }

    function save($refetch=false) {
        if ($this->dirty) {
            $this->updated = SqlFunction::NOW();
        }
        return parent::save($refetch || $this->dirty);
    }

    function delete() {
        // Destroy the Email config
        $this->destroyConfig();
        // Delete the Plugin instance
        if ($this->isOAuthAuth() && ($i=$this->getOAuth2Instance()))
            $i->delete();
        // Delete the EmailAccount
        parent::delete();
    }

    static function create($ht=false) {
        $i = new static($ht);
        $i->active = isset($ht['active']) ? $ht['active'] : 0;
        $i->created = SqlFunction::NOW();
        return $i;
    }
}

class MailBoxAccount extends EmailAccount {
    static $meta = array(
        'table' => EMAIL_ACCOUNT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'email' => array(
                'constraint' => array('email_id' => 'Email.email_id'),
             ),
            'account' => array(
                'constraint' => array(
                    'type' => "'mailbox'",
                    'email_id' => 'Email.email_id'),
            ),
        ),
    );
    static protected $vars = [
        'active', 'host', 'port', 'protocol', 'auth_bk', 'folder',
        'fetchfreq', 'fetchmax', 'postfetch', 'archivefolder'
    ];

    private $cred;
    private $mailbox;

    static public function objects() {
        return parent::objects()
            ->filter(['type' => 'mailbox']);
    }

    //TODO: Morhp MailBoxAccount to ImapMailBoxAccount
    public function isImap() {
        return (strcasecmp($this->getProtocol(), 'IMAP') === 0);
    }

     //TODO: Morhp MailBoxAccount to PopMailBoxAccount
    public function isPop() {
        return (strcasecmp($this->getProtocol(), 'POP') === 0);
    }

    public function getProtocol() {
        return $this->protocol;
    }

    public function getFolder() {
        return $this->folder;
    }

    public function getFetchFolder() {
        return $this->getFolder();
    }

    public function getArchiveFolder() {
        return ($this->isImap() && $this->archivefolder)
            ? $this->archivefolder : null;
    }

    public function canDeleteEmails() {
        return !strcasecmp($this->postfetch, 'delete');
    }

    public function getMaxFetch() {
        return $this->fetchmax;
    }

    protected function validateCredentials(osTicket\Mail\AuthCredentials $creds) {
        return $this->getMailBox($creds);
    }

    public function getMailBox(osTicket\Mail\AuthCredentials $cred=null) {
        if (!isset($this->mailbox) || $cred) {
            $this->cred = $cred ?: $this->getFreshCredentials();
            $setting = $this->getAccountSetting();
            $setting->setCredentials($this->cred);
            switch  (strtolower($this->getProtocol())) {
                case 'imap':
                    $mailbox = new osTicket\Mail\Imap($setting);
                    break;
                case 'pop3':
                case 'pop':
                    $mailbox = new osTicket\Mail\Pop3($setting);
                    break;
                default:
                    throw new Exception('Unknown Mail protocol:
                            '.$this->getProtocol());
            }
            $this->mailbox = $mailbox;
        }
        return $this->mailbox;
    }

    public function fetchEmails() {
        try {
            $this->logLastFetch();
            $fetcher = new osTicket\Mail\Fetcher($this);
            return $fetcher->processEmails();
        } catch (Throwable $t) {
            // May throw an Exception or Error
            // Log the message
            $this->logFetchError($t->getMessage());
           // rethrow the throwable so caller can handle it
            throw $t;
        }
        return 0;
    }

    protected function setInfo($vars, &$errors) {
        $creds = null;
        if ($vars['mailbox_active']) {
            if (!$vars['mailbox_host'])
                $errors['mailbox_host'] = __('Host name required');
            if (!$vars['mailbox_port'])
                $errors['mailbox_port'] = __('Port required');
            if (!$vars['mailbox_protocol'])
                $errors['mailbox_protocol'] = __('Select protocol');
            elseif (!in_array($vars['mailbox_protocol'], Email::mailboxProtocols()))
                $errors['mailbox_protocol'] = __('Invalid protocol');
            if (!$vars['mailbox_auth_bk'])
                $errors['mailbox_auth_bk'] = __('Select Authentication');
            if (!$vars['mailbox_fetchfreq'] || !is_numeric($vars['mailbox_fetchfreq']))
                $errors['mailbox_fetchfreq'] = __('Fetch interval required');
            if (!$vars['mailbox_fetchmax'] || !is_numeric($vars['mailbox_fetchmax']))
                $errors['mailbox_fetchmax'] = __('Maximum emails required');
            if ($vars['mailbox_protocol'] == 'POP' && !empty($vars['mailbox_folder']))
                $errors['mailbox_folder'] = __('POP mail servers do not support folders');
            if (!$vars['mailbox_postfetch'])
                $errors['mailbox_postfetch'] = __('Indicate what to do with fetched emails');
        }

        if (!strcasecmp($vars['mailbox_postfetch'], 'archive')) {
            if ($vars['mailbox_protocol'] == 'POP')
                $errors['mailbox_postfetch'] =  __('POP mail servers do not support folders');
            elseif (!$vars['mailbox_archivefolder'])
                $errors['mailbox_postfetch'] = __('Valid folder required');
            elseif (!strcasecmp($vars['mailbox_folder'],
                        $vars['mailbox_archivefolder']))
                $errors['mailbox_postfetch'] = __('Archive folder cannot be same as fetched folder (INBOX)');
        }

        // Make sure authentication is configured if selection is made
        if ($vars['mailbox_auth_bk']
                && !($creds=$this->getFreshCredentials($vars['mailbox_auth_bk'])))
            $errors['mailbox_auth_bk'] = __('Configure Authentication');

        if (!$errors) {
            $this->active = $vars['mailbox_active'] ? 1 : 0;
            $this->host = $vars['mailbox_host'];
            $this->port = $vars['mailbox_port'] ?: 0;
            $this->protocol = $vars['mailbox_protocol'];
            $this->auth_bk = $vars['mailbox_auth_bk'] ?: null;
            $this->folder = $vars['mailbox_folder'] ?: null;
            $this->fetchfreq = $vars['mailbox_fetchfreq'] ?: 5;
            $this->fetchmax = $vars['mailbox_fetchmax'] ?: 30;
            $this->postfetch =  $vars['mailbox_postfetch'];
            $this->last_activity = null;
            $this->last_error_msg = null;
            $this->num_errors = 0;
            //Post fetch email handling...
            switch ($vars['mailbox_postfetch']) {
                case 'archive':
                    $this->archivefolder = $vars['mailbox_archivefolder'];
                    break;
                case 'delete':
                default:
                    $this->archivefolder = null;
            }
            // If mailbox is active and we have credentials then attemp to
            // authenticate
            if ($this->active && $creds) {
                try {
                    // Get mailbox (Storage Backend)
                    if (($mb=$this->getMailBox($creds))) {
                        if  ($this->folder &&
                                !$mb->hasFolder($this->folder))
                            $errors['mailbox_folder'] = __('Unknown Folder');
                        if ($this->archivefolder
                                && $this->isImap()
                                && !$mb->hasFolder($this->archivefolder)
                                && !$mb->createFolder($this->archivefolder))
                            $errors['mailbox_archivefolder'] =
                                    __('Unable to create Folder');
                    } else {
                        $errors['mailbox_auth'] = __('Authentication Error');
                    }
                } catch (Exception $ex) {
                     $errors['mailbox_auth'] = $ex->getMessage();
                }
            }
        }
        return !$errors;
    }

    public function logLastFetch($now=null) {
        return $this->logActivity(null, $now);
    }

    private function logFetchError($error) {
        return $this->logActivity($error ?: __('Mail Fetch Error'));
    }

    public function update($vars, &$errors) {
        if (!$this->setInfo($vars, $errors))
            return false;

        return $this->save();
    }

    static function create($ht=false) {
        $i = parent::create($ht);
        $i->type = 'mailbox';
        return $i;
    }
}

class SmtpAccount extends EmailAccount {
    static $meta = array(
        'table' => EMAIL_ACCOUNT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'email' => array(
                'constraint' => array('email_id' => 'Email.email_id'),
             ),
            'account' => array(
                'constraint' => array(
                    'type' => "'smtp'",
                    'email_id' => 'Email.email_id'),
            ),
        ),
    );

    static protected $vars = [
        'active', 'host', 'port', 'protocol', 'auth_bk', 'allow_spoofing'
    ];
    private $smtp;
    private $cred;

    static public function objects() {
        return parent::objects()
            ->filter(['type' => 'smtp']);
    }

    public function isMailboxAuth() {
        return (strcasecmp($this->getAuthBk(), 'mailbox') === 0);
    }

    /*
     * Check if using mailbox auth and MailboxAccount exists if so
     * return the MailboxAccount config, otherwise return it's own
     * config
     */
    protected function getConfig() {
        if ($this->isMailboxAuth()
                && ($email=$this->getEmail())
                && ($account=$email->getMailBoxAccount()))
            return $account->getConfig();
        return parent::getConfig();
    }

    public function allowSpoofing() {
        return ($this->allow_spoofing);
    }

    protected function validateCredentials(osTicket\Mail\AuthCredentials $creds) {
        return $this->getSmtp($creds);
    }

    public function getSmtpConnection() {
        $this->smtp = $this->getSmtp();
        if (!$this->smtp->connect())
            return false;

        return $this->smtp;
    }

    public function getSmtp(osTicket\Mail\AuthCredentials $cred=null) {
        if (!isset($this->smtp) || $cred) {
            $this->cred = $cred ?: $this->getFreshCredentials();
            if ($this->cred) {
                $setting = $this->getAccountSetting();
                $setting->setCredentials($this->cred);
                $smtpOptions = new osTicket\Mail\SmtpOptions($setting);
                $smtp = new osTicket\Mail\Smtp($smtpOptions);
                // Attempt to connect now if credentials are sent in
                if ($cred) $smtp->connect();
                $this->smtp = $smtp;
            }
        }
        return $this->smtp;
    }

    protected function setInfo($vars, &$errors) {
        $creds = null;
        $_errors = [];
        if ($vars['smtp_active']) {
            if (!$vars['smtp_host'])
                $_errors['smtp_host'] = __('Host name required');
            if (!$vars['smtp_port'])
                $_errors['smtp_port'] = __('Port required');
            if (!$vars['smtp_auth_bk'])
                $_errors['smtp_auth_bk'] = __('Select Authentication');
            elseif (!($creds=$this->getFreshCredentials($vars['smtp_auth_bk'])))
                $_errors['smtp_auth_bk'] = ($vars['smtp_auth_bk'] == 'mailbox')
                    ? __('Configure Mailbox Authentication')
                    : __('Configure Authentication');
        } elseif ($vars['smtp_auth_bk']
                // We default to mailbox - so we're not going to check
                // unless account is active, see above!
                && strcasecmp($vars['smtp_auth_bk'], 'mailbox')
                && !($creds=$this->getFreshCredentials($vars['smtp_auth_bk'])))
            $_errors['smtp_auth_bk'] = __('Configure Authentication');

        // Check if set to active and using mailbox auth, if so check strict
        // matching.
        if ($vars['smtp_active'] == 1
                && ($vars['smtp_auth_bk'] === 'mailbox')
                && (strpos($vars['auth_bk'], 'oauth2') === 0)
                && !$this->checkStrictMatching())
            $_errors['smtp_auth_bk'] = sprintf('%s and %s', __('Resource Owner'), __('Email Mismatch'));

        if (!$_errors) {
            $this->active = $vars['smtp_active'] ? 1 : 0;
            $this->host = $vars['smtp_host'];
            $this->port = $vars['smtp_port'] ?: 0;
            $this->auth_bk = $vars['smtp_auth_bk'] ?: null;
            $this->protocol = 'SMTP';
            $this->allow_spoofing = $vars['smtp_allow_spoofing'] ? 1 : 0;
            $this->last_activity = null;
            $this->last_error_msg = null;
            $this->num_errors = 0;
            // If account is active then attempt to authenticate
            if ($this->active && $creds) {
                try {
                    if (!($smtp=$this->getSmtp($creds)))
                        $_errors['smtp_auth'] = __('Authentication Error');
                } catch (Exception $ex) {
                     $_errors['smtp_auth'] = $ex->getMessage();
                }
            }
        }
        $errors = array_merge($errors, $_errors);
        return !$errors;
    }

    function update($vars, &$errors) {
        if (!$this->setInfo($vars, $errors))
            return false;
        return $this->save();
    }

    static function create($ht=false) {
        $i = parent::create($ht);
        $i->type = 'smtp';
        return $i;
    }
}


/*
 * Email Config Store
 *
 * Extends base central config store
 *
 */
class EmailAccountConfig extends Config {
    /*
     * Get strict matching (default: true)
     */
    public function getStrictMatching() {
        return $this->get('strict_matching', true);
    }

    /*
     * Set strict matching
     */
    public function setStrictMatching($mode) {
        return $this->set('strict_matching', !!$mode);
    }

    public function updateInfo($vars) {
        return parent::updateAll($vars);
    }
}

/*
 * Basic Authentication Configuration Form
 *
 */
class BasicAuthConfigForm extends AbstractForm {
    private $account;
    private $host;
    private $password;

    function __construct($source=null, $options=array()) {
        parent::__construct($source, $options);
    }

    private function getPassword() {
        return $this->_source['passwd'];
    }

    function buildFields() {
        $password = $this->getPassword();
        return array(
            'username' => new TextboxField(array(
                'required' => true,
                'label' => __('Username'),
                'configuration' => array(
                    'length' => 0,
                    'autofocus' => true,
                ),
            )),
            'passwd' => new PasswordField(array(
                'label' => __('Password'),
                'required' => !$password,
                'validator' => 'noop',
                'hint' => $password
                    ? __('Enter a new password to change current one')
                    : '',
                'configuration' => array(
                    'length' => 0,
                    'classes' => 'span12',
                    'placeholder' => $password
                            ? str_repeat('•', strlen($password)*2)
                            : __('Password'),
                ),
            )),
        );
    }
}
?>
