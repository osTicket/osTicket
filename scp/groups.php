<?php
/*********************************************************************
    groups.php

    User Groups.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
$group=null;
if($_REQUEST['id'] && !($group=Group::lookup($_REQUEST['id'])))
    $errors['err']='Identifiant de groupe inconnu ou invalide.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$group){
                $errors['err']='Groupe inconnu ou invalide.';
            }elseif($group->update($_POST,$errors)){
                $msg='Groupe mise à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Impossible de mettre à jour le group. Corrigez les erreurs ci-dessous et réessayez !';
            }
            break;
        case 'create':
            if(($id=Group::create($_POST,$errors))){
                $msg=Format::htmlchars($_POST['name']).' ajouté avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter un groupe. Corrigez les erreurs ci-dessous et réessayez.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins un groupe.';
            } elseif(in_array($thisstaff->getGroupId(), $_POST['ids'])) {
                $errors['err'] = "En tant qu'administrateur, vous ne pouvez pas désactiver ou supprimer un groupe auquel vous appartenez — vous pourriez bloquer tous les accès admins !";
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=1, updated=NOW() '
                            .' WHERE group_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = 'Groupes sélectionnés activés';
                            else
                                $warn = "$num groupes activés sur $count sélectionnés";
                        } else {
                            $errors['err'] = 'Impossible d\'activer les groupes sélectionnés';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=0, updated=NOW() '
                            .' WHERE group_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Groupes sélectionnés désactivés';
                            else
                                $warn = "$num groupes désactivés sur $count sélectionnés";
                        } else {
                            $errors['err'] = 'Impossible de désactiver les groupes sélectionnés';
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($g=Group::lookup($v)) && $g->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Groupes sélectionnés supprimés avec succès';
                        elseif($i>0)
                            $warn = "$i groupes supprimés sur $count sélectionnés";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les groupes sélectionnés';
                        break;
                    default:
                        $errors['err']  = 'Action inconnue. Contactez le support technique !';
                }
            }
            break;
        default:
            $errors['err']='Action inconnue';
            break;
    }
}

$page='groups.inc.php';
$tip_namespace = 'staff.groups';
if($group || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='group.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
