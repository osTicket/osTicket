<?php

class StatusListCreater extends MigrationTask {
    var $description = "Add ticket statuses (if not already)";

    function run($max_time) {
        global $cfg;

        // Moved here from core/8f99b8bf-03ff59bf.task.php
        // Moved here from core/b26f29a6-1ee831c8.task.php
        require_once(INCLUDE_DIR . 'class.list.php');
        if ($list = DynamicList::objects()->filter(array('type' => 'ticket-status'))->first())
            // Already have ticket statuses
            return;

        $i18n = new Internationalization($cfg->get('system_language', 'en_US'));
        $lists = $i18n->getTemplate('list.yaml')->getData();
        foreach ($lists as $l) {
            DynamicList::create($l);
        }

        $statuses = $i18n->getTemplate('ticket_status.yaml')->getData();
        foreach ($statuses as $s) {
            TicketStatus::__create($s);
        }
    }
}
return 'StatusListCreater';
