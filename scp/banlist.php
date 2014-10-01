<?php
/*********************************************************************
    banlist.php

    List of banned email addresses

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.banlist.php');

/* Get the system ban list filter */
if(!($filter=Banlist::getFilter()))
    $warn = 'Liste de bannissement système vide.';
elseif(!$filter->isActive())
    $warn = 'Le filtre de LISTE DE BANNISSEMENT SYSTÈME est <b>DÉSACTIVÉ</b> - <a href="filters.php">l\'activer ici</a>.';

$rule=null; //ban rule obj.
if($filter && $_REQUEST['id'] && !($rule=$filter->getRule($_REQUEST['id'])))
    $errors['err'] = 'Identifiant de liste de bannissement inconnu ou invalide';

if($_POST && !$errors && $filter){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$rule){
                $errors['err']='Règle de bannissement inconnue ou invalide.';
            }elseif(!$_POST['val'] || !Validator::is_email($_POST['val'])){
                $errors['err']=$errors['val']='Une adresse de courriel valide est requise';
            }elseif(!$errors){
                $vars=array('w'=>'email',
                            'h'=>'equal',
                            'v'=>trim($_POST['val']),
                            'filter_id'=>$filter->getId(),
                            'isactive'=>$_POST['isactive'],
                            'notes'=>$_POST['notes']);
                if($rule->update($vars,$errors)){
                    $msg='Adresse de courriel mise à jour avec succès';
                }elseif(!$errors['err']){
                    $errors['err']='Erreur lors de la mise à jour de la règle de bannissement. Essayez encore !';
                }
            }
            break;
        case 'add':
            if(!$filter) {
                $errors['err']='Liste de bannissement inconnue ou invalide';
            }elseif(!$_POST['val'] || !Validator::is_email($_POST['val'])) {
                $errors['err']=$errors['val']='Une adresse de courriel valide est requise';
            }elseif(BanList::includes(trim($_POST['val']))) {
                $errors['err']=$errors['val']='L\'adresse de courriel est déjà dans la liste de bannissement';
            }elseif($filter->addRule('email','equal',trim($_POST['val']),array('isactive'=>$_POST['isactive'],'notes'=>$_POST['notes']))) {
                $msg='L\'adresse de courriel a été ajoutée à la liste de bannissement avec succès';
                $_REQUEST['a']=null;
                //Add filter rule here.
            }elseif(!$errors['err']){
                $errors['err']='Erreur lors de la création de la liste de bannissement. Essayez encore !';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins une adresse de courriel à utiliser.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.FILTER_RULE_TABLE.' SET isactive=1 '
                            .' WHERE filter_id='.db_input($filter->getId())
                            .' AND id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = 'Bannissement activé sur les adresses de courriel sélectionnées';
                            else
                                $warn = "Bannissement activé sur $num adresses de courriel sur $count sélectionnées";
                        } else  {
                            $errors['err'] = 'Impossible d\'activer le bannissement des adresses de courriel sélectionnées';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.FILTER_RULE_TABLE.' SET isactive=0 '
                            .' WHERE filter_id='.db_input($filter->getId())
                            .' AND id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Bannissement désactivé sur les adresses de courriel sélectionnées';
                            else
                                $warn = "Bannissement désactivé sur $num adresses de courriel sur $count sélectionnées";
                        } else {
                            $errors['err'] = 'Impossible de désactiver le bannissement des adresses de courriel sélectionnées';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($r=FilterRule::lookup($v)) && $r->getFilterId()==$filter->getId() && $r->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = 'Les adresses de courriel sélectionnées ont été supprimées de la liste de bannissement avec succès';
                        elseif($i>0)
                            $warn = "$i adresses de courriel sur $count sélectionnées ont été supprimées de la liste de bannissement avec succès";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les adresses de courriel sélectionnées';

                        break;
                    default:
                        $errors['err'] = 'Action inconnue — demandez de l\'aide technique';
                }
            }
            break;
        default:
            $errors['err']='Action inconnue';
            break;
    }
}

$page='banlist.inc.php';
$tip_namespace = 'emails.banlist';
if(!$filter || ($rule || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add')))) {
    $page='banrule.inc.php';
}

$nav->setTabActive('emails');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
