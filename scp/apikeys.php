<?php
/*********************************************************************
    apikeys.php

    API keys.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.api.php');

$api=null;
if($_REQUEST['id'] && !($api=API::lookup($_REQUEST['id'])))
    $errors['err']='ID de clé d\'API inconnue ou invalide.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$api){
                $errors['err']='Clé d\'API inconnue ou invalide.';
            }elseif($api->update($_POST,$errors)){
                $msg='Clé d\'API mise à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Erreur lors de la mise à jour de la clé d\'API. Essayez encore !';
            }
            break;
        case 'add':
            if(($id=API::add($_POST,$errors))){
                $msg='Clé d\'API ajoutée avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter une clé d\'API. Corrigez les erreurs ci-dessous et essayez encore.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins une clé d\'API';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.API_KEY_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Clés d\'API sélectionnées activées';
                            else
                                $warn = "$num clés d\'API sur $count sélectionnées activées";
                        } else {
                            $errors['err'] = 'Impossible d\'activer les clés d\'API sélectionnées.';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.API_KEY_TABLE.' SET isactive=0 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Clés d\'API sélectionnées désactivées';
                            else
                                $warn = "$num clés d\'API sur $count sélectionnées désactivées";
                        } else {
                            $errors['err'] = 'Impossible de désactiver les clés d\'API sélectionnées.';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=API::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = 'Clés d\'API sélectionnées supprimées';
                        elseif($i>0)
                            $warn = "$i clés d\'API sur $count sélectionnées supprimées";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les clés d\'API sélectionnées.';
                        break;
                    default:
                        $errors['err']='Action inconnue - demandez de l\'aide technique';
                }
            }
            break;
        default:
            $errors['err']='Action/commande inconnue';
            break;
    }
}

$page='apikeys.inc.php';
$tip_namespace = 'manage.api_keys';

if($api || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add')))
    $page = 'apikey.inc.php';

$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
