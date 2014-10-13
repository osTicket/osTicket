<?php
require('admin.inc.php');
require_once(INCLUDE_DIR.'class.list.php');


$list=null;
$criteria=array();
if ($_REQUEST['id'])
    $criteria['id'] = $_REQUEST['id'];
elseif ($_REQUEST['type'])
    $criteria['type'] = $_REQUEST['type'];

if ($criteria) {
    $list = DynamicList::lookup($criteria);

    if ($list)
         $form = $list->getForm();
    else
        $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
            __('custom list'));
}

$errors = array();
$max_isort = 0;

if($_POST) {
    switch(strtolower($_POST['do'])) {
        case 'update':
            if (!$list)
                $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
                    __('custom list'));
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
                        $errors["value-$id"] = __('Value required');
                    elseif (in_array($value, $items))
                        $errors["value-$id"] = __('Value already in-use');
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
                            $field->addError(__('Field variable name is not unique'), 'name');
                        if (preg_match('/[.{}\'"`; ]/u', $field->get('name')))
                            $field->addError(__('Invalid character in variable name. Please use letters and numbers only.'), 'name');
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
                     $errors['err'] = $errors['err'] ?: sprintf(__('Unable to update %s. Correct error(s) below and try again!'),
                        __('custom list items'));
                else {
                    $list->_items = null;
                    $msg = sprintf(__('Successfully updated %s'),
                        __('this custom list'));
                }

            } elseif ($errors)
                $errors['err'] = $errors['err'] ?: sprintf(__('Unable to update %s. Correct error(s) below and try again!'),
                    __('this custom list'));
            else
                $errors['err']=sprintf(__('Unable to update %s.'), __('this custom list'))
                    .' '.__('Internal error occurred');

            break;
        case 'add':
            if ($list=DynamicList::add($_POST, $errors)) {
                 $msg = sprintf(__('Successfully added %s'),
                    __('this custom list'));
            } elseif ($errors) {
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this custom list'));
            } else {
                $errors['err']=sprintf(__('Unable to add %s.'), __('this custom list'))
                    .' '.__('Internal error occurred');
            }
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

    if ($list && $list->allowAdd()) {
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
