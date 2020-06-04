<?php


class TemplateContentLoader2 extends MigrationTask {
    var $description = "Loading initial system templates";

    function run($max_time) {
        foreach (array('email2fa-staff') as $type) {
            $i18n = new Internationalization();
            $tpl = $i18n->getTemplate("templates/page/{$type}.yaml");
            if (!($page = $tpl->getData()))
                // No such template on disk
                continue;

            if ($id = db_result(db_query('select id from '.PAGE_TABLE
                    .' where `type`='.db_input($type))))
                // Already have a template for the content type
                continue;

            $sql = 'INSERT INTO '.PAGE_TABLE.' SET type='.db_input($type)
                .', name='.db_input($page['name'])
                .', body='.db_input($page['body'])
                .', notes='.db_input($page['notes'])
                .', created=NOW(), updated=NOW(), isactive=1';
            db_query($sql);
        }
    }
}
return 'TemplateContentLoader2';
