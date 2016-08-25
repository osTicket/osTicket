<?php
require('admin.inc.php');

$set = null;
if ($_REQUEST['group_id'] && !($set=TaskTemplateGroup::lookup($_REQUEST['group_id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('task template set'));

$template = null;
if ($_REQUEST['tpl_id'] && !($template=TaskTemplate::lookup($_REQUEST['tpl_id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('task template'));

if ($_POST) {
    switch ($_POST['do']) {
    case 'add-template':
        if (!$set instanceof TaskTemplateGroup)
            break;

        $template = new TaskTemplate(array(
            'group_id' => $_POST['group_id'],
        ));

        // Fall through to the update routine
    case 'update':
        $errors = array();
        if (!$template->update($_POST, $errors)) {
            foreach ($errors as $e)
                Messages::error($e);
        }
        elseif (!$template->save()) {
            Messages::error(sprintf(__('Unable to commit %s. Check validation errors'),
                __('this task template')));
        }
        elseif (!$template->updateForms($_POST['forms'] ?: array(), $_POST['fields'] ?: array())) {
            Messages::warning(__('Unable to update associated forms'));
        }
        else {
            Messages::success(__('FIXME Successfully created ...'));
        }
        break;

    // Called from the set-list page to update sort order
    case 'resort':
        $i = 0;
        $tasks = $set->getTemplates();
        foreach ($_POST['sort'] as $tplid) {
            if (isset($tasks[$tplid])) {
                $T = $tasks[$tplid];
                $T->sort = $i++; 
                $T->save();
            }
        }
        break;

    case 'enable':
    case 'disable':
    }
}

$page='task-template-sets.inc.php';
if ($template || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'], 'add-tpl')))
    $page='task-template.inc.php';
elseif ($set)
    $page='task-template-set.inc.php';

$ost->addExtraHeader('<meta name="tip-namespace" content="task-templates" />',
    "$('#content').data('tipNamespace', 'task-templates');");
$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
