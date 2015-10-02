<?php
/*********************************************************************
    emails.php

    Emails

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.email.php');

$email=null;
if($_REQUEST['id'] && !($email=Email::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('email'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$email){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('email'));
            }elseif($email->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s'),
                    __('this email'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Error updating %s. Try again!'), __('this email'));
            }
            break;
        case 'create':
            $box = Email::create();
            if ($box->update($_POST, $errors)) {
                $id = $box->getId();
                $msg=sprintf(__('Successfully added %s'), Format::htmlchars($_POST['name']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this email'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s'),
                    __('one email'));
            } else {
                $count=count($_POST['ids']);

                switch (strtolower($_POST['a'])) {
                case 'delete':
                    $i=0;
                    foreach($_POST['ids'] as $k=>$v) {
                        if($v!=$cfg->getDefaultEmailId() && ($e=Email::lookup($v)) && $e->delete())
                            $i++;
                    }

                    if($i && $i==$count)
                        $msg = sprintf(__('Successfully deleted %s'),
                            _N('selected email', 'selected emails', $count));
                    elseif($i>0)
                        $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                            _N('selected email', 'selected emails', $count));
                    elseif(!$errors['err'])
                        $errors['err'] = sprintf(__('Unable to delete %s'),
                            _N('selected email', 'selected emails', $count));
                    break;

                default:
                    $errors['err'] = __('Unknown action - get technical help.');
                }
            }
            break;
        default:
            $errors['err'] = __('Unknown action');
            break;
    }
}

$page='emails.inc.php';
$tip_namespace = 'emails.email';
if($email || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='email.inc.php';
}

$nav->setTabActive('emails');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
