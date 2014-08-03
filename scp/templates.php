<?php
/*********************************************************************
    templates.php

    Email Templates

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.template.php');
$template=null;
if($_REQUEST['tpl_id'] &&
        !($template=EmailTemplateGroup::lookup($_REQUEST['tpl_id'])))
    $errors['err']='Identifiant de groupe inconnu ou invalide.';
elseif($_REQUEST['id'] &&
        !($template=EmailTemplate::lookup($_REQUEST['id'])))
    $errors['err']='Identifiant de modèle inconnu ou invalide.';
elseif($_REQUEST['default_for']) {
    $sql = 'SELECT id FROM '.EMAIL_TEMPLATE_TABLE
        .' WHERE tpl_id='.db_input($cfg->getDefaultTemplateId())
        .' AND code_name='.db_input($_REQUEST['default_for']);
    if ($id = db_result(db_query($sql)))
        Http::redirect('templates.php?a=manage&id='.db_input($id));
}

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'updatetpl':
            if(!$template){
                $errors['err']='Modèle inconnu ou invalide';
            }elseif($template->update($_POST,$errors)){
                $msg='Modèle de message mis à jour avec succès';
                // Drop drafts for this template for ALL users
                Draft::deleteForNamespace('tpl.'.$template->getCodeName()
                    .'.'.$template->getTplId());
            }elseif(!$errors['err']){
                $errors['err']='Erreur de mise à jour du modèle de message. Essayez encore !';
            }
            break;
        case 'implement':
            if(!$template){
                $errors['err']='Modèle inconnu ou invalide';
            }elseif($new = EmailTemplate::add($_POST,$errors)){
                $template = $new;
                $msg='Modèle de message mis à jour avec succès';
                // Drop drafts for this user for this template
                Draft::deleteForNamespace('tpl.'.$new->getCodeName()
                    .$new->getTplId(), $thisstaff->getId());
            }elseif(!$errors['err']){
                $errors['err']='Erreur de mise à jour du modèle de message. Essayez encore !';
            }
            break;
        case 'update':
            if(!$template){
                $errors['err']='Modèle inconnu ou invalide';
            }elseif($template->update($_POST,$errors)){
                $msg='Modèle mis à jour avec succès';
            }elseif(!$errors['err']){
                $errors['err']='Erreur de mise à jour du modèle. Essayez encore !';
            }
            break;
        case 'add':
            if(($new=EmailTemplateGroup::add($_POST,$errors))){
                $template=$new;
                $msg='Modèle ajouté avec succès';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Impossible d\'ajouter un modèle. Corrigez les erreurs ci-dessous et essayez encore.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='Vous devez choisir au moins un modèle pour continuer.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.EMAIL_TEMPLATE_GRP_TABLE.' SET isactive=1 '
                            .' WHERE tpl_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = 'Modèles sélectionnés activés';
                            else
                                $warn = "$num modèle(s) activés sur $count modèles sélectionnés";
                        } else {
                            $errors['err'] = 'Impossible d\'activer les modèles sélectionnés';
                        }
                        break;
                    case 'disable':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=EmailTemplateGroup::lookup($v)) && !$t->isInUse() && $t->disable())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = 'Modèles sélectionnés désactivés';
                        elseif($i)
                            $warn = "$i modèle(s) désactivés sur $count modèles sélectionnés (les modèles en cours d'utilisation ne peuvent être désactivés)";
                        else
                            $errors['err'] = "Impossible de désactiver les modèles sélectionnés (les modèles en cours d'utilisation et le modèle par défaut ne peuvent être désactivés)";
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=EmailTemplateGroup::lookup($v)) && !$t->isInUse() && $t->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Modèles sélectionnés supprimés avec succès';
                        elseif($i>0)
                            $warn = "$i modèle(s) supprimé(s) sur $count modèles sélectionnés";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les modèles sélectionnés';
                        break;
                    default:
                        $errors['err']='Action de modèle inconnue';
                }
            }
            break;
        default:
            $errors['err']='Action inconnue';
            break;
    }
}

$page='templates.inc.php';
$tip_namespace = 'emails.template';
if($template && !strcasecmp($_REQUEST['a'],'manage')){
    $page='tpl.inc.php';
}elseif($template && !strcasecmp($_REQUEST['a'],'implement')){
    $page='tpl.inc.php';
}elseif($template || !strcasecmp($_REQUEST['a'],'add')){
    $page='template.inc.php';
}

$nav->setTabActive('emails');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
