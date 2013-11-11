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

        if (!($user = User::lookup($user_id)))
            Http::response(404, 'Unknown user');

        $custom = $user->getForms();
        include(STAFFINC_DIR . 'templates/user-info.tmpl.php');
    }

    function saveUserInfo($user_id) {

        $errors = array();
        if (!($user = User::lookup($user_id)))
            Http::response(404, 'Unknown user');

        if ($user->updateInfo($_POST, $errors))
            return Http::response(201, $user->to_json());

        $custom = $user->getForms($_POST);
        include(STAFFINC_DIR . 'templates/user-info.tmpl.php');
        return;
    }
}

?>
