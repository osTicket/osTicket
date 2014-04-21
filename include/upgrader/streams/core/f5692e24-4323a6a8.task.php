<?php


class TemplateContentLoader extends MigrationTask {
    var $description = "Loading initial system templates";

    function run($max_time) {
        foreach (array(
                'registration-staff', 'pwreset-staff', 'banner-staff',
                'registration-client', 'pwreset-client', 'banner-client',
                'registration-confirm', 'registration-thanks',
                'access-link') as $type) {
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
                .', lang='.db_input($tpl->getLang())
                .', notes='.db_input($page['notes'])
                .', created=NOW(), updated=NOW(), isactive=1';
            db_query($sql);
        }
        // Set the content_id for all the new items
        db_query('UPDATE '.PAGE_TABLE
            .' SET `content_id` = `id` WHERE `content_id` = 0');
    }
}
return 'TemplateContentLoader';
