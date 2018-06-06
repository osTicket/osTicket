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
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
        __('SLA Plan'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$sla){
                $errors['err']=sprintf(__('%s: Unknown or invalid'),
                    __('SLA Plan'));
            }elseif($sla->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s.'),
                    __('this SLA plan'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this SLA plan')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'add':
            $_sla = SLA::create();
            if (($_sla->update($_POST, $errors))) {
                $msg=sprintf(__('Successfully added %s.'),
                    __('a SLA plan'));
                $_REQUEST['a']=null;
            } elseif (!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this SLA plan')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one SLA plan'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $num = SLA::objects()->filter(array(
                            'id__in' => $_POST['ids']
                        ))->update(array(
                            'flags' => SqlExpression::bitor(
                                new SqlField('flags'), SLA::FLAG_ACTIVE)
                        ));
                        if ($num) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully enabled %s'),
                                    _N('selected SLA plan', 'selected SLA plans', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s enabled'), $num, $count,
                                    _N('selected SLA plan', 'selected SLA plans', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to enable %s'),
                                _N('selected SLA plan', 'selected SLA plans', $count));
                        }
                        break;
                    case 'disable':
                        $num = SLA::objects()->filter(array(
                            'id__in' => $_POST['ids']
                        ))->update(array(
                            'flags' => SqlExpression::bitand(
                                new SqlField('flags'), ~SLA::FLAG_ACTIVE)
                        ));

                        if ($num) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected SLA plan', 'selected SLA plans', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected SLA plan', 'selected SLA plans', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected SLA plan', 'selected SLA plans', $count));
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach ($_POST['ids'] as $k => $v) {
                            if (($p=SLA::lookup($v))
                                && $p->getId() != $cfg->getDefaultSLAId()
                                && $p->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected SLA plan', 'selected SLA plans', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected SLA plan', 'selected SLA plans', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s.'),
                                _N('selected SLA plan', 'selected SLA plans', $count));
                        break;
                    default:
                        $errors['err']=sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
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
