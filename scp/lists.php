<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.dynamic_forms.php");

$list=null;
if($_REQUEST['id'] && !($list=DynamicList::lookup($_REQUEST['id'])))
    $errors['err']='Unknown or invalid dynamic list ID.';

if ($list)
    $form = $list->getForm();

if($_POST) {
    $fields = array('name', 'name_plural', 'sort_mode', 'notes');
    $required = array('name');
    switch(strtolower($_POST['do'])) {
        case 'update':
            foreach ($fields as $f)
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf('%s is required',
                        mb_convert_case($f, MB_CASE_TITLE));
                elseif (isset($_POST[$f]))
                    $list->set($f, $_POST[$f]);
            if ($errors)
                $errors['err'] = 'Unable to update custom list. Correct any error(s) below and try again.';
            elseif ($list->save(true))
                $msg = 'Custom list updated successfully';
            else
                $errors['err'] = 'Unable to update custom list. Unknown internal error';

            foreach ($list->getAllItems() as $item) {
                $id = $item->get('id');
                if ($_POST["delete-$id"] == 'on') {
                    $item->delete();
                    continue;
                }
                foreach (array('sort','value','extra') as $i)
                    if (isset($_POST["$i-$id"]))
                        $item->set($i, $_POST["$i-$id"]);

                if ($_POST["disable-$id"] == 'on')
                    $item->disable();
                else
                    $item->enable();

                $item->save();
            }

            $names = array();
            if (!$form) {
                $form = DynamicForm::create(array(
                    'type'=>'L'.$_REQUEST['id'],
                    'title'=>$_POST['name'] . ' Properties'
                ));
                $form->save(true);
            }
            foreach ($form->getDynamicFields() as $field) {
                $id = $field->get('id');
                if ($_POST["delete-$id"] == 'on' && $field->isDeletable()) {
                    $field->delete();
                    // Don't bother updating the field
                    continue;
                }
                if (isset($_POST["type-$id"]) && $field->isChangeable())
                    $field->set('type', $_POST["type-$id"]);
                if (isset($_POST["name-$id"]) && !$field->isNameForced())
                    $field->set('name', $_POST["name-$id"]);
                # TODO: make sure all help topics still have all required fields
                foreach (array('sort','label') as $f) {
                    if (isset($_POST["prop-$f-$id"])) {
                        $field->set($f, $_POST["prop-$f-$id"]);
                    }
                }
                if (in_array($field->get('name'), $names))
                    $field->addError('Field variable name is not unique', 'name');
                if (preg_match('/[.{}\'"`; ]/u', $field->get('name')))
                    $field->addError('Invalid character in variable name. Please use letters and numbers only.', 'name');
                if ($field->get('name'))
                    $names[] = $field->get('name');
                if ($field->isValid())
                    $field->save();
                else
                    # notrans (not shown)
                    $errors["field-$id"] = 'Field has validation errors';
                // Keep track of the last sort number
                $max_sort = max($max_sort, $field->get('sort'));
            }
            break;
        case 'add':
            foreach ($fields as $f)
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf('%s is required',
                        mb_convert_case($f, MB_CASE_TITLE));
            $list = DynamicList::create(array(
                'name'=>$_POST['name'],
                'name_plural'=>$_POST['name_plural'],
                'sort_mode'=>$_POST['sort_mode'],
                'notes'=>$_POST['notes']));

            $form = DynamicForm::create(array(
                'title'=>$_POST['name'] . ' Properties'
            ));

            if ($errors)
                $errors['err'] = 'Unable to create custom list. Correct any error(s) below and try again.';
            elseif (!$list->save(true))
                $errors['err'] = 'Unable to create custom list: Unknown internal error';

            $form->set('type', 'L'.$list->get('id'));
            if (!$errors && !$form->save(true))
                $errors['err'] = 'Unable to create properties for custom list: Unknown internal error';
            else
                $msg = 'Custom list added successfully';
            break;

        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one API key';
            } else {
                $count = count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=DynamicList::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if ($i && $i==$count)
                            $msg = 'Selected custom lists deleted successfully';
                        elseif ($i > 0)
                            $warn = "$i of $count selected lists deleted";
                        elseif (!$errors['err'])
                            $errors['err'] = 'Unable to delete selected custom lists'
                                .' &mdash; they may be in use on a custom form';
                        break;
                }
            }
            break;
    }

    if ($list) {
        for ($i=0; isset($_POST["prop-sort-new-$i"]); $i++) {
            if (!$_POST["value-new-$i"])
                continue;
            $item = DynamicListItem::create(array(
                'list_id'=>$list->get('id'),
                'sort'=>$_POST["sort-new-$i"],
                'value'=>$_POST["value-new-$i"],
                'extra'=>$_POST["extra-new-$i"]
            ));
            $item->save();
        }
        # Invalidate items cache
        $list->_items = false;
    }

    if ($form) {
        for ($i=0; isset($_POST["prop-sort-new-$i"]); $i++) {
            if (!$_POST["prop-label-new-$i"])
                continue;
            $field = DynamicFormField::create(array(
                'form_id'=>$form->get('id'),
                'sort'=>$_POST["prop-sort-new-$i"]
                    ? $_POST["prop-sort-new-$i"] : ++$max_sort,
                'label'=>$_POST["prop-label-new-$i"],
                'type'=>$_POST["type-new-$i"],
                'name'=>$_POST["name-new-$i"],
            ));
            $field->setForm($form);
            if ($field->isValid())
                $field->save();
            else
                $errors["new-$i"] = $field->errors();
        }
        // XXX: Move to an instrumented list that can handle this better
        if (!$errors)
            $form->_dfields = $form->_fields = null;
    }
}

$page='dynamic-lists.inc.php';
if($list || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='dynamic-list.inc.php';
    $ost->addExtraHeader('<meta name="tip-namespace" content="manage.custom_list" />',
        "$('#content').data('tipNamespace', 'manage.custom_list');");
}

$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
