<?php
/*********************************************************************
    filters.php

    Email Filters

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.filter.php');
require_once(INCLUDE_DIR.'class.canned.php');
$filter=null;
if($_REQUEST['id'] && !($filter=Filter::lookup($_REQUEST['id'])))
    $errors['err']='Filtre inconnu ou invalide.';

/* NOTE: Banlist has its own interface*/
if($filter && $filter->isSystemBanlist())
    header('Location: banlist.php');

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$filter){
                $errors['err']='Filtre inconnu ou invalide.';
            }elseif($filter->update($_POST,$errors)){
                $msg='Filtre mis à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Erreur lors de la mise à jour du filtre. Essayez encore !';
            }
            break;
        case 'add':
            if((Filter::create($_POST,$errors))){
                $msg='Filtre ajouté avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter le filtre. Corrigez les erreurs ci-dessous et essayez encore.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins un filtre.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.FILTER_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Filtres sélectionnés activés';
                            else
                                $warn = "$num filtres activés sur $count sélectionnés";
                        } else {
                            $errors['err'] = 'Impossible d\'activer les filtres sélectionnés';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.FILTER_TABLE.' SET isactive=0 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Filtres sélectionnés désactivés';
                            else
                                $warn = "$num filtres désactivés sur $count sélectionnés";
                        } else {
                            $errors['err'] = 'Impossible de désactiver les filtres sélectionnés';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($f=Filter::lookup($v)) && !$f->isSystemBanlist() && $f->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Filtres sélectionnés supprimés avec succès';
                        elseif($i>0)
                            $warn = "$i filtres supprimés sur $count sélectionnés";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les filtres sélectionnés';
                        break;
                    default:
                        $errors['err']='Action inconnue - Demandez de l\'aide au support technique';
                }
            }
            break;
        default:
            $errors['err']='Commande/action inconnue';
            break;
    }
}

$page='filters.inc.php';
$tip_namespace = 'manage.filter';
if($filter || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='filter.inc.php';
}

$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
