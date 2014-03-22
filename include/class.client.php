<?php
/*********************************************************************
    class.client.php

    Handles everything about EndUser

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
abstract class TicketUser {

    static private $token_regex = '/^(?P<type>\w{1})(?P<algo>\d+)x(?P<hash>.*)$/i';

    protected  $user;

    function __construct($user) {
        $this->user = $user;
    }

    function __call($name, $args) {
        global $cfg;

        $rv = null;
        if($this->user && is_callable(array($this->user, $name)))
            $rv = $args
                ? call_user_func_array(array($this->user, $name), $args)
                : call_user_func(array($this->user, $name));

        if ($rv) return $rv;

        $tag =  substr($name, 3);
        switch (strtolower($tag)) {
            case 'ticket_link':
                return sprintf('%s/view.php?auth=%s',
                        $cfg->getBaseUrl(),
                        urlencode($this->getAuthToken()));
                break;
        }

        return false;

    }

    function sendAccessLink() {
        global $ost;

        if (!($ticket = $this->getTicket())
                || !($dept = $ticket->getDept())
                || !($email = $dept->getAutoRespEmail())
                || !($tpl = $dept->getTemplate()->getMsgTemplate('user.accesslink')))
            return;

        $vars = array(
            'url' => $ost->getConfig()->getBaseUrl(),
            'ticket' => $this->getTicket(),
            'recipient' => $this);

        $msg = $ost->replaceTemplateVariables($tpl->asArray(), $vars);
        $email->send($this->getEmail(), $msg['subj'], $msg['body']);
    }

    protected function getAuthToken($algo=1) {

        //Format: // <user type><algo id used>x<pack of uid & tid><hash of the algo>
        $authtoken = sprintf('%s%dx%s',
                ($this->isOwner() ? 'o' : 'c'),
                $algo,
                Base32::encode(pack('VV',$this->getId(), $this->getTicketId())));

        switch($algo) {
            case 1:
                $authtoken .= substr(base64_encode(
                            md5($this->getId().$this->getTicket()->getCreateDate().$this->getTicketId().SECRET_SALT, true)), 8);
                break;
            default:
                return null;
        }

        return $authtoken;
    }

    static function lookupByToken($token) {

        //Expecting well formatted token see getAuthToken routine for details.
        $matches = array();
        if (!preg_match(static::$token_regex, $token, $matches))
            return null;

        //Unpack the user and ticket ids
        $matches +=unpack('Vuid/Vtid',
                Base32::decode(strtolower(substr($matches['hash'], 0, 13))));

        $user = null;
        switch ($matches['type']) {
            case 'c': //Collaborator c
                if (($user = Collaborator::lookup($matches['uid']))
                        && $user->getTicketId() != $matches['tid'])
                    $user = null;
                break;
            case 'o': //Ticket owner
                if (($ticket = Ticket::lookup($matches['tid']))) {
                    if (($user = $ticket->getOwner())
                            && $user->getId() != $matches['uid'])
                        $user = null;
                }
                break;
        }

        if (!$user
                || !$user instanceof TicketUser
                || strcasecmp($user->getAuthToken($matches['algo']), $token))
            return false;

        return $user;
    }

    static function lookupByEmail($email) {

        if (!($user=User::lookup(array('emails__address' => $email))))
            return null;

        return new EndUser($user);
    }

    function isOwner() {
        return  ($this->user
                    && $this->user->getId() == $this->getTicket()->getOwnerId());
    }

    abstract function getTicketId();
    abstract function getTicket();
}

class TicketOwner extends  TicketUser {

    protected $ticket;

    function __construct($user, $ticket) {
        parent::__construct($user);
        $this->ticket = $ticket;
    }

    function getTicket() {
        return $this->ticket;
    }

    function getTicketId() {
        return $this->ticket->getId();
    }
}

/*
 * Decorator class for authenticated user
 *
 */

class  EndUser extends AuthenticatedUser {

    protected $user;
    protected $_account = false;

    function __construct($user) {
        $this->user = $user;
    }

    /*
     * Delegate calls to the user
     */
    function __call($name, $args) {

        if(!$this->user
                || !is_callable(array($this->user, $name)))
            return false;

        return  $args
            ? call_user_func_array(array($this->user, $name), $args)
            : call_user_func(array($this->user, $name));
    }

    function getId() {
        //We ONLY care about user ID at the ticket level
        if ($this->user instanceof Collaborator)
            return $this->user->getUserId();

        elseif ($this->user)
            return $this->user->getId();

        return false;
    }

    function getUserName() {
        //XXX: Revisit when real usernames are introduced  or when email
        // requirement is removed.
        return $this->user->getEmail();
    }

    function getRole() {
        return $this->isOwner() ? 'owner' : 'collaborator';
    }

    function getAuthBackend() {
        list($authkey,) = explode(':', $this->getAuthKey());
        return UserAuthenticationBackend::getBackend($authkey);
    }

    function getTicketStats() {

        if (!isset($this->ht['stats']))
            $this->ht['stats'] = $this->getStats();

        return $this->ht['stats'];
    }

    function getNumTickets() {
        return ($stats=$this->getTicketStats())?($stats['open']+$stats['closed']):0;
    }

    function getNumOpenTickets() {
        return ($stats=$this->getTicketStats())?$stats['open']:0;
    }

    function getNumClosedTickets() {
        return ($stats=$this->getTicketStats())?$stats['closed']:0;
    }

    function getAccount() {
        if ($this->_account === false)
            $this->_account =
                ClientAccount::lookup(array('user_id'=>$this->getId()));

        return $this->_account;
    }

    private function getStats() {

        $sql='SELECT count(open.ticket_id) as open, count(closed.ticket_id) as closed '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.TICKET_TABLE.' open
                ON (open.ticket_id=ticket.ticket_id AND open.status=\'open\') '
            .' LEFT JOIN '.TICKET_TABLE.' closed
                ON (closed.ticket_id=ticket.ticket_id AND closed.status=\'closed\')'
            .' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
                ON (collab.ticket_id=ticket.ticket_id
                    AND collab.user_id = '.db_input($this->getId()).' )'
            .' WHERE ticket.user_id = '.db_input($this->getId())
            .' OR collab.user_id = '.db_input($this->getId());

        return db_fetch_array(db_query($sql));
    }
}

require_once INCLUDE_DIR.'class.orm.php';
class ClientAccountModel extends VerySimpleModel {
    static $meta = array(
        'table' => USER_ACCOUNT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'user' => array(
                'null' => false,
                'constraint' => array('user_id' => 'UserModel.id')
            ),
        ),
    );
}

class ClientAccount extends ClientAccountModel {
    var $_options = null;
    var $timezone;

    const CONFIRMED             = 0x0001;
    const LOCKED                = 0x0002;
    const PASSWD_RESET_REQUIRED = 0x0004;

    function __onload() {
        if ($this->get('timezone_id')) {
            $this->timezone = Timezone::getOffsetById($this->ht['timezone_id']);
            $_SESSION['TZ_OFFSET'] = $this->timezone;
            $_SESSION['TZ_DST'] = $this->get('dst');
        }
    }

    function getId() {
        return $this->get('id');
    }

    function getUserId() {
        return $this->get('user_id');
    }

    function checkPassword($password, $autoupdate=true) {

        /*bcrypt based password match*/
        if(Passwd::cmp($password, $this->get('passwd')))
            return true;

        //Fall back to MD5
        if(!$password || strcmp($this->get('passwd'), MD5($password)))
            return false;

        //Password is a MD5 hash: rehash it (if enabled) otherwise force passwd change.
        if ($autoupdate)
            $this->set('passwd', Passwd::hash($password));

        if (!$autoupdate || !$this->save())
            $this->forcePasswdReset();

        return true;
    }

    function hasCurrentPassword($password) {
        return $this->checkPassword($password, false);
    }

    function hasPassword() {
        return (bool) $this->get('passwd');
    }

    function sendResetEmail($template='pwreset-client') {
        global $ost, $cfg;

        $token = Misc::randCode(48); // 290-bits

        $email = $cfg->getDefaultEmail();
        $content = Page::lookup(Page::getIdByType($template));

        if (!$email ||  !$content)
            return new Error('Unable to retrieve password reset email template');

        $vars = array(
            'url' => $ost->getConfig()->getBaseUrl(),
            'token' => $token,
            'recipient' => $this->getUser(),
            'link' => sprintf(
                "%s/pwreset.php?token=%s",
                $ost->getConfig()->getBaseUrl(),
                $token),
        );
        $vars['reset_link'] = &$vars['link'];

        $info = array('email' => $email, 'vars' => &$vars, 'log'=>true);
        Signal::send('auth.pwreset.email', $this, $info);

        $msg = $ost->replaceTemplateVariables(array(
            'subj' => $content->getName(),
            'body' => $content->getBody(),
        ), $vars);

        $_config = new Config('pwreset');
        $_config->set($vars['token'], $this->user->getId());

        $email->send($this->user->default_email->get('address'),
            Format::striptags($msg['subj']), $msg['body']);
    }

    function confirm() {
        $this->_setStatus(self::CONFIRMED);
        return $this->save();
    }

    function isConfirmed() {
        return $this->_getStatus(self::CONFIRMED);
    }

    function lock() {
        $this->_setStatus(self::LOCKED);
        $this->save();
    }

    function isLocked() {
        return $this->_getStatus(self::LOCKED);
    }

    function forcePasswdReset() {
        $this->_setStatus(self::PASSWD_RESET_REQUIRED);
        return $this->save();
    }

    function isPasswdResetForced() {
        return $this->_getStatus(self::PASSWD_RESET_REQUIRED);
    }

    function _getStatus($flag) {
        return 0 !== ($this->get('status') & $flag);
    }

    function _clearStatus($flag) {
        return $this->set('status', $this->get('status') & ~$flag);
    }

    function _setStatus($flag) {
        return $this->set('status', $this->get('status') | $flag);
    }

    function cancelResetTokens() {
        // TODO: Drop password-reset tokens from the config table for
        //       this user id
        $sql = 'DELETE FROM '.CONFIG_TABLE.' WHERE `namespace`="pwreset"
            AND `key`='.db_input($this->getUserId());
        if (!db_query($sql, false))
            return false;

        unset($_SESSION['_client']['reset-token']);
    }

    function getInfo() {
        $base = $this->ht;
        $base['tz_offset'] = $this->timezone;
        return $base;
    }

    function getUser() {
        $user = User::lookup($this->get('user_id'));
        $user->set('account', $this);
        return $user;
    }

    function update($vars, &$errors) {
        $rtoken = $_SESSION['_client']['reset-token'];
        if ($vars['passwd1'] || $vars['passwd2'] || $vars['cpasswd'] || $rtoken) {

            if (!$vars['passwd1'])
                $errors['passwd1']='New password required';
            elseif ($vars['passwd1'] && strlen($vars['passwd1'])<6)
                $errors['passwd1']='Must be at least 6 characters';
            elseif ($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2']='Password(s) do not match';

            if ($rtoken) {
                $_config = new Config('pwreset');
                if ($_config->get($rtoken) != $this->getUserId())
                    $errors['err'] =
                        'Invalid reset token. Logout and try again';
                elseif (!($ts = $_config->lastModified($rtoken))
                        && ($cfg->getPwResetWindow() < (time() - strtotime($ts))))
                    $errors['err'] =
                        'Invalid reset token. Logout and try again';
            }
            elseif ($this->get('passwd')) {
                if (!$vars['cpasswd'])
                    $errors['cpasswd']='Current password required';
                elseif (!$this->hasCurrentPassword($vars['cpasswd']))
                    $errors['cpasswd']='Invalid current password!';
                elseif (!strcasecmp($vars['passwd1'], $vars['cpasswd']))
                    $errors['passwd1']='New password MUST be different from the current password!';
            }
        }

        if (!$vars['timezone_id'])
            $errors['timezone_id']='Time zone required';

        if ($errors) return false;

        $this->set('timezone_id', $vars['timezone_id']);
        $this->set('dst', isset($vars['dst']) ? 1 : 0);

        if ($vars['passwd1']) {
            $this->set('passwd', Passwd::hash($vars['passwd1']));
            $info = array('password' => $vars['passwd1']);
            Signal::send('auth.pwchange', $this, $info);
            $this->cancelResetTokens();
            $this->_clearStatus(self::PASSWD_RESET_REQUIRED);
        }

        return $this->save();
    }

    static function createForUser($user) {
        return static::create(array('user_id'=>$user->getId()));
    }

    static function lookupByUsername($username) {
        if (strpos($username, '@') !== false)
            $user = self::lookup(array('user__emails__address'=>$username));
        else
            $user = self::lookup(array('username'=>$username));

        return $user;
    }
}
ClientAccount::_inspect();

?>
