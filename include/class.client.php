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
require_once INCLUDE_DIR.'class.user.php';

abstract class TicketUser
implements EmailContact, ITicketUser, TemplateVariable {

    static private $token_regex = '/^(?P<type>\w{1})(?P<algo>\d+)x(?P<hash>.*)$/i';

    protected  $user;
    protected $_guest = false;

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
                return sprintf('%s/view.php?%s',
                        $cfg->getBaseUrl(),
                        Http::build_query(
                            array('auth' => $this->getTicket()->getAuthToken($this)),
                            false
                            )
                        );
                break;
        }

        return false;

    }

    // Required for Internationalization::getCurrentLanguage() in templates
    function getLanguage() {
        return $this->user->getLanguage();
    }

    static function getVarScope() {
        return array(
            'email' => __('Email address'),
            'name' => array('class' => 'PersonsName', 'desc' => __('Full name')),
            'ticket_link' => __('Auth. token used for auto-login'),
        );
    }

    function getId() { return ($this->user) ? $this->user->getId() : null; }
    function getEmail() { return ($this->user) ? $this->user->getEmail() : null; }

    static function lookupByToken($token) {

        //Expecting well formatted token see getAuthToken routine for details.
        $matches = array();
        if (!preg_match(static::$token_regex, $token, $matches))
            return null;

        //Unpack the user and ticket ids
        $matches +=unpack('Vuid/Vtid',
                Base32::decode(strtolower(substr($matches['hash'], 0, 13))));

        $user = null;
        if (!($ticket = Ticket::lookup($matches['tid'])))
            // Require a ticket for now
            return null;

        switch ($matches['type']) {
            case 'c': //Collaborator c
                if (($user = Collaborator::lookup($matches['uid']))
                        && $user->getTicketId() != $matches['tid'])
                    $user = null;
                break;
            case 'o': //Ticket owner
                if (($user = $ticket->getOwner())
                        && $user->getId() != $matches['uid']) {
                    $user = null;
                }
                break;
        }

        if (!$user
                || !$user instanceof ITicketUser
                || strcasecmp($ticket->getAuthToken($user, $matches['algo']), $token))
            return false;

        return $user;
    }

    static function lookupByEmail($email) {

        if (!($user=User::lookup(array('emails__address' => $email))))
            return null;

        return new EndUser($user);
    }

    function isOwner() {
        return $this instanceof TicketOwner;
    }

    function flagGuest() {
        $this->_guest = true;
    }

    function isGuest() {
        return $this->_guest;
    }

    function getUserId() {
        return $this->user->getId();
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

class  EndUser extends BaseAuthenticatedUser {

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
            return $this->getVar(substr($name, 3));

        return  $args
            ? call_user_func_array(array($this->user, $name), $args)
            : call_user_func(array($this->user, $name));
    }

    function getVar($tag) {
        $u = $this;
        // Traverse the $user properties of all nested user objects to get
        // to the User instance with the custom data
        while (isset($u->user)) {
            $u = $u->user;
            if (method_exists($u, 'getVar')) {
                if ($rv = $u->getVar($tag))
                    return $rv;
            }
        }
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

    function getUserType() {
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
        if (!($stats=$this->getTicketStats()))
            return 0;

        return $stats['open']+$stats['closed'];
    }

    function getNumOpenTickets() {
        return ($stats=$this->getTicketStats())?$stats['open']:0;
    }

    function getNumClosedTickets() {
        return ($stats=$this->getTicketStats())?$stats['closed']:0;
    }

    function getNumTopicTickets($topic_id) {
        return ($stats=$this->getTicketStats())?$stats['topics'][$topic_id]:0;
    }

    function getNumOrganizationTickets() {
        if (!($stats=$this->getTicketStats()))
            return 0;

        return $stats['org']['open']+$stats['org']['closed'];
    }
    function getNumOpenOrganizationTickets() {
        return ($stats=$this->getTicketStats())?$stats['org']['open']:0;
    }
    function getNumClosedOrganizationTickets() {
        return ($stats=$this->getTicketStats())?$stats['org']['closed']:0;
    }

    function getAccount() {
        if ($this->_account === false)
            $this->_account =
                ClientAccount::lookup(array('user_id'=>$this->getId()));

        return $this->_account;
    }

    function getLanguage($flags=false) {
        if ($acct = $this->getAccount())
            return $acct->getLanguage($flags);
    }

    private function getStats() {
        $basic = Ticket::objects()
            ->annotate(array('count' => SqlAggregate::COUNT('ticket_id')))
            ->values('status__state', 'topic_id', 'user__org_id');

        $q = new Q(); $q->union();
        if ($this->getOrgId())
            $q->add(array('user__org_id' => $this->getOrgId()));

        // Share tickets among the organization for owners only
        $owners = clone $basic;
        $q->add(array('user_id' => $this->getId()));
        $owners->filter($q);

        $collabs = clone $basic;
        $collabs->filter(array('thread__collaborators__user_id' => $this->getId()));

        // TODO: Implement UNION ALL support in the ORM

        $stats = array('open' => 0, 'closed' => 0, 'topics' => array());
        foreach (array($owners, $collabs) as $rs) {
            foreach ($rs as $row) {
                $stats[$row['status__state']] += $row['count'];
                if ($row['topic_id'])
                    $stats['topics'][$row['topic_id']] += $row['count'];
                if ($row['user__org_id'])
                    $stats['org'][$row['status__state']] += $row['count'];
            }
        }
        return $stats;
    }

    function onLogin($bk) {
        if ($account = $this->getAccount())
            $account->onLogin($bk);
    }
}

class ClientAccount extends UserAccount {

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

    function cancelResetTokens() {
        // TODO: Drop password-reset tokens from the config table for
        //       this user id
        $sql = 'DELETE FROM '.CONFIG_TABLE.' WHERE `namespace`="pwreset"
            AND `value`='.db_input('c'.$this->getUserId());
        if (!db_query($sql, false))
            return false;

        unset($_SESSION['_client']['reset-token']);
    }

    function onLogin($bk) {
        $this->setExtraAttr('browser_lang',
            Internationalization::getCurrentLanguage());
        $this->save();
    }

    function update($vars, &$errors) {
        global $cfg;

        $rtoken = $_SESSION['_client']['reset-token'];
        if ($vars['passwd1'] || $vars['passwd2'] || $vars['cpasswd'] || $rtoken) {

            if (!$vars['passwd1'])
                $errors['passwd1']=__('New password is required');
            elseif ($vars['passwd1'] && strlen($vars['passwd1'])<6)
                $errors['passwd1']=__('Password must be at least 6 characters');
            elseif ($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2']=__('Passwords do not match');

            if ($rtoken) {
                $_config = new Config('pwreset');
                if ($_config->get($rtoken) != 'c'.$this->getUserId())
                    $errors['err'] =
                        __('Invalid reset token. Logout and try again');
                elseif (!($ts = $_config->lastModified($rtoken))
                        && ($cfg->getPwResetWindow() < (time() - strtotime($ts))))
                    $errors['err'] =
                        __('Invalid reset token. Logout and try again');
            }
            elseif ($this->get('passwd')) {
                if (!$vars['cpasswd'])
                    $errors['cpasswd']=__('Current password is required');
                elseif (!$this->hasCurrentPassword($vars['cpasswd']))
                    $errors['cpasswd']=__('Invalid current password!');
                elseif (!strcasecmp($vars['passwd1'], $vars['cpasswd']))
                    $errors['passwd1']=__('New password MUST be different from the current password!');
            }
        }

        // Timezone selection is not required. System default is a valid
        // fallback

        if ($errors) return false;

        $this->set('timezone', $vars['timezone']);
        $this->set('dst', isset($vars['dst']) ? 1 : 0);
        // Change language
        $this->set('lang', $vars['lang'] ?: null);
        Internationalization::setCurrentLanguage(null);
        TextDomain::configureForUser($this);

        if ($vars['backend']) {
            $this->set('backend', $vars['backend']);
            if ($vars['username'])
                $this->set('username', $vars['username']);
        }

        if ($vars['passwd1']) {
            $this->set('passwd', Passwd::hash($vars['passwd1']));
            $info = array('password' => $vars['passwd1']);
            Signal::send('auth.pwchange', $this->getUser(), $info);
            $this->cancelResetTokens();
            $this->clearStatus(UserAccountStatus::REQUIRE_PASSWD_RESET);
        }

        return $this->save();
    }
}

// Used by the email system
interface EmailContact {
    // function getId()
    // function getName()
    // function getEmail()
}

interface ITicketUser {
/* PHP 5.3 < 5.3.8 will crash with some abstract inheritance issue
 * ------------------------------------------------------------
    function isOwner();
    function flagGuest();
    function isGuest();
    function getUserId();
    function getTicketId();
    function getTicket();
 */
}
?>
