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

        return $rv ?: false;
    }

    // Required for Internationalization::getCurrentLanguage() in templates
    function getLanguage() {
        return $this->user->getLanguage();
    }

    static function getVarScope() {
        return array(
            'email' => __('Email Address'),
            'name' => array('class' => 'PersonsName', 'desc' => __('Full Name')),
            'ticket_link' => __('Link to view the ticket'),
        );
    }

    function getVar($tag) {
        switch (strtolower($tag)) {
        case 'ticket_link':
            $ticket = $this->getTicket();
            return $this->getTicketLink(($ticket &&
                        !$ticket->getNumCollaborators()));
            break;
        }
    }

    function getTicketLink($authtoken=true) {
        global $cfg;

        $ticket = $this->getTicket();
        if ($authtoken
                && $ticket
                && $cfg->isAuthTokenEnabled()) {
            $qstr = array();
            $qstr['auth'] = $ticket->getAuthToken($this);
            return sprintf('%s/view.php?%s',
                        $cfg->getBaseUrl(),
                        Http::build_query($qstr, false)
                        );
        }

        return sprintf('%s/view.php?id=%s',
                $cfg->getBaseUrl(),
                $ticket ? $ticket->getId() : 0
                );
    }

    function getId() { return ($this->user) ? $this->user->getId() : null; }
    function getEmail() { return ($this->user) ? $this->user->getEmail() : null; }
    function getName() {
        return ($this->user) ? $this->user->getName() : null;
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

    function getEmailAddress() {
        $emailaddr =  (string) $this->getEmail();
        if (($name=$this->getName()))
            $emailaddr = sprintf('"%s" <%s>',
                    (string) $name,
                    $emailaddr);
        return $emailaddr;
    }

    function __toString() {
        return $this->getEmailAddress();
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

    function __toString() {
        return (string) $this->getName();
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

class EndUser extends BaseAuthenticatedUser {

    protected $user;
    protected $_account = false;
    protected $_stats;
    protected $topic_stats;

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

    function get2FABackend() {
        //TODO: support 2FA on client portal
        return null;
    }

    function getTicketStats() {
        if (!isset($this->_stats))
            $this->_stats = $this->getStats();

        return $this->_stats;
    }

    function getNumTickets($forMyOrg=false, $state=false) {
        $stats = $this->getTicketStats();
        $count = 0;
        $section = $forMyOrg ? 'myorg' : 'mine';
        foreach ($stats[$section] as $row) {
            if ($state && $row['status__state'] != $state)
                continue;
            $count += $row['count'];
        }
        return $count;
    }

    function getNumOpenTickets($forMyOrg=false) {
        return $this->getNumTickets($forMyOrg, 'open') ?: 0;
    }

    function getNumClosedTickets($forMyOrg=false) {
        return $this->getNumTickets($forMyOrg, 'closed') ?: 0;
    }

    function getNumTopicTickets($topic_id, $forMyOrg=false) {
        $stats = $this->getTicketStats();
        $section = $forMyOrg ? 'myorg' : 'mine';
        if (!isset($this->topic_stats)) {
            $this->topic_stats = array();
            foreach ($stats[$section] as $row) {
                $this->topic_stats[$row['topic_id']] += $row['count'];
            }
        }
        return $this->topic_stats[$topic_id];
    }

    function getNumTopicTicketsInState($topic_id, $state=false, $forMyOrg=false) {
        $stats = $this->getTicketStats();
        $count = 0;
        $section = $forMyOrg ? 'myorg' : 'mine';
        foreach ($stats[$section] as $row) {
            if ($topic_id != $row['topic_id'])
                continue;
            if ($state && $state != $row['status__state'])
                continue;
            $count += $row['count'];
        }
        return $count;
    }

    function getNumOrganizationTickets() {
        return $this->getNumTickets(true);
    }
    function getNumOpenOrganizationTickets() {
        return $this->getNumTickets(true, 'open');
    }
    function getNumClosedOrganizationTickets() {
        return $this->getNumTickets(true, 'closed');
    }

    function getAccount() {
        if ($this->_account === false)
            $this->_account =
                ClientAccount::lookup(array('user_id'=>$this->getId()));

        return $this->_account;
    }

    function getUser() {
        if ($this->user === false)
            $this->user = User::lookup($this->getId());

        return $this->user;
    }

    function getLanguage($flags=false) {
        if ($acct = $this->getAccount())
            return $acct->getLanguage($flags);
    }

    private function getStats() {
        global $cfg;
        $basic = Ticket::objects()
            ->annotate(array('count' => SqlAggregate::COUNT('ticket_id')))
            ->values('status__state', 'topic_id')
            ->distinct('status_id', 'topic_id');

        // Share tickets among the organization for owners only
        $mine = clone $basic;
        $collab = clone $basic;
        $mine->filter(array(
            'user_id' => $this->getId(),
        ));

        // Also add collaborator tickets to the list. This may seem ugly;
        // but the general rule for SQL is that a single query can only use
        // one index. Therefore, to scan two indexes (by user_id and
        // thread.collaborators.user_id), we need two queries. A union will
        // help out with that.
        if ($cfg->collaboratorTicketsVisibility())
            $mine->union($collab->filter(array(
                'thread__collaborators__user_id' => $this->getId(),
                Q::not(array('user_id' => $this->getId()))
            )));

        if ($orgid = $this->getOrgId()) {
            // Also generate a separate query for all the tickets owned by
            // either my organization or ones that I'm collaborating on
            // which are not part of the organization.
            $myorg = clone $basic;
            $myorg->values('user__org_id');
            $collab = clone $myorg;

            $myorg->filter(array('user__org_id' => $orgid));
            $myorg->union($collab->filter(array(
                'thread__collaborators__user_id' => $this->getId(),
                Q::not(array('user__org_id' => $orgid))
            )));
        }

        return array('mine' => $mine, 'myorg' => $myorg);
    }

    function onLogin($bk) {
        if ($account = $this->getAccount())
            $account->onLogin($bk);
    }
}

class ClientAccount extends UserAccount {

    function check_passwd($password, $autoupdate=true) {

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
        return $this->check_passwd($password, false);
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

        // FIXME: Updates by agents should go through UserAccount::update()
        global $thisstaff, $thisclient;
        if ($thisstaff)
            return parent::update($vars, $errors);

        $rtoken = $_SESSION['_client']['reset-token'];


		if ($rtoken) {
			$_config = new Config('pwreset');
			if ($_config->get($rtoken) != 'c'.$this->getUserId())
				$errors['err'] =
					__('Invalid reset token. Logout and try again');
			elseif (!($ts = $_config->lastModified($rtoken))
					&& ($cfg->getPwResetWindow() < (time() - strtotime($ts))))
				$errors['err'] =
					__('Invalid reset token. Logout and try again');
		} elseif ($vars['passwd1'] || $vars['passwd2'] || $vars['cpasswd']) {

            if (!$vars['passwd1'])
                $errors['passwd1']=__('New password is required');
            elseif ($vars['passwd1'] && strcmp($vars['passwd1'], $vars['passwd2']))
                $errors['passwd2']=__('Passwords do not match');
            elseif ($this->get('passwd')) {
                if (!$vars['cpasswd'])
                    $errors['cpasswd']=__('Current password is required');
                elseif (!$this->hasCurrentPassword($vars['cpasswd']))
                    $errors['cpasswd']=__('Invalid current password!');
            }

            // Check password policies
			if (!$errors) {
                try {
                    UserAccount::checkPassword($vars['passwd1'], @$vars['cpasswd']);
                } catch (BadPassword $ex) {
                    $errors['passwd1'] = $ex->getMessage();
                }
            }
        }

        // Timezone selection is not required. System default is a valid
        // fallback

        if ($errors) return false;

        $this->set('timezone', $vars['timezone']);
        // Change language
        $this->set('lang', $vars['lang'] ?: null);
        Internationalization::setCurrentLanguage(null);
        TextDomain::configureForUser($this);

        if ($vars['backend']) {
            $this->set('backend', $vars['backend']);
            if ($vars['username'])
                $this->set('username', Format::sanitize($vars['username']));
        }

        if ($vars['passwd1']) {
            $this->set('passwd', Passwd::hash($vars['passwd1']));
            $info = array('password' => $vars['passwd1']);
            Signal::send('auth.pwchange', $this->getUser(), $info);
            $this->cancelResetTokens();
            $this->clearStatus(UserAccountStatus::REQUIRE_PASSWD_RESET);
            // Clean sessions
            Signal::send('auth.clean', $this->getUser(), $thisclient);
        }

        return $this->save();
    }
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
