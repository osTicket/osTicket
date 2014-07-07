<?php
require('admin.inc.php');
require_once(INCLUDE_DIR.'class.list.php');


$list=null;
if ($_REQUEST['id']) {
    if (is_numeric($_REQUEST['id']))
        $list = DynamicList::lookup($_REQUEST['id']);
    else
        $list = BuiltInCustomList::lookup($_REQUEST['id']);

    if ($list)
         $form = $list->getForm();
    else
        $errors['err'] = 'Unknown or invalid dynamic list ID.';
}

$errors = array();
$max_isort = 0;

if($_POST) {
    switch(strtolower($_POST['do'])) {
        case 'update':
            if (!$list)
                $errors['err'] = 'Unknown or invalid list';
            elseif ($list->update($_POST, $errors)) {
                // Update items
                $items = array();
                foreach ($list->getAllItems() as $item) {
                    $id = $item->getId();
                    if ($_POST["delete-item-$id"] == 'on' && $item->isDeletable()) {
                        $item->delete();
                        continue;
                    }

                    $ht = array(
                            'value' => $_POST["value-$id"],
                            'abbrev' => $_POST["abbrev-$id"],
                            'sort' => $_POST["sort-$id"],
                            );
                    $value = mb_strtolower($ht['value']);
                    if (!$value)
                        $errors["value-$id"] = 'Value required';
                    elseif (in_array($value, $items))
                        $errors["value-$id"] = 'Value already in-use';
                    elseif ($item->update($ht, $errors)) {
                        if ($_POST["disable-$id"] == 'on')
                            $item->disable();
                        elseif(!$item->isEnabled() && $item->isEnableable())
                            $item->enable();

                        $item->save();
                        $items[] = $value;
                    }

                    $max_isort = max($max_isort, $_POST["sort-$id"]);
                }

                // Update properties
                if (!$errors && ($form = $list->getForm())) {
                    $names = array();
                    foreach ($form->getDynamicFields() as $field) {
                        $id = $field->get('id');
                        if ($_POST["delete-prop-$id"] == 'on' && $field->isDeletable()) {
                            $field->delete();
                            // Don't bother updating the field
                            continue;
                        }
                        if (isset($_POST["type-$id"]) && $field->isChangeable())
                            $field->set('type', $_POST["type-$id"]);
                        if (isset($_POST["name-$id"]) && !$field->isNameForced())
                            $field->set('name', $_POST["name-$id"]);

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
                }

                if ($errors)
                     $errors['err'] = $errors['err'] ?: 'Unable to update custom list items.  Correct any error(s) and try again.';
                else
                    $msg = 'Custom list updated successfully';

            } elseif ($errors)
                $errors['err'] = 'Unable to update custom list. Correct any error(s) below and try again.';
            else
                $errors['err'] = 'Unable to update custom list. Unknown internal error';

            break;
        case 'add':
            if ($list=DynamicList::add($_POST, $errors)) {
                 $msg = 'Custom list added successfully';
            } elseif ($errors) {
                $errors['err'] = 'Unable to create custom list. Correct any
                    error(s) below and try again.';
            } else {
                $errors['err'] = 'Unable to create custom list: Unknown internal error';
            }
            break;

        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one custom list';
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
        for ($i=0; isset($_POST["sort-new-$i"]); $i++) {
            if (!$_POST["value-new-$i"])
                continue;

            $list->addItem(array(
                        'value' => $_POST["value-new-$i"],
                        'abbrev' =>$_POST["abbrev-new-$i"],
                        'sort' => $_POST["sort-new-$i"] ?: ++$max_isort,
                        ), $errors);
        }
    }

    if ($form) {
        for ($i=0; isset($_POST["prop-sort-new-$i"]); $i++) {
            if (!$_POST["prop-label-new-$i"])
                continue;
            $field = DynamicFormField::create(array(
                'form_id' => $form->get('id'),
                'sort' => $_POST["prop-sort-new-$i"] ?: ++$max_sort,
                'label' => $_POST["prop-label-new-$i"],
                'type' => $_POST["type-new-$i"],
                'name' => $_POST["name-new-$i"],
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
