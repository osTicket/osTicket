<?php

require_once('client.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error');
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE); //make includes happy

require_once(INCLUDE_DIR.'class.client.php');

$inc = 'pwreset.request.php';
if($_POST) {
    if (!$ost->checkCSRFToken()) {
        Http::response(400, __('Valid CSRF Token Required'));
        exit;
    }
    switch ($_POST['do']) {
        case 'sendmail':
            if (($acct=ClientAccount::lookupByUsername($_POST['userid']))) {
                if (!$acct->isPasswdResetEnabled()) {
                    $banner = __('Password reset is not enabled for your account. Contact your administrator');
                }
                elseif ($acct->sendResetEmail()) {
                    $inc = 'pwreset.sent.php';
                }
                else
                    $banner = __('Unable to send reset email.')
                        .' '.__('Internal error occurred');
            }
            else
                $banner = sprintf(__('Unable to verify username %s'),
                    Format::htmlchars($_POST['userid']));
            break;
        case 'reset':
            $inc = 'pwreset.login.php';
            $errors = array();
            if ($client = UserAuthenticationBackend::processSignOn($errors)) {
                Http::redirect('index.php');
            }
            elseif (isset($errors['msg'])) {
                $banner = $errors['msg'];
            }
            break;
    }
}
elseif ($_GET['token']) {
    $banner = __('Re-enter your username or email');
    $inc = 'pwreset.login.php';
    $_config = new Config('pwreset');
    if (($id = $_config->get($_GET['token']))
            && ($acct = ClientAccount::lookup(array('user_id'=>substr($id,1))))) {
        if (!$acct->isConfirmed()) {
            $inc = 'register.confirmed.inc.php';
            $acct->confirm();
            // FIXME: The account has to be uncached in order for the lookup
            // in the ::processSignOn to detect the confirmation
            ModelInstanceManager::uncache($acct);
            // Log the user in
            if ($client = UserAuthenticationBackend::processSignOn($errors)) {
                if ($acct->hasPassword() && !$acct->get('backend')) {
                    $acct->cancelResetTokens();
                }
                // No password setup yet -- force one to be created
                else {
                    $_SESSION['_client']['reset-token'] = $_GET['token'];
                    $acct->forcePasswdReset();
                }
                Http::redirect('account.php?confirmed');
            }
        }
    }
    elseif ($id && ($user = User::lookup($id)))
        $inc = 'pwreset.create.php';
    else
        Http::redirect('index.php');
}
else {
    $banner = __('Enter your username or email address below');
}

$nav = new UserNav();
$nav->setActiveNav('status');
require CLIENTINC_DIR.'header.inc.php';
require CLIENTINC_DIR.$inc;
require CLIENTINC_DIR.'footer.inc.php';
?>
