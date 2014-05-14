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
    $errors['err']='Unknown or invalid category ID.';

if($_POST){
    switch(strtolower($_POST['do'])) {
        case 'update':
            if(!$category) {
                $errors['err']='Unknown or invalid category.';
            } elseif($category->update($_POST,$errors)) {
                $msg='Category updated successfully';
            } elseif(!$errors['err']) {
                $errors['err']='Error updating category. Try again!';
            }
            break;
        case 'create':
            if(($id=Category::create($_POST,$errors))) {
                $msg='Category added successfully';
                $_REQUEST['a']=null;
            } elseif(!$errors['err']) {
                $errors['err']='Unable to add category. Correct error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='You must select at least one category';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $sql='UPDATE '.FAQ_CATEGORY_TABLE.' SET ispublic=1 '
                            .' WHERE category_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected categories made PUBLIC';
                            else
                                $warn = "$num of $count selected categories made PUBLIC";
                        } else {
                            $errors['err'] = 'Unable to enable selected categories public.';
                        }
                        break;
                    case 'make_private':
                        $sql='UPDATE '.FAQ_CATEGORY_TABLE.' SET ispublic=0 '
                            .' WHERE category_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected categories made PRIVATE';
                            else
                                $warn = "$num of $count selected categories made PRIVATE";
                        } else {
                            $errors['err'] = 'Unable to disable selected categories PRIVATE';
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($c=Category::lookup($v)) && $c->delete())
                                $i++;
                        }

                        if($i==$count)
                            $msg = 'Selected categories deleted successfully';
                        elseif($i>0)
                            $warn = "$i of $count selected categories deleted";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to delete selected categories';
                        break;
                    default:
                        $errors['err']='Unknown action/command';
                }
            }
            break;
        default:
            $errors['err']='Unknown action';
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
