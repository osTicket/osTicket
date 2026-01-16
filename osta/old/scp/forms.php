<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.dynamic_forms.php");

$form=null;
if($_REQUEST['id'] && !($form=DynamicForm::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('custom form'));

if($_POST) {
    $_POST = Format::htmlchars($_POST, true);
    $_POST['instructions'] = Format::htmldecode($_POST['instructions']);
    $fields = array('title', 'notes', 'instructions');
    $required = array('title');
    $max_sort = 0;
    $form_fields = array();
    $names = array();
    switch(strtolower($_POST['do'])) {
        case 'update':
            foreach ($fields as $f)
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf(__('%s is required'),
                        mb_convert_case($f, MB_CASE_TITLE));
                elseif (isset($_POST[$f]))
                    $form->set($f, $_POST[$f]);
            $form->save(true);
            foreach ($form->getDynamicFields() as $field) {
                $id = $field->get('id');
                if ($_POST["delete-$id"] == 'on' && $field->isDeletable()) {
                    if ($_POST["delete-data-$id"]) {
                        DynamicFormEntryAnswer::objects()
                            ->filter(array('field_id'=>$id))
                            ->delete();
                    }
                    $field->delete();
                    // Don't bother updating the field
                    continue;
                }
                if (isset($_POST["type-$id"]) && $field->isChangeable())
                    $field->set('type', $_POST["type-$id"]);
                if (isset($_POST["name-$id"]) && !$field->isNameForced())
                    $field->set('name', trim($_POST["name-$id"]));
                # TODO: make sure all help topics still have all required fields
                $field->setRequirementMode($_POST["visibility-$id"]);

                foreach (array('sort','label') as $f) {
                    if (isset($_POST["$f-$id"])) {
                        $field->set($f, $_POST["$f-$id"]);
                    }
                }
                if (in_array(strtolower($field->get('name')), $names))
                    $field->addError(__('Field variable name is not unique'), 'name');
                // Subject (Issue Summary) must always have data
                if ($form->get('type') == 'T' && $field->get('name') == 'subject') {
                    if (($f = $field->getField(false)->getImpl()) && !$f->hasData())
                        $field->addError(__('The issue summary must be a field that supports user input, such as short answer'),
                            'type');
                }
                if ($field->get('name'))
                    $names[] = strtolower($field->get('name'));
                if ($field->isValid())
                    $form_fields[] = $field;
                else
                    # notrans (not shown)
                    $errors["field-$id"] = __('Field has validation errors');
                // Keep track of the last sort number
                $max_sort = max($max_sort, $field->get('sort'));
            }
            $type = array('type' => 'edited');
            Signal::send('object.edited', $form, $type);
            break;
        case 'add':
            $form = DynamicForm::create();
            foreach ($fields as $f) {
                if (in_array($f, $required) && !$_POST[$f])
                    $errors[$f] = sprintf('%s is required',
                        mb_convert_case($f, MB_CASE_TITLE));
                elseif (isset($_POST[$f]))
                    $form->set($f, $_POST[$f]);
            }
            $type = array('type' => 'created');
            Signal::send('object.created', $form, $type);
            break;

        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'), __('one custom form'));
            } else {
                $count = count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=DynamicForm::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if ($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected custom form', 'selected custom forms', $count));
                        elseif ($i > 0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected custom form', 'selected custom forms', $count));
                        elseif (!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s.'),
                                _N('selected custom form', 'selected custom forms', $count));
                        break;
                }
            }
            break;
    }

    if ($form) {
        for ($i=0; isset($_POST["sort-new-$i"]); $i++) {
            if (!$_POST["label-new-$i"])
                continue;
            $field = DynamicFormField::create(array(
                'sort'=>$_POST["sort-new-$i"] ? $_POST["sort-new-$i"] : ++$max_sort,
                'label'=>$_POST["label-new-$i"],
                'type'=>$_POST["type-new-$i"],
                'name'=>trim($_POST["name-new-$i"]),
            ));
            $field->setRequirementMode($_POST["visibility-new-$i"]);
            $form->fields->add($field);
            if (in_array(strtolower($field->get('name')), $names))
                $field->addError(__('Field variable name is not unique'), 'name');
            if ($field->isValid()) {
                $form_fields[] = $field;
                if ($field->get('name'))
                    $names[] = strtolower($field->get('name'));
            }
            else
                $errors["new-$i"] = $field->errors();
        }
        if (!$errors) {
            $form->save(true);
            foreach ($form_fields as $field) {
                $field->form = $form;
                $field->save();
            }
            // No longer adding a new form
            unset($_REQUEST['a']);
        }
    }
    if ($errors)
        $errors['err'] = sprintf(__('Unable to commit %s. Check validation errors'), __('this custom form'));
    else
        $msg = sprintf(__('Successfully updated %s.'),
            __('this custom form'));
}

$page='dynamic-forms.inc.php';
if($form || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add')))
    $page='dynamic-form.inc.php';

$ost->addExtraHeader('<meta name="tip-namespace" content="forms" />',
    "$('#content').data('tipNamespace', 'forms');");
$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
