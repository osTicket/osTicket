<?php
/*********************************************************************
    install.php

    osTicket Installer.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('setup.inc.php');

require_once INC_DIR.'class.installer.php';


//define('OSTICKET_CONFIGFILE','../include/ost-config.php'); //osTicket config file full path.
define('OSTICKET_CONFIGFILE','../include/ost-config.php'); //XXX: Make sure the path is corrent b4 releasing.


$installer = new Installer(OSTICKET_CONFIGFILE); //Installer instance.
$wizard=array();
$wizard['title']='Installateur osTicket';
$wizard['tagline']='Installation de osTicket '.$installer->getVersionVerbose();
$wizard['logo']='logo.png';
$wizard['menu']=array('Guide d\'installation'=>'http://osticket.com/wiki/Installation',
        'Obtenez une aide professionnelle'=>'http://osticket.com/support');

if($_POST && $_POST['s']) {
    $errors = array();
    $_SESSION['ost_installer']['s']=$_POST['s'];
    switch(strtolower($_POST['s'])) {
        case 'prereq':
            if($installer->check_prereq())
                $_SESSION['ost_installer']['s']='config';
            else
                $errors['prereq']='Minimums requis non détectés !';
            break;
        case 'config':
            if(!$installer->config_exists())
                $errors['err']='Le fichier de configuration n\'existe pas. Suivez les étapes ci-dessous pour l\'ajouter.';
            elseif(!$installer->config_writable())
                $errors['err']='Un accès en écriture est nécessaire pour continuer.';
            else
                $_SESSION['ost_installer']['s']='install';
            break;
        case 'install':
            if($installer->install($_POST)) {
                $_SESSION['info']=array('Nom'  =>ucfirst($_POST['fname'].' '.$_POST['lname']),
                                        'email' =>$_POST['admin_email'],
                                        'URL'=>URL);
                //TODO: Go to subscribe step.
                $_SESSION['ost_installer']['s']='done';
            } elseif(!($errors=$installer->getErrors()) || !$errors['err']) {
                $errors['err']='Erreur lors de l\'installation d\'osTicket - Corrigez les erreurs et essayez à nouveau.';
            }
            break;
        case 'subscribe':
            if(!trim($_POST['name']))
                $errors['name'] = 'Obligatoire';

            if(!$_POST['email'])
                $errors['email'] = 'Obligatoire';
            elseif(!Validator::is_email($_POST['email']))
                $errors['email'] = 'Invalide';

            if(!$_POST['alerts'] && !$_POST['news'])
                $errors['notify'] = 'Sélectionnez-en au moins un';

            if(!$errors)
                $_SESSION['ost_installer']['s'] = 'done';
            break;
    }

}elseif($_GET['s'] && $_GET['s']=='ns' && $_SESSION['ost_installer']['s']=='subscribe') {
    $_SESSION['ost_installer']['s']='done';
}

switch(strtolower($_SESSION['ost_installer']['s'])) {
    case 'config':
    case 'install':
        if(!$installer->config_exists()) {
            $inc='file-missing.inc.php';
        } elseif(!($cFile=file_get_contents($installer->getConfigFile()))
                || preg_match("/define\('OSTINSTALLED',TRUE\)\;/i",$cFile)) { //osTicket already installed or empty config file?
            $inc='file-unclean.inc.php';
        } elseif(!$installer->config_writable()) { //writable config file??
            clearstatcache();
            $inc='file-perm.inc.php';
        } else { //Everything checked out show install form.
            $inc='install.inc.php';
        }
        break;
    case 'subscribe': //TODO: Prep for v1.7 RC1
       $inc='subscribe.inc.php';
        break;
    case 'done':
        $inc='install-done.inc.php';
        if (!$installer->config_exists())
            $inc='install-prereq.inc.php';
        else // Clear installer session
            $_SESSION['ost_installer'] =  array();
        break;
    default:
        //Fail IF any of the old config files exists.
        if(file_exists(INCLUDE_DIR.'settings.php')
                || file_exists(ROOT_DIR.'ostconfig.php')
                || (file_exists(OSTICKET_CONFIGFILE)
                    && preg_match("/define\('OSTINSTALLED',TRUE\)\;/i",
                        file_get_contents(OSTICKET_CONFIGFILE)))
                )
            $inc='file-unclean.inc.php';
        else
            $inc='install-prereq.inc.php';
}

require(INC_DIR.'header.inc.php');
require(INC_DIR.$inc);
require(INC_DIR.'footer.inc.php');
?>
