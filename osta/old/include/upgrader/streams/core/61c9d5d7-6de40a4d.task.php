<?php

/*
 * Loads the company info form and copies the helpdesk name to the company
 * name
 */

class CompanyFormLoader extends MigrationTask {
    var $description = "Loading initial company data";

    function run($max_time) {
        global $ost, $cfg;

        $form = $ost->company->getForm();
        if ($form && $cfg) {
            $form->setAnswer('name', $cfg->getTitle());
            $form->save();
        }
    }
}

return 'CompanyFormLoader';

?>
