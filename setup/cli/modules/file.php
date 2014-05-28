<?php
require_once dirname(__file__) . "/class.module.php";
require_once dirname(__file__) . "/../cli.inc.php";

class FileManager extends Module {
    var $prologue = 'CLI file manager for osTicket';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'list' => 'List files matching criteria',
                'export' => 'Export files from the system',
                'import' => 'Load files exported via `export`',
                'dump' => 'Dump file content to stdout',
                'load' => 'Load file contents from stdin',
                'migrate' => 'Migrate a file to another backend',
                'backends' => 'List configured storage backends',
                'expunge' => 'Remove matching files from the system',
            ),
        ),
    );

    var $options = array(
        'ticket' => array('-T', '--ticket', 'metavar'=>'id',
            'help' => 'Search by internal ticket id'),
        'file-id' => array('-F', '--file-id', 'metavar'=>'id',
            'help' => 'Search by file id'),
        'name' => array('-N', '--name', 'metavar'=>'name',
            'help' => 'Search by file name (subsring match)'),
        'backend' => array('-b', '--backend', 'metavar'=>'BK',
            'help' => 'Search by file backend. See `backends` action
                for a list of available backends'),
        'status' => array('-S', '--status', 'metavar'=>'STATUS',
            'help' => 'Search on ticket state (`open` or `closed`)'),
        'min-size' => array('-z', '--min-size', 'metavar'=>'SIZE',
            'help' => 'Search for files larger than this. k, M, G are welcome'),
        'max-size' => array('-Z', '--max-size', 'metavar'=>'SIZE',
            'help' => 'Search for files smaller than this. k, M, G are welcome'),

        'limit' => array('-L', '--limit', 'metavar'=>'count',
            'help' => 'Limit search results to this count'),

        'to' => array('-m', '--to', 'metavar'=>'BK',
            'help' => 'Target backend for migration. See `backends` action
                for a list of available backends'),

        'file' => array('-f', '--file', 'metavar'=>'FILE',
            'help' => 'Filename used for import and export'),

        'verbose' => array('-v', '--verbose', 'action'=>'store_true',
            'help' => 'Be more verbose'),
    );


    function run($args, $options) {
        Bootstrap::connect();
        osTicket::start();

        switch ($args['action']) {
        case 'backends':
            // List configured backends
            foreach (FileStorageBackend::allRegistered() as $char=>$bk) {
                print "$char -- {$bk::$desc} ($bk)\n";
            }
            break;

        case 'list':
            // List files matching criteria
            // ORM would be nice!
            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);
            foreach ($files as $f) {
                printf("% 5d %s % 8d %s % 16s %s\n", $f->id, $f->bk,
                    $f->size, $f->created, $f->type, $f->name);
                if ($f->attrs) {
                    printf("        %s\n", $f->attrs);
                }
            }
            break;

        case 'dump':
            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);
            if ($files->count() != 1)
                $this->fail('Criteria must select exactly 1 file');

            if (($f = AttachmentFile::lookup($files[0]->id))
                    && ($bk = $f->open()))
                $bk->passthru();
            break;

        case 'load':
            // Load file content from STDIN
            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);
            if ($files->count() != 1)
                $this->fail('Criteria must select exactly 1 file');

            $f = AttachmentFile::lookup($files[0]->id);
            try {
                if ($bk = $f->open())
                    $bk->unlink();
            }
            catch (Exception $e) {}

            if ($options['to'])
                $bk = FileStorageBackend::lookup($options['to'], $f);
            else
                // Use the system default
                $bk = AttachmentFile::getBackendForFile($f);

            $type = false;
            $signature = '';
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($options['file'] && $options['file'] != '-') {
                if (!file_exists($options['file']))
                    $this->fail($options['file'].': Cannot open file');
                if (!$bk->upload($options['file']))
                    $this->fail('Unable to upload file contents to backend');
                $type = $finfo->file($options['file']);
                list(, $signature) = AttachmentFile::_getKeyAndHash($options['file'], true);
            }
            else {
                $stream = fopen('php://stdin', 'rb');
                while ($block = fread($stream, $bk->getBlockSize())) {
                    if (!$bk->write($block))
                        $this->fail('Unable to send file contents to backend');
                    if (!$type)
                        $type = $finfo->buffer($block);
                }
                if (!$bk->flush())
                    $this->fail('Unable to commit file contents to backend');
            }

            // TODO: Update file metadata
            $sql = 'UPDATE '.FILE_TABLE.' SET bk='.db_input($bk->getBkChar())
                .', created=CURRENT_TIMESTAMP'
                .', type='.db_input($type)
                .', signature='.db_input($signature)
                .' WHERE id='.db_input($f->getId());

            if (!db_query($sql) || db_affected_rows()!=1)
                $this->fail('Unable to update file metadata');

            $this->stdout->write("Successfully saved contents\n");
            break;

        case 'migrate':
            if (!$options['to'])
                $this->fail('Please specify a target backend for migration');

            if (!FileStorageBackend::isRegistered($options['to']))
                $this->fail('Target backend is not installed. See `backends` action');

            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);

            $count = 0;
            foreach ($files as $m) {
                $f = AttachmentFile::lookup($m->id);
                if ($f->getBackend() == $options['to'])
                    continue;
                if ($options['verbose'])
                    $this->stdout->write('Migrating '.$m->name."\n");
                try {
                    if (!$f->migrate($options['to']))
                        $this->stderr->write('Unable to migrate '.$m->name."\n");
                    else
                        $count++;
                }
                catch (IOException $e) {
                    $this->stderr->write('IOError: '.$e->getMessage());
                }
            }
            $this->stdout->write("Migrated $count files\n");
            break;

        case 'export':
            // Create a temporary ZIP file
            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);

            if (!$options['file'])
                $this->fail('Please specify zip file with `-f`');

            $zip = new ZipArchive();
            if (true !== ($reason = $zip->open($options['file'],
                    ZipArchive::CREATE)))
                $this->fail($reason.': Unable to create zip file');

            $manifest = array();
            foreach ($files as $m) {
                $f = AttachmentFile::lookup($m->id);
                $zip->addFromString($f->getId(), $f->getData());
                $zip->setCommentName($f->getId(), $f->getName());
                // TODO: Log %attachment and %ticket_attachment entries
                $info = array('file' => $f->getInfo());
                foreach ($m->tickets as $t)
                    $info['tickets'][] = $t->ht;

                $manifest[$f->getId()] = $info;
            }
            $zip->addFromString('MANIFEST', serialize($manifest));
            $zip->close();
            break;

        case 'expunge':
            // Create a temporary ZIP file
            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);

            foreach ($files as $f) {
                $f->tickets->expunge();
                $f->unlink() && $f->delete();
            }
        }
    }

    function _applyCriteria($options, $qs) {
        foreach ($options as $name=>$val) {
            if (!$val) continue;
            switch ($name) {
            case 'ticket':
                $qs->filter(array('tickets__ticket_id'=>$val));
                break;
            case 'file-id':
                $qs->filter(array('id'=>$val));
                break;
            case 'name':
                $qs->filter(array('name__contains'=>$val));
                break;
            case 'backend':
                $qs->filter(array('bk'=>$val));
                break;
            case 'status':
                if (!in_array($val, array('open','closed')))
                    $this->fail($val.': Unknown ticket status');

                $qs->filter(array('tickets__ticket__status'=>$val));
                break;

            case 'min-size':
            case 'max-size':
                $info = array();
                if (!preg_match('/([\d.]+)([kmgbi]+)?/i', $val, $info))
                    $this->fail($val.': Invalid file size');
                if ($info[2]) {
                    $info[2] = str_replace(array('b','i'), array('',''), $info[2]);
                    $sizes = array('k'=>1<<10,'m'=>1<<20,'g'=>1<<30);
                    $val = (float) $val * $sizes[strtolower($info[2])];
                }
                if ($name == 'min-size')
                    $qs->filter(array('size__gte'=>$val));
                else
                    $qs->filter(array('size__lte'=>$val));
                break;

            case 'limit':
                if (!is_numeric($val))
                    $this->fail('Provide an result count number to --limit');
                $qs->limit($val);
                break;
            }
        }
    }
}

require_once INCLUDE_DIR . 'class.orm.php';

class FileModel extends VerySimpleModel {
    static $meta = array(
        'table' => FILE_TABLE,
        'pk' => 'id',
        'joins' => array(
            'tickets' => array(
                'null' => true,
                'constraint' => array('id' => 'TicketAttachmentModel.file_id')
            ),
        ),
    );
}
class TicketAttachmentModel extends VerySimpleModel {
    static $meta = array(
        'table' => TICKET_ATTACHMENT_TABLE,
        'pk' => 'attach_id',
        'joins' => array(
            'ticket' => array(
                'null' => false,
                'constraint' => array('ticket_id' => 'TicketModel.ticket_id'),
            ),
        ),
    );
}

class AttachmentModel extends VerySimpleModel {
    static $meta = array(
        'table' => ATTACHMENT_TABLE,
        'pk' => array('object_id', 'type', 'file_id'),
    );
}

Module::register('file', 'FileManager');
?>
