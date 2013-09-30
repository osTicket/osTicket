<?php

class NewHtmlTemplate extends MigrationTask {
    var $description = "Adding new super-awesome HTML templates";

    function run($runtime) {
        $i18n = new Internationalization('en_US');
        $tpls = $i18n->getTemplate('email_template_group.yaml')->getData();
        foreach ($tpls as $t)
            EmailTemplateGroup::create($t);
    }
}
return 'NewHtmlTemplate';

?>
