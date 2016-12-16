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
    $warn = __('System ban list is empty.');
elseif(!$filter->isActive())
    // XXX: This should never happen and can no longer be enabled via
    // this link
    $warn = __('SYSTEM BAN LIST filter is <b>DISABLED</b>').' - <a href="filters.php">'.__('enable here').'</a>.';

$rule=null; //ban rule obj.
if($filter && $_REQUEST['id'] && !($rule=$filter->getRule($_REQUEST['id'])))
    $errors['err'] = sprintf(__('%s: Unknown or invalid ID.'), __('ban list'));

if($_POST && !$errors && $filter){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$rule){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('ban rule'));
            }elseif(!$_POST['val'] || !Validator::is_email($_POST['val'])){
                $errors['err']=$errors['val']=__('Valid email address is required');
            }elseif(!$errors){
                $vars=array('what'=>'email',
                            'how'=>'equal',
                            'val'=>trim($_POST['val']),
                            'filter_id'=>$filter->getId(),
                            'isactive'=>$_POST['isactive'],
                            'notes'=>$_POST['notes']);
                if($rule->update($vars,$errors)){
                    $msg=sprintf(__('Successfully updated %s.'), Format::htmlchars($_POST['val']));
                }elseif(!$errors['err']){
                    $errors['err'] = sprintf('%s %s',
                        sprintf(__('Unable to update %s.'), __('this ban rule')),
                        __('Correct any errors below and try again.'));
                }
            }
            break;
        case 'add':
            if(!$filter) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('ban list'));
            }elseif(!$_POST['val'] || !Validator::is_email($_POST['val'])) {
                $errors['err']=$errors['val']=__('Valid email address is required');
            }elseif(BanList::includes(trim($_POST['val']))) {
                $errors['err']=$errors['val']=__('Email already in the ban list');
            }elseif($filter->addRule('email','equal',trim($_POST['val']),array('isactive'=>$_POST['isactive'],'notes'=>$_POST['notes']))) {
                $msg=__('Email address added to ban list successfully');
                $_REQUEST['a']=null;
                //Add filter rule here.
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s', sprintf(__('Error creating %s.'), __('ban rule')), __('Please try again!'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s to process.'),
                    __('one email'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.FILTER_RULE_TABLE.' SET isactive=1 '
                            .' WHERE filter_id='.db_input($filter->getId())
                            .' AND id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = sprintf(__('Successfully enabled %s'),
                                    _N('selected ban rule', 'selected ban rules', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s enabled'), $num, $count,
                                    _N('selected ban rule', 'selected ban rules', $count));
                        } else  {
                            $errors['err'] = sprintf(__('Unable to enable %s'),
                                _N('selected ban rule', 'selected ban rules', $count));
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.FILTER_RULE_TABLE.' SET isactive=0 '
                            .' WHERE filter_id='.db_input($filter->getId())
                            .' AND id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected ban rule', 'selected ban rules', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected ban rule', 'selected ban rules', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected ban rule', 'selected ban rules', $count));
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($r=FilterRule::lookup($v)) && $r->getFilterId()==$filter->getId() && $r->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected ban rule', 'selected ban rules', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected ban rule', 'selected ban rules', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s.'),
                                _N('selected ban rule', 'selected ban rules', $count));

                        break;
                    default:
                        $errors['err'] = sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
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
