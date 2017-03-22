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
if(!$thisstaff ||
        !$thisstaff->hasPerm(FAQ::PERM_MANAGE)) {
    header('Location: kb.php');
    exit;
}


$category=null;
if($_REQUEST['id'] && !($category=Category::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('Category'));

if($_POST){
    switch(strtolower($_POST['do'])) {
        case 'update':
            if(!$category) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('Category'));
            } elseif($category->update($_POST,$errors)) {
                $msg=sprintf(__('Successfully updated %s.'),
                    __('this category'));
            } elseif(!$errors['err']) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this category')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'create':
            $category = Category::create();
            if ($category->update($_POST, $errors)) {
                $msg=sprintf(__('Successfully added %s.'), Format::htmlchars($_POST['name']));
                $_REQUEST['a']=null;
            } elseif(!$errors['err']) {
                $errors['err'] = sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this category')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']=sprintf(__('You must select at least %s.'), __('one category'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $num = Category::objects()->filter(array(
                            'category_id__in'=>$_POST['ids']
                        ))->update(array(
                            'ispublic'=>true
                        ));
                        if ($num > 0) {
                            if ($num==$count)
                                $msg = sprintf(__('Successfully made %s PUBLIC'),
                                    _N('selected category', 'selected categories', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s made PUBLIC'), $num, $count,
                                    _N('selected category', 'selected categories', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to make %s PUBLIC.'),
                                _N('selected category', 'selected categories', $count));
                        }
                        break;
                    case 'make_private':
                        $num = Category::objects()->filter(array(
                            'category_id__in'=>$_POST['ids']
                        ))->update(array(
                            'ispublic'=>false
                        ));
                        if ($num > 0) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully made %s PRIVATE'),
                                    _N('selected category', 'selected categories', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s made PRIVATE'), $num, $count,
                                    _N('selected category', 'selected categories', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to make %s PRIVATE'),
                                _N('selected category', 'selected categories', $count));
                        }
                        break;
                    case 'delete':
                        $i = Category::objects()->filter(array(
                            'category_id__in'=>$_POST['ids']
                        ))->delete();

                        if ($i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected category', 'selected categories', $count));
                        elseif ($i > 0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected category', 'selected categories', $count));
                        elseif (!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s.'),
                                _N('selected category', 'selected categories', $count));
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
