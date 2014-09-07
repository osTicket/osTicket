<?php
/*********************************************************************
    categories.php

    FAQ categories

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
include_once(INCLUDE_DIR.'class.category.php');

/* check permission */
if(!$thisstaff || !$thisstaff->canManageFAQ()) {
    header('Location: kb.php');
    exit;
}


$category=null;
if($_REQUEST['id'] && !($category=Category::lookup($_REQUEST['id'])))
    $errors['err']='Identifiant de catégorie inconnu ou invalide.';

if($_POST){
    switch(strtolower($_POST['do'])) {
        case 'update':
            if(!$category) {
                $errors['err']='Catégorie inconnue ou invalide.';
            } elseif($category->update($_POST,$errors)) {
                $msg='Catégorie mise à jour avec succès';
            } elseif(!$errors['err']) {
                $errors['err']='Erreur lors de la mise à jour de la catégorie. Essayez encore !';
            }
            break;
        case 'create':
            if(($id=Category::create($_POST,$errors))) {
                $msg='Catégorie ajoutée avec succès';
                $_REQUEST['a']=null;
            } elseif(!$errors['err']) {
                $errors['err']='Impossible d\'ajouter une catégorie. Corrigez les erreurs ci-dessous et essayez encore.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='Vous devez sélectionner au moins une catégorie';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $sql='UPDATE '.FAQ_CATEGORY_TABLE.' SET ispublic=1 '
                            .' WHERE category_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Les catégories sélectionnées ont été rendues PUBLIQUES';
                            else
                                $warn = "$num catégories sur $count sélectionnées ont été rendues PUBLIQUES";
                        } else {
                            $errors['err'] = 'Impossible de rendre publiques les catégories sélectionnées.';
                        }
                        break;
                    case 'make_private':
                        $sql='UPDATE '.FAQ_CATEGORY_TABLE.' SET ispublic=0 '
                            .' WHERE category_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Les catégories sélectionnées ont été rendues PRIVÉES';
                            else
                                $warn = "$num catégories sur $count sélectionnées ont été rendues PRIVÉES";
                        } else {
                            $errors['err'] = 'Impossible de rendre privées les catégories sélectionnées.';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($c=Category::lookup($v)) && $c->delete())
                                $i++;
                        }

                        if($i==$count)
                            $msg = 'Les catégories sélectionnées ont été supprimées avec succès';
                        elseif($i>0)
                            $warn = "$i catégories sur $count sélectionnées ont été supprimées";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les catégories sélectionnées.';
                        break;
                    default:
                        $errors['err']='Action/Commande inconnue';
                }
            }
            break;
        default:
            $errors['err']='Action inconnue';
            break;
    }
}

$page='categories.inc.php';
$tip_namespace = 'knowledgebase.category';
if($category || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='category.inc.php';
}

$nav->setTabActive('kbase');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
