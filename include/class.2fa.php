<?php

/**
 * TwoFactorAuthentication backend
 *
 * Provides the basis of abstracting 2fa backends 

 * The authentication backend should define a validate() method which
 * receives a user and OPT.
 */
abstract class TwoFactorAuthenticationBackend {
    // Global registry
    static protected $registry = array();
    // Grace period in minutes before OTP is expired and user logged out
    // It's hardcoded to 6 minutes here but downstream backends can make it
    // configurable 
    protected $timeout = 6;

    // Maximum number of validation attempts before the user is logged out
    // It's hardcoded to 3 attempts here but downstream backends can make it
    // configurable
    protected $maxstrikes = 3;

    // Base properties
    static $id;
    static $name;
    static $desc;

    // Forms
    private $_setupform;
    private $_inputform;


    // Send OTP to user specified and stash it
    abstract function send($user);
    // validate OTP provided by user
    abstract function validate($form, $user);

    function getId() {
        return static::$id;
    }

    function getName() {
        return __(static::$name);
    }

    function getDescription() {
        return __(static::$desc);
    }

    function getTimeout() {
        return $this->timeout;
    }

    function getMaxStrikes() {
        return $this->maxstrikes;
    }

    protected function getSetupOptions() {
        return array();
    }

    protected function getInputOptions() {
        return array();
    }

    // stash OTP info in the session 
    protected function store($otp) {
       $store =  &$_SESSION['_2fa'][$this->getId()];
       $store = ['otp' => $otp, 'time' => time(), 'strikes' => 0];
       return $store;
    }

    // Validate OPT
    // On strict mode check strikes and timeout
    protected function _validate($otp, $strict=true) {
        $store = &$_SESSION['_2fa'][$this->getId()];
        // Track and check the attempts
        $store['strikes'] += 1;   
        if ($strict && $store['strikes'] > $this->getMaxStrikes())
            throw new ExpiredOTP(__('Too many attempts'));

        // Check timeout - if expired throw an exception.
        if ($strict 
                && ($timeout=$this->getTimeout())
                && ($store['time']+($timeout*60)) < time())
            throw new ExpiredOTP(__('Expired OTP'));

        // Check the OTP
        return (!strcmp($store['otp'], $otp));
    }

    // Called on a successfull validation for house keeping e.g clear 2fa
    // flags
    protected function onValidate($user) {
         $user->clear2FA();
    }

    // Get a form the user uses to setup 2fa
    function getSetupForm($data=null) {
        if (!$this->_setupForm) {
            $this->_setupForm = new SimpleForm($this->getSetupOptions(), $data);
        }
        return $this->_setupForm;
    }

    // Get a form the user uses to input OTP
    function getInputForm($data=null) {
        if (!$this->_inputForm) {
            $this->_inputForm = new SimpleForm($this->getInputOptions(), $data);
        }
        return $this->_inputForm;
    }

    static function register($class) {
        if (is_string($class) && class_exists($class))
            $class = new $class();

        if (!is_object($class)
                || !($class instanceof TwoFactorAuthenticationBackend))
            return false;

        return static::_register($class);
    }

    static function _register($class) {
        if (isset(static::$registry[$class::$id]))
            return false;

        static::$registry[$class::$id] = $class;
    }

    static function allRegistered() {
        return static::$registry;
    }

    static function getBackend($id) {

        if ($id
                && ($backends = static::allRegistered())
                && isset($backends[$id]))
            return $backends[$id];
    }

    static function lookup($id) {
        return static::getBackend($id);
    }
}

class ExpiredOTP extends Exception {}

/*
 * user type container classes to aid in registry segmentation
 *
 */
abstract class Staff2FABackend extends TwoFactorAuthenticationBackend {
    static private $_registry = array();
   
    static function _register($class) {
        if (isset(static::$_registry[$class::$id]))
            return false;

        static::$_registry[$class::$id] = $class;
    }

    static function allRegistered() {
        return array_merge(self::$_registry, parent::allRegistered());
    }


    abstract function send($user);
    abstract function validate($form, $user);
}

abstract class User2FABackend extends TwoFactorAuthenticationBackend {
    static private $_registry = array();

    static function _register($class) {
        if (isset(static::$_registry[$class::$id]))
            return false;

        static::$_registry[$class::$id] = $class;
    }

    static function allRegistered() {
        return array_merge(self::$_registry, parent::allRegistered());
    }

    abstract function send($user);
    abstract function validate($form, $user);
}

/*
 * Email2FABackend
 *
 * Email based two factor authentication. 
 * 
 * This is the default 2FA that works out of the box once users configure
 * it.
 *
 */

class Email2FABackend extends TwoFactorAuthenticationBackend {
    static $id = "2fa-email";
    static $name = /* @trans */ 'Email';
    static $desc = /* @trans */ 'Verification codes are sent by email';

    protected function getSetupOptions() {
        return array(
            'email' => new TextboxField(array(
                'id'=>2, 'label'=>__('Email Address'), 'required'=>true, 'default'=>'',
                'validator'=>'email', 'hint'=>__('Valid email address'),
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    protected function getInputOptions() {
        return array(
            'token' => new TextboxField(array(
                'id'=>1, 'label'=>__('Verification Code'), 'required'=>true, 'default'=>'',
                'validator'=>'number', 
                'hint'=>__('Please enter the code you were sent'),
                'configuration'=>array(
                    'size'=>40, 'length'=>40,
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'validator-error' => __('Invalid Code format'),
                    ),
            )),
        );
    }

    function validate($form, $user) {
        // Make sure form is valid and token exists
        if (!($form->isValid() 
                    && ($clean=$form->getClean())
                    && $clean['token']))
            return false;

        // upstream validation might throw an exception due to expired token
        // or too many attempts (timeout). It's the responsibility of the
        // caller to catch and handle such exceptions.
        if (!$this->_validate($clean['token']))
            return false;

        // Validator doesn't do house cleaning - it's our responsibility
        $this->onValidate($user);
        return true;
    }

    function send($user) {
        global $ost, $cfg;

        // Get backend configuration for this user
        if (!$cfg || !($info = $user->get2FAConfig($this->getId())))
            return false;

        // Email to send the OTP via
        if (!($email = $cfg->getAlertEmail() ?: $cfg->getDefaultEmail()))
            return false;

        // Generate OTP
        $otp = Misc::randNumber(6);
        // Stash it in the session
        $this->store($otp);

        $template = 'email2fa-staff';
        $content = Page::lookupByType($template);

        if (!$content)
           return new BaseError(/* @trans */ 'Unable to retrieve two factor authentication email template');

        $vars = array(
           'url' => $ost->getConfig()->getBaseUrl(),
           'otp' => $otp,
           'staff' => $user,
           'recipient' => $user,
       );

       $lang = $user->lang ?: $user->getExtraAttr('browser_lang');
       $msg = $ost->replaceTemplateVariables(array(
           'subj' => $content->getLocalName($lang),
           'body' => $content->getLocalBody($lang),
       ), $vars);

        $email->send($user->getEmail(), Format::striptags($msg['subj']),
           $msg['body']);

        // MD5 here is not meant to be secure here - just done to avoid plain leaks
        return md5($otp);
    }
}
// Register email2fa for both agents and users (parent class)
TwoFactorAuthenticationBackend::register('Email2FABackend');
?>
