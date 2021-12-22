<?php

class UserManager extends Module {
    var $prologue = 'CLI user manager';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'import' => 'Import users from CSV file',
                'export' => 'Export users from the system to CSV',
                'activate' => 'Create or activate an account',
                'lock' => "Lock a user's account",
                'set-password' => "Set a user's account password",
                'list' => 'List users based on search criteria',
            ),
        ),
    );


    var $options = array(
        'file' => array('-f', '--file', 'metavar'=>'path',
            'help' => 'File or stream to process'),
        'org' => array('-O', '--org', 'metavar'=>'ORGID',
            'help' => 'Set the organization ID on import'),

        'send-mail' => array('-m', '--send-mail',
            'help' => 'Send the user an email. Used with `activate` and `set-password`',
            'default' => false, 'action' => 'store_true'),

        'verbose' => array('-v', '--verbose', 'default'=>false,
            'action'=>'store_true', 'help' => 'Be more verbose'
        ),

        // -- Search criteria
        'account' => array('-A', '--account', 'type'=>'bool', 'metavar'=>'bool',
            'help' => 'Search for users based on activation status'),
        'isconfirmed' => array('-C', '--isconfirmed', 'type'=>'bool', 'metavar'=>'bool',
            'help' => 'Search for users based on confirmation status'),
        'islocked' => array('-L', '--islocked', 'type'=>'bool', 'metavar'=>'bool',
            'help' => 'Search for users based on locked status'),
        'email' => array('-E', '--email',
            'help' => 'Search by email address'),
        'id' => array('-U', '--id',
            'help' => 'Search by user id'),
        );

    var $stream;

    function run($args, $options) {

        Bootstrap::connect();

        switch ($args['action']) {
        case 'import':
            // Properly detect Macintosh style line endings
            ini_set('auto_detect_line_endings', true);

            if (!$options['file'] || $options['file'] == '-')
                $options['file'] = 'php://stdin';
            if (!($this->stream = fopen($options['file'], 'rb')))
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

        case 'activate':
            $users = $this->getQuerySet($options);
            foreach ($users as $U) {
                if ($options['verbose']) {
                    $this->stderr->write(sprintf(
                        "Activating %s <%s>\n",
                        $U->getName(), $U->getDefaultEmail()
                    ));
                }
                if (!($account = $U->getAccount())) {
                    $account = UserAccount::create(array('user' => $U));
                    $U->account = $account;
                    $U->save();
                }

                if ($options['send-mail']) {
                    global $ost, $cfg;
                    $ost = osTicket::start();
                    $cfg = $ost->getConfig();

                    if (($error = $account->sendConfirmEmail()) && $error !== true) {
                        $this->warn(sprintf('%s: Unable to send email: %s',
                            $U->getDefaultEmail(), $error->getMessage()
                        ));
                    }
                }
            }

            break;

        case 'lock':
            $users = $this->getQuerySet($options);
            $users->select_related('account');
            foreach ($users as $U) {
                if (!($account = $U->getAccount())) {
                    $this->warn(sprintf(
                        '%s: User does not have a client account',
                        $U->getName()
                    ));
                }
                $account->setFlag(UserAccountStatus::LOCKED);
                $account->save();
            }

            break;

        case 'list':
            $users = $this->getQuerySet($options);

            foreach ($users as $U) {
                $this->stdout->write(sprintf(
                    "%d %s <%s>%s\n",
                    $U->id, $U->getName(), $U->getDefaultEmail(),
                    ($O = $U->getOrganization()) ? " ({$O->getName()})" : ''
                ));
            }

            break;

        case 'set-password':
            $this->stderr->write('Enter new password: ');
            $ps1 = fgets(STDIN);
            if (!function_exists('posix_isatty') || !posix_isatty(STDIN)) {
                $this->stderr->write('Re-enter new password: ');
                $ps2 = fgets(STDIN);

                if ($ps1 != $ps2)
                    $this->fail('Passwords do not match');
            }

            // Account is required
            $options['account'] = true;
            $users = $this->getQuerySet($options);

            $updated  = 0;
            foreach ($users as $U) {
                $U->account->setPassword($ps1);
                if ($U->account->save())
                    $updated++;
            }
            $this->stdout->write(sprintf('Updated %d users', $updated));
            break;

        default:
            $this->stderr->write('Unknown action!');
        }
        @fclose($this->stream);
    }

    function getQuerySet($options, $requireOne=false) {
        $users = User::objects();
        foreach ($options as $O=>$V) {
            if (!isset($V))
                continue;
            switch ($O) {
            case 'account':
                $users->filter(array('account__isnull' => !$V));
                break;

            case 'isconfirmed':
            case 'islocked':
                $flags = array(
                    'isconfirmed' => UserAccountStatus::CONFIRMED,
                    'islocked' => UserAccountStatus::LOCKED,
                );
                $Q = new Q(array('account__status__hasbit'=>$flags[$O]));
                if (!$V)
                    $Q->negate();
                $users->filter($Q);
                break;

            case 'org':
                if (is_numeric($V))
                    $users->filter(array('org__id'=>$V));
                else
                    $users->filter(array('org__name__contains'=>$V));
                break;

            case 'id':
                $users->filter(array('id'=>$V));
                break;
            }

        }
        return $users;
    }
}
Module::register('user', 'UserManager');
?>
