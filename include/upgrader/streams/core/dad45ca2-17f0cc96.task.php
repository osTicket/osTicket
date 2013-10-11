<?php

/*
 * Loads the initial data for dynamic forms into the system. This is
 * preferred over providing the data inside the SQL scripts
 */

class DynamicFormLoader extends MigrationTask {
    var $description = "Loading initial data for dynamic forms";

    function run($max_time) {
        $i18n = new Internationalization('en_US');
        $forms = $i18n->getTemplate('form.yaml')->getData();
        foreach ($forms as $f)
            DynamicForm::create($f);
    }
}

return 'DynamicFormLoader';

?>
