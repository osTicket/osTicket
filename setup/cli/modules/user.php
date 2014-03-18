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
        );

    var $stream;

    function run($args, $options) {

        Bootstrap::connect();
        osTicket::start();

        switch ($args['action']) {
            case 'import':
                if (!$options['file'])
                    $this->fail('CSV file to import users from is required!');
                elseif (!($this->stream = fopen($options['file'], 'rb')))
                    $this->fail("Unable to open input file [{$options['file']}]");

                while (($data = fgetcsv($this->stream, 1000, ",")) !== FALSE)
                    if (!User::fromVars(array('name' => $data[0], 'email' => $data[1])))
                        $this->stderr->write('Unable to import user: '.print_r($data, true));
                break;
            case 'export':
                $stream = $options['file'] ?: 'php://stdout';
                if (!($this->stream = fopen($stream, 'c')))
                    $this->fail("Unable to open output file [{$options['file']}]");

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
