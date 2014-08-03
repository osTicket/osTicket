<?php
/*********************************************************************
    users.php

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');

require_once INCLUDE_DIR.'class.note.php';

$user = null;
if ($_REQUEST['id'] && !($user=User::lookup($_REQUEST['id'])))
    $errors['err'] = 'Identifiant d\'utilisateur inconnu ou invalide.';

if ($_POST) {
    switch(strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$user) {
                $errors['err']='Utilisateur inconnu ou invalide.';
            } elseif(($acct = $user->getAccount())
                    && !$acct->update($_POST, $errors)) {
                 $errors['err']='Impossible de mettre à jour les informations du compte de l\'utilisateur';
            } elseif($user->updateInfo($_POST, $errors)) {
                $msg='Utilisateur mis à jour avec succès';
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']='Impossible de mettre à jour le profil de l\'utilisateur. Corrigez les erreurs ci-dessous et essayez encore !';
            }
            break;
        case 'create':
            $form = UserForm::getUserForm()->getForm($_POST);
            if (($user = User::fromForm($form))) {
                $msg = Format::htmlchars($user->getName()).' ajouté avec succès';
                $_REQUEST['a'] = null;
            } elseif (!$errors['err']) {
                $errors['err'] = 'Impossible d\'ajouter un utilisateur. Corrigez les erreurs ci-dessous et essayez encore.';
            }
            break;
        case 'confirmlink':
            if (!$user || !$user->getAccount())
                $errors['err'] = 'Compte utilisateur inconnu ou invalide';
            elseif ($user->getAccount()->isConfirmed())
                $errors['err'] = 'Le compte est déjà confirmé';
            elseif ($user->getAccount()->sendConfirmEmail())
                $msg = 'Courriel d\'activation du compte envoyé à '.$user->getEmail();
            else
                $errors['err'] = 'Impossible d\'envoyer le courriel d\'activation du compte - essayez encore !';
            break;
        case 'pwreset':
            if (!$user || !$user->getAccount())
                $errors['err'] = 'Compte utilisateur inconnu ou invalide';
            elseif ($user->getAccount()->sendResetEmail())
                $msg = 'Courriel de réinitialisation du mot de passe du compte envoyé à '.$user->getEmail();
            else
                $errors['err'] = 'Impossible d\'envoyer le courriel de réinitialisation du mot de passe du compte - essayez encore !';
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins un utilisateur membre.';
            } else {
                $errors['err'] = "Bientôt!";
            }
            break;
        case 'import-users':
            $status = User::importFromPost($_FILES['import'] ?: $_POST['pasted']);
            if (is_numeric($status))
                $msg = "Import avec succès de $status clients";
            else
                $errors['err'] = $status;
            break;
        default:
            $errors['err'] = 'Action/commande inconnue';
            break;
    }
} elseif($_REQUEST['a'] == 'export') {
    require_once(INCLUDE_DIR.'class.export.php');
    $ts = strftime('%Y%m%d');
    if (!($token=$_REQUEST['qh']))
        $errors['err'] = 'Jeton de requête requis';
    elseif (!($query=$_SESSION['users_qs_'.$token]))
        $errors['err'] = 'Jeton de requête non trouvé';
    elseif (!Export::saveUsers($query, "users-$ts.csv", 'csv'))
        $errors['err'] = 'Erreur interne : Impossible de fournir les résultats de la requête';
}

$page = $user? 'user-view.inc.php' : 'users.inc.php';

$nav->setTabActive('users');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
