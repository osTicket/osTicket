<?php
/*********************************************************************
    teams.php

    Evertything about teams

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
$team=null;
if($_REQUEST['id'] && !($team=Team::lookup($_REQUEST['id'])))
    $errors['err']='Identifiant d\'équipe inconnu ou invalide.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$team){
                $errors['err']='Équipe inconnue ou invalide.';
            }elseif($team->update($_POST,$errors)){
                $msg='Équipe mise à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Impossible de mettre à jour l\'équipe. Corrigez les erreurs ci-dessous et réessayez !';
            }
            break;
        case 'create':
            if(($id=Team::create($_POST,$errors))){
                $msg=Format::htmlchars($_POST['team']).' ajouté avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter une équipe. Corrigez les erreurs ci-dessous et réessayez.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='Vous devez sélectionner au moins une équipe.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.TEAM_TABLE.' SET isenabled=1 '
                            .' WHERE team_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Équipes sélectionnées activées';
                            else
                                $warn = "$num équipe(s) activée(s) sur $count équipes sélectionnées";
                        } else {
                            $errors['err'] = 'Impossible d\'activer les équipes sélectionnées';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.TEAM_TABLE.' SET isenabled=0 '
                            .' WHERE team_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Équipes sélectionnées désactivées';
                            else
                                $warn = "$num équipe(s) désactivée(s) sur $count équipes sélectionnées";
                        } else {
                            $errors['err'] = 'Impossible de désactiver les équipes sélectionnées';
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=Team::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = 'Équipes sélectionnées supprimées avec succès';
                        elseif($i>0)
                            $warn = "$i équipe(s) supprimées sur $count équipes sélectionnées";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les équipes sélectionnées';
                        break;
                    default:
                        $errors['err'] = 'Action inconnue. Demandez de l\'aide technique!';
                }
            }
            break;
        default:
            $errors['err']='Action/commande inconnue';
            break;
    }
}

$page='teams.inc.php';
$tip_namespace = 'staff.team';
if($team || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='team.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
