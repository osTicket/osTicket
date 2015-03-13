<?php
require_once dirname(__file__) . "/class.module.php";
require_once dirname(__file__) . "/../cli.inc.php";

class OrganizationManager extends Module {
    var $prologue = 'CLI organization manager';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'import' => 'Import organizations to the system',
                'export' => 'Export organizations from the system',
            ),
        ),
    );


    var $options = array(
        'file' => array('-f', '--file', 'metavar'=>'path',
            'help' => 'File or stream to process'),
        );

    var $stream;

    function run($args, $options) {

        Bootstrap::connect();

        switch ($args['action']) {
            case 'import':
                if (!$options['file'])
                    $this->fail('Import CSV file required!');
                elseif (!($this->stream = fopen($options['file'], 'rb')))
                    $this->fail("Unable to open input file [{$options['file']}]");

                //Read the header (if any)
                if (($data = fgetcsv($this->stream, 1000, ","))) {
                    if (strcasecmp($data[0], 'name'))
                        fseek($this->stream, 0); // We don't have an header!
                    else;
                    // TODO: process the header here to figure out the columns
                    // for now we're assuming one column of Name
                }

                while (($data = fgetcsv($this->stream, 1000, ",")) !== FALSE) {
                    if (!$data[0])
                        $this->stderr->write('Invalid data format: Name
                                required');
                    elseif (!Organization::fromVars(array('name' => $data[0], 'email')))
                        $this->stderr->write('Unable to import record: '.print_r($data, true));
                }

                break;
            case 'export':
                $stream = $options['file'] ?: 'php://stdout';
                if (!($this->stream = fopen($stream, 'c')))
                    $this->fail("Unable to open output file [{$options['file']}]");

                fputcsv($this->stream, array('Name'));
                foreach (Organization::objects() as $org)
                    fputcsv($this->stream,
                            array((string) $org->getName()));
                break;
            default:
                $this->stderr->write('Unknown action!');
        }
        @fclose($this->stream);
    }
}
Module::register('org', 'OrganizationManager');
?>
