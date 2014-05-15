<?php
/*********************************************************************
    slas.php

    SLA - Service Level Agreements

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.sla.php');

$sla=null;
if($_REQUEST['id'] && !($sla=SLA::lookup($_REQUEST['id'])))
    $errors['err']='Unknown or invalid API key ID.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$sla){
                $errors['err']='Unknown or invalid SLA plan.';
            }elseif($sla->update($_POST,$errors)){
                $msg='SLA plan updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Error updating SLA plan. Try again!';
            }
            break;
        case 'add':
            if(($id=SLA::create($_POST,$errors))){
                $msg='SLA plan added successfully';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Unable to add SLA plan. Correct error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one plan.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.SLA_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected SLA plans enabled';
                            else
                                $warn = "$num of $count selected SLA plans enabled";
                        } else {
                            $errors['err'] = 'Unable to enable selected SLA plans.';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.SLA_TABLE.' SET isactive=0 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected SLA plans disabled';
                            else
                                $warn = "$num of $count selected SLA plans disabled";
                        } else {
                            $errors['err'] = 'Unable to disable selected SLA plans';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if (($p=SLA::lookup($v))
                                && $p->getId() != $cfg->getDefaultSLAId()
                                && $p->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Selected SLA plans deleted successfully';
                        elseif($i>0)
                            $warn = "$i of $count selected SLA plans deleted";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to delete selected SLA plans';
                        break;
                    default:
                        $errors['err']='Unknown action - get technical help.';
                }
            }
            break;
        default:
            $errors['err']='Unknown action/command';
            break;
    }
}

$page='slaplans.inc.php';
if($sla || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='slaplan.inc.php';
    $ost->addExtraHeader('<meta name="tip-namespace" content="manage.sla" />',
            "$('#content').data('tipNamespace', 'manage.sla');");
}

$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
