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
    $errors['err']=sprintf(__('%s: Unknown or invalid'), __('FAQ article'));

if($_REQUEST['cid'] && !$faq && !($category=Category::lookup($_REQUEST['cid'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid'), __('FAQ category'));

$form_fields = array(
    // Attachments for all languages — that is, attachments not specific to
    // a particular language
    'attachments' => new FileUploadField(array('id'=>'attach',
        'configuration'=>array('extensions'=>false,
            'size'=>$cfg->getMaxFileSize())
    )),
);

// Build attachment lists for language-specific attachment fields
if ($langs = $cfg->getSecondaryLanguages()) {
    // Primary-language specific files
    $langs[] = $cfg->getPrimaryLanguage();
    // Secondary-language specific files
    foreach ($langs as $l) {
        $form_fields['attachments.'.$l] = new FileUploadField(array(
            'id'=>'attach','name'=>'attach:'.$l,
            'configuration'=>array('extensions'=>false,
                'size'=>$cfg->getMaxFileSize())
        ));
    }
}

$faq_form = new SimpleForm($form_fields, $_POST);

if ($_POST) {
    $errors=array();
    // General attachments
    $_POST['files'] = $faq_form->getField('attachments')->getClean();
    // Language-specific attachments
    if ($langs) {
        $langs[] = $cfg->getPrimaryLanguage();
        foreach ($langs as $lang) {
            $_POST['files_'.$lang] = $faq_form->getField('attachments.'.$lang)->getClean();
        }
    }
    switch(strtolower($_POST['do'])) {
        case 'create':
        case 'add':
            $faq = FAQ::create();
            if($faq->update($_POST,$errors)) {
                $msg=sprintf(__('Successfully added %s.'), Format::htmlchars($faq->getQuestion()));
                // Delete draft for this new faq
                Draft::deleteForNamespace('faq', $thisstaff->getId());
            } elseif(!$errors['err'])
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this FAQ article')),
                    __('Correct any errors below and try again.'));
        break;
        case 'update':
        case 'edit':
            if(!$faq)
                $errors['err'] = sprintf(__('%s: Invalid or unknown'), __('FAQ article'));
            elseif($faq->update($_POST,$errors)) {
                $msg=sprintf(__('Successfully updated %s.'), __('this FAQ article'));
                $_REQUEST['a']=null; //Go back to view
                // Delete pending draft updates for this faq (for ALL users)
                Draft::deleteForNamespace('faq.'.$faq->getId());
            } elseif(!$errors['err'])
                $errors['err'] = sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this FAQ article')),
                    __('Correct any errors below and try again.'));
            break;
        case 'manage-faq':
            if(!$faq) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('FAQ article'));
            } else {
                switch(strtolower($_POST['a'])) {
                    case 'edit':
                        $_GET['a']='edit';
                        break;
                    case 'publish';
                        if($faq->publish())
                            $msg=sprintf(__('Successfully published %s'), __('this FAQ article'));
                        else
                            $errors['err']=sprintf(__('Unable to publish %s. Try editing it.'),
                                __('this FAQ article'));
                        break;
                    case 'unpublish';
                        if($faq->unpublish())
                            $msg=sprintf(__('Successfully unpublished %s'), __('this FAQ article'));
                        else
                            $errors['err']=sprintf(__('Unable to unpublish %s. Try editing it.'), __('this FAQ article'));
                        break;
                    case 'delete':
                        $category = $faq->getCategory();
                        if($faq->delete()) {
                            $msg=sprintf(__('Successfully deleted %s.'), Format::htmlchars($faq->getQuestion()));
                            $faq=null;
                        } else {
                            $errors['err']=sprintf(__('Unable to delete %s.'), __('this FAQ article'));
                        }
                        break;
                    default:
                        $errors['err']=__('Invalid action');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');

    }
}
else {
    // Not a POST — load database-backed attachments to attachment fields
    if ($langs && $faq) {
        // Multi-lingual system
        foreach ($langs as $lang) {
            $attachments = $faq_form->getField('attachments.'.$lang);
            $attachments->setAttachments($faq->getAttachments($lang)->window(array('inline' => false)));
        }
    }
    if ($faq) {
        // Common attachments
        $attachments = $faq_form->getField('attachments');
        $attachments->setAttachments($faq->getAttachments()->window(array('inline' => false)));
    }
}

$inc='faq-categories.inc.php'; //FAQs landing page.
if($faq) {
    $inc='faq-view.inc.php';
    if ($_REQUEST['a']=='edit'
            && $thisstaff->hasPerm(FAQ::PERM_MANAGE))
        $inc='faq.inc.php';
    elseif ($_REQUEST['a'] == 'print')
        return $faq->printPdf();
}elseif($_REQUEST['a']=='add'
        && $thisstaff->hasPerm(FAQ::PERM_MANAGE)) {
    $inc='faq.inc.php';
} elseif($category && $_REQUEST['a']!='search') {
    $inc='faq-category.inc.php';
}
$tip_namespace = 'knowledgebase.faq';
$nav->setTabActive('kbase');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
print $faq_form->getMedia();
require_once(STAFFINC_DIR.'footer.inc.php');
?>
