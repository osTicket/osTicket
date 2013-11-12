<?php
require(INCLUDE_DIR.'class.ostsession.php');
require(INCLUDE_DIR.'class.usersession.php');

class AuthenticatedUser {
    // How the user was authenticated
    var $backend;

    // Get basic information
    function getId() {}
    function getUsername() {}
}

/**
 * Authentication backend
 *
 * Authentication provides the basis of abstracting the link between the
 * login page with a username and password and the staff member,
 * administrator, or client using the system.
 *
 * The system works by allowing the AUTH_BACKENDS setting from
 * ost-config.php to determine the list of authentication backends or
 * providers and also specify the order they should be evaluated in.
 *
 * The authentication backend should define a authenticate() method which
 * receives a username and optional password. If the authentication
 * succeeds, an instance deriving from <User> should be returned.
 */
class AuthenticationBackend {
    static private $registry = array();
    static $name;
    static $id;

    /* static */
    static function register($class) {
        if (is_string($class))
            $class = new $class();
        static::$registry[] = $class;
    }

    static function allRegistered() {
        return static::$registry;
    }

    /* static */
    function process($username, $password=null, &$errors) {
        if (!$username)
            return false;

        $backend = static::_getAllowedBackends($username);

        foreach (static::$registry as $bk) {
            if ($backend && $bk->supportsAuthentication() && $bk::$id != $backend)
                // User cannot be authenticated against this backend
                continue;
            // All backends are queried here, even if they don't support
            // authentication so that extensions like lockouts and audits
            // can be supported.
            $result = $bk->authenticate($username, $password);
            if ($result instanceof AuthenticatedUser) {
                static::_login($result, $username, $bk);
                $result->backend = $bk;
                return $result;
            }
            // TODO: Handle permission denied, for instance
            elseif ($result instanceof AccessDenied) {
                $errors['err'] = $result->reason;
                break;
            }
        }
        $info = array('username'=>$username, 'password'=>$password);
        Signal::send('auth.login.failed', null, $info);
    }

    function singleSignOn(&$errors) {
        global $ost;

        foreach (static::$registry as $bk) {
            // All backends are queried here, even if they don't support
            // authentication so that extensions like lockouts and audits
            // can be supported.
            $result = $bk->signOn();
            if ($result instanceof AuthenticatedUser) {
                // Ensure staff members are allowed to be authenticated
                // against this backend
                if ($result instanceof Staff
                        && !static::_isBackendAllowed($result, $bk))
                    continue;
                static::_login($result, $result->getUserName(), $bk);
                $result->backend = $bk;
                return $result;
            }
            // TODO: Handle permission denied, for instance
            elseif ($result instanceof AccessDenied) {
                $errors['err'] = $result->reason;
                break;
            }
        }
    }

    function _isBackendAllowed($staff, $bk) {
        $sql = 'SELECT backend FROM '.STAFF_TABLE
            .' WHERE staff_id='.db_input($staff->getId());
        $backend = db_result(db_query($sql));
        return !$backend || strcasecmp($bk, $backend) === 0;
    }

    function _getAllowedBackends($username) {
        $username = trim($_POST['userid']);
        $sql = 'SELECT backend FROM '.STAFF_TABLE
            .' WHERE username='.db_input($username)
            .' OR email='.db_input($username);
        return db_result(db_query($sql));
    }

    function _login($user, $username, $bk) {
        global $ost;

        if ($user instanceof Staff) {
            //Log debug info.
            $ost->logDebug('Staff login',
                sprintf("%s logged in [%s], via %s", $user->getUserName(),
                    $_SERVER['REMOTE_ADDR'], get_class($bk))); //Debug.

            $sql='UPDATE '.STAFF_TABLE.' SET lastlogin=NOW() '
                .' WHERE staff_id='.db_input($user->getId());
            db_query($sql);
            //Now set session crap and lets roll baby!
            $_SESSION['_staff'] = array(); //clear.
            $_SESSION['_staff']['userID'] = $username;

            $user->refreshSession(); //set the hash.

            $_SESSION['TZ_OFFSET'] = $user->getTZoffset();
            $_SESSION['TZ_DST'] = $user->observeDaylight();
        }

        //Regenerate session id.
        $sid = session_id(); //Current id
        session_regenerate_id(true);
        // Destroy old session ID - needed for PHP version < 5.1.0
        // DELME: remove when we move to php 5.3 as min. requirement.
        if(($session=$ost->getSession()) && is_object($session)
                && $sid!=session_id())
            $session->destroy($sid);

        Signal::send('auth.login.succeeded', $user);

        $user->cancelResetTokens();
    }

    /**
     * Fetches the friendly name of the backend
     */
    function getName() {
        return static::$name;
    }

    /**
     * Indicates if the backed supports authentication. Useful if the
     * backend is used for logging or lockout only
     */
    function supportsAuthentication() {
        return true;
    }

    /**
     * Indicates if the backend can be used to search for user information.
     * Lookup is performed to find user information based on a unique
     * identifier.
     */
    function supportsLookup() {
        return false;
    }

    /**
     * Indicates if the backend supports searching for usernames. This is
     * distinct from information lookup in that lookup is intended to lookup
     * information based on a unique identifier
     */
    function supportsSearch() {
        return false;
    }

    /**
     * Indicates if the backend supports changing a user's password. This
     * would be done in two fashions. Either the currently-logged in user
     * want to change its own password or a user requests to have their
     * password reset. This requires an administrative privilege which this
     * backend might not possess, so it's defined in supportsPasswordReset()
     */
    function supportsPasswordChange() {
        return false;
    }

    function supportsPasswordReset() {
        return false;
    }

    /* abstract */
    function authenticate($username, $password) {
        return false;
    }

    /* abstract */
    function signOn() {
        return false;
    }
}

class RemoteAuthenticationBackend {
    var $create_unknown_user = false;
}

/**
 * This will be an exception in later versions of PHP
 */
class AccessDenied {
    function AccessDenied() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct($reason) {
        $this->reason = $reason;
    }
}

/**
 * Simple authentication backend which will lock the login form after a
 * configurable number of attempts
 */
class AuthLockoutBackend extends AuthenticationBackend {

    function authenticate($username, $password=null) {
        global $cfg, $ost;

        if($_SESSION['_staff']['laststrike']) {
            if((time()-$_SESSION['_staff']['laststrike'])<$cfg->getStaffLoginTimeout()) {
                $_SESSION['_staff']['laststrike'] = time(); //reset timer.
                return new AccessDenied('Max. failed login attempts reached');
            } else { //Timeout is over.
                //Reset the counter for next round of attempts after the timeout.
                $_SESSION['_staff']['laststrike']=null;
                $_SESSION['_staff']['strikes']=0;
            }
        }

        $_SESSION['_staff']['strikes']+=1;
        if($_SESSION['_staff']['strikes']>$cfg->getStaffMaxLogins()) {
            $_SESSION['_staff']['laststrike']=time();
            $alert='Excessive login attempts by a staff member?'."\n".
                   'Username: '.$username."\n"
                   .'IP: '.$_SERVER['REMOTE_ADDR']."\n"
                   .'TIME: '.date('M j, Y, g:i a T')."\n\n"
                   .'Attempts #'.$_SESSION['_staff']['strikes']."\n"
                   .'Timeout: '.($cfg->getStaffLoginTimeout()/60)." minutes \n\n";
            $ost->logWarning('Excessive login attempts ('.$username.')', $alert,
                    $cfg->alertONLoginError());
            return new AccessDenied('Forgot your login info? Contact Admin.');
        //Log every other failed login attempt as a warning.
        } elseif($_SESSION['_staff']['strikes']%2==0) {
            $alert='Username: '.$username."\n"
                    .'IP: '.$_SERVER['REMOTE_ADDR']."\n"
                    .'TIME: '.date('M j, Y, g:i a T')."\n\n"
                    .'Attempts #'.$_SESSION['_staff']['strikes'];
            $ost->logWarning('Failed staff login attempt ('.$username.')', $alert, false);
        }
    }

    function supportsAuthentication() {
        return false;
    }
}
AuthenticationBackend::register(AuthLockoutBackend);

class osTicketAuthentication extends AuthenticationBackend {
    static $name = "Local Authenication";
    static $id = "local";

    function authenticate($username, $password) {
        if (($user = new StaffSession($username)) && $user->getId() &&
                $user->check_passwd($password)) {

            //update last login && password reset stuff.
            $sql='UPDATE '.STAFF_TABLE.' SET lastlogin=NOW() ';
            if($user->isPasswdResetDue() && !$user->isAdmin())
                $sql.=',change_passwd=1';
            $sql.=' WHERE staff_id='.db_input($user->getId());
            db_query($sql);

            return $user;
        }
    }
}
AuthenticationBackend::register(osTicketAuthentication);
?>
