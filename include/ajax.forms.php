<?php

require_once(INCLUDE_DIR . 'class.topic.php');
require_once(INCLUDE_DIR . 'class.dynamic_forms.php');

class DynamicFormsAjaxAPI extends AjaxController {
    function getForm($form_id) {
        $form = DynamicForm::lookup($form_id);
        if (!$form) return;

        foreach ($form->getFields() as $field) {
            $field->render();
        }
    }

    function getFormsForHelpTopic($topic_id, $client=false) {
        $topic = Topic::lookup($topic_id);
        if ($topic->ht['form_id']
                && ($form = DynamicForm::lookup($topic->ht['form_id'])))
            $form->render(!$client);
    }

    function getClientFormsForHelpTopic($topic_id) {
        return $this->getFormsForHelpTopic($topic_id, true);
    }

    function getFieldConfiguration($field_id) {
        $field = DynamicFormField::lookup($field_id);
        include(STAFFINC_DIR . 'templates/dynamic-field-config.tmpl.php');
    }

    function saveFieldConfiguration($field_id) {
        $field = DynamicFormField::lookup($field_id);
        if (!$field->setConfiguration())
            include(STAFFINC_DIR . 'templates/dynamic-field-config.tmpl.php');
        else
            $field->save();
    }

    function getUserInfo($user_id) {
        $user = User::lookup($user_id);

        $data = $user->ht;
        $data['email'] = $user->default_email->address;

        $custom = array();
        foreach ($user->getDynamicData() as $cd) {
            $cd->addMissingFields();
            foreach ($cd->getFields() as $f) {
                if ($f->get('name') == 'name')
                    $f->value = $user->getFullName();
                elseif ($f->get('name') == 'email')
                    $f->value = $user->getEmail();
            }
            $custom[] = $cd->getForm();
        }

        include(STAFFINC_DIR . 'templates/user-info.tmpl.php');
    }

    function saveUserInfo($user_id) {
        $user = User::lookup($user_id);

        $custom_data = $user->getDynamicData();
        $custom = array();
        $valid = true;
        foreach ($custom_data as $cd) {
            $cd->addMissingFields();
            $cf = $custom[] = $cd->getForm();
            $valid &= $cd->isValid();
        }

        if (!$valid) {
            include(STAFFINC_DIR . 'templates/user-info.tmpl.php');
            return;
        }

        // Save custom data
        foreach ($custom_data as $cd) {
            foreach ($cd->getFields() as $f) {
                if ($f->get('name') == 'name') {
                    $user->name = $f->getClean();
                    $user->save();
                }
                elseif ($f->get('name') == 'email') {
                    $user->default_email->address = $f->getClean();
                    $user->default_email->save();
                }
            }
            $cd->save();
        }
    }
}

?>
