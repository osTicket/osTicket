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

        // Redirect to the group page for additions
        if ($_POST['do'] == 'add-template')
            unset($template);
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
        $flags = new SqlField('flags');
        $count = TaskTemplate::objects()
            ->filter(['id__in' => $_POST['ids']])
            ->update(['flags' => $flags->bitor(TaskTemplate::FLAG_ENABLED)]);

        if ($count)
            Messages::success(sprintf(__('Successfully enabled %s'),
                sprintf(_N('one task template', '%d task templates', $count), $count)));
        break;

    case 'disable':
        $flags = new SqlField('flags');
        $count = TaskTemplate::objects()
            ->filter(['id__in' => $_POST['ids']])
            ->update(['flags' => $flags->bitand(~TaskTemplate::FLAG_ENABLED)]);
        if ($count)
            Messages::success(sprintf(__('Successfully disabled %s'),
                sprintf(_N('one task template', '%d task templates', $count), $count)));
        break;

    case 'delete':
        // Deleting is a bit different. If there are no tasks which are
        // based on this template, then the template can be safely removed.
        // Otherwise, it should be marked as deleted.
        $count = 0;
        foreach (TaskTemplate::objects()
            ->filter(['id__in' => $_POST['ids']])
            ->annotate(['inuse' => SqlAggregate::COUNT('instances')])
        as $template) {
            if ($template->inuse) {
                $template->setFlag(TaskTemplate::FLAG_DELETED);
                if ($template->save())
                    $count++;
            }
            else {
                if ($template->delete())
                    $count++;
            }
        }
        if ($count)
            Messages::success(sprintf(__('Successfully deleted %s.'),
                sprintf(_N('one task template', '%d task templates', $count), $count)));
        unset($template);
        break;
    }
}

$page='task-template-sets.inc.php';
if ($template)
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
