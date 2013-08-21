<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.dynamic_forms.php");

$group=null;
if($_REQUEST['id'] && !($group=DynamicFormset::lookup($_REQUEST['id'])))
    $errors['err']='Unknown or invalid dynamic form ID.';

if($_POST) {
    $fields = array('title', 'notes');
    $deleted = array();
    switch(strtolower($_POST['do'])) {
        case 'update':
            foreach ($fields as $f)
                if (isset($_POST[$f]))
                    $group->set($f, $_POST[$f]);
            foreach ($group->getForms() as $idx=>$form) {
                $id = $form->get('id');
                if ($_POST["delete-$id"] == 'on') {
                    // Don't delete yet, in case this makes the formset
                    // invalid. XXX: When is osTicket going to adopt database
                    // transactions?
                    unset($group->_forms[$idx]);
                    $deleted[] = $form;
                    continue;
                }
                foreach (array('sort','section_id') as $f)
                    if (isset($_POST["$f-$id"]))
                        $form->set($f, $_POST["$f-$id"]);
                if ($form->isValid())
                    $form->save();
            }
            break;
        case 'add':
            $group = DynamicFormset::create(array(
                'title'=>$_POST['title'],
                'notes'=>$_POST['notes']));
            break;
    }

    if ($group) {
        for ($i=0; isset($_POST["sort-new-$i"]); $i++) {
            if (!$_POST["section_id-new-$i"])
                continue;
            $form = DynamicFormsetSections::create(array(
                'formset_id'=>$group->get('id'),
                'sort'=>$_POST["sort-new-$i"],
                'section_id'=>$_POST["section_id-new-$i"],
            ));
            // XXX: Use an instrumented list to make this better
            $group->_forms[] = $form;
            if ($form->isValid())
                $form->save();
        }

        if ($group->isValid()) {
            $new = $group->__new__;
            $group->save();
            // Add the correct 'id' value to the attached form sections
            if ($new) {
                foreach ($group->getForms() as $form) {
                    $form->set('formset_id', $group->get('id'));
                    $form->save();
                }
            }
            // Now delete requested items
            foreach ($deleted as $form)
                $form->delete();
        }
        else
            $errors = array_merge($errors, $group->errors());
    }
}

$page='dynamic-forms.inc.php';
if($group || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add')))
    $page='dynamic-form.inc.php';

$nav->setTabActive('forms');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
