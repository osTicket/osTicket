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
                'dump' => 'Dump file content to stdout',
                'migrate' => 'Migrate a file to another backend',
                'backends' => 'List configured storage backends',
            ),
        ),
    );

    var $options = array(
        'ticket' => array('-T', '--ticket', 'metavar'=>'id',
            'help' => 'Search by internal ticket id'),
        'file' => array('-F', '--file', 'metavar'=>'id',
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
                printf("% 5d %s % 8d %s % 12s %s\n", $f->id, $f->bk,
                    $f->size, $f->created, $f->type, $f->name);
            }
            break;

        case 'dump':
            $files = FileModel::objects();
            $this->_applyCriteria($options, $files);
            if ($files->count() != 1)
                $this->fail('Criteria must select exactly 1 file');

            $f = AttachmentFile::lookup($files[0]->id);
            $f->sendData();
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
        }


    }

    function _applyCriteria($options, $qs) {
        foreach ($options as $name=>$val) {
            if (!$val) continue;
            switch ($name) {
            case 'ticket':
                $qs->filter(array('tickets__ticket_id'=>$val));
                break;
            case 'file':
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
class TicketModel extends VerySimpleModel {
    static $meta = array(
        'table' => TICKET_TABLE,
        'pk' => 'ticket_id',
    );
}

Module::register('file', 'FileManager');
?>
