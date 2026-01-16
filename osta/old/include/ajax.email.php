<?php
require_once INCLUDE_DIR . 'class.email.php';
class EmailAjaxAPI extends AjaxController {
    function access() {
        global $thisstaff;
        if (!$thisstaff || !$thisstaff->isAdmin())
             Http::response(403, 'Access Denied');
        return true;
    }

    function stashFormData($id) {
        if (!($email=Email::lookup($id)))
            Http::response(404, 'Unknown Email');
        $email->stashFormData($_POST ?: []);
        Http::response(201, 'Form Stashed Maybe!');
    }

    function configureAuth($id, $type, $auth) {
        if (!($email=Email::lookup($id)))
            Http::response(404, 'Unknown Email');
        if (!($account=$email->getAuthAccount($type)))
            Http::response(404, 'Unknown Authentication Type');
        if (!($form=$account->getAuthConfigForm($auth, $_POST ?: null)))
             Http::response(404, 'Unknown Authentication Provider');
        $info = $errors = [];
        if ($_POST && $account->saveAuth($auth, $form, $errors)) {
            if ($account->isOAuthAuth()
                    && $account->shouldAuthorize()) {
                 Http::response(201, JsonDataEncoder::encode([
                             'redirect' => sprintf('emails.php?id=%d&do=autho&bk=%s',
                                 $email->getId(), $account->getBkId())]),
                         'application/json');
            } else {
                Http::response(201, __('Successfully Updated Credentials.'));
            }
        }
        // Passible types are basic or oauth2
        list($authtype, $provider) = explode(':', $auth);
        $template = sprintf('email-%sauth.tmpl.php', $authtype);
        include INCLUDE_DIR . "staff/templates/$template";
    }

    /*
     * Delete the OAuth2 token
     */
    function deleteToken($id, $type) {
        // Check to make sure the email exists
        if (!($email=Email::lookup($id)))
            Http::response(404, 'Unknown Email');
        // Get the authentication account
        if (!($account=$email->getAuthAccount($type)))
            Http::response(404, 'Unknown Authentication Type');
        // Destory the account config which will delete the token
        if ($account->destroyConfig())
            Http::response(201, __('Token Deleted Successfully.'));
        // Couldn't delete the Token
        Http::response(404, 'Unable to delete token.');
    }
}
