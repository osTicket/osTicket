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

        $files = $i18n->getTemplate('file.yaml')->getData();
        foreach ($files as $f) {
            $id = AttachmentFile::create($f, $errors);

            // Ensure the new files are never deleted (attached to Disk)
            $sql ='INSERT INTO '.ATTACHMENT_TABLE
                .' SET object_id=0, `type`=\'D\', inline=1'
                .', file_id='.db_input($id);
            db_query($sql);
        }
    }
}
return 'NewHtmlTemplate';

?>
