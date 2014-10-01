<?php

require_once(INCLUDE_DIR . 'class.filter.php');

class FilterAjaxAPI extends AjaxController {

    function getFilterActionForm($type) {
        if (!($A = FilterAction::lookupByType($type)))
            Http::response(404, 'No such filter action type');

        $form = $A->getConfigurationForm();
        include STAFFINC_DIR . 'templates/dynamic-form-simple.tmpl.php';
    }

}
