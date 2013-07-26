<?php
/*********************************************************************
    faq.php

    FAQs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.faq.php');

$faq=$category=null;
if($_REQUEST['id'] && !($faq=FAQ::lookup($_REQUEST['id'])))
   $errors['err']='Unknown or invalid FAQ';

if($_REQUEST['cid'] && !$faq && !($category=Category::lookup($_REQUEST['cid'])))
    $errors['err']='Unknown or invalid FAQ category';

if($_POST):
    $errors=array();
    switch(strtolower($_POST['do'])) {
        case 'create':
        case 'add':
            if(($faq=FAQ::add($_POST,$errors))) {
                $msg='FAQ added successfully';
                // Delete draft for this new faq
                Draft::deleteForNamespace('faq', $thisstaff->getId());
            } elseif(!$errors['err'])
                $errors['err'] = 'Unable to add FAQ. Try again!';
        break;
        case 'update':
        case 'edit';
            if(!$faq)
                $errors['err'] = 'Invalid or unknown FAQ';
            elseif($faq->update($_POST,$errors)) {
                $msg='FAQ updated successfully';
                $_REQUEST['a']=null; //Go back to view
                $faq->reload();
                // Delete pending draft updates for this faq (for ALL users)
                Draft::deleteForNamespace('faq.'.$faq->getId());
            } elseif(!$errors['err'])
                $errors['err'] = 'Unable to update FAQ. Try again!';
            break;
        case 'manage-faq':
            if(!$faq) {
                $errors['err']='Unknown or invalid FAQ';
            } else {
                switch(strtolower($_POST['a'])) {
                    case 'edit':
                        $_GET['a']='edit';
                        break;
                    case 'publish';
                        if($faq->publish())
                            $msg='FAQ published successfully';
                        else
                            $errors['err']='Unable to publish the FAQ. Try editing it.';
                        break;
                    case 'unpublish';
                        if($faq->unpublish())
                            $msg='FAQ unpublished successfully';
                        else
                            $errors['err']='Unable to unpublish the FAQ. Try editing it.';
                        break;
                    case 'delete':
                        $category = $faq->getCategory();
                        if($faq->delete()) {
                            $msg='FAQ deleted successfully';
                            $faq=null;
                        } else {
                            $errors['err']='Unable to delete FAQ. Try again';
                        }
                        break;
                    default:
                        $errors['err']='Invalid action';
                }
            }
            break;
        default:
            $errors['err']='Unknown action';

    }
endif;


$inc='faq-categories.inc.php'; //FAQs landing page.
if($faq) {
    $inc='faq-view.inc.php';
    if($_REQUEST['a']=='edit' && $thisstaff->canManageFAQ())
        $inc='faq.inc.php';
}elseif($_REQUEST['a']=='add' && $thisstaff->canManageFAQ()) {
    $inc='faq.inc.php';
} elseif($category && $_REQUEST['a']!='search') {
    $inc='faq-category.inc.php';
}
$nav->setTabActive('kbase');
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
