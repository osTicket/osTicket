<?php
include_once INCLUDE_DIR .'class.translation.php';


class ListManager extends Module {
    var $prologue = 'CLI list manager';
    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'import' => 'Import list items to the system',
                'export' => 'Export list items from the system',
                'show' => 'Show the lists',
            ),
        ),
    );


    var $options = array(
        'file' => array('-f', '--file', 'metavar'=>'path',
            'help' => 'File or stream to process'),
        'id' => array('-ID', '--id', 'metavar'=>'id',
            'help' => 'List ID'),
        );

    var $stream;

    function run($args, $options) {

        Bootstrap::connect();

        $list = null;
        if ($options['id'])
            $list = DynamicList::lookup($options['id']);

        switch ($args['action']) {
            case 'import':
                if (!$list)
                    $this->fail("List ID required for items import");

                // Properly detect Macintosh style line endings
                ini_set('auto_detect_line_endings', true);
                if (!$options['file'])
                    $this->fail('CSV file to import list items from is required!');
                elseif (!($this->stream = fopen($options['file'], 'rb')))
                    $this->fail("Unable to open input file [{$options['file']}]");

                $extras = array();
                $status = $list->importCsv($this->stream, $extras);
                if (is_numeric($status))
                    $this->stderr->write("Successfully imported $status list items\n");
                else
                    $this->fail($status);
                break;
            case 'export':

                if (!$list)
                    $this->fail("List ID required for export");

                $stream = $options['file'] ?: 'php://stdout';
                if (!($this->stream = fopen($stream, 'c')))
                    $this->fail("Unable to open output file [{$options['file']}]");

                fputcsv($this->stream, array('Value', 'Abbrev'));
                foreach ($list->getItems() as $item)
                    fputcsv($this->stream, array(
                                (string) $item->getValue(),
                                $item->getAbbrev()));
                break;
            case 'show':
                $lists = DynamicList::objects()->order_by('-type', 'name');
                foreach ($lists as $list) {
                    $this->stdout->write(sprintf("%d %s \n",
                                $list->getId(),
                                $list->getName(),
                                $list->getPluralName() ?: $list->getName()
                                ));
                }
                break;
            default:
                $this->stderr->write('Unknown action!');
        }
        @fclose($this->stream);
    }
}
Module::register('list', 'ListManager');
?>
