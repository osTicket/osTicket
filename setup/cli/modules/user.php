<?php
require_once dirname(__file__) . "/class.module.php";
require_once dirname(__file__) . "/../cli.inc.php";

class UserManager extends Module {
    var $prologue = 'CLI user manager';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'import' => 'Import users to the system',
                'export' => 'Export users from the system',
            ),
        ),
    );


    var $options = array(
        'file' => array('-f', '--file', 'metavar'=>'path',
            'help' => 'File or stream to process'),
        'org' => array('-O', '--org', 'metavar'=>'ORGID',
            'help' => 'Set the organization ID on import'),
        );

    var $stream;

    function run($args, $options) {

        Bootstrap::connect();

        switch ($args['action']) {
            case 'import':
                // Properly detect Macintosh style line endings
                ini_set('auto_detect_line_endings', true);

                if (!$options['file'])
                    $this->fail('CSV file to import users from is required!');
                elseif (!($this->stream = fopen($options['file'], 'rb')))
                    $this->fail("Unable to open input file [{$options['file']}]");

                $extras = array();
                if ($options['org']) {
                    if (!($org = Organization::lookup($options['org'])))
                        $this->fail($options['org'].': Unknown organization ID');
                    $extras['org_id'] = $options['org'];
                }
                $status = User::importCsv($this->stream, $extras);
                if (is_numeric($status))
                    $this->stderr->write("Successfully imported $status clients\n");
                else
                    $this->fail($status);
                break;

            case 'export':
                $stream = $options['file'] ?: 'php://stdout';
                if (!($this->stream = fopen($stream, 'c')))
                    $this->fail("Unable to open output file [{$options['file']}]");

                fputcsv($this->stream, array('Name', 'Email'));
                foreach (User::objects() as $user)
                    fputcsv($this->stream,
                            array((string) $user->getName(), $user->getEmail()));
                break;
            default:
                $this->stderr->write('Unknown action!');
        }
        @fclose($this->stream);
    }
}
Module::register('user', 'UserManager');
?>
