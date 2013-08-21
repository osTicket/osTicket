<?php

require_once(INCLUDE_DIR . 'class.topic.php');
require_once(INCLUDE_DIR . 'class.dynamic_forms.php');

class DynamicFormsAjaxAPI extends AjaxController {
    function getForm($form_id) {
        $form = DynamicFormSection::lookup($form_id);
        if (!$form) return;

        foreach ($form->getFields() as $field) {
            $field->render();
        }
    }

    function getFormsForHelpTopic($topic_id, $client=false) {
        $topic = Topic::lookup($topic_id);
        foreach (DynamicFormset::lookup($topic->ht['formset_id'])->getForms() as $form) {
            $set=$form;
            $form=$form->getForm();
            if ($client)
                include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
            else
                include(STAFFINC_DIR . 'templates/dynamic-form.tmpl.php');
        }
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
}

?>
