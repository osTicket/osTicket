<?php
require('admin.inc.php');
require_once(INCLUDE_DIR.'class.list.php');


$list=null;
$criteria=array();
$redirect = false;
if ($_REQUEST['id'])
    $criteria['id'] = $_REQUEST['id'];
elseif ($_REQUEST['type'])
    $criteria['type'] = $_REQUEST['type'];

if ($criteria) {
    $list = DynamicList::lookup($criteria);
    if ($list)
        $list = CustomListHandler::forList($list);
    if ($list)
         $form = $list->getForm();
    else
        $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
            __('custom list'));
}

$errors = array();

if($_POST) {
    switch(strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$list)
                $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
                    __('custom list'));
            elseif ($list->update($_POST, $errors)) {
                // Update item sorting
                if ($list->getSortMode() == 'SortCol') {
                    foreach ($list->getAllItems() as $item) {
                        $id = $item->getId();
                        if (isset($_POST["sort-{$id}"])) {
                            $item->sort = $_POST["sort-$id"];
                            $item->save();
                        }
                    }
                }

                // Update properties
                if (!$errors && ($form = $list->getForm())) {
                    $names = array();
                    $fields = $form->getDynamicFields();
                    foreach ($fields as $field) {
                        $id = $field->get('id');
                        if ($_POST["delete-prop-$id"] == 'on' && $field->isDeletable()) {
                            $fields->remove($field);
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
                    $errors['err'] = sprintf('%s %s',
                        sprintf(__('Unable to update %s.'), __('custom list items')),
                        __('Correct any errors below and try again.'));
                else {
                    $list->_items = null;
                    $msg = sprintf(__('Successfully updated %s.'),
                        __('this custom list'));
                }

            } elseif ($errors)
                $errors['err'] = $errors['err'] ?: sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this custom list')),
                    __('Correct any errors below and try again.'));
            else
                $errors['err']=sprintf(__('Unable to update %s.'), __('this custom list'))
                    .' '.__('Internal error occurred');

            break;
        case 'add':
            if ($list=DynamicList::add($_POST, $errors)) {
                 $form = $list->getForm(true);
                 Messages::success(sprintf(__('Successfully added %s.'), __('this custom list')));
                 $type = array('type' => 'created');
                 Signal::send('object.created', $list, $type);
                 // Redirect to list page
                 $redirect = "lists.php?id={$list->id}#items";
            } elseif ($errors) {
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this custom list')),
                    __('Correct any errors below and try again.'));
            } else {
                $errors['err']=sprintf(__('Unable to add %s.'), __('this custom list'))
                    .' '.__('Internal error occurred');
            }
            break;

        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
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
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected custom list', 'selected custom lists', $count));
                        elseif ($i > 0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected custom list', 'selected custom lists', $count));
                        elseif (!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s. They may be in use.'),
                                _N('selected custom list', 'selected custom lists', $count));
                        break;
                }
            }
            break;

        case 'import-items':
            if (!$list) {
                $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
                    __('custom list'));
            }
            else {
                $status = $list->importFromPost($_FILES['import'] ?: $_POST['pasted']);
                if (is_numeric($status))
                    $msg = sprintf(__('Successfully imported %1$d %2$s'), $status,
                        _N('list item', 'list items', $status));
                else
                    $errors['err'] = $status;
            }
            break;
    }

    if ($form) {
        for ($i=0; isset($_POST["prop-sort-new-$i"]); $i++) {
            if (!$_POST["prop-label-new-$i"])
                continue;
            $field = DynamicFormField::create(array(
                'sort' => $_POST["prop-sort-new-$i"] ?: ++$max_sort,
                'label' => $_POST["prop-label-new-$i"],
                'type' => $_POST["type-new-$i"],
                'name' => $_POST["name-new-$i"],
                'flags' => DynamicFormField::FLAG_ENABLED
                    | DynamicFormField::FLAG_AGENT_VIEW
                    | DynamicFormField::FLAG_AGENT_EDIT,
            ));
            if ($field->isValid()) {
                $form->fields->add($field);
                $field->save();
            }
            else
                $errors["new-$i"] = $field->errors();
        }
    }
}

if ($redirect)
    Http::redirect($redirect);

$page='dynamic-lists.inc.php';
if($list && !strcasecmp(@$_REQUEST['a'],'items') && isset($_SERVER['HTTP_X_PJAX'])) {
    $page='templates/list-items.tmpl.php';
    $pjax_container = @$_SERVER['HTTP_X_PJAX_CONTAINER'];
    require(STAFFINC_DIR.$page);
    // Don't emit the header
    return;
}
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
