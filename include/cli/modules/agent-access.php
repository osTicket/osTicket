<?php

class AgentAccessManager extends Module {
    var $prologue = 'CLI agent access manager';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'import' => 'Import agents access from CSV file',
                'export' => 'Export agents access from the system to CSV',
            ),
        ),
    );

    var $options = array(
        'file' => array('-f', '--file', 'metavar'=>'path',
            'help' => 'File or stream to process'),
        'verbose' => array('-v', '--verbose', 'default'=>false,
            'action'=>'store_true', 'help' => 'Be more verbose'),

        // -- Search criteria
        'username' => array('-U', '--username',
            'help' => 'Search by username'),
        'email' => array('-E', '--email',
            'help' => 'Search by email address'),
        'id' => array('-U', '--id',
            'help' => 'Search by user id'),
        'dept' => array('-D', '--dept', 'help' => 'Search by access to department name or id'),
        'team' => array('-T', '--team', 'help' => 'Search by membership in team name or id'),
    );

    var $stream;

    function run($args, $options) {
        global $ost, $cfg;

        Bootstrap::connect();

        if (!($ost=osTicket::start()) || !($cfg = $ost->getConfig()))
            $this->fail('Unable to load config info!');

        switch ($args['action']) {
        case 'import':
            if (!$options['file'])
                $this->fail('YAML file to import agents access from is required!');

            if (!($entries = YamlDataParser::load($options['file'])))
                $this->fail("Unable to open input file [{$options['file']}]");

            $i = 0;
            foreach ($entries as $entry) {
                $errors = array();
                if (StaffAccess::__load($entry, $errors))
                    $i++;
                else
                    var_dump($errors);
            }
            $this->stderr->write(
                    sprintf("Successfully processed %d of %d agents\n",
                        $i, count($entries)));
            break;
        case 'export':
            $stream = $options['file'] ?: 'php://stdout';
            if (!($this->stream = fopen($stream, 'c')))
                $this->fail("Unable to open output file [{$options['file']}]");

            fputcsv($this->stream, array('Name', 'UserName'));
            foreach ($this->getAgents($options) as $agent)
                fputcsv($this->stream, array(
                    sprintf('%s, %s',
                        $agent->getLastName(),
                        $agent->getFirstName()),
                    $agent->getUserName(),
                ));
            break;
        default:
            $this->fail($args['action'].': Unknown action!');
        }
        @fclose($this->stream);
    }

    function getAgents($options, $requireOne=false) {
        $agents = Staff::objects();
        if ($options['email'])
            $agents->filter(array('email__contains' => $options['email']));
        if ($options['username'])
            $agents->filter(array('username__contains' => $options['username']));
        if ($options['id'])
            $agents->filter(array('staff_id' => $options['id']));
        if ($options['dept'])
            $agents->filter(Q::any(array(
                'dept_id' => $options['dept'],
                'dept__name__contains' => $options['dept'],
                'dept_access__dept_id' => $options['dept'],
                'dept_access__dept__name__contains' => $options['dept'],
            )));
        if ($options['team'])
            $agents->filter(Q::any(array(
                'teams__team_id' => $options['team'],
                'teams__team__name__contains' => $options['team'],
            )));

        $agents->distinct('staff_id');
        $agents->order_by(array('lastname', 'firstname'));

        return $agents;
    }
}
Module::register('agent-access', 'AgentAccessManager');
