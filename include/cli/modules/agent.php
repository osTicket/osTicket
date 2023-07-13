<?php

class AgentManager extends Module {
    var $prologue = 'CLI agent manager';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'import' => 'Import agents from CSV file',
                'export' => 'Export agents from the system to CSV',
                'list' => 'List agents based on search criteria',
                'login' => 'Attempt login as an agent',
                'backends' => 'List agent authentication backends',
            ),
        ),
    );

    var $options = array(
        'file' => array('-f', '--file', 'metavar'=>'path',
            'help' => 'File or stream to process'),
        'verbose' => array('-v', '--verbose', 'default'=>false,
            'action'=>'store_true', 'help' => 'Be more verbose'),

        'welcome' => array('-w', '--welcome', 'default'=>false,
            'action'=>'store_true', 'help'=>'Send a welcome email on import'),

        'backend' => array('', '--backend',
            'help'=>'Specify the authentication backend (used with `login` and `import`)'),

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
            if (!$options['file'] || $options['file'] == '-')
                $options['file'] = 'php://stdin';
            if (!($this->stream = fopen($options['file'], 'rb')))
                $this->fail("Unable to open input file [{$options['file']}]");

            // Defaults
            $extras = array(
                'isadmin' => 0,
                'isactive' => 1,
                'isvisible' => 1,
                'dept_id' => $cfg->getDefaultDeptId(),
                'timezone' => $cfg->getDefaultTimezone(),
                'welcome_email' => $options['welcome'],
            );

            if ($options['backend'])
                $extras['backend'] = $options['backend'];

            $stderr = $this->stderr;
            $status = Staff::importCsv($this->stream, $extras,
                function ($agent, $data) use ($stderr, $options) {
                    if (!$options['verbose'])
                        return;
                    $stderr->write(
                        sprintf("\n%s - %s  --- imported!",
                        $agent->getName(),
                        $agent->getUsername()));
                }
            );
            if (is_numeric($status))
                $this->stderr->write("Successfully processed $status agents\n");
            else
                $this->fail($status);
            break;

        case 'export':
            $stream = $options['file'] ?: 'php://stdout';
            if (!($this->stream = fopen($stream, 'c')))
                $this->fail("Unable to open output file [{$options['file']}]");

            fputcsv($this->stream, array('First Name', 'Last Name', 'Email', 'UserName'));
            foreach ($this->getAgents($options) as $agent)
                fputcsv($this->stream, array(
                    $agent->getFirstName(),
                    $agent->getLastName(),
                    $agent->getEmail(),
                    $agent->getUserName(),
                ));
            break;

        case 'list':
            $agents = $this->getAgents($options);
            foreach ($agents as $A) {
                $this->stdout->write(sprintf(
                    "%d \t - %s\t<%s>\n",
                    $A->staff_id, $A->getName(), $A->getEmail()));
            }
            break;

        case 'login':
            $this->stderr->write('Username: ');
            $username = trim(fgets(STDIN));
            $this->stderr->write('Password: ');
            $password = trim(fgets(STDIN));

            $agent = null;
            foreach (StaffAuthenticationBackend::allRegistered() as $id=>$bk) {
                if ((!$options['backend'] || $options['backend'] == $id)
                    && $bk->supportsInteractiveAuthentication()
                    && ($agent = $bk->authenticate($username, $password))
                    && $agent instanceof AuthenticatedUser
                ) {
                    break;
                }
            }

            if ($agent instanceof Staff) {
                $this->stdout->write(sprintf("Successfully authenticated as '%s', using '%s'\n",
                    (string) $agent->getName(),
                    $bk->getName()
                ));
            }
            else {
                $this->stdout->write('Authentication failed');
            }
            break;

        case 'backends':
            foreach (StaffAuthenticationBackend::allRegistered() as $name=>$bk) {
                if (!$bk->supportsInteractiveAuthentication())
                    continue;
                $this->stdout->write(sprintf("%s\t%s\n",
                    $name, $bk->getName()
                ));
            }
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

        return $agents->distinct('staff_id');
    }
}
Module::register('agent', 'AgentManager');
