<?php

class NewHtmlTemplate extends MigrationTask {
    var $description = "Adding new super-awesome HTML templates";

    function run($runtime) {
        $errors = array();

        $i18n = new Internationalization('en_US');
        $tpls = $i18n->getTemplate('email_template_group.yaml')->getData();
        foreach ($tpls as $t) {
            // If the email template group specifies an id attribute, remove
            // it for upgrade because we cannot assume that the id slot is
            // available
            unset($t['id']);
            EmailTemplateGroup::create($t, $errors);
        }

        // NOTE: Core files import moved to 934954de-f1ccd3bb.task.php
    }
}
return 'NewHtmlTemplate';

?>
