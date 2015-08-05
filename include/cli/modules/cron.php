<?php

class CronManager extends Module {
    var $prologue = 'CLI cron manager for osTicket';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'fetch' => 'Fetch email',
                'search' => 'Build search index'
            ),
        ),
    );

    function run($args, $options) {
        Bootstrap::connect();
        $ost = osTicket::start();

        switch (strtolower($args[0])) {
        case 'fetch':
            Cron::MailFetcher();
            break;
        case 'search':
            $ost->searcher->backend->IndexOldStuff();
            break;
        }
    }
}

Module::register('cron', 'CronManager');
?>
