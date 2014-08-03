<?php

require_once('client.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error');
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE); //make includes happy

require_once(INCLUDE_DIR.'class.client.php');

$inc = 'pwreset.request.php';
if($_POST) {
    if (!$ost->checkCSRFToken()) {
        Http::response(400, 'Jeton CSRF valide requis');
        exit;
    }
    switch ($_POST['do']) {
        case 'sendmail':
            if (($acct=ClientAccount::lookupByUsername($_POST['userid']))) {
                if (!$acct->isPasswdResetEnabled()) {
                    $banner = 'La réinitialisation du mot de passe n\'est pas activée sur votre compte. '
                        .'Veuillez contacter votre administrateur';
                }
                elseif ($acct->sendResetEmail()) {
                    $inc = 'pwreset.sent.php';
                }
                else
                    $banner = 'Impossible de réinitialiser votre adresse de courriel. Erreur interne';
            }
            else
                $banner = 'Impossible de vérifier l\'identifiant '
                    .Format::htmlchars($_POST['userid']);
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
    $banner = 'Entrez de nouveau votre identifiant ou votre adresse de courriel';
    $inc = 'pwreset.login.php';
    $_config = new Config('pwreset');
    if (($id = $_config->get($_GET['token']))
            && ($acct = ClientAccount::lookup(array('user_id'=>$id)))) {
        if (!$acct->isConfirmed()) {
            $inc = 'register.confirmed.inc.php';
            $acct->confirm();
            // TODO: Log the user in
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
elseif ($cfg->allowPasswordReset()) {
    $banner = 'Entrez votre identifiant ou votre adresse de courriel ci-dessous';
}
else {
    $_SESSION['_staff']['auth']['msg']='La réinitialisation des mots de passe est désactivée';
    return header('Location: index.php');
}

$nav = new UserNav();
$nav->setActiveNav('status');
require CLIENTINC_DIR.'header.inc.php';
require CLIENTINC_DIR.$inc;
require CLIENTINC_DIR.'footer.inc.php';
?>
