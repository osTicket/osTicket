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
            $_SESSION[':form-data'] = array_merge($_SESSION[':form-data'],
                    Format::htmlchars($_GET));
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

        if ($field->setConfiguration($_POST)) {
            $field->save();
            Http::response(201, 'Field successfully updated');
        }

        include STAFFINC_DIR . 'templates/dynamic-field-config.tmpl.php';
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


    function getListItem($list_id, $item_id) {

        $list = DynamicList::lookup($list_id);
        if (!$list)
            Http::response(404, 'No such list item');

        $list = CustomListHandler::forList($list);
        if (!($item = $list->getItem( (int) $item_id)))
            Http::response(404, 'No such list item');

        $action = "#list/{$list->getId()}/item/{$item->getId()}/update";
        $item_form = $list->getListItemBasicForm($item->ht, $item);

        include(STAFFINC_DIR . 'templates/list-item-properties.tmpl.php');
    }

    function getListItems($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');

        if (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');

        $pjax_container = '#items';
        include(STAFFINC_DIR . 'templates/list-items.tmpl.php');
    }

    function previewListItem($list_id, $item_id) {

        $list = DynamicList::lookup($list_id);
        if (!$list)
            Http::response(404, 'No such list item');

        $list = CustomListHandler::forList($list);
        if (!($item = $list->getItem( (int) $item_id)))
            Http::response(404, 'No such list item');

        $form = $list->getListItemBasicForm($item->ht, $item);
        include(STAFFINC_DIR . 'templates/list-item-preview.tmpl.php');
    }

    function saveListItem($list_id, $item_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');

        $list = DynamicList::lookup($list_id);
        if (!$list)
            Http::response(404, 'No such list item');

        $list = CustomListHandler::forList($list);
        if (!$list || !($item = $list->getItem( (int) $item_id)))
            Http::response(404, 'No such list item');

        $item_form = $list->getListItemBasicForm($_POST, $item);

        if ($valid = $item_form->isValid()) {
            if ($_item = DynamicListItem::lookup(array(
                'list_id' => $list->getId(), 'value'=>$item->getValue()))
            ) {
                if ($_item && $_item->id != $item->id)
                    $item_form->getField('value')->addError(
                        __('Value already in use'));
            }
            if ($item_form->isValid()) {
                // Update basic information
                $basic = $item_form->getClean();
                $item->update([
                    'name' =>   $basic['name'],
                    'value' =>  $basic['value'],
                    'abbrev' =>  $basic['extra'],
                ]);
            }
        }

        // Context
        $action = "#list/{$list->getId()}/item/{$item->getId()}/update";
        $icon = ($list->get('sort_mode') == 'SortCol')
            ? '<i class="icon-sort"></i>&nbsp;' : '';

        if (!$valid || !$item->setConfiguration($_POST)) {
            include STAFFINC_DIR . 'templates/list-item-properties.tmpl.php';
            return;
        }
        else {
            $item->save();
        }

        Http::response(201, $this->encode(array(
            'id' => $item->getId(),
            'row' => $this->_renderListItem($item, $list),
            'success' => true,
        )));
    }

    function _renderListItem($item, $list=false) {
        $list = $list ?: $item->getList();

        // Send the whole row back
        $prop_fields = $list->getSummaryFields();
        $icon = ($list->get('sort_mode') == 'SortCol')
            ? '<i class="icon-sort"></i>&nbsp;' : '';
        ob_start();
        $item->_config = null;
        include STAFFINC_DIR . 'templates/list-item-row.tmpl.php';
        return ob_get_clean();
    }

    function searchListItems($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');
        elseif (!($q = $_GET['q']))
            Http::response(400, '"q" query arg is required');

        $list = CustomListHandler::forList($list);
        $items = $list->search($q);

        $results = array();
        foreach ($items as $I) {
            $display = $I->getValue();
            if (isset($I->extra))
              $display .= " ({$I->extra})";
            $results[] = array(
                'value' => $I->getValue(),
                'display' => $display,
                'id' => $I->id,
                'list_id' => $list->getId(),
            );
        }
        return $this->encode($results);
    }

    function addListItem($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');

        $list = CustomListHandler::forList($list);
        $action = "#list/{$list->getId()}/item/add";
        $item_form = $list->getListItemBasicForm($_POST ?: null);
        $errors = array();

        if ($_POST && ($valid = $item_form->isValid())) {
            $data = $item_form->getClean();
            if ($list->isItemUnique($data)) {
                $item = $list->addItem($data, $errors);
                if ($item->setConfiguration($_POST, $errors)) {
                    Http::response(201, $this->encode(array(
                        'success' => true,
                        'row' => $this->_renderListItem($item, $list)
                    )));
                }
            }
            else {
                $item_form->getField('value')->addError(
                    __('Value already in use'));
            }
        }

        include(STAFFINC_DIR . 'templates/list-item-properties.tmpl.php');
    }

    function importListItems($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');

        $list = CustomListHandler::forList($list);
        $info = array(
            'title' => sprintf('%s &mdash; %s',
                $list->getName(), __('Import Items')),
            'action' => "#list/{$list_id}/import",
            'upload_url' => "lists.php?id={$list_id}&amp;do=import-items",
        );

        if ($_POST) {
            $status = $list->importFromPost($_FILES['import'] ?: $_POST['pasted']);
            if ($status && is_numeric($status))
                Http::response(201, $this->encode( array('success' => true, 'count' => $status)));

            $info['error'] = $status;
            $info['pasted'] = Format::htmlchars($_POST['pasted']);
        }

        include(STAFFINC_DIR . 'templates/list-import.tmpl.php');
    }

    function disableItems($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');
        elseif (!$_POST['ids'])
            Http::response(422, 'Send `ids` parameter');

        $list = CustomListHandler::forList($list);
        foreach ($_POST['ids'] as $id) {
            if ($item = $list->getItem( (int) $id)) {
                $item->disable();
                $item->save();
            }
            else {
                Http::response(404, 'No such list item');
            }
        }
        Http::response(200, $this->encode(array('success' => true)));
    }

    function undisableItems($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');
        elseif (!$_POST['ids'])
            Http::response(422, 'Send `ids` parameter');

        $list = CustomListHandler::forList($list);
        foreach ($_POST['ids'] as $id) {
            if ($item = $list->getItem( (int) $id)) {
                $item->enable();
                $item->save();
            }
            else {
                Http::response(404, 'No such list item');
            }
        }
        Http::response(200, $this->encode(array('success' => true)));
    }

    function deleteItems($list_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($list = DynamicList::lookup($list_id)))
            Http::response(404, 'No such list');
        elseif (!$_POST['ids'])
            Http::response(422, 'Send `ids` parameter');

        foreach ($_POST['ids'] as $id) {
            if ($item = $list->getItem( (int) $id)) {
                $item->delete();
            }
            else {
                Http::response(404, 'No such list item');
            }
        }
        Http::response(200, $this->encode(array('success' => true)));
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
            array('id'=>$field->ajaxUpload())
        );
    }

    function getAllFields($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!$form = DynamicForm::lookup($id))
            Http::response(400, 'No such form');

        // XXX: Fetch the form via the list!
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
