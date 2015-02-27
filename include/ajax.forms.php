<?php

require_once(INCLUDE_DIR . 'class.topic.php');
require_once(INCLUDE_DIR . 'class.dynamic_forms.php');
require_once(INCLUDE_DIR . 'class.forms.php');

class DynamicFormsAjaxAPI extends AjaxController {
    function getForm($form_id) {
        $form = DynamicForm::lookup($form_id);
        if (!$form) return;

        foreach ($form->getFields() as $field) {
            $field->render();
        }
    }

    function getFormsForHelpTopic($topic_id, $client=false) {
        if (!($topic = Topic::lookup($topic_id)))
            Http::response(404, 'No such help topic');

        if ($_GET || isset($_SESSION[':form-data'])) {
            if (!is_array($_SESSION[':form-data']))
                $_SESSION[':form-data'] = array();
            $_SESSION[':form-data'] = array_merge($_SESSION[':form-data'], $_GET);
        }

        foreach ($topic->getForms() as $form) {
            if (!$form->hasAnyVisibleFields())
                continue;
            ob_start();
            $form->getForm($_SESSION[':form-data'])->render(!$client);
            $html .= ob_get_clean();
            ob_start();
            print $form->getMedia();
            $media .= ob_get_clean();
        }
        return $this->encode(array(
            'media' => $media,
            'html' => $html,
        ));
    }

    function getClientFormsForHelpTopic($topic_id) {
        return $this->getFormsForHelpTopic($topic_id, true);
    }

    function getFieldConfiguration($field_id) {
        $field = DynamicFormField::lookup($field_id);
        include(STAFFINC_DIR . 'templates/dynamic-field-config.tmpl.php');
    }

    function saveFieldConfiguration($field_id) {
        if (!($field = DynamicFormField::lookup($field_id)))
            Http::response(404, 'No such field');

        $DFF = 'DynamicFormField';

        // Capture flags which should remain unchanged
        $p_mask = $DFF::MASK_MASK_ALL;
        if ($field->isPrivacyForced()) {
            $p_mask |= $DFF::FLAG_CLIENT_VIEW | $DFF::FLAG_AGENT_VIEW;
        }
        if ($field->isRequirementForced()) {
            $p_mask |= $DFF::FLAG_CLIENT_REQUIRED | $DFF::FLAG_AGENT_REQUIRED;
        }
        if ($field->hasFlag($DFF::FLAG_MASK_DISABLE)) {
            $p_mask |= $DFF::FLAG_ENABLED;
        }

        // Capture current state of immutable flags
        $preserve = $field->flags & $p_mask;

        // Set admin-configured flag states
        $flags = array_reduce($_POST['flags'],
            function($a, $b) { return $a | $b; }, 0);
        $field->flags = $flags | $preserve;

        if (!$field->setConfiguration()) {
            include STAFFINC_DIR . 'templates/dynamic-field-config.tmpl.php';
            return;
        }
        else
            $field->save();
        Http::response(201, 'Field successfully updated');
    }

    function deleteAnswer($entry_id, $field_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');

        $ent = DynamicFormEntryAnswer::lookup(array(
            'entry_id'=>$entry_id, 'field_id'=>$field_id));
        if (!$ent)
            Http::response(404, 'Answer not found');

        $ent->delete();
    }

    function getListItemProperties($list_id, $item_id) {

        $list = DynamicList::lookup($list_id);
        if (!$list || !($item = $list->getItem( (int) $item_id)))
            Http::response(404, 'No such list item');

        include(STAFFINC_DIR . 'templates/list-item-properties.tmpl.php');
    }

    function saveListItemProperties($list_id, $item_id) {

        $list = DynamicList::lookup($list_id);
        if (!$list || !($item = $list->getItem( (int) $item_id)))
            Http::response(404, 'No such list item');

        if (!$item->setConfiguration()) {
            include STAFFINC_DIR . 'templates/list-item-properties.tmpl.php';
            return;
        }
        else
            $item->save();

        Http::response(201, 'Successfully updated record');
    }

    function upload($id) {
        if (!$field = DynamicFormField::lookup($id))
            Http::response(400, 'No such field');

        $impl = $field->getImpl();
        if (!$impl instanceof FileUploadField)
            Http::response(400, 'Upload to a non file-field');

        return JsonDataEncoder::encode(
            array('id'=>$impl->ajaxUpload())
        );
    }

    function attach() {
        $field = new FileUploadField();
        return JsonDataEncoder::encode(
            array('id'=>$field->ajaxUpload(true))
        );
    }

    function getAllFields($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!$form = DynamicForm::lookup($id))
            Http::response(400, 'No such form');

        ob_start();
        include STAFFINC_DIR . 'templates/dynamic-form-fields-view.tmpl.php';
        $html = ob_get_clean();

        return $this->encode(array(
            'success'=>true,
            'html' => $html,
        ));
    }
}
?>
