<?php
/*********************************************************************
    apikeys.php

    API keys.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.api.php');

$api=null;
if($_REQUEST['id'] && !($api=API::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('API key'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$api){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('API key'));
            }elseif($api->update($_POST,$errors)){
                $msg=sprintf(__('Succesfully updated %s'), __('this API key'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Error updating %s. Try again!'), __('this API key'));
            }
            break;
        case 'add':
            if(($id=API::add($_POST,$errors))){
                $msg=sprintf(__('Successfully added %s'), __('an API key'));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this API key'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s'), __('one API key'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.API_KEY_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully enabled %s'),
                                    _N('selected API key', 'selected API keys', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s enabled'), $num, $count,
                                    _N('selected API key', 'selected API keys', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to enable %s.'),
                                _N('selected API key', 'selected API keys', $count));
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.API_KEY_TABLE.' SET isactive=0 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected API key', 'selected API keys', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected API key', 'selected API keys', $count));
                        } else {
                            $errors['err']=sprintf(__('Unable to disable %s'),
                                _N('selected API key', 'selected API keys', $count));
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=API::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s'),
                                _N('selected API key', 'selected API keys', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $num, $count,
                                _N('selected API key', 'selected API keys', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s'),
                                _N('selected API key', 'selected API keys', $count));
                        break;
                    default:
                        $errors['err']=__('Unknown action - get technical help.');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
}

$page='apikeys.inc.php';
$tip_namespace = 'manage.api_keys';

if($api || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add')))
    $page = 'apikey.inc.php';

$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
