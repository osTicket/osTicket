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
        if ($form =DynamicForm::lookup($topic->ht['form_id']))
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

    function _getUserForms() {
        $static = new Form(array(
            'name' => new TextboxField(array(
                'label'=>'Full Name', 'configuration'=>array('size'=>40))
            ),
            'email' => new TextboxField(array(
                'label'=>'Default Email', 'configuration'=>array(
                    'validator'=>'email', 'size'=>40))
            ),
        ));

        return $static;
    }

    function getUserInfo($user_id) {
        $user = User::lookup($user_id);
        $static = $this->_getUserForms();

        $data = $user->ht;
        $data['email'] = $user->default_email->address;
        $static->data($data);

        $custom = array();
        foreach ($user->getDynamicData() as $cd) {
            $cd->addMissingFields();
            $custom[] = $cd->getForm();
        }

        include(STAFFINC_DIR . 'templates/user-info.tmpl.php');
    }

    function saveUserInfo($user_id) {
        $user = User::lookup($user_id);
        $static = $this->_getUserForms();
        $valid = $static->isValid();

        $custom_data = $user->getDynamicData();
        $custom = array();
        foreach ($custom_data as $cd) {
            $cd->addMissingFields();
            $cf = $custom[] = $cd->getForm();
            $valid &= $cd->isValid();
        }

        if (!$valid) {
            include(STAFFINC_DIR . 'templates/user-info.tmpl.php');
            return;
        }

        $data = $static->getClean();
        $user->first = $data['first'];
        $user->last = $data['last'];
        $user->default_email->address = $data['email'];
        $user->save();
        $user->default_email->save();

        // Save custom data
        foreach ($custom_data as $cd)
            $cd->save();
    }
}

?>
