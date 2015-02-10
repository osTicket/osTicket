<?php

class FileImport extends MigrationTask {
    var $description = "Import core osTicket attachment files";

    function run($runtime) {
        $i18n = new Internationalization('en_US');
        $files = $i18n->getTemplate('file.yaml')->getData();
        foreach ($files as $f) {
            if (!($file = AttachmentFile::create($f)))
                continue;

            // Ensure the new files are never deleted (attached to Disk)
            $sql ='INSERT INTO '.ATTACHMENT_TABLE
                .' SET object_id=0, `type`=\'D\', inline=1'
                .', file_id='.db_input($file->getId());
            db_query($sql);
        }
    }
}

return 'FileImport';

?>
