<?php
/*********************************************************************
    logs.php

    System Logs

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'Vous devez sélectionner au moins un journal à supprimer';
            } else {
                $count=count($_POST['ids']);
                if($_POST['a'] && !strcasecmp($_POST['a'], 'delete')) {

                    $sql='DELETE FROM '.SYSLOG_TABLE
                        .' WHERE log_id IN ('.implode(',', db_input($_POST['ids'])).')';
                    if(db_query($sql) && ($num=db_affected_rows())){
                        if($num==$count)
                            $msg='Journaux sélectionnés supprimés avec succès';
                        else
                            $warn="Suppression de $num journaux sélectionnés sur $count";
                    } elseif(!$errors['err'])
                        $errors['err']='Impossible de supprimer les journaux sélectionnés';
                } else {
                    $errors['err']='Action inconnue - Demandez de l\'aide au support technique';
                }
            }
            break;
        default:
            $errors['err']='Commande/action inconnue';
            break;
    }
}

$page='syslogs.inc.php';
$nav->setTabActive('dashboard');
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.system_logs" />',
    "$('#content').data('tipNamespace', 'dashboard.system_logs');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
