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
    $errors['err']='Unknown or invalid API key ID.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$api){
                $errors['err']='Unknown or invalid API key.';
            }elseif($api->update($_POST,$errors)){
                $msg='API key updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Error updating API key. Try again!';
            }
            break;
        case 'add':
            if(($id=API::add($_POST,$errors))){
                $msg='API key added successfully';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Unable to add an API key. Correct error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one API key';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.API_KEY_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected API keys enabled';
                            else
                                $warn = "$num of $count selected API keys enabled";
                        } else {
                            $errors['err'] = 'Unable to enable selected API keys.';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.API_KEY_TABLE.' SET isactive=0 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected API keys disabled';
                            else
                                $warn = "$num of $count selected API keys disabled";
                        } else {
                            $errors['err']='Unable to disable selected API keys';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=API::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = 'Selected API keys deleted successfully';
                        elseif($i>0)
                            $warn = "$i of $count selected API keys deleted";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to delete selected API keys';
                        break;
                    default:
                        $errors['err']='Unknown action - get technical help';
                }
            }
            break;
        default:
            $errors['err']='Unknown action/command';
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
