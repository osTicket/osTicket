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
    $errors['err']=__('Unknown or invalid filter.');

/* NOTE: Banlist has its own interface*/
if($filter && $filter->isSystemBanlist())
    header('Location: banlist.php');

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$filter){
                $errors['err']=__('Unknown or invalid filter.');
            }elseif($filter->update($_POST,$errors)){
                $msg=__('Filter updated successfully');
            }elseif(!$errors['err']){
                $errors['err']=__('Error updating filter. Try again!');
            }
            break;
        case 'add':
            if((Filter::create($_POST,$errors))){
                $msg=__('Filter added successfully');
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=__('Unable to add filter. Correct error(s) below and try again.');
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = __('You must select at least one filter to process.');
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.FILTER_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = __('Selected filters enabled');
                            else
                                $warn = sprintf(__('%1$d of %2$d selected filters enabled'), $num, $count);
                        } else {
                            $errors['err'] = __('Unable to enable selected filters');
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.FILTER_TABLE.' SET isactive=0 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = __('Selected filters disabled');
                            else
                                $warn = sprintf(__('%1$d of %2$d selected filters disabled'), $num, $count);
                        } else {
                            $errors['err'] = __('Unable to disable selected filters');
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($f=Filter::lookup($v)) && !$f->isSystemBanlist() && $f->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = __('Selected filters deleted successfully');
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d selected filters deleted'), $i, $count);
                        elseif(!$errors['err'])
                            $errors['err'] = __('Unable to delete selected filters');
                        break;
                    default:
                        $errors['err']=__('Unknown action - get technical help');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown command/action');
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
