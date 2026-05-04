<?php
if(!defined('INCLUDE_DIR')) die('403');

require_once INCLUDE_DIR . 'class.2fa.php';

class ClientAjaxAPI extends AjaxController {

    private $thisuser;
    private $thisaccount;
    
    public function __construct()
    {
        global $thisclient;
        if ($thisclient) {
            $this->thisuser = $thisclient->getUser(true)->getAccount();
        }
    }

    
    function configure2FA($userId, $id=0) {
        global $thisclient;
        $thisuser = $this->thisuser;
        if (!$thisclient)
            Http::response(403, 'User login required');
        if ($userId != $thisuser->getId())
            Http::response(403, 'Access denied');
        if ($id && !($auth= User2FABackend::lookup($id))) {
            Http::response(404, 'Unknown 2FA');
        }

        $user = $thisuser;
        $info = array();
        // If specific backend requested (e.g. configuring Email or Authenticator)
        if ($auth) {
            $state = @$_POST['state'] ?: 'validate';
            switch ($state) {
                case 'verify':
                    try {
                        $form = $auth->getInputForm($_POST);
                        if ($_POST && $form
                                && $form->isValid()
                                && $auth->validate($form, $user)) {
                            // Mark the settings as verified
                            if (($config = $user->get2FAConfig($auth->getId()))) {
                                $config['verified'] = time();
                                $user->updateConfig(array(
                                            $auth->getId() => JsonDataEncoder::encode($config)));
                            }
                            // We're done here
                            $auth = null;
                            $info['notice'] = __('Setup completed successfully');
                        } else {
                            $info['error'] = __('Unable to verify the token - try again!');
                        }
                    } catch (ExpiredOTP $ex) {
                        // giving up cleanly
                        $info['error'] = $ex->getMessage();
                        $auth = null;
                    }
                    break;
                case 'validate':
                default:
                    $config = $user->get2FAConfig($auth->getId());
                    $vars = $_POST ?: $config['config'] ?: array('email' => $user->getUser()->getEmail());
                    $form = $auth->getSetupForm($vars);
                    if ($_POST && $form && $form->isValid()) {
                        if ($config['config'] && $config['config']['external2fa'])
                            $external2fa = true;

                        // Save the setting based on setup form
                        $clean = $form->getClean();
                        if (!$external2fa) {
                            $config = ['config' => $clean, 'verified' => 0];
                            $user->updateConfig(array(
                                        $auth->getId() => JsonDataEncoder::encode($config)));
                        }

                        // Send verification token to the user
                        if ($token=$auth->send($user)) {
                            // Transition to verify state
                            $form =  $auth->getInputForm($vars);
                            $state = 'verify';
                            $info['notice'] = __('Token sent to you!');
                        } else {
                            // Generic error TODO: better wording
                            $info['error'] = __('Error sending Token - double check entry');
                        }
                    }
            }
        }

        // Include the template we created (2fas.tmpl.php)
        include CLIENTINC_DIR . 'templates/2fas.tmpl.php';
    }

    function reset2fA($userId) {
        global $thisclient;
        $thisuser = $this->thisuser;

        if (!$thisclient)
            Http::response(403, 'User login required');
        if ($userId != $thisuser->getId())
            Http::response(403, 'Access denied');


        $default_2fa = ConfigItem::getConfigsByNamespace('user.'.$userId, 'default_2fa');

        if ($default_2fa)
            $default_2fa->delete();
    }
}
?>
