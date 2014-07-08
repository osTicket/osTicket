<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.dynamic_forms.php");

$list=null;
if($_REQUEST['id'] && !($list=DynamicList::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
        __('custom list'));

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
                $errors['err'] = sprintf(__('Unable to update %s. Correct any error(s) below and try again.'),
                    __('this custom list'));
            elseif ($list->save(true))
                $msg = sprintf(__('Successfully updated %s'),
                    __('this custom list'));
            else
                $errors['err'] = sprintf(__('Unable to update %s.'), __('this custom list'))
                    .' '.__('Internal error occurred');

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
                    $field->addError(__('Field variable name is not unique'), 'name');
                if (preg_match('/[.{}\'"`; ]/u', $field->get('name')))
                    $field->addError(__('Invalid character in variable name. Please use letters and numbers only.'), 'name');
                if ($field->get('name'))
                    $names[] = $field->get('name');
                if ($field->isValid())
                    $field->save();
                else
                    # notrans (not shown)
                    $errors["field-$id"] = __('Field has validation errors');
                // Keep track of the last sort number
                $max_sort = max($max_sort, $field->get('sort'));
            }
            break;
        case 'add':
            foreach ($fields as $f)
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf(__('%s is required'),
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
                $errors['err'] = sprintf(__('Unable to create %s. Correct any error(s) below and try again.'),
                    __('this custom list'));
            elseif (!$list->save(true))
                $errors['err'] = sprintf(__('Unable to create %s: Unknown internal error'),
                    __('this custom list'));

            $form->set('type', 'L'.$list->get('id'));
            if (!$errors && !$form->save(true))
                $errors['err'] = __('Unable to create properties for custom list: Unknown internal error');
            else
                $msg = sprintf(__('Successfully added %s'),
                    __('this custom list'));
            break;

        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s'),
                    __('one custom list'));
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
                            $msg = sprintf(__('Successfully deleted %s'),
                                _N('selected custom list', 'selected custom lists', $count));
                        elseif ($i > 0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected custom list', 'selected custom lists', $count));
                        elseif (!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s — they may be in use on a custom form'),
                                _N('selected custom list', 'selected custom lists', $count));
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
