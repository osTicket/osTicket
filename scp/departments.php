<?php
/*********************************************************************
    departments.php

    Departments

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
$dept=null;
if($_REQUEST['id'] && !($dept=Dept::lookup($_REQUEST['id'])))
    $errors['err']='Identificant de département inconnu ou invalide.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$dept){
                $errors['err']='Département inconnu ou invalide.';
            }elseif($dept->update($_POST,$errors)){
                $msg='Département mis à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Erreur lors de la mise à jour du département. Essayez encore !';
            }
            break;
        case 'create':
            if(($id=Dept::create($_POST,$errors))){
                $msg=Format::htmlchars($_POST['name']).' ajouté avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter un département. Corrigez les erreurs ci-dessous et réessayez.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins un département';
            }elseif(in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                $errors['err'] = 'Vous ne pouvez pas désactiver ou supprimer un département qui est défini par défaut. Supprimez la définition par défaut et réessayez.';
            }else{
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg='Les départements sélectionnés ont été rendus publics';
                            else
                                $warn="$num départements sur $count sélectionnés ont été rendus publics";
                        } else {
                            $errors['err']='Impossible de rendre public(s) le(s) département(s) sélectionné(s).';
                        }
                        break;
                    case 'make_private':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0  '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).') '
                            .' AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg='Les départements sélectionnés ont été rendus privés';
                            else
                                $warn="$num départements sur $count sélectionnés ont été rendus privés";
                        } else {
                            $errors['err']='Impossible de rendre privé(s) le(s) département(s) sélectionné(s) (peut-être est-il déjà privé ?).';
                        }
                        break;
                    case 'delete':
                        //Deny all deletes if one of the selections has members in it.
                        $sql='SELECT count(staff_id) FROM '.STAFF_TABLE
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        list($members)=db_fetch_row(db_query($sql));
                        if($members)
                            $errors['err']='Les départements avec du personnel ne peuvent être supprimés. Déplacez le personnel d\'abord.';
                        else {
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if($v!=$cfg->getDefaultDeptId() && ($d=Dept::lookup($v)) && $d->delete())
                                    $i++;
                            }
                            if($i && $i==$count)
                                $msg = 'Les départements sélectionnés ont été supprimés avec succès';
                            elseif($i>0)
                                $warn = "$i départements sur $count sélectionnés ont été supprimés";
                            elseif(!$errors['err'])
                                $errors['err'] = 'Impossible de supprimer le(s) département(s) sélectionné(s).';
                        }
                        break;
                    default:
                        $errors['err']='Action inconnue — demandez de l\'aide technique';
                }
            }
            break;
        default:
            $errors['err']='Action/Commande inconnue';
            break;
    }
}

$page='departments.inc.php';
$tip_namespace = 'staff.department';
if($dept || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='department.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
