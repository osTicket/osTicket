<?php
/*********************************************************************
    emails.php

    Emails

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.email.php');

$email=null;
if($_REQUEST['id'] && !($email=Email::lookup($_REQUEST['id'])))
    $errors['err']='Identifiant d\'adresse de courriel inconnu ou invalide.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$email){
                $errors['err']='Adresse de courriel inconnue ou invalide.';
            }elseif($email->update($_POST,$errors)){
                $msg='Adresse de courriel mise à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Erreur lors de la mise à jour de l\'adresse de courriel. Essayez encore !';
            }
            break;
        case 'create':
            if(($id=Email::create($_POST,$errors))){
                $msg='Adresse de courriel ajoutée avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter une adresse de courriel. Corrigez les erreurs ci-dessous et réessayez.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins une adresse de courriel';
            } else {
                $count=count($_POST['ids']);

                $sql='SELECT count(dept_id) FROM '.DEPT_TABLE.' dept '
                    .' WHERE email_id IN ('.implode(',', db_input($_POST['ids'])).') '
                    .' OR autoresp_email_id IN ('.implode(',', db_input($_POST['ids'])).')';

                list($depts)=db_fetch_row(db_query($sql));
                if($depts>0) {
                    $errors['err'] = 'Une ou plusieurs adresses de courriel sélectionnées est utilisée par un département. Supprimez cette association d\'abord !';
                } elseif(!strcasecmp($_POST['a'], 'delete')) {
                    $i=0;
                    foreach($_POST['ids'] as $k=>$v) {
                        if($v!=$cfg->getDefaultEmailId() && ($e=Email::lookup($v)) && $e->delete())
                            $i++;
                    }

                    if($i && $i==$count)
                        $msg = 'Les adresses de courriel sélectionnées ont été supprimées avec succès';
                    elseif($i>0)
                        $warn = "$i adresses de courriel sur $count sélectionnées ont été supprimées";
                    elseif(!$errors['err'])
                        $errors['err'] = 'Impossible de supprimer les adresses de courriel sélectionnées';

                } else {
                    $errors['err'] = 'Action inconnue — demandez de l\'aide technique';
                }
            }
            break;
        default:
            $errors['err'] = 'Action/Commande inconnue';
            break;
    }
}

$page='emails.inc.php';
$tip_namespace = 'emails.email';
if($email || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='email.inc.php';
}

$nav->setTabActive('emails');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
